# 🔐 GitHub Secrets Template

## 📋 Copy-Paste Template for GitHub Secrets

### 1. SSH_PRIVATE_KEY
```
Name: SSH_PRIVATE_KEY
Value: [Run: cat ~/.ssh/dokterku_deploy]
```

### 2. REMOTE_HOST
```
Name: REMOTE_HOST
Value: dokterkuklinik.com
```

### 3. REMOTE_USER
```
Name: REMOTE_USER
Value: [Your Hostinger SSH username - biasanya seperti u454362045]
```

### 4. DB_USERNAME
```
Name: DB_USERNAME
Value: u454362045_u45436245_kli
```

### 5. DB_PASSWORD
```
Name: DB_PASSWORD
Value: KlinikApp2025!
```

### 6. DB_DATABASE
```
Name: DB_DATABASE
Value: u454362045_u45436245_kli
```

## 🚀 Quick Setup Steps:

1. **Add public key ke Hostinger**:
```bash
mkdir -p ~/.ssh
echo "ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAACAQC33HycSly4DVFVscqDn30Ct3MFIz+N0MIUNGZFwE8i12B58UVF3kHjRZlmXjJcQQvltF81e82/USpRKBqsZaVA4bOWX2KPpE+c3ipbcy9wXFh9s3ZGoF4MmOd348hk9paFripp/o/U+sMi+xAuebQAEFdeupT4E/JkYWdl6qFr91yqtZkJF5zEOVTvVdIgsmFH+LUhQR4QsSNBPBlcPm7oA+dRfWdpDEKi1Ibu2Sr8R5u8te899WAFMH5TkAhmbxgBAerfDLEyi2ubjtqn/+4TRyTXG88x+ad/LgFxlTRhN2XnHO/ZIUaHDdyBT3ckGugE0JWNyx0SZtDMKfKd0z445wou5TbuASH8KKzgraRLFm54KlOXYh2o68he8HYehfKmrVP0IBiZ6l7KNKWJUdpKM+OomGGHI6J+Ojlsb+Z/AYE3VJDClX7/uDrRrO5W8PRdpSw0GZZICHHwhvurEuhKRJ5CPOC3SylZxTin741q5Xb5p6qt56ZxYXqSDSRdgAGPo+/byF5ao04NS69D5pDqkXlInxm1pCEGGNN5WeYpqli4Cy4/6/81k6eFGP4uGTIlrEqk6PQM9WNQRLDpwgXa6NKPsdfeABbL6ajUuEGUXG9uC3fohippuAXGT04TuA9U1iIejectf0KzJNnROWmO+ICINboRgU12p91rOSGuOw== deploy@dokterkuklinik.com" >> ~/.ssh/authorized_keys
chmod 600 ~/.ssh/authorized_keys
chmod 700 ~/.ssh
```

2. **Test SSH connection**:
```bash
ssh -i ~/.ssh/dokterku_deploy your_username@dokterkuklinik.com
```

3. **Copy private key for GitHub**:
```bash
cat ~/.ssh/dokterku_deploy
```

4. **Add all 6 secrets to GitHub**

5. **Deploy**:
```bash
git push origin main
```

## 🎯 Result:
- ✅ Website: https://dokterkuklinik.com
- ✅ Admin Panel: https://dokterkuklinik.com/admin
- ✅ All 5 panels working
- ✅ Database connected
- ✅ No 500 errors

## 🔧 Need Help?
Check **Actions** tab di GitHub untuk deployment logs!