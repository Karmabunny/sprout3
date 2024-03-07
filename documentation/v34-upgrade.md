
# SproutCMS 3.4

Grab-bag of features, mostly PHP 8.2!


## Overview

This version _requires_ PHP 8.1+.

We've done our best to make 8.2 work, but there's likely some small things that have slipped past.


### AI Content Generation

_Credit: `jamiemonksuk`_

Some exciting stuff here. Currently supports OpenAI but more backends are planned!


### Custom Email reports

_Credit: `jamiemonksuk`_


### Honeypots

_Credit: `jamiemonksuk`_

(Forward?)-ported from Sprout 2. Makes a lot sense.


### Manage Operator Locks

_Credit: `aitken`_


### Site Settings

_Credit: `aitken`_




## Upgrading


```sh
composer require -W sproutcms/cms:3.4.*

# That's it. Walk away.
```

_Alternatively use the `^3.4` constraint._ But that may install a **newer** Sprout that may have breaking changes.

Apologies in advance that we've made a good mess of semver already.
