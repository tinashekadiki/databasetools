# Databasement Local Runbook

Author: Tinashe K

This setup runs a DOT-branded Databasement deployment locally for database backup management. It stores application state in the bundled MySQL container and syncs backup files to `databasement-backups` through the container path `/var/backups`.

## What You Get

- Databasement UI on `http://localhost:2226`
- MySQL system database on the same Docker network
- DOT footer branding with a `/licenses` page preserving the upstream Databasement attribution
- DOT Database Tools product branding across the main shell, auth screens, and email notifications
- A dedicated Database Browser menu for Adminer access when the feature and role ability are enabled
- An embedded database-browser workspace with compact connection navigation and Adminer styling
- Application database persisted locally in `mysql-data`
- Runtime data persisted locally in `databasement-data`
- Backup output synced locally in `databasement-backups`
- Queue worker enabled for backup jobs
- A Docker Compose init step that creates required folders/files before the app starts
- SMTP email notification settings configurable from the UI

## Requirements

- Docker Desktop, Docker Engine, or another Docker runtime with Docker Compose.
- Port `2226` must be free, unless you override it with `DATABASEMENT_PORT`.
- Port `3307` must be free for the bundled MySQL system database, unless you override it with `DATABASEMENT_MYSQL_PORT`.
- Enough disk space for Docker images and database backup files.

## First Run

From this repository:

```bash
./deploy.sh
```

Open:

```text
http://localhost:2226
```

The first run may take a few minutes because Docker pulls the images and Databasement runs migrations.

The script creates `.env` from `.env.example` when needed, prepares local storage folders, builds the DOT Database Tools image, starts MySQL, waits for health checks, and verifies the web app before printing the connection details.

You can still run Docker Compose directly on a fresh setup, but `./deploy.sh` is preferred because it also verifies and repairs the MySQL database/user on existing volumes:

```bash
docker compose up -d --build
```

## Use A Different Port

```bash
DATABASEMENT_PORT=8080 docker compose up -d --build
```

Then open:

```text
http://localhost:8080
```

## Verify

```bash
docker compose ps
docker compose logs --tail 120 databasement
curl -I http://localhost:2226/login
```

Expected:

- `databasement` is running.
- `databasement-mysql` is running and healthy.
- Logs show migrations complete and FrankenPHP serving on `:2226`.
- `curl` returns `HTTP/1.1 200 OK`.

Verify the bundled MySQL system database:

```bash
docker compose exec databasement-mysql mysql \
  -udatabasement_user \
  -pdatabasement_password \
  -e "SHOW DATABASES;"
```

Expected output includes:

```text
databasetools
```

## Bundled MySQL System Database

Docker Compose starts two long-running containers:

```text
databasement         DOT Database Tools web app
databasement-mysql   MySQL system database
```

DOT Database Tools uses `databasement-mysql` for its own Laravel application database. SQLite is not used for application state.

The app container connects internally with:

```text
Connection: mysql
Host: databasement-mysql
Port: 3306
Database: databasetools
Username: databasement_user
Password: databasement_password
```

If you want DOT Database Tools to browse or back up its own system database, add the same MySQL server in the UI under `Database Servers` -> `Add Server`:

```text
Name: DOT System Database
Type: MySQL / MariaDB
Host: databasement-mysql
Port: 3306
Database: databasetools
Username: databasement_user
Password: databasement_password
```

Use this host-side connection only from tools running on your machine, not from inside the Databasement container:

```text
Host: 127.0.0.1
Port: 3307
```

Override the defaults when needed:

```bash
DATABASEMENT_MYSQL_PORT=3308 \
DATABASEMENT_MYSQL_DATABASE=databasetools \
DATABASEMENT_MYSQL_USER=client_user \
DATABASEMENT_MYSQL_PASSWORD='change-me' \
DATABASEMENT_MYSQL_ROOT_PASSWORD='change-root-me' \
./deploy.sh
```

Verify backup sync:

```bash
docker compose exec databasement sh -lc 'mkdir -p /var/backups/probe && echo ok > /var/backups/probe/sync.txt'
cat databasement-backups/probe/sync.txt
rm -rf databasement-backups/probe
```

Expected output:

```text
ok
```

## Day-To-Day Commands

Start:

```bash
./deploy.sh
```

Stop containers and keep data:

```bash
./deploy.sh stop
```

Reset and clear everything:

```bash
./deploy.sh down
```

`./deploy.sh down` removes the containers and deletes local persisted data in `mysql-data/`, `databasement-backups/`, and `databasement-data/`. The script displays a warning and requires you to type `CLEAR DATABASETOOLS` before it continues.

Restart:

```bash
./deploy.sh restart
```

View logs:

```bash
./deploy.sh logs
```

Update image:

```bash
docker compose build --pull databasement
docker compose up -d
```

Remove containers while keeping local data, for maintenance only:

```bash
docker compose down
```

## Data Locations

Do not delete these unless you intentionally want to reset local state:

```text
databasement-backups/
mysql-data/
databasement-data/
```

`mysql-data/` contains the DOT Database Tools application database. `databasement-data/` is retained for container runtime data, not SQLite application state.

In the Databasement UI, configure local storage volumes to use:

```text
/var/backups
```

Files written there appear locally in:

```text
databasement-backups/
```

## Email Notifications

Use this when you want backup and restore alerts sent through your own SMTP provider.

1. Open `http://localhost:2226` and sign in.
2. Go to `Configuration` -> `Notification`.
3. In `SMTP Delivery`, enable SMTP delivery and enter:
   - SMTP host, port, and security mode
   - username and password, if your provider requires authentication
   - from address and from name
   - a test recipient address
4. Click `Save SMTP Settings`.
5. Click `Send Test Email` and confirm the message arrives.
6. In `Notification Channels`, add an `Email` channel with the recipient email addresses.
7. Use the paper-airplane test button on the Email channel, then assign the channel to the database servers that should send backup or restore alerts.

SMTP settings are stored in the MySQL application database under `mysql-data/`. Password values are stored through Databasement's encrypted app configuration storage. Leave the password blank when saving if you want to keep the existing saved password.

## DOT Branding

This repository builds a small local image named `dot/databasement:local` on top of `davidcrty/databasement:1`.

The custom files are:

```text
Dockerfile
docker/customizations/public/dot-enterprise.css
docker/customizations/public/dot-adminer.css
docker/customizations/resources/views/components/app-brand.blade.php
docker/customizations/resources/views/components/logo-icon.blade.php
docker/customizations/resources/views/layouts/app.blade.php
docker/customizations/resources/views/layouts/auth.blade.php
docker/customizations/resources/views/pages/licenses.blade.php
docker/customizations/resources/views/mail/notification.blade.php
docker/customizations/resources/views/livewire/auth/login.blade.php
docker/customizations/resources/views/livewire/auth/register.blade.php
docker/customizations/resources/views/livewire/database-browser/index.blade.php
docker/customizations/resources/views/livewire/configuration/application.blade.php
docker/customizations/resources/views/livewire/configuration/notification.blade.php
docker/customizations/app/Livewire/Configuration/Notification.php
docker/customizations/app/Livewire/DatabaseBrowser/Index.php
docker/customizations/app/Services/AdminerService.php
docker/customizations/app/Services/adminer_object.php
docker/customizations/app/Services/NotificationService.php
docker/customizations/app/Services/SmtpSettingsService.php
docker/customizations/routes/web.php
```

The global app footer says:

```text
Developed by DOT (dots.co.zw)
```

The `/licenses` page preserves the upstream attribution:

```text
Made with ❤ by David-Crty
github.com/David-Crty/databasement
```

## Raw Docker Fallback

Use this only if Docker Compose is unavailable.

```bash
mkdir -p databasement-data databasement-backups
chmod 777 databasement-backups

docker rm -f databasement 2>/dev/null || true
docker rm -f databasement-mysql 2>/dev/null || true
docker network create databasement-local 2>/dev/null || true
docker build -t dot/databasement:local .

docker run -d \
  --name databasement-mysql \
  --network databasement-local \
  -p 3307:3306 \
  -e MYSQL_ROOT_PASSWORD=root-password \
  -e MYSQL_DATABASE=databasetools \
  -e MYSQL_USER=databasement_user \
  -e MYSQL_PASSWORD=databasement_password \
  -v "$PWD/mysql-data:/var/lib/mysql" \
  mysql:8.4

docker run -d \
  --name databasement \
  --network databasement-local \
  -p 2226:2226 \
  -e DB_CONNECTION=mysql \
  -e DB_HOST=databasement-mysql \
  -e DB_PORT=3306 \
  -e DB_DATABASE=databasetools \
  -e DB_USERNAME=databasement_user \
  -e DB_PASSWORD=databasement_password \
  -e ENABLE_QUEUE_WORKER=true \
  -v "$PWD/databasement-data:/data" \
  -v "$PWD/databasement-backups:/var/backups" \
  dot/databasement:local
```

## Troubleshooting

If port `2226` is already in use:

```bash
DATABASEMENT_PORT=8080 docker compose up -d
```

If backups fail with `Unable to create a directory at /var/backups`:

```bash
docker compose run --rm databasement-init
docker compose restart databasement
```

If the UI shows `ERR_EMPTY_RESPONSE` or `ERR_CONNECTION_RESET`, check for FrankenPHP crashes:

```bash
docker compose logs --tail 200 databasement | grep -E 'SIGSEGV|exited: frankenphp|ERROR|WARNING' || true
```

On Apple Silicon, do not force `--platform linux/amd64`. Let Docker pull the native image automatically.

If Docker fails with a layer registration error like this:

```text
failed to register layer: rename /var/lib/docker/image/overlay2/layerdb/tmp/write-set-...: file exists
```

restart Docker Desktop and run:

```bash
docker compose build --pull databasement
docker compose up -d
```

If the same layer error repeats, Docker Desktop's internal image metadata is stale and may need repair or a Docker Desktop reset. Back up anything important before resetting Docker Desktop because resets can remove images, containers, and Docker-managed volumes.
