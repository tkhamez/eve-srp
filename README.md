# EVE-SRP

## Install/Update

To run the application you need PHP >=7.3 <8.0, and a database supported by 
[Doctrine ORM](https://www.doctrine-project.org/projects/doctrine-orm/en/latest/index.html) 
(tested with MariaDB 10.4).

- Create an EVE application at https://developers.eveonline.com
- Copy `.env.dist` to `.env` and adjust values or set the corresponding environment variables in another way.
  At the very least set EVE_SRP_SSO_CLIENT_ID and EVE_SRP_SSO_CLIENT_SECRET, the rest works as is when using Docker.
- Install dependencies with `composer install`.
- Clear the template cache: `rm -R cache/compilation_cache`
- sync db schema:
  - **Backup the database first!**
  - `vendor/bin/doctrine orm:schema-tool:update --force`

### Permissions

Permissions are based on groups which are provided by the group provider which is configured by the
`EVE_SRP_GROUP_PROVIDER` environment variable.

Depending on which provider is used, the corresponding environment variables must be adapted, currently 
`EVE_SRP_NEUCORE_*` or `EVE_SRP_ESI_*` for the included providers.

### Error logging

Log messages are send to the file specified in the `error_log` configuration.

## Development

### Run Backend

You can use the PHP dev server like this:
```
php -S localhost:8000 -t web
```

### Build Frontend

Tested with Node.js 14 LTS.

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

### Docker

```
docker-compose build
docker-compose up
```

The database connection string is: `mysql://eve_srp:eve_srp@eve_srp_db/eve_srp`.  
The application is available at: http://localhost:8000.

Create/enter shells for PHP and Node.js:
```
docker-compose exec eve_srp_php /bin/sh
docker-compose run eve_srp_node /bin/sh
```

## Migration from paxswill/evesrp

MySQL/MariaDB databases: evesrp => eve_srp

Replace values for evesrp.entity.type_ (BraveOauthGroup) and evesrp.entity.authmethod (EVESSONeucore) if needed.

```
INSERT INTO eve_srp.users (id) SELECT id FROM evesrp.user;
INSERT INTO eve_srp.characters (id, user_id, name, main) SELECT id, user_id, name, 0 FROM evesrp.pilot;
INSERT INTO eve_srp.divisions (id, name) SELECT id, name FROM evesrp.division;
INSERT INTO eve_srp.requests 
    (id, submitter_id, division_id, created, pilot_id, corporation, alliance, ship, kill_time, 
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
