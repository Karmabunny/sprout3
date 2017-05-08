SproutCMS 3
===========

SproutCMS 3 is a content management system and framework which uses PHP and MySQL.


Requirements
------------

* PHP 5.5 or later

* A web server, e.g. Apache or nginx

* MySQL 5.1 or later


Installation
------------

1. Download
2. Extract the ZIP file somewhere
3. Load up that directory in a web browser, and browse to /welcome (e.g. http://localhost:8080/welcome)
4. Follow the on-screen instructions

For a detailed installation walk-through see [INSTALL.md](INSTALL.md)


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
$ phpunit
```

To run [NStrack](https://github.com/Karmabunny/nstrack), our namespace issue finder and fixer, run from the
root directory, e.g.
```
$ nstrack
```

To add license blocks to recently added files, run the following from the root directory:
```
$ php tools/license_block/license_block.php
```


