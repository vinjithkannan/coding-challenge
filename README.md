# Developer Implementation
# Used Symfony's recommended repo to set up docker container 

https://github.com/dunglas/symfony-docker

# Symfony Docker

A [Docker](https://www.docker.com/)-based installer and runtime for the [Symfony](https://symfony.com) web framework, with full [HTTP/2](https://symfony.com/doc/current/weblink.html), HTTP/3 and HTTPS support.

![CI](https://github.com/dunglas/symfony-docker/workflows/CI/badge.svg)

## Getting Started

1. If not already done, [install Docker Compose](https://docs.docker.com/compose/install/)
2. Run `docker compose build --pull --no-cache` to build fresh images
3. Run `docker compose up` (the logs will be displayed in the current shell)
4. Open `https://localhost` in your favorite web browser and [accept the auto-generated TLS certificate](https://stackoverflow.com/a/15076602/1352334)
5. Run `docker compose down --remove-orphans` to stop the Docker containers.
6. Run `docker exec -it patona-test-php-1 sh` to open shell prompt
7. Run `composer install` to install all dependency packages
8. Run `php bin/phpunit` to run Tests
9. Run `XDEBUG_MODE=coverage php -dxdebug.mode=coverage bin/phpunit --coverage-clover='reports/coverage/coverage.xml' --coverage-html='reports/coverage'` to generate code coverage report
10. Run `./vendor/bin/phpcs src` validating php coding standards
11. Run `./vendor/bin/phpstan analyse -l 3 src tests` analysing the code quality

## Features

* Kept same features which provided by the repo default. 
* Coding challenge implementation done on top of default feature.
* Requesting to reviewer follow the set up of this repo, if the recommended commands won't works.
* Used following additional package to full fill the requirements 
  * nesbot/carbon - to handle date formatting
  * stolt/json-lines  - to handle parsing of JsonLine file contents
  * friendsofphp/php-cs-fixer - to handle code standard fixing
  * phpstan/phpstan - to handle code quality analyser
  * squizlabs/php_codesniffer - to handle coding standards

