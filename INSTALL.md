# Installation

## Prerequisites
Sprout3 requires the following software stack
- NGINX, Apache or other PHP compatible web server
- PHP >= 5.5 with `pdo-mysql`, `fpm`, `gd`
- MySQL/MariaDB >= 5.1

Running on Debian/Ubuntu you can pull in these dependencies with `apt-get`

```
testbob@testbob:~ $ sudo apt-get update
......

testbob@testbob:~ $ sudo apt-get install nginx mysql-server php5 php5-mysql php5-fpm php5-gd
```

## Extracting Sprout

Create the destination directory for Sprout3. In this example it is created in the user's home
directory; in practice this will instead be `/var/www` or some other web server dependent directory.
```
testbob@testbob:~ $ mkdir -v ./sprout3
```

Unzip the release ZIP to the destination directory created above
```
testbob@testbob:~ $ unzip -v -d ./sprout3 sprout3-release.zip
```

## Security considerations
The extracted source should be owned (see `man 1 chown`) by a user **other** than the web server user.
That is: if the web server is running as `www-data` the source should **not** be owned by `www-data`, pick another
user, e.g. your own shell user.

## NGINX setup
NGINX is the preferred web server for deployment use; it's fast, stable, secure and easy to setup.

We've bundled a working `server` block config in [documentation/nginx/server.conf](documentation/nginx/server.conf) which you can
edit and copy into your existing NGINX configuration. This includes a lot of sane defaults e.g. keep alives, GZIP, security
and FastCGI configuration. This file should be configured for each Sprout site.

The second configuration file needed is [nginx_sprout.conf](nginx_sprout.conf): this contains the various routes needed
for a working Sprout install. It doesn't need to be customised per-site so you can just `include` it for each site.

You'll need to customise [documentation/nginx/server.conf](documentation/nginx/server.conf) for your site but don't worry, we've
done most of it for you.

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
  
  It will need to point to the directory where Sprout's index.php is located, usually this is the `src` directory found
  where you extracted Sprout.
  
  **Example**
  ```
  testbob@testbob:/var/www/sprout3/src$ ls -lha
  total 52K
  drwxr-xr-x  8 testbob testbob 4.0K Aug  4 12:17 .
  drwxr-xr-x 10 testbob testbob 4.0K Aug  4 12:06 ..
  drwxr-xr-x  2 testbob testbob 4.0K Aug  4 10:45 config
  -rw-r--r--  1 testbob testbob 1.7K May  2 10:34 cron_daily.sh
  drwxrwxrwx  2 testbob testbob 4.0K Aug  1 10:43 files
  -rw-r--r--  1 testbob testbob   37 Nov 17  2015 .gitignore
  -rw-r--r--  1 testbob testbob 2.1K Aug  4 12:17 .htaccess
  -rw-r--r--  1 testbob testbob 4.3K Aug  4 10:45 index.php
  drwxr-xr-x 11 testbob testbob 4.0K Jan 18  2016 media
  drwxr-xr-x 13 testbob testbob 4.0K Jul 19 16:29 modules
  drwxr-xr-x  4 testbob testbob 4.0K Aug  3 14:07 skin
  drwxr-xr-x 18 testbob testbob 4.0K Aug  3 14:07 sprout
  ```
  
  As you can see we're in the same directory as index.php; this is the one we want. So `root` will simply be:
  
  ```
  root /var/www/sprout3/src;
  ```

4. `include /home/test/sprout3/nginx_sprout.conf;`

  This line pulls in the NGINX Sprout configuration. It should point to your nginx_sprout.conf.
  If Sprout was extracted to `/var/www/sprout3` it should look like:
  
  ```
  include /var/www/sprout3/nginx_sprout.conf;
  ```

Check you haven't made any syntax errors in the configuration files by running
```
testbob@testbob:/var/www/sprout3/src$ sudo nginx -t
nginx: the configuration file /usr/local/nginx/conf/nginx.conf syntax is ok
nginx: configuration file /usr/local/nginx/conf/nginx.conf test is successful
```

And that's it! You're now ready to move onto setting up Sprout itself.

## Apache setup
**TODO**
