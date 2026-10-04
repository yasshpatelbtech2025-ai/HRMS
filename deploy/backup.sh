#!/usr/bin/env bash
# Nightly backup of the database and uploaded files (run by /etc/cron.d/srympex as root).
# Kept 14 days on this server, and copied to the USB backup disk when it is plugged in (kept 90 days there).
set -euo pipefail

APP=/var/www/srympex
LOCAL=/var/backups/srympex
USB=/mnt/backup-usb
STAMP=$(date +%Y-%m-%d_%H%M)

mkdir -p "$LOCAL"
chmod 700 "$LOCAL"

echo "[$STAMP] Backing up"
# Database: compressed custom format (restore with pg_restore).
sudo -u postgres pg_dump --format=custom --compress=9 srympex > "$LOCAL/db_$STAMP.dump"
# Uploaded files: photos, documents, attachments.
tar -czf "$LOCAL/files_$STAMP.tar.gz" -C "$APP/storage/app" private
# The .env holds the APP_KEY, without which encrypted fields (Aadhaar, PAN, bank) can't be read.
cp "$APP/.env" "$LOCAL/env_$STAMP"
chmod 600 "$LOCAL"/*

# A dump that isn't readable is not a backup.
pg_restore --list "$LOCAL/db_$STAMP.dump" > /dev/null

find "$LOCAL" -type f -mtime +14 -delete

if mountpoint -q "$USB"; then
    mkdir -p "$USB/srympex"
    rsync -a "$LOCAL/" "$USB/srympex/"
    find "$USB/srympex" -type f -mtime +90 -delete
    sync
    echo "[$STAMP] Copied to USB disk"
else
    echo "[$STAMP] WARNING: USB backup disk not mounted at $USB; backup kept on this server only"
fi

echo "[$STAMP] Done: $(du -sh "$LOCAL" | cut -f1) in $LOCAL"
