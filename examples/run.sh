#!/usr/bin/env bash
#
# Запуск примеров через Docker Compose — без установленного локально PHP/Composer.
# Используется сервис `examples` из docker-compose.yml: образ, volume и переменные
# окружения (MAX_API_TOKEN, MAX_WEBHOOK_SECRET) описаны в одном месте и берутся из .env.
#
# Использование:
#   examples/run.sh <example.php>
#
# Примеры:
#   examples/run.sh echo-bot-long-polling.php
#   examples/run.sh echo-bot-webhook.php     # слушает http://localhost:8080
#
# Порт вебхука можно переопределить:
#   MAX_EXAMPLES_PORT=9090 examples/run.sh echo-bot-webhook.php
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SCRIPT="${1:-}"
PORT="${MAX_EXAMPLES_PORT:-8080}"

if [[ -z "${SCRIPT}" || ! -f "${ROOT}/examples/${SCRIPT}" ]]; then
    echo "Usage: examples/run.sh <example.php>" >&2
    echo "Examples:" >&2
    for f in "${ROOT}"/examples/*.php; do
        name="$(basename "${f}")"
        case "${name}" in
            bootstrap.php | echo-handler.php) continue ;;
        esac
        echo "  ${name}" >&2
    done
    exit 1
fi

cd "${ROOT}"

if [[ ! -f "${ROOT}/vendor/autoload.php" ]]; then
    echo "Installing dependencies..." >&2
    docker compose run --rm -e COMPOSER_ROOT_VERSION=dev-main examples \
        composer install --no-interaction --prefer-dist
fi

# network_mode host задан в docker-compose.yml (TLS до platform-api2.max.ru
# из Docker-сети блокируется).
if [[ "${SCRIPT}" == "echo-bot-webhook.php" ]]; then
    exec docker compose run --rm examples \
        php -S 0.0.0.0:"${PORT}" "examples/${SCRIPT}"
fi

exec docker compose run --rm examples php "examples/${SCRIPT}"
