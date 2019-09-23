# Brave Collective - SRP

## Install

- Copy `.env.dist` to `.env` and adjust values or set the corresponding environment variables in another way.
- Install dependencies with `composer install`.
- Clear the template cache: `rm -R cache/compilation_cache`
- sync db schema: `vendor/bin/doctrine orm:schema-tool:update --force`

## Migration from paxswill/evesrp

...
