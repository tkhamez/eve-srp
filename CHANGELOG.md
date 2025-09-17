# Changelog

## 1.4.0

2025-09-17

- PHP 8.4 compatibility.
- Compatibility with the new ESI API versioning system (compatibility date).
- Updated libraries.

## 1.3.2

2024-08-15

- Added arm64 Docker image.

## 1.3.1

2024-08-15

- Added robots.txt.
- Improved error logging.
- Fixed a fatal error.

## 1.3.0

2024-07-21

- Increased minimum required PHP version to 8.1.0 (from 8.0.0).
- Updated screenshot of kill report.
- Replaced choices.js with selectize.
- Fix: MySQL database column for a kill mail was sometimes too small.  
- Fix: Added missing trailing slash to ESI killmail URL.
- Updated Node.js for development environment.

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
