# Changelog

## next

- Increased minimum required PHP version to 8.0.2 (from 8.0.0).
- Fix: MySQL database column for a kill mail was sometimes too small.  
  (manually fix until there's a release: `ALTER TABLE requests CHANGE killMail killMail MEDIUMTEXT DEFAULT NULL`)
- Fix: Added missing trailing slash to ESI killmail URL.

## 1.2.0

2023-07-01

- It is now configurable how modifiers are applied, see 
  [README.md - Further Configuration](README.md#further-configuration). The default configuration uses the old method.
- Updated libraries.

## 1.1.0

2023-05-27

- New: Added `EVE_SRP_SESSION_SECURE` environment variable to configure the secure flag for the session cookie.
- Change: Moved log directory to `storage/logs`.
- Fix: It is now possible to use decimal values for the base payout and absolute modifiers.
- Fix: Some ESI URLs did not work.
- Other small improvements.

## 1.0.0

2023-05-07

Initial release with the following features:

- Submit requests.
- Add comments to requests.
- Base payout with modifiers.
- Lists with open, in progress and approved requests.
- Search function for all requests.
- Admin UI to manage divisions and set their permissions.
- Various options to customize the installation (texts, logo).
- Included providers: ESI and [Neucore](https://github.com/tkhamez/neucore).
- Optional [zKillboard](https://github.com/zKillboard/zKillboard) integration.
- Support for MySQL, MariaDB and PostgreSQL.
