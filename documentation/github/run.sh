#!/usr/bin/env bash
set -e
cd "$(dirname "$0")"

docker build -t php82cli -f Dockerfile .

if [[ ! -f phpunit.phar ]]; then
    curl https://phar.phpunit.de/phpunit-9.6.22.phar > phpunit.phar
    chmod +x phpunit.phar
fi


cd ../..

echo "Running tests..."
exec docker run --rm \
    --volume "$(pwd):/app" \
    --volume "$(pwd)/documentation/github/phpunit.phar:/usr/local/bin/phpunit" \
    --workdir /app \
    --network host \
    --env-file documentation/github/env \
    php82cli \
    phpunit --configuration phpunit.xml.dist --testdox
    # ./vendor/bin/phpunit --debug --configuration phpunit.xml.dist
