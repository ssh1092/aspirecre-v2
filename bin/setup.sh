#!/usr/bin/env bash
set -euo pipefail

PROJECT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$PROJECT_DIR"

fail() {
  echo
  echo "SETUP STOPPED: $1"
  exit 1
}

if ! command -v docker >/dev/null 2>&1; then
  fail "Docker is not installed. Install/open Docker Desktop first."
fi

if ! docker compose version >/dev/null 2>&1; then
  fail "Docker Compose is not available in Docker Desktop."
fi

if ! docker info >/dev/null 2>&1; then
  fail "Docker Desktop is installed but is not running. Open Docker Desktop and wait until it shows that Docker is running, then run this setup again."
fi

if [ ! -f .env ]; then
  cp .env.example .env
  echo "Created local .env from .env.example."
fi

# Read the simple project .env safely. Do not source/eval it as shell code.
while IFS='=' read -r key value || [ -n "${key:-}" ]; do
  key="${key%$'\r'}"
  value="${value%$'\r'}"

  [[ -z "$key" || "$key" == \#* ]] && continue

  key="$(printf '%s' "$key" | sed 's/^[[:space:]]*//;s/[[:space:]]*$//')"
  value="$(printf '%s' "$value" | sed 's/^[[:space:]]*//;s/[[:space:]]*$//')"

  if [[ ${#value} -ge 2 && "$value" == \"*\" ]]; then
    value="${value:1:${#value}-2}"
  elif [[ ${#value} -ge 2 && "$value" == \'*\' ]]; then
    value="${value:1:${#value}-2}"
  fi

  export "$key=$value"
done < .env

required_vars=(
  WP_PORT DB_NAME DB_USER DB_PASSWORD DB_ROOT_PASSWORD
  WP_ADMIN_USER WP_ADMIN_PASSWORD WP_ADMIN_EMAIL WP_SITE_TITLE
)
for var_name in "${required_vars[@]}"; do
  if [ -z "${!var_name:-}" ]; then
    fail "Required setting $var_name is missing from .env."
  fi
done

echo "Validating Docker configuration..."
if ! docker compose config -q; then
  fail "docker-compose.yml or .env is invalid."
fi

echo "Starting MariaDB and WordPress..."
if ! docker compose up -d db wordpress; then
  echo
  docker compose logs --tail=100 db wordpress 2>/dev/null || true
  fail "Docker could not start the AspireCRE containers."
fi

echo "Waiting for WordPress files to initialize..."
ready=0
for _ in $(seq 1 90); do
  if docker compose exec -T wordpress test -f /var/www/html/wp-config.php >/dev/null 2>&1 \
     && docker compose exec -T wordpress test -f /var/www/html/wp-load.php >/dev/null 2>&1; then
    ready=1
    break
  fi
  sleep 2
done

if [ "$ready" -ne 1 ]; then
  echo
  docker compose logs --tail=150 db wordpress 2>/dev/null || true
  fail "WordPress files did not initialize correctly."
fi

echo "Checking WordPress installation..."
if docker compose --profile tools run --rm cli wp core is-installed >/dev/null 2>&1; then
  echo "WordPress is already installed."
else
  echo "Installing WordPress..."
  if ! docker compose --profile tools run --rm cli wp core install \
      --url="http://localhost:${WP_PORT}" \
      --title="${WP_SITE_TITLE}" \
      --admin_user="${WP_ADMIN_USER}" \
      --admin_password="${WP_ADMIN_PASSWORD}" \
      --admin_email="${WP_ADMIN_EMAIL}" \
      --skip-email; then
    echo
    docker compose logs --tail=150 db wordpress 2>/dev/null || true
    fail "WP-CLI could not install WordPress."
  fi
fi

echo "Activating AspireCRE theme..."
docker compose --profile tools run --rm cli wp theme activate aspirecre >/dev/null

echo "Activating Aspire Core plugin..."
docker compose --profile tools run --rm cli wp plugin activate aspire-core >/dev/null

echo "Verifying WordPress, theme, and plugin..."
docker compose --profile tools run --rm cli wp core is-installed >/dev/null
docker compose --profile tools run --rm cli wp theme is-active aspirecre >/dev/null
docker compose --profile tools run --rm cli wp plugin is-active aspire-core >/dev/null

HTTP_OK=0
if command -v curl >/dev/null 2>&1; then
  for _ in $(seq 1 30); do
    if curl -fsS --max-time 5 "http://localhost:${WP_PORT}/" >/dev/null 2>&1; then
      HTTP_OK=1
      break
    fi
    sleep 2
  done
fi

echo
echo "AspireCRE V2 local WordPress is ready."
echo "Site:  http://localhost:${WP_PORT}"
echo "Admin: http://localhost:${WP_PORT}/wp-admin/"
echo "User:  ${WP_ADMIN_USER}"
echo "Pass:  ${WP_ADMIN_PASSWORD}"
if command -v curl >/dev/null 2>&1; then
  if [ "$HTTP_OK" -eq 1 ]; then
    echo "HTTP check: PASS"
  else
    echo "HTTP check: WordPress installed, but the browser endpoint did not answer yet. Check 'docker compose ps' if the page does not open."
  fi
fi
echo
echo "Container status:"
docker compose ps
