# Brave Collective - SRP

## Install/Update

- Copy `.env.dist` to `.env` and adjust values or set the corresponding environment variables in another way.
- Install dependencies with `composer install`.
- Clear the template cache: `rm -R cache/compilation_cache`
- sync db schema:
  - **Backup the database first!**
  - `vendor/bin/doctrine orm:schema-tool:update --force`

### Error logging

Log messages are send to the file specified in the `error_log` configuration.

## Migration from paxswill/evesrp

Databases: evesrp => eve_srp

Replace values for evesrp.entity.type_ (BraveOauthGroup) and evesrp.entity.authmethod (EVESSONeucore) if needed.

```
INSERT INTO eve_srp.users (id) SELECT id FROM evesrp.user;
INSERT INTO eve_srp.characters (id, user_id, name, main) SELECT id, user_id, name, 0 FROM evesrp.pilot;
INSERT INTO eve_srp.divisions (id, name) SELECT id, name FROM evesrp.division;
INSERT INTO eve_srp.requests 
    (id, submitter_id, division_id, created, pilot_id, corporation, alliance, ship, kill_time, solar_system, killboard_url, details, status, base_payout, payout)
    SELECT id, submitter_id, division_id, timestamp, pilot_id, corporation, alliance, ship_type, kill_timestamp, `system`, killmail_url, details, status, base_payout, payout
    FROM evesrp.request;
INSERT INTO eve_srp.actions (id, user_id, request_id, created, category, note) 
    SELECT id, user_id, request_id, timestamp, type_, note FROM evesrp.action;
INSERT INTO eve_srp.external_groups (id, name) 
    SELECT id, name FROM evesrp.entity WHERE type_ = 'BraveOauthGroup' AND authmethod = 'EVESSONeucore';
INSERT INTO eve_srp.permissions (id, division_id, external_group_id, role) 
    SELECT evesrp.permission.id, division_id, entity_id, permission 
    FROM evesrp.permission
    INNER JOIN evesrp.entity ON evesrp.permission.entity_id = evesrp.entity.id
    WHERE type_ = 'BraveOauthGroup' AND authmethod = 'EVESSONeucore' AND permission IN ('submit', 'review', 'pay', 'admin');
INSERT INTO eve_srp.user_external_group (user_id, external_group_id)
    SELECT users_groups.user_id, group_id FROM evesrp.users_groups
```

permission "audit" is not copied.

## Development

### Rebuild Frontend

```
npm i
npm run build
```
