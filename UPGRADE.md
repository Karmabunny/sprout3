Upgrade notes
=============

Sprout in developed on an evergreen branch (master). This contains the latest current version of Sprout, of which releases are made with a version tag, .e.g. `v3.2.25`.

Old releases are maintained on version branched, e.g. `v3.0`.

New features should only be developed from the master branch. Should old release require updates, these can then be backported into their respective branch.


### A quick note (about this document)

Being itself versioned, only the latest (master) version of this document is relevant. When performing migrations - only the latest version.


## SproutCMS 3.0

This version is in maintenance-mode only.

Installing new modules is also not advised. Upgrade to the latest Sprout.

3.0 remains only because of dated hosting services. Therefore, Sprout 3.0 must continue to support PHP 5.6 and Composer-free environments.


## SproutCMS 3.1

[Migration Docs](documentation/v31-upgrade.md)

Several components have been moved out of the main tree thanks to a hard-requirement for Composer autoloading.


### Major changes:

- Minimum PHP 7.1
- Composer is required for autoloading
- Twig support
- Several components are now out-of-tree:
  - Pdb
  - Rdb
  - Router
  - PHPMailer
  - TextStatistics
  - FPDF + FPDI


### Security

One must update rewrites on any production server:

- restrict the `/vendor` folder
- restrict `composer.*` files



## SproutCMS 3.2

[Migration Docs](documentation/v32-upgrade.md)

This is a major shift in the structure of core Sprout. The clear separation of core from site modules creates a clear mindset when developing new features. Per-site hacks and patches are no longer an answer.

This migration can be significant depending on how old or extensive the existing project is. The benefits are also significant.

An overview and migration guide is available here:


### Major changes

- Minimum version 7.4
- Document root moves to `web/`
- MediaController for serving assets
- Pluggable services; for authentication, users, error reporting
- Consistent environments (no more dev_hosts!)
- Sprout itself is a Composer dependency via `sproutcms/cms`
- Available on packagist.org


### Security

The updated layout of a Sprout 3.2 application permits one to more rapidly and easily update any given site. To receive updates for core features or any dependencies, simply run: `composer update`.



## SproutCMS 3.3

[Migration Docs](documentation/v33-upgrade.md)

This is a gentle update, mostly support for PHP 8.



## SproutCMS 3.4

[Migration Docs](documentation/v34-upgrade.md)

This requires PHP 8.1.

A few key upgrades for migrations and AI integrations.



## SproutCMS 4.0

[Migration Docs](documentation/v40-upgrade.md)

Big changes.


### Major Changes

- unified controller names
- external modules
- new events system



## SproutCMS 4.3

[Migration Docs](documentation/v43-upgrade.md)

Maturity upgrades.

- PDB v1.0
- AWS S3 files backend
- Neon forms
- New model rules
- Checksum media

