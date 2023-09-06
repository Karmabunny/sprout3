
# SproutCMS 3.3

A gentle update.


## Overview

It's just new features!


### PHP 8.x support

_Credit: `gwillz, jamiemonksuk`_

There are many many many fixes for subtle bugs like implicit null-to-string casts.

The test suite is updated to support phpunit v10, although it is still currently using v9 so we can run tests on PHP 7.4.

PHPStan is reporting less than 900 warnings. This will improve.


### JS Exceptions

_Credit: `gwillz`_

Register using the `JsError::needs()` helper in the skin `sprout_load.php` file.


### Media helper

_Credit: `gwillz`_

A media helper for Sprout + module resources (js, css, images).


### Secrets sanitiser

_Credit: `gwillz`_

A utility to mask or clean out passwords/keys/tokens from array datasets such as `$_SERVER` and `$_ENV`. This makes exception logs significantly less dangerous.


### Moderate interface

_Credit: `jamiemonksuk`_

Moderation classes can now fully customise the section data, as well as process additional data (such as notes), or perform post-transaction actions.

All markup is contained within the common `Moderate` base class. Alternatively, break free of the table markup and use the `ModerateInterface` directly.


### More

- HttpReq timeouts (Credit `gwillz`)
- ColModifierJsonArray (Credit `jamiemonksuk`)
- Restore actions in notification set methods  (Credit `jamiemonksuk`)
- Webp support (Credit: `thejosh`)



## Upgrading


```sh
composer require -W sproutcms/cms:3.3.*

# That's it. Walk away.
```

_Alternatively use the `^3.3` constraint._ But that may install a **newer** Sprout that may have breaking changes.

Apologies in advance that we've made a good mess of semver already.
