# Brave Collective - SRP

## Install

- Copy `.env.dist` to `.env` and adjust values or set the corresponding environment variables in another way.
- Install dependencies with `composer install`.
- Clear the template cache: `rm -R cache/compilation_cache`
- sync db schema:
  - **backup the database table**
  - `vendor/bin/doctrine orm:schema-tool:update --force`

## Migration from paxswill/evesrp

Tables: evesrp => eve_srp

```
INSERT INTO eve_srp.users (id, name) SELECT id, '' FROM evesrp.user;
INSERT INTO eve_srp.characters (id, user_id, name, main) SELECT id, user_id, name, 0 FROM evesrp.pilot;
INSERT INTO eve_srp.divisions (id, name) SELECT id, name FROM evesrp.division;
INSERT INTO eve_srp.requests 
    (id, submitter_id, division_id, created, pilot_id, corporation, alliance, ship, kill_time, solar_system, kill_mail, details, status, base_payout, payout)
    SELECT id, submitter_id, division_id, timestamp, pilot_id, corporation, alliance, ship_type, kill_timestamp, `system`, killmail_url, details, status, base_payout, payout
    FROM evesrp.request;
INSERT INTO eve_srp.actions (id, user_id, request_id, created, category, note) 
    SELECT id, user_id, request_id, timestamp, type_, note FROM evesrp.action;
```
