SproutCMS 3.2
=============

SproutCMS is a flexible and feature rich cms and application framework, developed in PHP,
designed to enable quick and agile custom development. SproutCMS was built to reward
innovation and encourage developers to produce complex applications.
It is built by developers, for developers.

Website:
http://getsproutcms.com/

Development docs:
http://docs.getsproutcms.com/

User manual:
http://manual.getsproutcms.com/3.0


**For older versions of PHP (5.5 through 7.2) use the [Sprout 3.0](tree/v3.0) branch or update your hosting (recommended).**

Requirements
------------

* PHP 7.3 or later

* A web server, e.g. Apache or nginx

* MySQL 5.7 or later, or MariaDB 10.3 or later

* Composer 2 or later


Getting started
---------------

1. Run `composer create-project sproutcms/site`
2. Run `composer serve`
3. Browse to http://localhost:8080/

This is a quick start example. For a detailed installation walk-through see [INSTALL.md](INSTALL.md)


Deployment
----------

SproutCMS runs natively with [git deploy](https://github.com/mislav/git-deploy).

1. Set up your remote, e.g.
```
git remote add production "user@example.com:/apps/mynewapp"
```

2. Run the setup task
```
git deploy setup -r "production"
```

3. No need to run `git deploy init` as this has already been done

4. Push the code
```
git push production master
```


Development tools
-----------------

To run unit tests, execute the following from the root directory:
```
$ composer test
```

To run [NStrack](https://github.com/Karmabunny/nstrack), our namespace issue finder and fixer, run from the
root directory, e.g.
```
$ composer nstrack
```

To add license blocks to recently added files, run the following from the root directory:
```
$ php tools/license_block/license_block.php
```


Publishing
----------

_(for contributors)_

To publish a new version simply create a git tag with the next appropriate version. This is then automatically pushed to [packagist.org](https://packagist.org/packages/sproutcms/cms) via a web hook.

We've created a script to automate this:

```sh
./tools/publish.sh v4.x.x "My new changes"
```

For example, given the last version (from `git log`) is `v3.2.10` then tag and push `v3.2.11`.

Please be careful and don't publish untested code. Keep your messy business in a branch and require it into your projects using the `dev-` prefixes.

Such as:

```
composer require sproutcms/cms:dev-my-broken-branch
```

Or use the `composer patch-locals` script to symlink the dependency while locally building your site.
