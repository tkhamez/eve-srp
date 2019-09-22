# Neucore connector boilerplate example 

- Install dependencies with `composer install`.
- Copy `.env.dist` to `.env` and adjust values or set the corresponding environment variables in another way.
- Add any URL you need in `config/routes.php`.

- If you need groups from Brave Core to secure routes, see `Bootstrap::enableRoutes()`,
enable the appropriate middlewares and configure your roles in `config/security.php`.

## Changelog

### 3.0.0

Preconfigured for
- EVE SSO v2
- Slim 4 with slim/psr7, php-di
- Added .env file for configuration variables instead of config.php

Needs PHP >= 7.2

### 2.0.0

Preconfigured for
- EVE SSO v2
- Slim 3

Needs PHP >= 7.1

### 1.0.0

Preconfigured for
- EVE SSO v1
- Slim 3

Needs PHP >= 5.5
