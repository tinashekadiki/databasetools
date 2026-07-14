# Databasement Local Runbook

Author: Tinashe K

This setup runs a DOT-branded Databasement deployment locally for database backup management. It stores application state in SQLite under `databasement-data` and syncs backup files to `databasement-backups` through the container path `/var/backups`.

## What You Get

- Databasement UI on `http://localhost:2226`
- DOT footer branding with a `/licenses` page preserving the upstream Databasement attribution
- SQLite app database persisted locally in `databasement-data/database.sqlite`
- Backup output synced locally in `databasement-backups`
- Queue worker enabled for backup jobs
- A Docker Compose init step that creates required folders/files before the app starts
- SMTP email notification settings configurable from the UI

## Requirements

- Docker Desktop, Docker Engine, or another Docker runtime with Docker Compose.
- Port `2226` must be free, unless you override it with `DATABASEMENT_PORT`.
- Enough disk space for Docker images and database backup files.

## First Run

From this repository:

```bash
docker compose up -d --build
```

Open:

```text
http://localhost:2226
```

The first run may take a few minutes because Docker pulls the images and Databasement runs migrations.

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
- Logs show migrations complete and FrankenPHP serving on `:2226`.
- `curl` returns `HTTP/1.1 200 OK`.

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
docker compose up -d
```

Stop:

```bash
docker compose stop
```

Restart:

```bash
docker compose restart databasement
```

View logs:

```bash
docker compose logs -f databasement
```

Update image:

```bash
docker compose build --pull databasement
docker compose up -d
```

Remove containers while keeping local data:

```bash
docker compose down
```

## Data Locations

Do not delete these unless you intentionally want to reset local state:

```text
databasement-data/database.sqlite
databasement-backups/
```

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

SMTP settings are stored in the SQLite application database under `databasement-data/database.sqlite`. Password values are stored through Databasement's encrypted app configuration storage. Leave the password blank when saving if you want to keep the existing saved password.

## DOT Branding

This repository builds a small local image named `dot/databasement:local` on top of `davidcrty/databasement:1`.

The custom files are:

```text
Dockerfile
docker/customizations/resources/views/layouts/app.blade.php
docker/customizations/resources/views/pages/licenses.blade.php
docker/customizations/resources/views/livewire/configuration/notification.blade.php
docker/customizations/app/Livewire/Configuration/Notification.php
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
touch databasement-data/database.sqlite
chmod 666 databasement-data/database.sqlite
chmod 777 databasement-backups

docker rm -f databasement 2>/dev/null || true
docker build -t dot/databasement:local .

docker run -d \
  --name databasement \
  -p 2226:2226 \
  -e DB_CONNECTION=sqlite \
  -e DB_DATABASE=/data/database.sqlite \
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

If startup logs say the SQLite database file does not exist:

```bash
docker compose run --rm databasement-init
docker compose restart databasement
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
