# Installation

## Prerequisites

Sprout3 requires the following software stack:

- NGINX, Apache or other PHP compatible web server
- PHP 7.3+ with `pdo-mysql`, `fpm`, `gd`
- MySQL 5.7+, MariaDB 10.3+

Running on Debian/Ubuntu you can pull in these dependencies with `apt-get`:

```
sudo apt-get update
sudo apt-get install nginx mysql-server php php-mysql php-fpm php-gd
```


## Creating a Sprout application

Sprout 3.2 is a package that can be installed via Composer.

There are two approaches to creating a base application:

1. Run `composer create-project sproutcms/site`
2. Download and extract the zip file

Note, the zip file is not frequently updated.


## Security considerations

The application should be owned (see `man 1 chown`) by a user **other** than the web server user.

That is: if the web server is running as `www-data` the source should **not** be owned by `www-data`, pick another user, e.g. your own shell user.


## NGINX setup

NGINX is the preferred web server for deployment use; it's fast, stable, secure and easy to setup.

We've bundled a working `server` block config in [documentation/nginx/server.conf](documentation/nginx/server.conf) which you can edit and copy into your existing NGINX configuration. This includes a lot of sane defaults e.g. keep alives, GZIP, security and FastCGI configuration. This file should be configured for each Sprout site.

The second configuration file needed is [documentation/nginx/sprout.conf](documentation/nginx/sprout.conf). This contains the various routes needed for a working Sprout install. It doesn't need to be customised per-site so you can just `include` it for each site.


### Some customisations

1. `listen`

  The first thing needed is to enable one of the `listen` directives; this tells NGINX which port(s) to listen on and which protocols to use.
  For plain HTTP:

  ```
  listen 80 default_server;
  ```

  `default_server` simply tells NGINX this is the default site to display, which only matters if you're serving multiple domain names.

  For HTTPS configuration see [NGINX SSL configuration](documentation/nginx/SSL.md)

2. `server_name`

  This sets the domain names NGINX will respond to. If you want your Sprout site to appear at *muchblog.example.com*
  you would use the following:

  ```
  server_name muchblog.example.com;
  ```

3. `root`

  NGINX needs to know where the Sprout files are located before it can display them.

  It will need to point to the directory where Sprout's `index.php` is located. This is almost always `web/`. It is not advised to expose your `src/` folder to the web server. Most modern configurations will permit you to store your application away from the document root.

  For example:

  ```
  root /var/www/my-sprout-site/web;
  ```

4. `include /var/www/my-sprout-site/nginx.conf;`

  This line pulls in the NGINX Sprout configuration. It should point to your sprout 'rewrites' configuration.

  For example:

  ```
  include /var/www/my-sprout-site/nginx.conf;
  ```


Now check you haven't made any syntax errors before reloading the nginx server:

```sh
sudo nginx -t
sudo nginx -s reload
```

And that's it! You're now ready to move onto setting up Sprout itself.


## Apache setup

**FOREVER TODO**
