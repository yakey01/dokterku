# 🔐 GitHub Secrets Setup Guide

## 📋 Overview
Panduan untuk setup GitHub Secrets yang diperlukan untuk deployment otomatis ke Hostinger.

## 🔑 SSH Key Setup

### Step 1: Generate SSH Key (Di Local Machine)
```bash
# Generate SSH key pair
ssh-keygen -t rsa -b 4096 -C "deploy@dokterkuklinik.com" -f ~/.ssh/dokterku_deploy

# Files yang dihasilkan:
# ~/.ssh/dokterku_deploy (private key)
# ~/.ssh/dokterku_deploy.pub (public key)
```

### Step 2: Add Public Key ke Hostinger
```bash
# Copy public key
cat ~/.ssh/dokterku_deploy.pub

# Login ke Hostinger SSH, tambahkan ke authorized_keys
mkdir -p ~/.ssh
echo "your_public_key_here" >> ~/.ssh/authorized_keys
chmod 600 ~/.ssh/authorized_keys
chmod 700 ~/.ssh
```

### Step 3: Test SSH Connection
```bash
# Test connection
ssh -i ~/.ssh/dokterku_deploy username@dokterkuklinik.com

# Jika berhasil, Anda akan masuk ke server
```

## 🔧 GitHub Repository Settings

### Step 1: Access GitHub Secrets
1. Go to your GitHub repository
2. Click **Settings** tab
3. Click **Secrets and variables** > **Actions**
4. Click **New repository secret**

### Step 2: Add Required Secrets

#### 1. SSH_PRIVATE_KEY
```bash
# Copy private key content
cat ~/.ssh/dokterku_deploy

# Paste seluruh content (termasuk -----BEGIN dan -----END)
```

#### 2. REMOTE_HOST
```
Value: dokterkuklinik.com
```

#### 3. REMOTE_USER
```
Value: [Your Hostinger SSH username]
# Biasanya sama dengan FTP username
```

#### 4. DB_USERNAME
```
Value: u454362045_u45436245_kli
```

#### 5. DB_PASSWORD
```
Value: KlinikApp2025!
```

#### 6. DB_DATABASE
```
Value: u454362045_u45436245_kli
```

## 📊 GitHub Secrets Summary

Berikut adalah semua secrets yang perlu ditambahkan:

| Secret Name | Description | Example Value |
|-------------|-------------|---------------|
| `SSH_PRIVATE_KEY` | Private SSH key untuk deploy | `-----BEGIN RSA PRIVATE KEY-----...` |
| `REMOTE_HOST` | Hostname server | `dokterkuklinik.com` |
| `REMOTE_USER` | SSH username | `u454362045` |
| `DB_USERNAME` | Database username | `u454362045_u45436245_kli` |
| `DB_PASSWORD` | Database password | `KlinikApp2025!` |
| `DB_DATABASE` | Database name | `u454362045_u45436245_kli` |

## 🧪 Test Setup

### Step 1: Manual Test
```bash
# Test SSH connection
ssh -i ~/.ssh/dokterku_deploy username@dokterkuklinik.com

# Test database connection
mysql -u u454362045_u45436245_kli -p u454362045_u45436245_kli
```

### Step 2: Test GitHub Actions
1. Push ke repository untuk trigger deployment
2. Check GitHub Actions tab untuk melihat progress
3. Verify deployment berhasil

## 🚀 Deployment Process

### What Happens When You Push:
1. **🚀 Checkout code** - Download repository
2. **🐘 Setup PHP** - Install PHP 8.3 dan extensions
3. **📦 Install Composer** - Install Laravel dependencies
4. **🟢 Setup Node.js** - Install Node.js dan NPM
5. **📦 Install NPM** - Install frontend dependencies
6. **🏗️ Build assets** - Compile CSS/JS untuk production
7. **🧹 Cleanup** - Hapus development files
8. **🚀 Deploy** - Upload ke Hostinger dengan SSH
9. **🔧 Configure** - Setup environment dan database
10. **🧪 Test** - Verify deployment berhasil

### Auto Cleanup Process:
- **Database backup** otomatis sebelum deploy
- **Hapus file lama** kecuali .well-known dan cgi-bin
- **Upload file baru** dari GitHub
- **Setup environment** untuk production
- **Run migrations** dan optimizations

## 🔧 Troubleshooting

### Issue 1: SSH Permission Denied
```bash
# Check SSH key permissions
chmod 600 ~/.ssh/dokterku_deploy
chmod 700 ~/.ssh

# Test manual connection
ssh -i ~/.ssh/dokterku_deploy -v username@dokterkuklinik.com
```

### Issue 2: Database Connection Failed
```bash
# Check database credentials
mysql -u u454362045_u45436245_kli -p u454362045_u45436245_kli

# Check if database exists
SHOW DATABASES;
```

### Issue 3: GitHub Actions Failing
1. Check **Actions** tab di GitHub
2. Click pada failed workflow
3. Expand step yang failed
4. Check error message

### Issue 4: File Permissions
```bash
# SSH ke server, fix permissions
chmod -R 755 storage bootstrap/cache
chmod 644 .env
```

## 📋 Pre-Deployment Checklist

### Before First Deploy:
- [ ] SSH key generated dan tested
- [ ] Public key added ke Hostinger
- [ ] All GitHub secrets configured
- [ ] Database credentials verified
- [ ] Test SSH connection successful

### Before Each Deploy:
- [ ] Code tested locally
- [ ] Database migrations ready
- [ ] Environment variables updated
- [ ] No breaking changes

### After Each Deploy:
- [ ] Check website: https://dokterkuklinik.com
- [ ] Test all 5 panels
- [ ] Verify database connection
- [ ] Check error logs

## 🎉 Benefits of GitHub Deployment

### ✅ Automated
- **Push to deploy** - Otomatis deploy setiap push
- **No manual steps** - Semua otomatis
- **Consistent** - Proses yang sama setiap kali

### ✅ Safe
- **Database backup** otomatis sebelum deploy
- **Rollback capability** via Git history
- **Testing included** - Verify deployment success

### ✅ Efficient
- **Fast deployment** - Optimized untuk speed
- **Production ready** - Otomatis optimization
- **Error handling** - Comprehensive error checking

## 📞 Support

### GitHub Actions Logs
- Check **Actions** tab untuk deployment logs
- Expand each step untuk detail
- Download logs untuk troubleshooting

### Server Logs
```bash
# SSH ke server
ssh -i ~/.ssh/dokterku_deploy username@dokterkuklinik.com

# Check Laravel logs
tail -f domains/dokterkuklinik.com/public_html/storage/logs/laravel.log

# Check system logs
tail -f /var/log/apache2/error.log
```

---

## 🎯 Next Steps

1. **Setup SSH Keys** menggunakan panduan di atas
2. **Add GitHub Secrets** sesuai dengan table
3. **Test SSH Connection** untuk memastikan works
4. **Push to GitHub** untuk trigger first deployment
5. **Monitor deployment** di GitHub Actions
6. **Verify website** working di https://dokterkuklinik.com

**Setelah setup ini, setiap `git push` akan otomatis deploy ke Hostinger!** 🚀