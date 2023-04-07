# EVE-SRP

A Ship-Replacement-Programm application for [EVE Online](https://www.eveonline.com).

## Install

To run the application you need a web server with support for PHP >=8.0 and URL rewriting, and a database supported by 
[Doctrine ORM](https://www.doctrine-project.org/projects/doctrine-orm/en/latest/index.html) 
(tested with MariaDB 10.6).

- Create an EVE application at https://developers.eveonline.com, no scopes required.
- Clone the repository, build the frontend and backend (see below) - there will be pre-built releases later.
- Copy `config/.env.dist` to `config/.env` and adjust values or set the corresponding environment variables.
  At the very least set EVE_SRP_SSO_CLIENT_ID and EVE_SRP_SSO_CLIENT_SECRET, the rest works as is when using the
  Docker development environment.
- Install dependencies and generate Doctrine proxy classes with `composer install`.
- Clear the template cache: `rm -R storage/compilation_cache`
- Make sure that the `storage` directory is writable by the webserver.
- Set the document root to the `web` directory and configure URL rewriting to `index.php` (see
  [Slim framework - Web Servers](https://www.slimframework.com/docs/v4/start/web-servers.html) for details)
- sync db schema:
  - **Backup the database first!**
  - `bin/doctrine orm:schema-tool:update --dump-sql`  
    Review SQLs and if ok execute:  
    `bin/doctrine orm:schema-tool:update --force`

### Permissions

Permissions are based on groups which are provided by a provider which is configured by the
`EVE_SRP_PROVIDER` environment variable.

Depending on which provider is used, the corresponding environment variables must be adapted, currently 
`EVE_SRP_NEUCORE_*` or `EVE_SRP_ESI_*` for the included providers.

There is only one fixed role, the global admin. It is mapped to groups with the environment variable
`EVE_SRP_ROLE_GLOBAL_ADMIN`. If you use the default configuration with the ESI provider add your own EVE character 
ID to the `EVE_SRP_ESI_GLOBAL_ADMIN_CHARACTERS` environment variable to become a global admin.

Global admins can create divisions and configure all other permissions for each of them separately.

Only global admins can see SRP requests without a division, e.g. when a division was deleted.

### Error logging

Log messages are sent to the file specified in the PHP `error_log` configuration.

## Development Environment (Docker)

```
docker-compose build
docker-compose up
```

The database connection string is: `mysql://eve_srp:eve_srp@eve_srp_db/eve_srp`.  
The application is available at: http://localhost:8000.

Consoles for PHP and Node.js:
```
docker-compose exec -u www-data eve_srp_php /bin/sh
docker-compose exec -u node eve_srp_node /bin/sh
```

### Install Backend

```
composer install
```

The logs are in `storage/error.log`.

### Build Frontend

Install dependencies:
```
npm i
```

Production build:
```
npm run build
```

During development:
```
npm run watch
```

## Migration from paxswill/evesrp

MySQL/MariaDB databases: evesrp => eve_srp

Replace values for evesrp.entity.type_ (BraveOauthGroup) and evesrp.entity.authmethod (EVESSONeucore) if needed.

```
INSERT INTO eve_srp.users (id) SELECT id FROM evesrp.user;
INSERT INTO eve_srp.characters (id, user_id, name, main) SELECT id, user_id, name, 0 FROM evesrp.pilot;
INSERT INTO eve_srp.divisions (id, name) SELECT id, name FROM evesrp.division;
INSERT INTO eve_srp.requests 
    (id, user_id, division_id, created, character_id, corporation, alliance, ship, kill_time, 
        solar_system, killboard_url, details, status, base_payout, payout)
    SELECT id, submitter_id, division_id, timestamp, pilot_id, corporation, alliance, ship_type, kill_timestamp, 
        `system`, killmail_url, details, status, base_payout, payout
    FROM evesrp.request;
INSERT INTO eve_srp.actions (id, user_id, request_id, created, category, note) 
    SELECT id, user_id, request_id, timestamp, type_, note FROM evesrp.action;
INSERT INTO eve_srp.external_groups (id, name) 
    SELECT id, name FROM evesrp.entity WHERE type_ = 'BraveOauthGroup' AND authmethod = 'EVESSONeucore';
INSERT INTO eve_srp.permissions (id, division_id, external_group_id, role) 
    SELECT evesrp.permission.id, division_id, entity_id, permission 
    FROM evesrp.permission
    INNER JOIN evesrp.entity ON evesrp.permission.entity_id = evesrp.entity.id
    WHERE type_ = 'BraveOauthGroup' AND authmethod = 'EVESSONeucore' 
      AND permission IN ('submit', 'review', 'pay', 'admin');
INSERT INTO eve_srp.user_external_group (user_id, external_group_id)
    SELECT users_groups.user_id, group_id FROM evesrp.users_groups
```

Note: the permission "audit" is not copied.
