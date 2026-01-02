# Git-Based Deployment Guide: GitHub → Hostinger

Complete setup instructions for deploying PayFlow to a Hostinger subdomain using Git-based automation.

---

## Step 1: Create a GitHub Repository

```bash
# Initialize and push to GitHub
git add .
git commit -m "Initial commit: PayFlow application"
git branch -M main
git remote add origin https://github.com/YOUR_USERNAME/payflow.git
git push -u origin main
```

Replace `YOUR_USERNAME` with your actual GitHub username.

---

## Step 2: Connect Hostinger via SSH Key

### Generate SSH Key on Your Local Machine
```bash
ssh-keygen -t ed25519 -C "your_email@example.com"
```
Accept defaults, no passphrase needed for deployment keys.

### Add SSH Key to Hostinger

1. **Access Hostinger Dashboard** → Account Settings → Security → SSH Keys
2. **Upload your public key:**
   ```bash
   cat ~/.ssh/id_ed25519.pub  # Copy this content
   ```
3. **Paste into Hostinger** and save

---

## Step 3: Set Up Deployment Directory on Hostinger

### Via Hostinger SSH Terminal (or cPanel Terminal):

```bash
# Connect to your hosting account
ssh user@your-hosting-ip

# Navigate to subdomain directory
cd /home/username/public_html/subdomain

# Clone repository
git clone git@github.com:YOUR_USERNAME/payflow.git .

# Or if cloning into a specific folder:
git clone git@github.com:YOUR_USERNAME/payflow.git payflow
cd payflow
```

---

## Step 4: Configure Environment on Hostinger

```bash
# Copy example env file
cp .env.example .env

# Edit with your Hostinger database credentials
nano .env  # or use Hostinger's file editor

# Set these critical values:
# APP_URL=https://subdomain.yourdomain.com
# DB_HOST=your-db-host
# DB_DATABASE=your-db-name
# DB_USERNAME=your-db-user
# DB_PASSWORD=your-db-password
# STRIPE_PUBLIC_KEY=your-stripe-key
# STRIPE_SECRET_KEY=your-stripe-secret
```

---

## Step 5: Install Dependencies & Build

```bash
# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Install Node dependencies
npm install

# Build frontend assets
npm run build

# Generate app key (if not in .env)
php artisan key:generate

# Run migrations
php artisan migrate --force

# Optimize for production
php artisan optimize
php artisan config:cache
php artisan route:cache
```

---

## Step 6: Set Up Automatic Deployment with GitHub Actions

Create `.github/workflows/deploy.yml`:

```yaml
name: Deploy to Hostinger

on:
  push:
    branches: [main]

jobs:
  deploy:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v3

      - name: Deploy via SSH
        uses: appleboy/ssh-action@master
        with:
          host: ${{ secrets.HOSTINGER_HOST }}
          username: ${{ secrets.HOSTINGER_USER }}
          key: ${{ secrets.HOSTINGER_SSH_KEY }}
          script: |
            cd /home/username/public_html/subdomain
            git pull origin main
            composer install --no-dev --optimize-autoloader
            npm install
            npm run build
            php artisan migrate --force
            php artisan optimize:clear
            php artisan optimize
```

### Add GitHub Secrets:

1. Go to **GitHub Repo** → **Settings** → **Secrets and Variables** → **Actions**
2. Add these secrets:
   - `HOSTINGER_HOST`: Your hosting IP/domain
   - `HOSTINGER_USER`: Your SSH username
   - `HOSTINGER_SSH_KEY`: Your private SSH key (cat ~/.ssh/id_ed25519)

---

## Step 7: Configure Web Server on Hostinger

Your `public/` directory should be the document root for the subdomain:

1. **Hostinger cPanel** → Addon Domains
2. Point subdomain to: `/home/username/public_html/subdomain/public`
3. Ensure `.htaccess` is present in `public/` (Laravel includes this by default)

---

## File Permissions on Hostinger

```bash
# Set correct permissions
chmod 755 storage bootstrap/cache
chmod 644 storage/* bootstrap/cache/*
```

---

## Testing Your Deployment

After initial setup:

```bash
# On Hostinger via SSH
php artisan config:cache
php artisan route:cache

# Visit your subdomain
# https://subdomain.yourdomain.com
```

If you see errors, check logs:
```bash
tail -f storage/logs/laravel.log
```

---

## Future Deployments

Now every time you push to `main` on GitHub:
```bash
git add .
git commit -m "Your changes"
git push origin main
```

GitHub Actions will automatically:
1. Pull latest code
2. Install dependencies
3. Build frontend
4. Run migrations
5. Optimize application

---

## Common Issues on Hostinger

| Issue | Solution |
|-------|----------|
| 500 error | Check `storage/logs/laravel.log` |
| Assets not loading | Ensure `npm run build` ran successfully |
| Database errors | Verify `.env` credentials match cPanel database |
| Permission denied on git pull | Ensure SSH key is added to Hostinger |
| Node not found | Contact Hostinger support to install Node.js |

---

## Application Stack

- **PHP**: 8.3.19
- **Laravel**: 12.44.0
- **Database**: MySQL
- **Frontend**: Vue 3 + Inertia v2 + Tailwind v4
- **Build Tool**: Vite
- **Dependencies Manager**: Composer + NPM
