# 🚀 Query Desk - Shared Hosting Setup Guide

## 📋 Pre-Hosting Checklist

- [ ] Hosting plan purchased
- [ ] Domain registered/configured
- [ ] Project files prepared
- [ ] Database credentials ready
- [ ] cPanel access available

## 🌐 Step 1: Purchase Hosting

### Recommended Provider: Hostinger
1. Go to [Hostinger.com](https://hostinger.com)
2. Select **Premium Plan** ($2.99/month)
3. Choose your domain
4. Complete purchase

### Alternative Providers:
- **HostGator**: Hatchling Plan ($3.95/month)
- **Bluehost**: Basic Plan ($3.95/month)
- **A2 Hosting**: Startup Plan ($2.99/month)

## 📁 Step 2: Upload Files

### Via cPanel File Manager:
1. Login to your hosting cPanel
2. Open **File Manager**
3. Navigate to `public_html/`
4. Create folder: `query-desk`
5. Upload all files from `hosting-files/` folder

### Via FTP (Alternative):
- **Host**: Your hosting FTP hostname
- **Username**: Your hosting FTP username
- **Password**: Your hosting FTP password
- **Port**: 21 (default)

## 🗄️ Step 3: Database Setup

### Create Database:
1. In cPanel, go to **MySQL Databases**
2. Create new database: `query_desk`
3. Create database user
4. Assign user to database with **ALL PRIVILEGES**

### Import Database:
1. Go to **phpMyAdmin**
2. Select your database
3. Click **Import**
4. Choose your SQL file
5. Click **Go**

## ⚙️ Step 4: Configuration

### Update Database Settings:
1. Edit `config.php`
2. Update these values:
   ```php
   define('DB_NAME', 'your_actual_database_name');
   define('DB_USER', 'your_actual_db_username');
   define('DB_PASS', 'your_actual_db_password');
   define('APP_URL', 'https://yourdomain.com/query-desk/');
   ```

### Update API Files:
1. Edit `api/_bootstrap.php`
2. Replace `get_pdo()` function with:
   ```php
   require_once __DIR__ . '/../config.php';
   function get_pdo() {
       return get_hosting_pdo();
   }
   ```

## 🔒 Step 5: Security Setup

### SSL Certificate:
1. In cPanel, go to **SSL/TLS**
2. Enable **Let's Encrypt** (free)
3. Force HTTPS redirect

### File Permissions:
- **Files**: 644
- **Folders**: 755
- **Uploads folder**: 755

## 🧪 Step 6: Testing

### Test Your Application:
1. Visit: `https://yourdomain.com/query-desk/`
2. Test staff login
3. Test ticket creation
4. Test all features

### Common Issues & Solutions:

| Issue | Solution |
|-------|----------|
| **Database Connection Error** | Check database credentials in `config.php` |
| **500 Internal Server Error** | Check file permissions and PHP version |
| **404 Not Found** | Verify file paths and .htaccess |
| **Upload Failed** | Check uploads folder permissions |

## 📊 Performance Optimization

### Enable in cPanel:
- ✅ **Gzip Compression**
- ✅ **Browser Caching**
- ✅ **PHP OPcache**
- ✅ **MySQL Query Cache**

### File Optimization:
- Compress CSS/JS files
- Optimize images
- Enable CDN (if available)

## 🔄 Maintenance

### Regular Tasks:
- [ ] Database backups (weekly)
- [ ] File backups (monthly)
- [ ] Security updates
- [ ] Performance monitoring

### Backup Locations:
- **Database**: phpMyAdmin export
- **Files**: cPanel backup or FTP download

## 📞 Support

### Hosting Support:
- **Hostinger**: 24/7 Live Chat
- **HostGator**: 24/7 Phone & Chat
- **Bluehost**: 24/7 Phone & Chat

### Application Issues:
- Check error logs in cPanel
- Verify database connectivity
- Test individual API endpoints

## 🎯 Success Checklist

- [ ] Website loads without errors
- [ ] Staff can login successfully
- [ ] Tickets can be created
- [ ] All features working
- [ ] SSL certificate active
- [ ] Performance optimized
- [ ] Regular backups scheduled

---

## 🚨 Important Notes

1. **Never share** your database credentials
2. **Always backup** before making changes
3. **Test thoroughly** before going live
4. **Monitor** your website regularly
5. **Keep** PHP and MySQL updated

## 💰 Cost Breakdown

| Item | Cost | Frequency |
|------|------|-----------|
| **Hosting Plan** | $2.99-5.99 | Monthly |
| **Domain** | $10-15 | Yearly |
| **SSL Certificate** | FREE | Yearly |
| **Total** | **$3-7/month** | **Monthly** |

---

**Your Query Desk application will be live at: `https://yourdomain.com/query-desk/`** 🎉✨

**Need help with any specific step? Contact your hosting provider's support team!** 📞🤝
