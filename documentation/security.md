# Security

Sprout follows the usual PHP security best-practices
- Doesn't require any directory containing PHP executable content to be writeable.

  There are three directories which must be writeable by the web/PHP-fpm user: `files`, `sprout/cache`, and `sprout/temp`.
  None of these are intended
  to contain executable PHP files and using the provided NGINX config you a) can't execute the script (it's download only)
  b) can't access the file directly.

- Queries are built using PDO parametrisation rather than manual quoting and string interpolation.
  This means SQL injection would require an exploit of the underlying PHP database driver.

  String interpolation is occasionally used in building queries where parametrisation isn't an option, e.g. dynamic table names,
  and in those cases the input is either white-list validated or strictly filtered before use.

- Dangerous functions are used rarely, if ever.

  There are no occurrences of `eval()`, `create_function()`, `unserialize()`/`serialize()` within the main Sprout codebase, though
  we do have an occurrence of `eval()` within the Selenium test framework (inaccessible to the web).
  `shell_exec()`/`exec()` are used rarely and all input is passed through `escapeshellarg()` before being used in commands.

- Cross-site scripting is handled in the usual manner: HTML encoding of all user input.

  We've adopted the on-render method for HTML encoding user input; i.e. input is stored verbatim in the database then encoded
  once rendered on the page.
  
  This design allows for non-HTML outputting of content from the database (e.g. into a PDF) without requiring a HTML decode.
  
  In the rare event that users are permitted to enter HTML content there is a `RichTextSanitiser` class that applies whitelist
  restriction to elements and attributes that may appear in HTML. While this class is fairly draconian it's much better than using
  strip_tags with allowed tags which will allow JS on attributes.

- Cross-site request forgery is tackled using per-session secrets.

  While per-form secrets offer higher security they also have the potential to degrade user experience so we settled on per-session
  secrets. A secret value is generated upon session creation and added to each form, upon submission the submitted secret is
  compared against the session secret (see `Csrf::check`)

# Development

If you've started working on Sprout as a developer there's a few things you'll need to be familiar with.

## Database interaction
Sprout uses PDO through a wrapper class called `Pdb`. This uses parametrisation to build queries, e.g.

```php
$users = Pdb::q('SELECT name FROM ~users WHERE id = ? OR id = ?', [5, 10], 'arr');
```

Notice that we didn't have to manually interpolate/concatenate any input into the query?

If you need more complex queries you may build a `WHERE` clause using `Pdb::buildClause`. This function takes an array of conditions
along with a reference parameter array and outputs a parametrised `WHERE` clause.

```php
// Conditions are, by default, combined with AND
$conditions = [
  ['last_login', '<', '2015-01-01'],
  ['active', '=', 1]
];

$params = [];

// The $params array will be populated with any needed parameters
$where = Pdb::buildClause($conditions, $params);

$users = Pdb::q("SELECT * FROM ~users WHERE {$where}", $params, 'arr');
```

There are a few cases where you can't use parametrisation to build the query: column names, table names and limit/offset values.

For dynamic column and table names you must **always** pass the input through `Pdb::validateIdentifier` or `Pdb::validateExtendedIdentifier` before interpolation into query strings. These functions will generate an `InvalidArgumentException` if the input is invalid.

```php
public static function boringHelperFunction($table_name)
{
  Pdb::validateIdentifier($table_name);
  
  return Pdb::q("SELECT count(id) FROM ~{$table_name}", [], 'val');
}
```

Furthermore white-listing of input values is an excellent idea, if only to be able to present useful error messages rather than generic 'your input is wrong!' kind.

Limit and offset values must be cast to int before interpolation into the query.

```php
// Force these values into a valid range.
$limit_safe = max((int)$limit, 1);
$offset_safe = max((int)$offset, 0);

$q = "... LIMIT {$limit_safe} OFFSET {$offset_safe}";
```

## Views and rendering
Cross-site scripting (XSS) arises where user input is output directly onto the page without any escaping, allowing arbitrary HTML/JS to be placed on the page by an attacker.

For example the following code would result in an XSS attack.
```php
<!-- Terrible code -->
<p><?= $_GET['error_message']; ?></p>
```

Stopping this kind of attack is simple: any dynamic value output onto the page should be passed through one of the functions found in the `Enc` helper. For content output into HTML this would be `Enc::html`, for JS strings it would be `Enc::js` etc.

```php
<p><?= Enc::html($_GET['error_message']); ?></p>

<script>
var athing = '<?= Enc::js($_GET['message']); ?>';
</script>
```

Encoding should always be performed at the `View` level, never in the controller. If you find yourself writing
```php
<p><?= $some_var; ?></p>
```
then you're most likely doing it wrong.

## Uploads
File uploads are one of the most dangerous features of a site; they're a common source of remote code execution (RCE) exploits among PHP-based sites.

Because PHP is exposed to the physical file system, as opposed to an application which only serves virtual paths, if an attacker is able to upload a malicious file, e.g. a PHP script or .htaccess file, then they may be able to achieve remote code execution, authentication bypass and/or information disclosure.

Ideally you should let uploads be handled by a third-party provider such as DropBox. This comes with multiple advantages: you no longer have to think about files uploading malicious files to your server, you will use far less bandwidth and your users will likely have a better experience due to files being served by a CDN.

If you can't utilise a third party provider then you should utilise Sprout's `Form::chunkedUpload` (actually defined in `Fb::chunkedUpload`) function to provide a powerful and safe uploader. Avoid using HTML 'file' input fields and PHP's $_FILES superglobal.

If you ever need to manually handle file uploads (think twice about this) always call `FileUpload::checkFilename` on the **destination** file name (usually `$_FILES['field_name']['name']`) to ensure it isn't of a dangerous type.

```php
if (!FileUpload::checkFilename($_FILES['file']['name'])) {
  throw new Exception('Invalid file type provided');
}
```

Uploaded files shouldn't be moved into the `files` directory manually; use `File::moveUpload` instead.

User provided filenames should **not** be used for the filesystem name, take the white-listed extension and use a unique generated value for the file name, e.g.

```php
if (!FileUpload::checkFilename($_FILES['field']['name'])) {
  die('error');
}

$ext = File::getExt($_FILES['field']['name']);
$new_filename = sprintf('user_upload_%d_%d.%s', UserAuth::getId(), time(), $ext);

File::moveUpload($_FILES['field']['tmp_name'], $new_filename);
```
This avoids a user overriding another user's files or providing names with potentially risky characters such as ASCII control codes (00 to 1F) or malformed UTF-8 sequences.
