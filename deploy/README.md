# Installing SRYMPEX on the office server

For the Lenovo ThinkSystem ST45 V3 (AMD EPYC 4124P, 16 GB, one 2 TB disk), office network only.
Follow the steps in order; each command block can be copied as it is. Allow about two hours.

**Replace these placeholders everywhere** (the commands and the files in this folder use them):

| Placeholder | Means | Example |
|---|---|---|
| `hrms.example.com` | The address staff will type: a name on your domain | `hrms.srympex.com` |
| `192.168.1.50` | The server's fixed address on the office network | from your router |
| `192.168.1.0/24` | The office network | from your router |

What ends up on the server:

```
Browser (office only) ──HTTPS──▶ Nginx ──▶ PHP 8.4 (the app) ──▶ PostgreSQL 17
                                        └─▶ storage/app/private (photos, documents)
Background: queue worker (Supervisor) · scheduler (cron) · nightly backup → USB disk
```

---

## 1. Install Ubuntu Server 24.04 LTS

1. Download Ubuntu Server 24.04 LTS from ubuntu.com and write it to a USB stick (balenaEtcher or Rufus).
2. Boot the server from the stick (F12 for the boot menu on Lenovo).
3. During setup:
   - **Storage:** "Use an entire disk" (the 2 TB disk), with LVM.
   - **Profile:** create your own admin login (not `srympex`; that one is made later for the app).
   - **SSH:** tick **Install OpenSSH server**, so you can work on the server from another PC.
   - Skip the optional "snaps".
4. After the restart, sign in and note the server's address: `ip -4 addr` (e.g. `192.168.1.50`).

## 2. Give the server a fixed address

In your office router's settings, find **DHCP reservation** (also called "static lease" or "address reservation") and reserve the address the server got for its network card. The server then always has the same address.

From here on you can work from any office PC: `ssh youradmin@192.168.1.50`.

## 3. Updates, time zone and firewall

```bash
sudo apt update && sudo apt full-upgrade -y
sudo timedatectl set-timezone Asia/Kolkata

# Only office computers may connect (SSH, HTTP, HTTPS).
sudo ufw allow from 192.168.1.0/24 to any port 22 proto tcp
sudo ufw allow from 192.168.1.0/24 to any port 80 proto tcp
sudo ufw allow from 192.168.1.0/24 to any port 443 proto tcp
sudo ufw enable

# Security updates install themselves.
sudo dpkg-reconfigure -plow unattended-upgrades
```

## 4. Install the software

```bash
# PHP 8.4 (Ubuntu's own is 8.3; the app needs 8.4)
sudo add-apt-repository -y ppa:ondrej/php
sudo apt install -y nginx git unzip supervisor rsync \
  php8.4-fpm php8.4-cli php8.4-opcache php8.4-pgsql php8.4-mbstring php8.4-xml \
  php8.4-curl php8.4-zip php8.4-gd php8.4-intl php8.4-bcmath

# PostgreSQL 17 (the same version as Supabase, so the data copies straight across)
sudo apt install -y postgresql-common
sudo /usr/share/postgresql-common/pgdg/apt.postgresql.org.sh -y
sudo apt install -y postgresql-17

# Node.js 22 (builds the CSS and JavaScript)
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
sudo apt install -y nodejs

# Composer (PHP libraries)
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

php -v && psql --version && node -v && composer --version
```

`php -v` must say 8.4, `psql` 17, `node` 22.

## 5. Create the database

```bash
sudo -u postgres createuser --pwprompt srympex     # choose a long password; you'll need it in step 7
sudo -u postgres createdb --owner=srympex srympex
```

## 6. Create the app user and fetch the code

The app runs as its own user, `srympex`, which can't sign in by password.

```bash
sudo adduser --system --group --shell /bin/bash --home /home/srympex srympex
sudo mkdir -p /var/www/srympex && sudo chown srympex:srympex /var/www/srympex

# A key that lets the server read (not change) the GitHub repository.
sudo -u srympex ssh-keygen -t ed25519 -N "" -f /home/srympex/.ssh/id_ed25519 -C "srympex-server"
sudo cat /home/srympex/.ssh/id_ed25519.pub
```

On GitHub: repository **Settings › Deploy keys › Add deploy key**, paste the line printed above, leave "Allow write access" **off**, save. Then:

```bash
sudo -u srympex env GIT_SSH_COMMAND="ssh -o StrictHostKeyChecking=accept-new" \
  git clone git@github.com:yasshpatelbtech2025-ai/HRMS.git /var/www/srympex
```

## 7. Settings (.env)

```bash
cd /var/www/srympex
sudo -u srympex cp deploy/env.production.example .env
sudo -u srympex chmod 600 .env
sudo -u srympex nano .env
```

Fill in:

- `APP_URL=https://hrms.example.com`
- `DB_PASSWORD=` the password from step 5
- `APP_KEY=` **copy the exact `APP_KEY=...` line from the `.env` on the Mac.** Aadhaar, PAN, bank account numbers and the mail password are encrypted with this key; with a different key they can't be read. (On the Mac: `grep ^APP_KEY= /Users/JatinPatel/HRMS/.env`.)

Save with Ctrl+O, Enter, Ctrl+X.

## 8. Install the app

```bash
sudo -u srympex bash -c 'cd /var/www/srympex && composer install --no-dev --optimize-autoloader \
  && npm ci && npm run build && php artisan migrate --force && php artisan optimize'
```

`migrate` creates the empty tables. Whenever `.env` changes later, run `sudo -u srympex php /var/www/srympex/artisan optimize` again.

## 9. Move the data from Supabase (once)

In the Supabase dashboard: **Connect › Session pooler**, copy the connection string, and put your database password into it. Then:

```bash
/var/www/srympex/deploy/move-from-supabase.sh
```

It asks for that string (it isn't shown or saved), copies every table across, and prints the number of companies, employees and logins. Everyone signs in with the same email and password as before. No uploaded files are in Supabase storage yet, so there's nothing else to move.

## 10. HTTPS certificate

Browsers only allow the **camera** (employee photos) on HTTPS pages, so the office address needs a certificate, even though it's only reachable inside the office. A free one from Let's Encrypt renews itself; it's checked through your domain's DNS, so the server never has to be reachable from the internet.

These steps assume the domain's DNS is on **Cloudflare** (free). If it's elsewhere, add the domain to a free Cloudflare account and change the nameservers at your registrar as Cloudflare instructs, or ask for the steps for your DNS provider.

1. In Cloudflare › your domain › **DNS**: add a record **Type A**, **Name** `hrms`, **IPv4** `192.168.1.50`, **Proxy status: DNS only** (grey cloud).
2. **My Profile › API Tokens › Create Token** › template **Edit zone DNS** › Zone: your domain › create, and copy the token.
3. On the server:

```bash
sudo apt install -y certbot python3-certbot-dns-cloudflare
sudo mkdir -p /root/.secrets && sudo chmod 700 /root/.secrets
sudo nano /root/.secrets/cloudflare.ini      # one line:  dns_cloudflare_api_token = THE_TOKEN
sudo chmod 600 /root/.secrets/cloudflare.ini

sudo certbot certonly --dns-cloudflare --dns-cloudflare-credentials /root/.secrets/cloudflare.ini \
  -d hrms.example.com --deploy-hook "systemctl reload nginx"
```

Renewal is automatic (`sudo certbot renew --dry-run` checks it).

## 11. Web server, PHP and database settings

```bash
cd /var/www/srympex/deploy

# Nginx (put your address into the file first)
sudo cp nginx.conf /etc/nginx/sites-available/srympex
sudo sed -i 's/hrms.example.com/YOUR.ADDRESS.HERE/g' /etc/nginx/sites-available/srympex
sudo ln -sf /etc/nginx/sites-available/srympex /etc/nginx/sites-enabled/srympex
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl reload nginx

# PHP: the app's own pool (runs as srympex) and caching
sudo cp php-fpm-pool.conf /etc/php/8.4/fpm/pool.d/srympex.conf
sudo cp php-opcache.ini /etc/php/8.4/fpm/conf.d/99-srympex.ini
sudo systemctl restart php8.4-fpm

# PostgreSQL tuned for this machine
sudo cp postgresql.conf /etc/postgresql/17/main/conf.d/srympex.conf
sudo systemctl restart postgresql
```

## 12. Background worker, scheduler and updates

```bash
cd /var/www/srympex/deploy
sudo cp supervisor.conf /etc/supervisor/conf.d/srympex-worker.conf
sudo supervisorctl reread && sudo supervisorctl update
sudo supervisorctl status            # srympex-worker should say RUNNING

sudo cp cron /etc/cron.d/srympex && sudo chmod 644 /etc/cron.d/srympex

# Let the update script reload PHP (and nothing else) without a password.
echo 'srympex ALL=(root) NOPASSWD: /usr/bin/systemctl reload php8.4-fpm' | sudo tee /etc/sudoers.d/srympex
sudo chmod 440 /etc/sudoers.d/srympex
```

## 13. Backups

Every night at 01:30, `deploy/backup.sh` saves the database, the uploaded files and the `.env` (it holds the encryption key) to `/var/backups/srympex`, keeping 14 days.

**That copy is on the same disk, so it does not survive the disk failing.** Plug in an external USB disk (1–2 TB) and leave it connected; the script copies everything to it each night and keeps 90 days there. To set it up (this **erases** the USB disk):

```bash
lsblk                                   # find the USB disk, e.g. sdb (check the size!)
sudo parted /dev/sdb --script mklabel gpt mkpart primary ext4 0% 100%
sudo mkfs.ext4 -L srympex-backup /dev/sdb1
sudo mkdir -p /mnt/backup-usb
echo 'LABEL=srympex-backup /mnt/backup-usb ext4 defaults,nofail,x-systemd.device-timeout=5 0 2' | sudo tee -a /etc/fstab
sudo mount -a
```

Run one backup now and check it:

```bash
sudo /var/www/srympex/deploy/backup.sh
ls -lh /var/backups/srympex /mnt/backup-usb/srympex
```

Now and then, take the USB disk home or swap between two disks, so a copy is outside the office (fire, theft). The nightly log is `/var/log/srympex-backup.log`; a line with `WARNING` means the USB disk wasn't connected.

## 14. Check it works

From an office PC, open `https://hrms.example.com`:

- [ ] The sign-in page loads with a padlock (valid certificate).
- [ ] You can sign in, and companies and employees are there.
- [ ] Add employee › take photo: the browser asks for the camera.
- [ ] Employees › Import: a small sheet imports and the status card finishes.
- [ ] Company › Firm › Mail setup › send test email arrives.

If the address doesn't open inside the office but `https://192.168.1.50` does (with a certificate warning), the router is blocking DNS answers that point inside the office ("DNS rebinding protection"). Add an exception for your domain in the router, or a local DNS entry `hrms.example.com → 192.168.1.50`.

Once it's working, Supabase is no longer needed: pause the project in its dashboard (keep it a few weeks as a fallback, then delete it).

---

## Updating to a new version

```bash
sudo -u srympex /var/www/srympex/deploy/deploy.sh
```

Shows a "Back soon" page for the minute it takes, fetches the latest code from GitHub, installs it, applies database changes and restarts the worker.

## Restoring from a backup

Pick the backup by its date in `/var/backups/srympex` (or `/mnt/backup-usb/srympex`), then:

```bash
cd /var/www/srympex
sudo -u srympex php artisan down
sudo supervisorctl stop srympex-worker

sudo -u postgres dropdb srympex
sudo -u postgres createdb --owner=srympex srympex
sudo -u postgres pg_restore --no-owner --role=srympex -d srympex /var/backups/srympex/db_2026-11-02_0130.dump

sudo rm -rf storage/app/private
sudo tar -xzf /var/backups/srympex/files_2026-11-02_0130.tar.gz -C storage/app
sudo chown -R srympex:srympex storage/app/private

sudo supervisorctl start srympex-worker
sudo -u srympex php artisan up
```

When setting up a **replacement server**, follow steps 1–8 and 10–13, use the `APP_KEY` from the backed-up `env_...` file in step 7, and restore as above instead of step 9.

## Recommended hardware additions

- **A UPS** (600–1000 VA) so a power cut doesn't switch the server off mid-write.
- **A second 2 TB disk or an SSD** later: a mirror (RAID 1) survives a disk failure without downtime; an SSD makes the database faster.

## Where to look when something's wrong

| What | Where |
|---|---|
| App errors | `/var/www/srympex/storage/logs/laravel-YYYY-MM-DD.log` |
| Background worker | `/var/www/srympex/storage/logs/worker.log`, `sudo supervisorctl status` |
| Web server | `/var/log/nginx/error.log` |
| Backups | `/var/log/srympex-backup.log` |
| Activity (who did what) | In the app: Configs › Activity log |
