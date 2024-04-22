
# SproutCMS 3.2

## Overview

### The `'web/'` root:

PHP source files and media assets no longer share a common directory. The only PHP file accessible by the public is `web/index.php`. This means the application __document root__ (nginx + apache configurations) needs to be updated from `src/ -> web/`.

This is a necessity for security but also practicality. Core assets and module/skin assets are no under the same 'DOCROOT' path. This is where the MediaController was born. This facilitates the process of providing access to these files.

New rewrites have been added to the nginx + apache configurations to help prevent any backward incompatible breakages.

This also draws a clear line between what is protected and what is not. Caches, temp, and secure files can be stored within any directory (other than `web/`) without needing to create explicit protections in nginx or apache configurations.

#### Relative and module paths

This web root change affects how relative paths are perceived. For core sprout modules this is no longer 'DOCROOT' and therefore one should always prefer using absolute paths where possible.

A new `BaseController::getAbsModulePath()` helper has been added to assist.



### Media controller

- This serves skin/core/module assets
- Caching can be toggled via the bootstrap config
- use `/media_tools/clean` to refresh the cache


#### A note about caching

Deploy hooks should clear the media cache whenever possible. In development environment the cache is disabled. However, this is always configurable with the BootstrapConfig class.

To clear the cache:

```sh
php web/index.php media_tools/clean
```

### Path constants

This upgrade introduces changes to the path structure and these are reflected in the core constants. New path constants have also been added to assist.

Existing constants:

- `KOHANA = index.php` - unchanged
- `DOCROOT = src/` - unchanged, contains: modules, skin, config
- `APPPATH = vendor/sproutcms/cms/src/sprout/`

New constants:

- `BASE_PATH = .` - this is the application/repository root path
- `STORAGE_PATH = storage/` - contains: cache, temp, logs
- `VENDOR_PATH = vendor/`
- `COREPATH = vendor/sproutcms/src`
- `WEBROOT = web/` - contains: files, _media (cache)

(Apologies for the very inconsistent snake_case naming. It's too late now.)

When updating modules, careful attention must be paid to migrate any references to these paths.
For example:

- `APPPATH/cache/`
- `APPPATH/temp/`
- `DOCROOT/files/`
- `DOCROOT/sprout/`

Also take note of any relative paths. Sprout has always guaranteed the working directory matches the 'DOCROOT' constant. Laziness might have tempted some to not use absolute paths for references to: files, sprout core media, caches, and so on. So these need to be fixed.


### Dependency injection

Sprout 3.2 provides abstract interfaces for integrating external services in the `Sprout\Services` namespace. This enables one to extend the base application without modifying core subsystems.

In particular:

- RemoteAuthInterface
- UserAuthInterface
- UserPermsInterface
- TraceInterface

The goal is to improve safety and flexibility of these interfaces. When installing a concrete implementation one must register their class with Sprout.

This somewhat compliments but could replace the `Register::feature()` concept, although the 'feature' is somewhat broader. Currently only the 'Users' module makes use of this. So replacing 'features' with 'services' could eventually happen.

For example:

```php
// file: sprout_load.php
use Sprout\Helpers\Services;
use SproutModules\Users\Helpers\UserAuth;
use SproutModules\Users\Helpers\UserPerms;

Services::register(UserAuth::class);
Services::register(UserPerms::class);

// Elsewhere. Some controller probably.

/** @var UserAuthInterface|null $auth */
$auth = Services::get(UserAuthInterface::class);

if ($auth) {
  $id = $auth::getId();
  echo "Registered, id: {$id}\n";
} else {
  echo "Not registered\n";
}
```


## Step-by-step Guide

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

1. Add the sprout dependency with `composer require "sproutcms/cms:3.2.*"`
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


#### 4.4 ColModifer->modify expects an additional argument

ColModifier classes now pass the entire record row through as a 3rd argument.
Ensure any custom ColModifier class (if any) have their `modify` function amended to suit

All `modify` functions should be `public function modify($val, $field_name, $row)`

You can find these in the following ways:

- Perform a full code search for `public function modify(`
- If your editor supports it, do a file open search (ctrl+p in VSCode) and start with `ColModifier`


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
9. Cron jobs have updated paths to the entry `web/index.php`


### 7. Prod Server Environment

We will need to ensure the following things are checked and in place on the prod server.

Before upgrading:

1. PHP version is minimum 7.4.
2. Composer is installed and usable by the web user (at least v2.0).
3. Nginx rewrites must be updated, compare and update from the `documentation/nginx/sprout.conf` file.
4. Ensure a `.env` file is in place at base dir level, with prod details in it.


After upgrading:

1. Restart nginx/apache to process new rewrites.
2. Move files from `src/files` to `web/files`
3. Delete any existing `database.config.php` files (if any) to avoid confusion
4. Check the `storage/temp` and `storage/cache` folders exist
5. Check for any leftover files in `src/sprout` - this should be empty
6. Remove `src/vendor` - if any
