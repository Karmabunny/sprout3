
# SproutCMS 4.5

_Breaking changes abound._

This is a release that removes cruft and adds strong typings. As such, the new types for Controllers and Models will definitely make downstream project have a little whinge.

This is also preparation for Sprout 5.0 and so shuffles some configs around the place.

TLDR: skip to [Migration](#Migration).


## New things

### Strong Models

_Credit: `benno` + `gwillz`_

PDB and KB collections now fully support strong types and appropriate type casting.


### Strong Types

_Credit: `benno`_

For controllers and widgets.


### Controller Response

_Credit: `gwillz`_

New shorthand helpers on the base controller that build `ResponseInterface` objects. These can be returned from the method and Sprout will process them. This enables clearer flow-control and easy manipulation of prepared outputs.


### JSON Forms Controller

_Credit: `gwillz`_

This separates out logic from the `Controller` class for the action logger and JSON Forms helpers.

- `RecordController` - action logs, multi-edit editing
- `JsonFormsController` - form loading, editing, validation (inherits `RecordController`)

This fixes many of the implied but not guaranteed methods in ManagedAdminController.


### Session Drivers

_Credit: `gwillz`_

Extends our `SessionDriver` from the native `SessionHandlerInterface`. This means external session handlers can be installed without building a shim.

The `SessionDriver` is deprecated in favour the native interface.


### Kohana Replacements

_Credit: `gwillz`_

Kohana is now husk of it's former self. The core bootstrap remains but all helper classes and methods have been replaced.

- `Kohana::lang()` -> `I18n::lang()`
- `Kohana::userAgent()` -> `Request::userAgent()`
- `Kohana::config()` -> `Config::get()`
- `Kohana::logException()` -> `Errors::logException()`
- `Kohana::backtrace()` -> `Errors::backtrace()`
- `Kohana_Exception` -> `HttpException`
- `Kohana_404_Exception` -> `HttpNotFoundException`

These will continue working but are marked as deprecated.


#### New Config Loader

Configs are now inheritable. Base configs are provided to downstream configs (app, modules, skin - in order) and effectively merged together.

This means that downstream configs don't need to redeclare _every_ property from an upstream config. Instead they only need to modify those that matter and leaving the defaults as-is.

Hopefully no breaking changes to downstream is required here.


#### HTTP Exceptions

Kohana exceptions are now children of `HttpException`. Whenever catching these best use the `HttpExceptionInterface` and checking the `getStatusCode()` method.

It's important to note that `Kohana_404_Exception` is not an alias of `HttpNotFoundException` and retains it's heirarchy from `Kohana_Exception`. This is intended to retain compatability. When Kohana is eventually fully removed the `Kohana_404_Exception` will become an alias of `HttpNotFoundException`.

However do try to update any `catch` blocks with `HttpExceptionInterface` instead. If that's too broad, pick a better exception (e.g. `RowMissingException`) or infer missing items with null or a result monad.


## Migration

Key things:

- Strong types on models, widgets, controllers
- Frontend JSON forms
- Config changes


### Add strong types

Controllers require strong types on all protected config variables, such as `$friendly_name` and `$main_columns`.

Models don't require strong types to function, however the `$id` property should be removed.

Collections (including models) have strong return types enforced. You may find some inherited methods need their types updated. For example:

- `update(iterable $config): void`
- `save(array $fields = [], bool|string $validate = true): bool`
- `rules(?string $scenario = null): array`


### Frontend JSON Forms

If you've used JSON forms in your frontend, update your controller to extend `JsonFormsController` instead of `Controller`.



### Bootstrap Configs

This was always a bit clumsy. It is now defined in the core `config` config. If Sprout finds a `_bootstrap_config.php` file and valid class it will still load those values (for now).

Both require SSL and origin cleanup are _removed_.

Some properties in the core `config` (that never belonged) have been _moved_ into the `sprout` config.


Migrated:

- `BootstrapConfig::ERROR_REPORTING` -> `config.error_reporting`
- `BootstrapConfig::ENABLE_FATAL_ERRORS` -> `config.enable_fatal_errors`
- `BootstrapConfig::enable_media_cache` -> `config.enable_media_cache`
- `BootstrapConfig::TIMEZONE` -> `locale.timezone`

Moved:

- `config.default_country_code` -> `sprout.default_country_code`
- `config.common_phone_codes` -> `sprout.common_phone_codes`
- `config.custom_head_tags` -> `sprout.custom_head_tags`
- `sprout.locale` -> `locale.locale`
- `sprout.load_balanced` -> `config.load_balanced`

Removed:

- `BootstrapConfig::ENABLE_KOHANA_CACHE`
- `BootstrapConfig::ORIGIN_DEBUG_CLEANUP`
- `require_ssl.*`
- `config.global_xss_filtering`
- `config.display_errors`


Important or most likely to need attention:

- `config.error_reporting`
- `config.load_balanced`
- `locale.timezone`

