#!/usr/bin/env bash
# One-time: copy the data from Supabase into this server's database.
# Run after the app is installed and `php artisan migrate --force` has created the tables (step 9 of the guide):
#   /var/www/srympex/deploy/move-from-supabase.sh
# It asks for the Supabase database connection string (Supabase dashboard › Connect › Session pooler / direct URI).
set -euo pipefail

read -r -s -p "Supabase connection string (postgresql://...): " SUPABASE_URL
echo
DUMP=$(mktemp /tmp/supabase-data.XXXXXX.sql)
trap 'rm -f "$DUMP"' EXIT

echo "→ Copying data from Supabase (tables only; the structure comes from the app's migrations)"
pg_dump "$SUPABASE_URL" --data-only --schema=public --no-owner --no-privileges \
    --exclude-table=public.migrations --exclude-table=public.sessions --exclude-table=public.cache \
    --exclude-table=public.cache_locks --exclude-table=public.jobs --exclude-table=public.failed_jobs \
    --exclude-table=public.job_batches > "$DUMP"

echo "→ Loading into this server's database"
sudo -u postgres psql --quiet --set ON_ERROR_STOP=1 --single-transaction \
    -c "set session_replication_role = replica" -f "$DUMP" srympex

echo "→ Row counts"
sudo -u postgres psql -At srympex -c "select 'companies', count(*) from companies union all select 'employees', count(*) from employees union all select 'users', count(*) from users"
echo "✓ Done. Sign in with the same email and password as before."
