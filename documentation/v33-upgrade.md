
# SproutCMS 3.3

For a quick migration, skip to the [Site Modules](#site-modules) section.


## Overview

Sprout 3.3 is largely refining the modules system as well as some efforts to move away from it's Kohana roots.


### Unified Admin Controller "short names"

`ManagedAdminController::$controller_name` still exists, but it's no longer required to specify when creating a controller. If empty it will populate from the register. If not in the register - the controller should be loading! (it's an exception.)

All short names can now be declared in one place (the register). This also means all core controllers are now registered (previously they weren't - more on this later).


#### Content controllers

There was a weak description of a 'content controller' - one that permits per-record and per-subsite permissions. This was loosely defined by `Register::coreContentControllers()` and some inheritance filters. All module controllers are implicitly a content controller.

We now have a clear filtering system. Controllers have a `_getContentsPermissionGroups()` helper that returns boolean map to inform the permission controllers if they have these features enables.

This means that module controllers can also opt-out of the builtin permission systems. Something that was not previously possible without core hacks.


#### URL discovery

Having resolved categorisation of 'content controllers' means all core controllers can be registered and their short names are also in one place. We now have an `admin_load.php` in the sprout app folder to achieve this.

When requesting an admin controller, for example `admin/edit/page/1` - this looks for the `page` shorthand in the register and finds the `PageController` class name, yada yada, render.


#### Perks

Controller registration doesn't need to conform to the `SproutModules\Author\Module\Controllers\Admin\` pattern. This was required to properly register the core controllers. However this same functionality is required for external modules support - so yay.

Friendly names, navigation names, table names - these are all now generated on construct (if empty).

Category controllers will naturally create all their own handles/names based on the expected structures. A basic category controller can be declared like this:

```php
class RedirectCategoryAdminController extends CategoryAdminController {}
```


### Builtin Modules

Now that modules are not tied into the `DOCROOT/modules` folder, the sample modules can now return the core library. These are available at the `SproutModules\Sample\` namespace.

In the same step, the HomePage module is not required by any core code. However, there is still much core behaviour based on the `home_page` controller shorthand.


### Events

The Kohana events system is now a shim into the new system. Existing event _should_ continue to work as is but it's recommend to migrate any existing kohana-event reliant code to the new events.

Having moved Sprout core into a vendored library, projects are less able to modify core behaviour without explicit configurations or exposed interfaces.The new event system is an easy _and consistent_ way to interact with code behaviour.

The new system is also independent of the Sprout project, meaning other libraries can expose event interfaces that can be quickly integrated into Sprout or Sprout modules.


## Step-by-step Guide

### 1. Modules

Classic Sprout modules are identified by their folder structure. The name of a modules matches it's base folder name and boot code is registered in the `sprout_load.php` and `admin_load.php` files.

A new `Sprout\Helpers\Module` class is introduced. This can be easily located using the autoloader, permitting a module to live at any path - and in any vendored package.

This class is already backwards compatible, simply place it in an existing module folder and it will locate the relative module files:

- sprout_load.php
- admin_load.php
- db_struct.xml
- config/*
- media/*
- views/*

The module name is inferred from the class name (with the Module suffix). However, this can be changed by overriding the static `getName()` helper.

The version should match the module package. Sprout provides some helpers for this:

```php
// This is the Composer version and VCS tag for the given package ID.
// Like: v1.0.1 - #abcde12
function getVersion(): string
{
    return Sprout::getInstalledVersion('karmabunny/my-fantastic-module');
}

// This fetches the 'root' package. Use this for when a modules lives
// in the project repo, i.e. src/modules/.
function getVersion(): string
{
    return Sprout::getSiteVersion();
}
```


#### Site Modules

A migration for a site module is relatively simple. As mentioned (above) the class simply needs to sit in the module folder with the bare-bones definition, below:

```php
// file: src/modules/Demo/DemoModule.php
namespace SproutModules\Karmabunny\Demo;

use Sprout\Helpers\Module;
use Sprout\Helpers\ModuleSiteTrait;

class DemoModule extends Module
{
    // This implements the 'site version'.
    use ModuleSiteTrait;
}
```


#### Migration Notes

Should any module code _assume_ the location of itself, or other modules - this will need to be updated. Sprout provides a `Modules` helper to access the module objects for this information.

```php
// name as defines by ::getName()
$module = Modules::getModule('Demo');

// OR full class name
$module = Module::getModuleByClass(MyModule::class);

// OR partial matches for a module path
$module = Module::getModuleByPath('/absolute/path/to/module/random/file.txt');

// OR find which modules a class belongs to
$module = Module::getModuleForClass(MyModuleController::class);
```

Modules instance themselves expose key helpers:

- `static getName()`
- `getPath()`
- `getVersion()`

For a full overview, read the `ModuleInterface` docs.


### 2. Events

Existing code using the Kohana `Event` class in `src/sprout/core/` will continue to work.

New event classes have a one-to-one relationship with the Kohana event IDs, described below.

These events are all emitted from the `Kohana` class.


| Kohana                             | Sprout 3.3                     |
|------------------------------------|--------------------------------|
| system.404                         | NotFoundEvent                  |
| system.shutdown                    | ShutdownEvent                  |
| system.pre_controller              | PreControllerEvent             |
| system.post_controller_constructor | PostControllerConstructorEvent |
| system.post_controller             | PostControllerEvent            |
| system.send_headers                | SendHeadersEvent               |
| system.display                     | DisplayEvent                   |
| system.session_write               | SessionWriteEvent              |
| system.redirect                    | RedirectEvent                  |



#### Additional events

Emitted from Kohana:
- ErrorEvent


Emitted from BaseController:

- AfterActionEvent
- BeforeActionEvent


Emitted from Router:

- PreRoutingEvent
- PostRoutingEvent

