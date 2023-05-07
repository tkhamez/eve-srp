# EVE-SRP

A web application to manage a **Ship Replacement Program** for [EVE Online](https://www.eveonline.com) 
with optional [zKillboard](https://github.com/zKillboard/zKillboard) integration.

<!-- toc -->

- [Install](#install)
  * [Further Configurations](#further-configurations)
  * [Initial Setup](#initial-setup)
- [Provider](#provider)
  * [ESI Provider](#esi-provider)
  * [Neucore Provider](#neucore-provider)
- [Development Environment](#development-environment)
  * [Install Backend](#install-backend)
  * [Build Frontend](#build-frontend)
- [Migration from paxswill/evesrp](#migration-from-paxswillevesrp)

<!-- tocstop -->

## Install

To run the application you need a Linux OS (others may work but were not tested), a web server with support 
for PHP >=8.0 and URL rewriting, a MySQL, MariaDB or PostgreSQL database (tested with MariaDB 10.6
and PostgreSQL 12).

- Create an EVE application at https://developers.eveonline.com, no scopes required. Set the callback URL to
  `https://your.domain.tld/auth`.
- Create a database for the application.
- Download the latest release from https://github.com/tkhamez/eve-srp/releases or build it yourself (see below) 
  and extract it.
- Set the document root to the `web` directory and configure URL rewriting to `index.php` (see
  [Slim framework - Web Servers](https://www.slimframework.com/docs/v4/start/web-servers.html) for details).
- Make sure that the `storage` directory is writable by the webserver.
- Copy `config/.env.dist` to `config/.env` and adjust values or set the corresponding environment variables. At
  a minimum set:
  - `EVE_SRP_DB_URL`
  - `EVE_SRP_SSO_CLIENT_ID`, `EVE_SRP_SSO_CLIENT_SECRET` and `EVE_SRP_SSO_REDIRECT_URI`
  - If you keep the default provider add your EVE character ID to `EVE_SRP_PROVIDER_ESI_GLOBAL_ADMIN_CHARACTERS`.

Log messages are sent to `storage/error-*.log` files.

### Further Configurations

Various texts and the logo can be changed via environment variables.

You can add your own JavaScript code to `web/static/custom.js`, for example for analytics software.

### Initial Setup

After you log in go to _Admin -> Groups_ and synchronize them. Then add divisions and configure permissions for them.

Permissions for each division are based on groups which are provided by a provider, see below.

Besides that there is only one fixed role, the _global admin_. It is given to members of the groups from the 
environment variable `EVE_SRP_ROLE_GLOBAL_ADMIN`.

Global admins can create divisions and configure permissions for each of them separately. Only global 
admins can see SRP requests without a division, e.g. when a division was deleted.

## Provider

Providers implement the `ProviderInterface` interface and provide groups for the logged-in character and 
optionally additional alternative characters for which the user can submit requests.

Only one provider can be used at once. It is determined by the environment variable `EVE_SRP_PROVIDER`.

### ESI Provider

This is a simple provider that assigns groups based on EVE characters, their corporation and alliance.

There are 5 groups: `member`, `review`, `pay`, `admin` and `global-admin`. For each of them there are one or more 
environment variables `EVE_SRP_PROVIDER_ESI_*` that accept list of character, corporation or alliance IDs.

### Neucore Provider

This provider needs a [Neucore](https://github.com/tkhamez/neucore) installation. It will provide groups and
alternative characters from it.

To use it add an app to Neucore and give it the `app-chars` and `app-groups` roles and add any group to it that you 
want to use. Then set the `EVE_SRP_PROVIDER_NEUCORE_*` environment variables.

## Development Environment

Only tested on Linux.

```
docker-compose up
```

The database connection string is: `mysql://root:eve_srp@eve_srp_db/eve_srp`. To run the unit tests add a
database named `eve_srp_test`.

The application is available at: http://localhost:8000.

Consoles for PHP and Node.js:
```
docker-compose exec -u www-data eve_srp_php /bin/sh
docker-compose exec -u node eve_srp_node /bin/sh
```

The script `build.sh` can be used to create a release.

### Install Backend

Copy `config/.env.dist` to `config/.env`, set `EVE_SRP_ENV` to `dev` and add your values for
`EVE_SRP_SSO_CLIENT_ID` and `EVE_SRP_SSO_CLIENT_SECRET`.

```
composer install
vendor/bin/doctrine-migrations migrations:migrate --no-interaction
```

Useful commands:
```
bin/doctrine orm:validate-schema
bin/doctrine dbal:reserved-words
bin/doctrine orm:generate-proxies
vendor/bin/doctrine-migrations migrations:diff

sudo rm -R ./storage/compilation_cache/*
```

### Build Frontend

Install dependencies:
```
npm install
```

Automatically rebuild during development:
```
npm run watch
```

Create production build:
```
npm run build
```

## Migration from paxswill/evesrp

MySQL/MariaDB databases: evesrp => eve_srp

Replace values for evesrp.entity.type_ (BraveOauthGroup) and evesrp.entity.authmethod (EVESSONeucore) if needed.

```sql
INSERT INTO eve_srp.users (id, name) 
    SELECT user.id, name FROM evesrp.user LEFT JOIN evesrp.entity ON user.id = entity.id;
INSERT INTO eve_srp.characters (id, user_id, name, main) SELECT id, user_id, name, 0 FROM evesrp.pilot;
INSERT INTO eve_srp.divisions (id, name) SELECT id, name FROM evesrp.division;
INSERT INTO eve_srp.requests
    (id, user_id, division_id, created, character_id, corporation_name, alliance_name, ship, kill_time, 
     solar_system, details, status, base_payout, payout)
    SELECT id, submitter_id, division_id, timestamp, pilot_id, corporation, alliance, ship_type, kill_timestamp, 
           `system`, details, IF(status = 'evaluating', 'open', status), base_payout, payout
    FROM evesrp.request;
INSERT INTO eve_srp.actions (id, user_id, request_id, created, category, note)
    SELECT id, user_id, request_id, timestamp, type_, note FROM evesrp.action;
INSERT INTO eve_srp.external_groups (id, name)
    SELECT id, name FROM evesrp.entity WHERE type_ = 'BraveOauthGroup' AND authmethod = 'EVESSONeucore';
INSERT INTO eve_srp.permissions (id, division_id, external_group_id, role_name)
    SELECT evesrp.permission.id, division_id, entity_id, permission
    FROM evesrp.permission
    INNER JOIN evesrp.entity ON evesrp.permission.entity_id = evesrp.entity.id
    WHERE type_ = 'BraveOauthGroup' AND authmethod = 'EVESSONeucore'
        AND permission IN ('submit', 'review', 'pay', 'admin');
INSERT INTO eve_srp.user_external_group (user_id, external_group_id)
    SELECT users_groups.user_id, group_id FROM evesrp.users_groups;
INSERT INTO eve_srp.modifiers 
    (id, request_id, user_id, created, mod_type, note, voided_time, voided_user_id, mod_value)
    SELECT modifier.id, request_id, user_id, timestamp, LOWER(SUBSTRING(_type, 1, 8)), note, voided_timestamp, 
           voided_user_id,
           CASE WHEN absolute_modifier.value IS NOT NULL THEN  CAST(absolute_modifier.value AS SIGNED) 
               WHEN relative_modifier.value IS NOT NULL THEN CAST(relative_modifier.value * 100 AS SIGNED) 
               END AS value
    FROM evesrp.modifier
    LEFT JOIN evesrp.absolute_modifier ON modifier.id = absolute_modifier.id
    LEFT JOIN evesrp.relative_modifier ON modifier.id = relative_modifier.id;
```

Note: Not everything is copied, e.g. the permission "audit" and user notes.
