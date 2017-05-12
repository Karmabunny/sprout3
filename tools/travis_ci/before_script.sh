#!/bin/bash

mysql -e 'CREATE DATABASE sprout3;'

cp tools/travis_ci/database.php src/config

cd src
php index.php dbtools/sync
cd ..
