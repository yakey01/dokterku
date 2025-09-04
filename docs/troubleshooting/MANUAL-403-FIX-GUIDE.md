# 🔧 Manual 403 Fix Guide

## 🚨 Current Issue
Your Laravel application is returning 403 Forbidden error instead of loading properly.

## 🎯 Solution Options

### Option 1: Hostinger Control Panel (Recommended)

1. **Login to Hostinger Control Panel**
   - Go to: https://hpanel.hostinger.com
   - Login with your Hostinger credentials

2. **Access File Manager**
   - Click on "File Manager" or "Files"
   - Navigate to: `domains/dokterkuklinik.com/public_html`

3. **Run the Fix Script**
   - Find the file `fix-403-complete.sh`
   - Right-click and select "Edit"
   - Copy the content below and paste it into a new file called `fix-manual.sh`

```bash
#!/bin/bash
echo "🔧 Manual 403 Fix"
echo "================="

echo "1. Setting permissions..."
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
chmod 644 public/.htaccess

echo "2. Creating root .htaccess..."
cat > .htaccess << 'EOF'
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
EOF

echo "3. Updating public/.htaccess..."
cat > public/.htaccess << 'EOF'
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>
    RewriteEngine On
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
EOF

echo "4. Checking .env..."
if [ ! -f .env ]; then
    cp .env.example .env
    php artisan key:generate
fi

echo "5. Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

echo "✅ Fix completed!"
```

4. **Execute the Script**
   - In File Manager, find the `fix-manual.sh` file
   - Right-click and select "Terminal" or "SSH"
   - Run: `chmod +x fix-manual.sh && ./fix-manual.sh`

### Option 2: GitHub Actions (Automatic)

The workflow has been updated to automatically run the fix script. Just push your code:

```bash
git add .
git commit -m "Trigger deployment with 403 fix"
git push origin main
```

### Option 3: Direct File Edits

If you can't run scripts, manually edit these files:

1. **Create root .htaccess** (in public_html folder):
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

2. **Update public/.htaccess**:
```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>
    RewriteEngine On
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

3. **Set permissions**:
   - storage/ folder: 755
   - bootstrap/cache/ folder: 755
   - public/.htaccess: 644

## 🧪 Testing

After applying the fix, test:
```bash
curl -I https://dokterkuklinik.com
```

Should return: `HTTP/2 200` instead of `HTTP/2 403`

## 🆘 If Still Not Working

1. Check Hostinger error logs
2. Verify PHP version is 8.3+
3. Ensure all Laravel files are present
4. Check if .env file exists and has correct database settings 