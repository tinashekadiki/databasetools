#!/usr/bin/env bash
# Author: Tinashe K

set -euo pipefail

COMMAND="${1:-up}"
CONFIRMATION_TOKEN="CLEAR DATABASETOOLS"
PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ENV_FILE="${PROJECT_ROOT}/.env"
ENV_EXAMPLE_FILE="${PROJECT_ROOT}/.env.example"

cd "${PROJECT_ROOT}"

create_env_file() {
    if [[ ! -f "${ENV_FILE}" ]]; then
        cp "${ENV_EXAMPLE_FILE}" "${ENV_FILE}"
        echo "Created .env from .env.example"
    fi
}

load_env_file() {
    create_env_file
    set -a
    # shellcheck disable=SC1090
    source "${ENV_FILE}"
    set +a
}

require_docker() {
    if ! command -v docker >/dev/null 2>&1; then
        echo "Docker is required but was not found on PATH." >&2
        exit 1
    fi

    if ! docker compose version >/dev/null 2>&1; then
        echo "Docker Compose is required but is not available through 'docker compose'." >&2
        exit 1
    fi

    if ! docker info >/dev/null 2>&1; then
        echo "Docker is installed but the daemon is not running." >&2
        exit 1
    fi
}

prepare_local_storage() {
    mkdir -p databasement-data databasement-backups mysql-data
    chmod 777 databasement-backups
}

wait_for_mysql() {
    local container_name="databasement-mysql"
    local max_attempts=60
    local attempt=1

    echo "Waiting for MySQL to become healthy..."
    while [[ "${attempt}" -le "${max_attempts}" ]]; do
        local health_status
        health_status="$(docker inspect --format='{{if .State.Health}}{{.State.Health.Status}}{{else}}{{.State.Status}}{{end}}' "${container_name}" 2>/dev/null || true)"

        if [[ "${health_status}" == "healthy" ]]; then
            echo "MySQL is healthy."
            return 0
        fi

        sleep 2
        attempt=$((attempt + 1))
    done

    echo "MySQL did not become healthy in time. Recent logs:" >&2
    docker compose logs --tail 120 databasement-mysql >&2
    exit 1
}

wait_for_web() {
    local app_url="http://localhost:${DATABASEMENT_PORT:-2226}/login"
    local max_attempts=60
    local attempt=1

    echo "Waiting for DOT Database Tools at ${app_url}..."
    while [[ "${attempt}" -le "${max_attempts}" ]]; do
        if curl -fsSI "${app_url}" >/dev/null 2>&1; then
            echo "DOT Database Tools is responding."
            return 0
        fi

        sleep 2
        attempt=$((attempt + 1))
    done

    echo "DOT Database Tools did not respond in time. Recent logs:" >&2
    docker compose logs --tail 160 databasement >&2
    exit 1
}

verify_mysql_connection() {
    docker compose exec -T \
        -e MYSQL_PWD="${DATABASEMENT_MYSQL_PASSWORD:-databasement_password}" \
        databasement-mysql \
        mysql -u"${DATABASEMENT_MYSQL_USER:-databasement_user}" \
        -D "${DATABASEMENT_MYSQL_DATABASE:-databasetools}" \
        -e "SELECT DATABASE();" >/dev/null
}

quote_mysql_identifier() {
    printf '`%s`' "${1//\`/\`\`}"
}

quote_mysql_literal() {
    printf "'%s'" "${1//\'/\'\'}"
}

ensure_application_database() {
    local database_name="${DATABASEMENT_MYSQL_DATABASE:-databasetools}"
    local database_user="${DATABASEMENT_MYSQL_USER:-databasement_user}"
    local database_password="${DATABASEMENT_MYSQL_PASSWORD:-databasement_password}"
    local quoted_database_name
    local quoted_database_user
    local quoted_database_password

    quoted_database_name="$(quote_mysql_identifier "${database_name}")"
    quoted_database_user="$(quote_mysql_literal "${database_user}")"
    quoted_database_password="$(quote_mysql_literal "${database_password}")"

    echo "Ensuring MySQL application database exists..."
    docker compose exec -T \
        -e MYSQL_PWD="${DATABASEMENT_MYSQL_ROOT_PASSWORD:-root-password}" \
        databasement-mysql \
        mysql -uroot <<SQL
CREATE DATABASE IF NOT EXISTS ${quoted_database_name}
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS ${quoted_database_user}@'%' IDENTIFIED BY ${quoted_database_password};
ALTER USER ${quoted_database_user}@'%' IDENTIFIED BY ${quoted_database_password};
GRANT ALL PRIVILEGES ON ${quoted_database_name}.* TO ${quoted_database_user}@'%';
FLUSH PRIVILEGES;
SQL
}

print_access_details() {
    cat <<DETAILS

DOT Database Tools is running.

Open:
  http://localhost:${DATABASEMENT_PORT:-2226}

System database:
  Connection: mysql
  Host: databasement-mysql
  Port: 3306
  Database: ${DATABASEMENT_MYSQL_DATABASE:-databasetools}
  Username: ${DATABASEMENT_MYSQL_USER:-databasement_user}

Host machine MySQL access:
  Host: 127.0.0.1
  Port: ${DATABASEMENT_MYSQL_PORT:-3307}

Useful commands:
  ./deploy.sh status
  ./deploy.sh logs
  ./deploy.sh down
DETAILS
}

start_stack() {
    load_env_file
    require_docker
    prepare_local_storage
    docker compose config >/dev/null
    docker compose up -d databasement-mysql
    wait_for_mysql
    ensure_application_database
    docker compose up -d --build databasement
    wait_for_web
    verify_mysql_connection
    print_access_details
}

show_status() {
    load_env_file
    require_docker
    docker compose ps
}

show_logs() {
    load_env_file
    require_docker
    docker compose logs -f --tail 160 databasement databasement-mysql
}

confirm_destructive_reset() {
    cat <<WARNING

WARNING: this will permanently clear the local DOT Database Tools stack.

It will remove:
  - databasement and databasement-mysql containers
  - local MySQL application database files in mysql-data/
  - local backup files in databasement-backups/
  - local runtime files in databasement-data/

This cannot be undone from Docker after it runs.
WARNING

    printf "\nType \"%s\" to continue: " "${CONFIRMATION_TOKEN}"
    read -r confirmation

    if [[ "${confirmation}" != "${CONFIRMATION_TOKEN}" ]]; then
        echo "Reset cancelled."
        exit 1
    fi
}

stop_stack() {
    load_env_file
    require_docker
    docker compose stop databasement databasement-mysql
    echo "DOT Database Tools containers stopped. Persisted data was kept."
}

reset_stack() {
    load_env_file
    require_docker
    confirm_destructive_reset
    docker compose down --remove-orphans
    rm -rf databasement-data databasement-backups mysql-data
    echo "Local containers and persisted data have been cleared."
}

restart_stack() {
    stop_stack
    start_stack
}

case "${COMMAND}" in
    up|start|deploy)
        start_stack
        ;;
    restart)
        restart_stack
        ;;
    status|ps)
        show_status
        ;;
    logs)
        show_logs
        ;;
    stop)
        stop_stack
        ;;
    down)
        reset_stack
        ;;
    *)
        echo "Usage: ./deploy.sh [up|restart|status|logs|down|stop]" >&2
        exit 1
        ;;
esac
