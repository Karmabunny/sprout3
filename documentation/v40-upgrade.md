
# SproutCMS 4.0

This isn't a huge departure from the typical Sprout architecture (yet). However, we wish to respect semver and this version makes for some considerable migration and breaking changes.

The big drop is a new events and module system which should enable one to build modules as Composer packages.



## Overview


### Unified Controller Names

_Credit: `gwillz`_

A general improvement to controller 'short names' a legacy from Sprout 2 that still serves a purpose but had some real foot-guns and hardcoded exceptions for custom modules.


### External Modules

_Credit: `gwillz`_

Flexibility en-mass, described below.


### New Events System

_Credit: `gwillz`_

A modern event system for the masses.


### Improved UTF8 support

_Credit: `gwillz`_

It's just htmlpurifier instead of iconv.



## Upgrading


```sh
composer require -W sproutcms/cms:^4
```


### Homepage module

You've likely got a homepage module and Sprout has always assumed it exists. No longer!

This was hardcoded into the core routes config and postrouting. Meaning, there _IS_ a migration.

Add this to the `routes` config for the homepage module.

```php
$config['_default'] = 'SproutModules\Karmabunny\HomePage\Controllers\HomePageController/index'
```


### Controller Permissions

```php
ManagedAdminController::_getContentPermissionGroups(): array;
```

Per-record and per-operator category permissions are now integrated into each controller instead of somewhere deep inside Sprout. This now permits modules to opt-out of permission systems, or even extend the utility CategoryAdmin or NoRecord abstract controllers and _add_ permission abilities.

The base ManagedAdminController will enable both 'record' and 'operator_category' permissions. A table of permissions for builtin controller is below:


| Controller                         | Shortname             | Record  | Operator Category |
|------------------------------------|-----------------------|---------|-------------------|
| ManagedAdminController             | --                    | **yes** | **yes**           |
| ListAdminController                | --                    | **yes** | **yes**           |
| HasCategoriesAdminController       | --                    | **yes** | **yes**           |
| CategoryAdminController            | --                    | no      | no                |
| NoRecordsAdminController           | --                    | no      | no                |
| ActionLoginAdminController         | action_log            | no      | no                |
| ContentSubscriptionAdminController | content_subscription  | no      | no                |
| CronJobAdminController             | cron_job              | no      | no                |
| FileAdminController                | file                  | no      | **yes**           |
| OperatorCategoryAdminController    | operator_category     | no      | no                |
| OperatorAdminController            | operator              | no      | no                |
| PageAdminController                | page                  | no      | **yes**           |
| PerRecordPermissionAdminController | per_record_permission | no      | no                |
| SubsiteAdminController             | subsite               | no      | no                |
| TagAdminController                 | tag                   | no      | no                |
| WorkerJobAdminController           | worker_job            | no      | no                |


There's still a backward compatible exception for a 'form_submission' controller if you have one but this shouldn't be relied on. This should be updated to use the new permissions method before the exception is removed entirely.


### Events

We've done our best to provide a backwards compatible hook for the old Kohana events. However, this doesn't support every feature so there are compromises. We believe though these features were never used (certainly not in our own work) and is largely a safe migration.

We're hoping this system is much more approachable and will make customising things like internal Sprout behaviours and external modules much easier.

This is a static events system. Events are keyed by their class name (namespace included).

One can emit events like so:

```php
$event = new MyEvent(['data' => 'something']);
Events::trigger(MyClass::class, $event);
echo $event->data;
```

Or listen to them:

```php
Events::on(MyClass::class, function(MyEvent $event) {
    $event->data = 'else';
});
```

Kohana events are mapped as so:

| Old name                           | New class                      |
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



### Site Modules

Modules now require a special 'Module' class. This helps Sprout locate your module wherever it lives. Whether it's a classic site module or loaded from afar via Composer. It is also responsible for the bootstrap hooks `sprout_load` and `admin_load`.

The migration is relatively simple, there are two steps:

1. Create the module class.

One can this by extending Module or the ModuleInterface, but for best results use the migration script. This will locate the module relative to your path and create the appropriate Module class.

```sh
php vendor/sproutcms/cms/tools/migrate_module.php <module-name>
```

2. Update the `Register::modules()` syntax in your config file.

This now requires the full namespace of the new module class.

```php
use Sprout\Helpers\Register;
use SproutModules\Karmabunny\Demo\DemoModule;

Register::modules([
    DemoModule::class,
]);
```


### External Modules

This will be a small exercise in writing a Composer package. We're not going into that detail here.

After creating your package, all you need is a namespace and a Module class like so:


```php
namespace My\Unique\Namespace;

use Sprout\Helpers\Module;
use Sprout\Helpers\Sprout;

class MyModule extends Module
{

    /** @inheritdoc */
    public function getVersion(): string
    {
        return Sprout::getInstalledVersion('my-vendor/my-package');
    }
}
```

Install the module into a Sprout site like any other:

```php
use Sprout\Helpers\Register;
use My\Unique\Namespace\MyModule;

Register::modules([
    MyModule::class,
]);
```

The `sprout_load.php` and `admin_load.php` files relative to the module class will be loaded automatically. However this isn't required. One can write these directly into the module class itself. Get a good read of the `ModuleInterface` to learn more.

