
# SproutCMS 4.4

Hopefully nothing _breaking_ in this release. Only some notable new features.

This release should be last of the 4.x series before Sprout v5.0.


## New things


### Passing PHPstan

_Credit: `gwillz`_

Over 600 errors have been cleaned up. Now you can confidently bash around the codebase knowing you're not introducing syntax or type errors (mostly).


### Worker Queue

_Credit: `gwillz`_

Workers within channels and a new `WorkerJob` class.

This supports timeout and priorities and all the goodies that come with a queue.


### Recaptcha Min Score

_Credit: `jamie`_

Sensible configuration of spam settings.


### Simple List Admin

_Credit: `jamie`_

A "naked" version of a list controller.


### Exception Origin

_Credit: `benno`_

Immediate visibility of where an error has come from.


### Symfony Process

_Credit: `gwillz`_

Workers and crons have been rewritten with the mature shell abstraction from Symfony.

