
# Upgrading Sprout 3.x to 3.2

_A step by step guide._


### 1. Merge latest Sprout 3.0 or 3.1

This should highlight if any modifications have been slipped into Sprout core. Fingers crossed that this isn't the case, but if it is:

1. Assess whether it's critical, consider dropping it.
2. Is it something solved by another library? (eg. null-returns in Pdb)
3. Can you shuffle it into a helper elsewhere?
4. Is it something upstream-able?


### 2. File structure

Ensure the repo has the correct structure. We're looking for a `src/` folder. If the site has a flattened layout that's going to make life pretty hard.

It's possible that you could move it all into `src/` and proceed. Best of luck though.


### 3. Merge from sprout3-site

This is key. _DO NOT_ merge from v3.2 branch. The upgrade process moves sprout core (sprout + sprout-kb repos) into a composer package, so we instead merge in the template repo (sprout3-site) that will include the composer dependency for `sproutcms/cms:3.2`.

```sh
git remote add sprout3-site git@github.com:Karmabunny/sprout3-site
git fetch sprout3-site
git merge sprout3-site/master
```

This will most definitely cause a merge conflict.


#### Merge goals:

- Remove `src/sprout` entirely.
- Composer file must have the `sproutcms/cms` dependency.
- Add `web/` folder.
- Move `src/index.php` to `web/` (with major edits)


#### Tips:

If the `composer.json` file conflicts, prefer the 'current' version and you can add the sprout dependency by hand with `composer require sproutcms/cms`.

If the `composer.lock` file conflicts, don't try to fix it. Simply delete it and run `composer install` to regenerate it.

If the `index.php` is mangled beyond recognition, use the provided `documentation/index.php` in this repo as a guide. If the old one was hacked up a bit, you may need to think a bit about how to resolve that. But this is a good starting point.


### 4. Refactoring

These are probably the only true breaking changes in the whole process.


#### 4.1 Constants

Perform a project-wide grep for the `DOCROOT` and `APPPATH` constants. Each usage will need a quick assessment whether it need to be refactored or not.

1. `DOCROOT` - no change:
    - basically anything site specific
    - `modules/`
    - `skin/`
    - `config/`
2. `DOCROOT` - must refactor:
    - `sprout/ -> APPPATH`
    - `media/ -> COREPATH/media`
    - `files/ -> WEBROOT/files`
3. `APPPATH` - no change:
    - anything that is sprout core
    - some examples (not exhaustive)
    - `config/`
    - `i18n/`
    - `media/`
    - `module_template/`
    - `views/`
    - `cacert.pem`
4. `APPPATH` - must refactor:
    - `cache/ -> STORAGE_PATH/cache`
    - `temp/ -> STORAGE_PATH/temp`

If you're using modules from the (private) `sprout3-modules` repo, many of these refactors should already exist. Understandably the module code may have changed dramatically since installing it - so a manual approach is still best. Use it as reference though.


#### 4.2 Also look out for

- Misuse of the `Kohana::autoload()` method.
- `file_*()` operations that use relative paths.


### 5. Services

To integrate functionality into Sprout core, a new feature called 'services' has been introduced. For migrations this should only affect two things:

1. Users module
2. Remote logins


#### 5.1 Users module

This is relatively simple. In the file: `src/modules/Users/sprout_load.php` add the following:

```php
use Sprout\Helpers\Register;
use SproutModules\Users\Helpers\UserAuth;
use SproutModules\Users\Helpers\UserPerms;

Register::services([
    UserAuth::class,
    UserPerms::class,
]);
```


#### 5.2 Remote logins

For KB sites this has been rolled into a library for convenience. This library is however behind our private composer repository. So add this to the `composer.json` file if it's not there already.

```json
{
    "repositories": [
        {
            "type": "composer",
            "url": "https://packages.bunnysites.com",
            "canonical": false
        }
    ]
}
```

Add the dependency:

```sh
composer require karmabunny/sproutremote
```

Register the service in `config/config.php`:

```php
use Sprout\Helpers\Register;
use SproutModules\Karmabunny\RemoteAuth;

Register::services([RemoteAuth::class => [
    'url' => 'https://ssl.karmabunny.com.au'
    'site_domain' => $config['cli_domain'],
]]);
```
