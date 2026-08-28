#!/usr/bin/env bash
# Run before every deploy — no CI exists for this project, so this is the manual
# gate. Non-zero exit means something needs attention before shipping.
set -euo pipefail

cd "$(dirname "$0")/.."

echo "==> composer audit (known CVEs in installed packages)"
docker compose exec -T app composer audit

echo
echo "==> composer outdated (direct dependencies only, for visibility)"
docker compose exec -T app composer outdated --direct || true

echo
echo "==> checking for a stray .env committed to git"
if git ls-files | grep -qE '(^|/)\.env$'; then
    echo "FOUND a tracked .env file — this must never be committed. Aborting." >&2
    exit 1
fi
echo "OK: no .env tracked in git"

echo
echo "All checks passed."
