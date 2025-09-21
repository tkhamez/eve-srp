# EVE-SRP

<!-- toc -->

- [About](#about)
- [Run the Application](#run-the-application)
  * [Prerequisites](#prerequisites)
  * [Run with Docker](#run-with-docker)
  * [Manual Installation](#manual-installation)
  * [Further Configuration](#further-configuration)
  * [Initial Setup](#initial-setup)
- [Provider](#provider)
  * [ESI Provider](#esi-provider)
  * [Neucore Provider](#neucore-provider)
- [Development Environment](#development-environment)
  * [Install Backend](#install-backend)
  * [Build Frontend](#build-frontend)
  * [Build Docker Container](#build-docker-container)
- [Contact and Support](#contact-and-support)
- [Migration from paxswill/evesrp](#migration-from-paxswillevesrp)

<!-- tocstop -->

## About

A web application to manage a **S**hip **R**eplacement **P**rogram for [EVE Online](https://www.eveonline.com).

Features:

- EVE SSO login.
- Multiple divisions.
- Permissions based on groups.
- Custom providers for groups and alternative characters.
- SRP requests with comments.
- Base payout with modifiers.
- Workflow with multiple statuses.
- Lists with open, in progress and approved requests.
- Search function for all requests.
- Admin UI to manage divisions and set their permissions.
- Various options to customise the installation (texts, logo).
- Optional [zKillboard](https://github.com/zKillboard/zKillboard) integration.

![Screenshot-Edit-Request.png](Screenshot-Edit-Request.png)

## Run the Application

### Prerequisites

The application needs a MySQL, MariaDB or PostgreSQL database (tested with MariaDB 10.11 and PostgreSQL 13).

- Create an EVE application at https://developers.eveonline.com, no scopes required. Set the callback URL to
  `https://your.domain.tld/auth`.
- Create a database for the application.

### Run with Docker

The Docker image is available at https://hub.docker.com/r/tkhamez/eve-srp. You can also build it yourself, see below.

Run with the following command, replace the environment variable values with your values. Example with minimum 
configuration using the EsiProvider:

```
docker run \
  --env=EVE_SRP_DB_URL=mysql://user:password@host/database_name \
  --env=EVE_SRP_SSO_CLIENT_ID=123 \
  --env=EVE_SRP_SSO_CLIENT_SECRET=abc \
  --env=EVE_SRP_SSO_REDIRECT_URI=https://your.domain.tld/auth \
  --env=EVE_SRP_PROVIDER_ESI_GLOBAL_ADMIN_CHARACTERS=96061222 \
  --workdir=/var/www \
  --publish=127.0.0.1:8080:80 \
  --name=eve_srp_prod \
  --restart=always \
  --detach=true \
  tkhamez/eve-srp
```

Run database migrations:
```
docker exec -u www-data eve-srp vendor/bin/doctrine-migrations migrations:migrate --no-interaction
```

View logs:
```
docker exec eve_srp_prod sh -c "tail -f ../storage/logs/*.log"
```

### Manual Installation

To run the application, you need a Linux OS (others may work but were not tested), PHP >=8.1.0
with the extension `json`, `gmp` and one of `pdo_mysql` or `pdo_pgsql`, and a web server with support for 
PHP and URL rewriting.

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
- Run database migrations: `vendor/bin/doctrine-migrations migrations:migrate --no-interaction`

Log messages are sent to `storage/logs/error-*.log` files.

### Further Configuration

Various texts, the logo and other settings can be changed via environment variables. They are documented in
`config/.env.dist` or below.

You can add your own JavaScript code to `web/static/custom.js`, for example, for analytics software.

**Modifiers**

There are three different ways how modifiers can be applied to the base payout, which is configured by the 
environment variable `EVE_SRP_MODIFIER_CALCULATION`:

- **sequentially** (default): They are applied in the order in which they were added.  
  E.g. 100 m base payout, minus 10%, plus 20 m, plus 30% = `((100 * 0.9) + 20) * 1.3 = 143`.
- **absolute_first**: Absolute modifiers are applied first, then the sum of all relative modifiers.  
  E.g. 100 m base payout, minus 10%, plus 20 m, plus 30% = `(100 + 20) * (1 - 0.1 + 0.3)) = 144`.
- **relative_first**: The sum of all relative modifiers is applied first, then the absolute modifiers.  
  E.g. 100 m base payout, minus 10%, plus 20 m, plus 30% = `(100 * (1 - 0.1 + 0.3)) + 20 = 140`.

The configuration can be changed without changing existing payouts. However, the new calculation is used when a 
payout is recalculated by changing the base payout or a modifier.

### Initial Setup

After you log in, go to _Admin → Groups_ and synchronise them. Then add divisions and configure permissions for them.

Permissions for each division are based on groups which are provided by a provider, see below.

Besides that, there is only one fixed role, the _global admin_. It is given to members of the groups from the 
environment variable `EVE_SRP_ROLE_GLOBAL_ADMIN`. In addition to administrative tasks, global admins can view all 
SRP requests and change their division.

## Provider

Providers implement the `ProviderInterface` interface and provide groups for the logged-in character and 
optionally additional alternative characters for which the user can submit requests.

Only one provider can be used at once. It is determined by the environment variable `EVE_SRP_PROVIDER`.

### ESI Provider

This is a simple provider that assigns groups based on EVE characters, their corporation and alliance.

There are 5 groups: `member`, `review`, `pay`, `admin` and `global-admin`. For each of them there are one or more 
environment variables `EVE_SRP_PROVIDER_ESI_*` that accept a list of character, corporation or alliance IDs.

### Neucore Provider

This provider needs a [Neucore](https://github.com/tkhamez/neucore) installation. It provides groups and
alternative characters from it.

To use it add an app to Neucore and give it the `app-chars` and `app-groups` roles and add any group to it that you 
want to use. Then set the `EVE_SRP_PROVIDER_NEUCORE_*` environment variables.

## Development Environment

Only tested on Linux.

```
docker compose up
```

The database connection string is: `mysql://root:eve_srp@eve_srp_db/eve_srp`. To run the unit tests add a
database named `eve_srp_test`.

The application is available at: http://localhost:8000.

Consoles for PHP and Node.js:
```
docker compose exec -u www-data eve_srp_php /bin/sh
docker compose exec -u node eve_srp_node /bin/sh
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
bin/doctrine orm:generate-proxies
vendor/bin/doctrine-migrations migrations:diff

sudo rm -R ./storage/compilation_cache/*
```

### Build Frontend

Install dependencies:
```
npm ci
```

Automatically rebuild during development:
```
npm run watch
```

Create production build:
```
npm run build
```

### Build Docker Container

```
docker build -f config/Dockerfile --no-cache -t eve-srp build
```

## Contact and Support

You can find me (Tian Khamez) on the [Neucore Discord](https://discord.gg/memUh56u8z). There's also a 
separate channel for EVE-SRP.

## Migration from paxswill/evesrp

https://github.com/paxswill/evesrp

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
