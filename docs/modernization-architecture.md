# TBDev Modernization Architecture

## Status

This document defines the initial target architecture for the `work/modernize-tbdev` branch. It is a design baseline only. It does not connect to the production database, does not expose a public domain and does not replace the existing Hestia/Apache/PHP services.

## Deployment decision

The modernized TBDev will initially be packaged as a dedicated Docker image and run through Docker Compose. HestiaCP/Nginx remains the public TLS reverse proxy. The application container binds only to a loopback port on the VPS, and the tracker announce/scrape endpoints are served by the same application boundary. No new public TCP port is required.

This approach isolates the modern PHP runtime from the PHP versions used by Nextcloud and Capital, gives the project an explicit image tag and Git SHA, and allows rollback by switching the Compose image tag. The historical `tbdevnet/tbdev-docker` project is not reused because it is based on PHP 5.3 and checks out source from Subversion during the build.

## Target components

| Component | Initial responsibility | Production note |
|---|---|---|
| `tbdev-web` | PHP web UI, authentication, admin, forums, messages, uploads, downloads and announce/scrape endpoints | Bind to localhost only behind Hestia |
| MariaDB | Existing production database or isolated lab database | Production migration must use a dedicated DB/user and an explicit schema plan |
| File storage | `.torrent` files, NFOs, avatars and generated exports | Kept outside the image and backed up separately |
| Background worker | Cleanup, peer expiry, statistics and scheduled maintenance | Introduced only after the routines are made safe and idempotent |
| Cache | Optional Redis/Memcached adapter | Not installed until the missing `include/cache_functions.php` dependency is understood |
| Hestia/Nginx | TLS, host routing, request limits and access logs | Existing service remains authoritative |

## Repository layout for the modernization

```text
Dockerfile
compose.lab.yml
docker/
  apache-vhost.conf
  php.ini
  entrypoint.sh
config/
  config.example.php
  config.local.php          # ignored, never committed
public/                     # eventual document root after front-controller migration
data/
  .gitkeep                  # runtime data is ignored
scripts/
tests/
docs/
```

The first compatibility phase may continue to serve the legacy root layout inside the container. The final phase should move the document root so that configuration, SQL, logs and maintenance scripts are not web-accessible.

## Build and release model

The build must copy the exact source tree from the branch being built. It must never download mutable source code from SVN or another repository during `docker build`. The base PHP image and all system packages will be pinned in the build configuration; release deployments will record the resolved image digest.

The intended tag sequence is:

```text
tbdev-web:0.1.0-compat
 tbdev-web:0.2.0-runtime
 tbdev-web:0.3.0-auth
 tbdev-web:0.4.0-tracker
 tbdev-web:1.0.0
```

Every image should contain OCI labels for the Git commit, build time and application version. A release is promoted only after static checks, unit/smoke tests and a lab deployment pass. The Hestia Compose file must reference an explicit release tag or digest, never `latest`.

## Configuration and secrets

No database password, mail credential, application secret or production URL belongs in Git. The container receives configuration from a root-owned environment file outside the repository or from a secret mechanism. The application must validate required configuration at startup and fail closed rather than silently using sample credentials.

The database connection will be migrated to PDO with explicit charset and exception mode. Production credentials will use a MariaDB account limited to the TBDev schema and required operations. Database root access is forbidden for the application.

## Data and backup boundaries

The image contains code only. The following paths are persistent data and must be mounted separately:

- database data is owned by the existing database backup process;
- `.torrent` files and NFOs are stored under a dedicated data directory;
- user-uploaded avatars and generated exports are stored separately from code;
- application logs are sent to the container logging driver and rotated;
- migration records are stored in a dedicated table or migration directory.

Before any production cutover, the database and torrent storage must have a verified backup and a documented restore test. The migration must be reversible at the schema level or run against a copy until the new runtime has passed the compatibility checklist.

## Security boundaries

The installer directory will not be reachable in production. The web container will run without privileged mode, will not mount the Docker socket, will not expose the database port and will have no write access to the application source tree. Write access is limited to the runtime data directories.

The first security pass prioritizes session termination after access checks, output encoding, CSRF protection for state-changing forms, prepared SQL statements, upload validation, SSRF-safe avatar handling, host allowlisting, secure cookies and rate limits for login, signup, recovery and announce. Historical vulnerabilities from `reputation_settings.php`, `takeprofedit.php`, `email-gateway.php`, `bitbucket-upload.php`, `login.php`, `redir.php` and `forums.php` become regression-test cases.

## Rollout and rollback

The lab stack uses a separate database and volume names. The production stack will use a new domain or a temporary Hestia host while the current application remains untouched. Cutover requires:

1. database and file backup verification;
2. build of an immutable release tag;
3. migration dry-run against a copy;
4. web and announce/scrape smoke tests;
5. monitoring of HTTP errors, database errors, announce latency and peer counts;
6. an explicit rollback command that restores the previous Compose tag and proxy target.

No production cutover is allowed merely because the image builds. A build-successful container is not proof that the old schema, account hashes, tracker accounting or upload path are compatible.

## Sources

[1](https://github.com/kakadea/tbdev) — user fork and `stg` baseline.
[2](https://github.com/Hyp3rionM4x/TBDev) — historical fork with 2019 fixes.
[3](https://www.php.net/manual/en/migration70.incompatible.php) — PHP migration guide confirming removal of `ext/mysql`.
[4](https://docs.docker.com/build/building/best-practices/) — Docker build and image pinning practices.
