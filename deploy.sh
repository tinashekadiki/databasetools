#!/usr/bin/env bash
# Author: Tinashe K

set -euo pipefail

COMMAND="${1:-up}"
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
    touch databasement-data/database.sqlite
    chmod 666 databasement-data/database.sqlite
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
        -e "SHOW DATABASES LIKE '${DATABASEMENT_MYSQL_DATABASE:-backup_demo}';" >/dev/null
}

print_access_details() {
    cat <<DETAILS

DOT Database Tools is running.

Open:
  http://localhost:${DATABASEMENT_PORT:-2226}

Add the bundled MySQL server in the app with:
  Name: Local MySQL
  Type: MySQL / MariaDB
  Host: databasement-mysql
  Port: 3306
  Database: ${DATABASEMENT_MYSQL_DATABASE:-backup_demo}
  Username: ${DATABASEMENT_MYSQL_USER:-databasement_user}
  Password: ${DATABASEMENT_MYSQL_PASSWORD:-databasement_password}

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
    docker compose up -d --build
    wait_for_mysql
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

stop_stack() {
    load_env_file
    require_docker
    docker compose down
}

case "${COMMAND}" in
    up|start|deploy)
        start_stack
        ;;
    restart)
        stop_stack
        start_stack
        ;;
    status|ps)
        show_status
        ;;
    logs)
        show_logs
        ;;
    down|stop)
        stop_stack
        ;;
    *)
        echo "Usage: ./deploy.sh [up|restart|status|logs|down]" >&2
        exit 1
        ;;
esac
