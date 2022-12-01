
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

_DO NOT_ merge from core sprout (currently v3.2 branch). The upgrade process moves sprout core (sprout + sprout-kb repos) into a composer package, so we instead want to merge in the template repo (sprout3-site) that will include the composer dependency for `sproutcms/cms:3.2`.

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
- Move `src/files` to `web/files`.
- Replace `src/index.php` with `web/index.php`.
- Delete any tracked `src/vendor` dependencies.


#### Tips:

If the `composer.json` file conflicts, prefer the 'current' version and then edit like so:

1. Add the sprout dependency with `composer require sproutcms/cms`
2. Remove the config `"vendor-dir"` (if present). Vendor is now at the repo root level. Please also ensure this is gitignored.
3. Introduce the autoloader to the local modules, example below.
4. Any of the other bits from the incoming changes - scripts, extra.locals, and configs are not essential but are good to have - so definitely include them where you can.

```json
{
    "autoload": {
        "psr-4": {
            "SproutModules\\Karmabunny\\": "src/modules"
        }
    }
}
```

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


#### 4.3 Twig templates

If you're coming from Sprout 3.0 then ~many~ all of your modules will assume that all views are PHP templates. For most things this is true, but if you want to write Twig skins then you're going to need to refactor these modules to be aware of this.

To enable Twig for a skin, add this to the relevant `sprout.php` skin config.

```php
$config['skin_views_type'] = 'twig';
```

All modules must update their code to use `BaseView::create('skin/*')` instead of `new View('skin/*')`. Note this only applies to 'skin' templates. In all other situations the module should be aware of the type of template it is rendering and should use `new TwigView` or `new PhpView` as appropriate.


### 5. Services

To integrate functionality into Sprout core, a new feature called 'services' has been introduced. For migrations this should only affect two things:

1. Users module
2. Remote logins


#### 5.1 Users module

Provided the users module hasn't been heavily modified, this should be relatively simple.

In the file: `src/modules/Users/sprout_load.php` add the following:

```php
use Sprout\Helpers\Register;
use SproutModules\Karmabunny\Users\Helpers\UserAuth;
use SproutModules\Karmabunny\Users\Helpers\UserPerms;

Register::services([
    UserAuth::class,
    UserPerms::class,
]);
```

Be aware these helper classes need to implement their respective 'service' interfaces. You can manually write this in yourself or just copy a fresh one from the modules repo.


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
    'url' => 'https://ssl.karmabunny.com.au',
    'site_domain' => $config['cli_domain'],
]]);
```


### 6. Final bits

Some considerations and check when tying things up.

1. PHP minimum is 7.4. Ensure the production server supports this.
2. Requires Composer + `composer install` on deploy, see the template repo `deploy/after_push` hook.
3. Nginx rewrites must be updated, compare and update from the `documentation/nginx/sprout.conf` file.
4. Similarly the Apache `.htaccess` files must be updated. Both in the root level and `web/` folder.
5. Generate a `.env` file (don't commit it)
6. Check your `src/config/database.php` file. This should be using environments without any prod/dev conditions.
7. Delete any `dev_hosts.php` file.
8. Check that `src/config/_bootstrap_config.php` matches the structure of `src/bootstrap/BootstrapConfig.php`.

