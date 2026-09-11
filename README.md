# AspireCRE V2 — local WordPress foundation

Private local prototype. It does not connect to or modify aspirecre.com.

## Requirement

Docker Desktop must be installed and running.

## First start

Open Terminal in this folder and run:

```bash
bash bin/setup.sh
```

No `chmod` command is required.

The script validates Docker, starts MariaDB + WordPress, installs WordPress with WP-CLI, activates the minimal AspireCRE theme and Aspire Core plugin, verifies them, and prints the local login details.

Default URLs:

- Site: http://localhost:8080
- Admin: http://localhost:8080/wp-admin/

## Normal start / stop

```bash
docker compose up -d
docker compose down
```

## Reset the local site completely

```bash
bash bin/reset.sh
```

This removes Docker database/WordPress-core volumes but keeps the project source under `wp-content`.
