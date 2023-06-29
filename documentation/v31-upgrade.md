
# SproutCMS 3.1


### Autoloading

Sprout 3.1 moves the autoloader to the entry `src/index.php` file.

It also removes the Kohana autoloader. Some 3.0 projects may have injected the Composer autoloader here also. Others also have a custom vendors autoloader.

Although multiple autoloaders _are_ supported by PHP - it's a confusing affair. When migrating be sure to stick to the one in the entry file.


### Deprecations

The `View` class is deprecated, an alias for `PhpView`. Existing code should continue to function as-is.

Pdb-related exceptions `RowMissingException` and friends are now aliases for their composer counterparts. These will continue to work but are also deprecated.


### Notes about Twig

Many modules will assume that all views are PHP templates. Sprout introduces a `BaseView::create()` helper to automatically select the correct template type based on the skin config.

Skins can declare a `skin_views_type = 'twig'` in their `sprout_load.php`. When using `BaseView::create('skin/inner')` this will look for `src/skin/<my-skin>/inner.twig`.

Sprout exposes are `sprout` global object with a selection of core helpers to assist writing templates. This is defined in `src/Helpers/SproutVariable.php`.

Additional helpers can be added using `Register::templateVariable()`.
