Upgrade notes
=============

## SproutCMS 3.0

This version is in maintenance-mode only.

New features _should not_ be developed. Instead one should first upgrade to Sprout 3.1.

Installing new modules is also not advised. Again, upgrade to Sprout 3.1.

### Support

3.0 remains only because of dated hosting services. Therefore, Sprout 3.0 must continue to support PHP 5.6 and Composer-free environments.



## SproutCMS 3.1

### Major changes:

- Minimum PHP 7.1
- Composer is required for autoloading

### New dependencies

Several helpers are now largely out-of-tree.

- Pdb
- Rdb
- Router
- PHPMailer
- TextStatistics
- FPDF + FPDI


### Notes about twig

- View is deprecated, an alias for PhpView
- TwigView will render '.twig' templates
- BaseView::create()
  - TODO when to use it
  - the 'skin_views_type' config
- TODO registering variables


### Security

One must update rewrites on any production server to prevent requests to the `/vendor` folder.



## SproutCMS 3.2

### Major changes

- Minimum version 7.3
- Sprout itself is a Composer dependency via `sproutcms/cms`


### Media controller

- This serves skin/core/module assets
- Caching can be toggled via the bootstrap config
- use `/media_tools/clean` to refresh the cache


### The `'web/'` root:

PHP source files and media assets no longer share a common directory. The only PHP file accessible by the public is `web/index.php`. This means the application __document root__ (nginx + apache configurations) needs to be updated from `src/ -> web/`.

This is a necessity for security but also practicality. Core assets and module/skin assets are no under the same 'DOCROOT' path. This is where the MediaController was born. This facilitates the process of providing access to these files.

New rewrites have been added to the nginx + apache configurations to help prevent any backward incompatible breakages.

This also draws a clear line between what is protected and what is not. Caches, temp, and secure files can be stored within any directory (other than `web/`) without needing to create explicit protections in nginx or apache configurations.

#### Relative and module paths

This web root change affects how relative paths are perceived. For core sprout modules this is no longer 'DOCROOT' and therefore one should always prefer using absolute paths where possible.

A new `BaseController::getAbsModulePath()` helper has been added to assist.

#### A note about caching

Deploy hooks should clear the media cache whenever possible. In development environment the cache is disabled. However, this is always configurable with the BootstrapConfig class.

To clear the cache:

```
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
- `WEBROOT = web/` - contains: files, _media (cache)

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


### External modules

Modules are expected to reside in the `src/modules/` folder of the application.

External modules, those being provided by a Composer dependency are theoretically possible but not yet supported.

A few options:

- Manually registering module paths or namespaces
- Discovering namespaces using a `Module` class as an anchor for the autoloader
- Adding the module `sprout_load.php` to the package's `autoload.files[]`


### Security

The updated layout of a Sprout 3.2 application permits one to more rapidly and easily update any given site. To receive updates for core features or any dependencies, simply run: `composer update`.

