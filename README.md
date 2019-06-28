# Neucore connector boilerplate example 

- Install dependencies with `composer install`.

- Copy `config/config.dist.php` to `config/config.php` and adjust values.

- Add any URL you need in `config/routes.php`.

- If you need groups from Brave Core to secure routes, see `Bootstrap::enableRoutes()`,
enable the appropriate middlewares and configure your roles in `config/security.php`.

## Changelog

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
