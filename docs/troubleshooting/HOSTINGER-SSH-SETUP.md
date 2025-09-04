# 🔑 Hostinger SSH Setup Instructions

## ✅ Your SSH Public Key:
```
ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAACAQC33HycSly4DVFVscqDn30Ct3MFIz+N0MIUNGZFwE8i12B58UVF3kHjRZlmXjJcQQvltF81e82/USpRKBqsZaVA4bOWX2KPpE+c3ipbcy9wXFh9s3ZGoF4MmOd348hk9paFripp/o/U+sMi+xAuebQAEFdeupT4E/JkYWdl6qFr91yqtZkJF5zEOVTvVdIgsmFH+LUhQR4QsSNBPBlcPm7oA+dRfWdpDEKi1Ibu2Sr8R5u8te899WAFMH5TkAhmbxgBAerfDLEyi2ubjtqn/+4TRyTXG88x+ad/LgFxlTRhN2XnHO/ZIUaHDdyBT3ckGugE0JWNyx0SZtDMKfKd0z445wou5TbuASH8KKzgraRLFm54KlOXYh2o68he8HYehfKmrVP0IBiZ6l7KNKWJUdpKM+OomGGHI6J+Ojlsb+Z/AYE3VJDClX7/uDrRrO5W8PRdpSw0GZZICHHwhvurEuhKRJ5CPOC3SylZxTin741q5Xb5p6qt56ZxYXqSDSRdgAGPo+/byF5ao04NS69D5pDqkXlInxm1pCEGGNN5WeYpqli4Cy4/6/81k6eFGP4uGTIlrEqk6PQM9WNQRLDpwgXa6NKPsdfeABbL6ajUuEGUXG9uC3fohippuAXGT04TuA9U1iIejectf0KzJNnROWmO+ICINboRgU12p91rOSGuOw== deploy@dokterkuklinik.com
```

## 🚀 Step 1: Add Public Key to Hostinger

### Method 1: Via SSH Terminal
1. **SSH ke Hostinger**:
```bash
ssh your_username@dokterkuklinik.com
```

2. **Add public key ke authorized_keys**:
```bash
mkdir -p ~/.ssh
echo "ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAACAQC33HycSly4DVFVscqDn30Ct3MFIz+N0MIUNGZFwE8i12B58UVF3kHjRZlmXjJcQQvltF81e82/USpRKBqsZaVA4bOWX2KPpE+c3ipbcy9wXFh9s3ZGoF4MmOd348hk9paFripp/o/U+sMi+xAuebQAEFdeupT4E/JkYWdl6qFr91yqtZkJF5zEOVTvVdIgsmFH+LUhQR4QsSNBPBlcPm7oA+dRfWdpDEKi1Ibu2Sr8R5u8te899WAFMH5TkAhmbxgBAerfDLEyi2ubjtqn/+4TRyTXG88x+ad/LgFxlTRhN2XnHO/ZIUaHDdyBT3ckGugE0JWNyx0SZtDMKfKd0z445wou5TbuASH8KKzgraRLFm54KlOXYh2o68he8HYehfKmrVP0IBiZ6l7KNKWJUdpKM+OomGGHI6J+Ojlsb+Z/AYE3VJDClX7/uDrRrO5W8PRdpSw0GZZICHHwhvurEuhKRJ5CPOC3SylZxTin741q5Xb5p6qt56ZxYXqSDSRdgAGPo+/byF5ao04NS69D5pDqkXlInxm1pCEGGNN5WeYpqli4Cy4/6/81k6eFGP4uGTIlrEqk6PQM9WNQRLDpwgXa6NKPsdfeABbL6ajUuEGUXG9uC3fohippuAXGT04TuA9U1iIejectf0KzJNnROWmO+ICINboRgU12p91rOSGuOw== deploy@dokterkuklinik.com" >> ~/.ssh/authorized_keys
chmod 600 ~/.ssh/authorized_keys
chmod 700 ~/.ssh
```

### Method 2: Via Hostinger Control Panel
1. **Login ke Hostinger Control Panel**
2. **Go to Advanced > SSH Access**
3. **Manage SSH Keys**
4. **Add New SSH Key**
5. **Paste public key di atas**

## 🧪 Step 2: Test SSH Connection
```bash
# Test connection dengan private key
ssh -i ~/.ssh/dokterku_deploy your_username@dokterkuklinik.com

# Jika berhasil, Anda akan masuk ke server tanpa password
```

## 🔐 Step 3: Get Private Key for GitHub
```bash
# Copy private key content
cat ~/.ssh/dokterku_deploy
```

**Copy seluruh output** (termasuk `-----BEGIN OPENSSH PRIVATE KEY-----` dan `-----END OPENSSH PRIVATE KEY-----`)

## 📋 Step 4: Add GitHub Secrets

Go to GitHub repository: https://github.com/yakey01/klinik-app
1. **Settings** tab
2. **Secrets and variables** > **Actions**
3. **New repository secret**

### Add These Secrets:

#### 1. SSH_PRIVATE_KEY
```
Name: SSH_PRIVATE_KEY
Value: [Paste output dari cat ~/.ssh/dokterku_deploy]
```

#### 2. REMOTE_HOST
```
Name: REMOTE_HOST
Value: dokterkuklinik.com
```

#### 3. REMOTE_USER
```
Name: REMOTE_USER
Value: [Your Hostinger SSH username]
```

#### 4. DB_USERNAME
```
Name: DB_USERNAME
Value: u454362045_u45436245_kli
```

#### 5. DB_PASSWORD
```
Name: DB_PASSWORD
Value: KlinikApp2025!
```

#### 6. DB_DATABASE
```
Name: DB_DATABASE
Value: u454362045_u45436245_kli
```

## 🚀 Step 5: Deploy!

Once all secrets are added:
```bash
git push origin main
```

## 🎯 What Will Happen:

1. **🗑️ Auto Cleanup**: Hapus semua file lama di Hostinger
2. **📦 Install Dependencies**: Install Composer dan NPM packages
3. **🏗️ Build Assets**: Compile CSS/JS untuk production
4. **🚀 Deploy**: Upload file ke Hostinger
5. **🔧 Configure**: Setup .env dan database
6. **🧪 Test**: Verify deployment success

## ✅ Expected Result:

- **Website**: https://dokterkuklinik.com ✅
- **Admin Panel**: https://dokterkuklinik.com/admin ✅
- **All 5 Panels**: Working perfectly ✅
- **Database**: MySQL connected ✅
- **No 500 errors**: Clean deployment ✅

## 🔧 Troubleshooting:

### SSH Connection Issues:
```bash
# Test with verbose output
ssh -i ~/.ssh/dokterku_deploy -v your_username@dokterkuklinik.com
```

### GitHub Actions Failing:
1. Check **Actions** tab di GitHub
2. Click pada failed workflow
3. Expand step yang failed
4. Check error message

---

## 🎉 Ready for Automated Deployment!

Setelah setup ini selesai, setiap `git push` akan otomatis deploy sistem healthcare ke Hostinger! 🚀