#!/usr/bin/env bash
set -euo pipefail
PROJECT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$PROJECT_DIR"
echo "This removes only the LOCAL AspireCRE database and WordPress core Docker volumes."
read -r -p "Type RESET to continue: " answer
[[ "$answer" == "RESET" ]] || { echo "Cancelled."; exit 0; }
docker compose down -v
echo "Local environment reset. Run: bash bin/setup.sh"
