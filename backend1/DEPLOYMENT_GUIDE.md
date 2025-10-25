# Deployment Guide - School Management System Backend

## Prerequisites

Before deployment, ensure you have:

1. **PHP 7.4+** or **PHP 8.x** installed
2. **MySQL 5.7+** or **MariaDB** running
3. **Composer** package manager
4. **Web Server** (Apache/Nginx) or use PHP's built-in server for development

## Step 1: Install Dependencies

```bash
cd backend1
composer install
```

## Step 2: Configure Environment

1. Copy the environment file:
```bash
cp .env.example .env
```

2. Edit `.env` with your database credentials:
```env
# Database Configuration
DB_HOST=localhost
DB_PORT=4306
DB_NAME=school_management
DB_USER=root
DB_PASS=1212
DB_CHARSET=utf8mb4

# JWT Configuration
JWT_SECRET=your-secret-key-change-this-in-production
JWT_EXPIRY=86400

# CORS Settings
CORS_ORIGIN=http://localhost:3000
```

**IMPORTANT:** Change `JWT_SECRET` to a strong random string in production!

## Step 3: Setup MySQL Database

### Option A: Using XAMPP (Windows)

1. **Start XAMPP Control Panel**
2. **Start MySQL** on port 4306 (or configure your MySQL port)
3. **Create Database:**
   - Open phpMyAdmin
   - Create a new database named `school_management`
   - Or use command line:

```bash
mysql -h localhost -P 4306 -u root -p -e "CREATE DATABASE school_management;"
```

4. **Import Schema:**
```bash
mysql -h localhost -P 4306 -u root -p school_management < database/schema.sql
```

### Option B: Using MySQL Command Line

```bash
# Create database
mysql -h localhost -P 4306 -u root -p1212 -e "CREATE DATABASE school_management;"

# Import schema
mysql -h localhost -P 4306 -u root -p1212 school_management < database/schema.sql
```

### Option C: Using Windows Batch Script

Simply run:
```bash
setup.bat
```

This will automatically:
- Install composer dependencies
- Create .env file
- Create database
- Import schema

## Step 4: Verify Database Setup

Connect to MySQL and verify tables were created:

```bash
mysql -h localhost -P 4306 -u root -p1212 school_management -e "SHOW TABLES;"
```

You should see 15 tables:
- admins
- students
- teachers
- classes
- subjects
- academic_years
- student_enrollments
- teacher_assignments
- exams
- exam_results
- grades
- attendance
- fees_payments
- notices
- complaints

## Step 5: Start the Backend Server

### Development Mode

Using PHP's built-in server:
```bash
php -S localhost:8080 -t public
```

Or using Composer:
```bash
composer start
```

The API will be available at: `http://localhost:8080/api`

### Production Mode (Apache)

1. **Configure Apache Virtual Host:**

```apache
<VirtualHost *:80>
    ServerName school-api.yourdomain.com
    DocumentRoot "C:/path/to/backend1/public"

    <Directory "C:/path/to/backend1/public">
        AllowOverride All
        Require all granted

        # Enable URL rewriting
        RewriteEngine On
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^ index.php [QSA,L]
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/school-api-error.log
    CustomLog ${APACHE_LOG_DIR}/school-api-access.log combined
</VirtualHost>
```

2. **Create .htaccess in public folder:**

```apache
RewriteEngine On

# Redirect to front controller
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^ index.php [QSA,L]
```

3. **Restart Apache**

### Production Mode (Nginx)

1. **Configure Nginx:**

```nginx
server {
    listen 80;
    server_name school-api.yourdomain.com;
    root /path/to/backend1/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

2. **Restart Nginx**

## Step 6: Test the API

### Quick Test

1. **Test API Availability:**
```bash
curl http://localhost:8080/api/admin/register
```

2. **Register Test Admin:**
```bash
curl -X POST http://localhost:8080/api/admin/register \
  -H "Content-Type: application/json" \
  -d '{
    "school_name": "Test School",
    "email": "admin@test.com",
    "password": "password123"
  }'
```

3. **Login:**
```bash
curl -X POST http://localhost:8080/api/admin/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@test.com",
    "password": "password123"
  }'
```

Save the `token` from the response.

4. **Test Protected Route:**
```bash
curl -X GET http://localhost:8080/api/admin/profile \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Using the Test Script

Windows:
```bash
test_api.bat
```

## Step 7: Configure Frontend

Update your frontend `.env` file:

```env
REACT_APP_BASE_URL=http://localhost:8080/api
```

For production:
```env
REACT_APP_BASE_URL=https://api.yourdomain.com/api
```

## Common Issues and Solutions

### Issue 1: Database Connection Failed

**Error:** `SQLSTATE[HY000] [2002] Connection refused`

**Solution:**
- Verify MySQL is running
- Check DB_HOST and DB_PORT in `.env`
- Verify credentials

**Test connection:**
```bash
mysql -h localhost -P 4306 -u root -p1212
```

### Issue 2: CORS Errors

**Error:** `Access-Control-Allow-Origin` error in browser

**Solution:**
- Update `CORS_ORIGIN` in `.env` to match your frontend URL
- Restart the backend server

### Issue 3: Token Invalid/Expired

**Error:** `Invalid or expired token`

**Solution:**
- Ensure token is being sent in `Authorization: Bearer <token>` header
- Token expires after 24 hours (configurable in `.env` via `JWT_EXPIRY`)
- Login again to get a new token

### Issue 4: Composer Install Fails

**Error:** Various composer errors

**Solution:**
```bash
# Clear composer cache
composer clear-cache

# Update composer
composer self-update

# Install with verbose output
composer install -vvv
```

### Issue 5: Permission Denied (Linux/Mac)

**Error:** Permission errors

**Solution:**
```bash
# Make storage writable
chmod -R 775 backend1
chown -R www-data:www-data backend1
```

## Security Checklist for Production

- [ ] Change `JWT_SECRET` to a strong random string
- [ ] Set `APP_DEBUG=false` in `.env`
- [ ] Use HTTPS (SSL certificate)
- [ ] Restrict database access to localhost only
- [ ] Use strong database passwords
- [ ] Enable rate limiting
- [ ] Keep dependencies updated: `composer update`
- [ ] Configure firewall to allow only necessary ports
- [ ] Regular database backups
- [ ] Monitor error logs

## Performance Optimization

### 1. Enable OPCache

Edit `php.ini`:
```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
opcache.revalidate_freq=60
```

### 2. Database Indexing

All necessary indexes are already created in the schema, but you can add more if needed:

```sql
-- Example: Add index on frequently queried fields
ALTER TABLE students ADD INDEX idx_admin_class (admin_id, class_id);
```

### 3. Response Caching

Consider implementing caching for:
- Academic year data
- Class listings
- Subject listings

## Monitoring

### Error Logs

Check error logs regularly:
- **Development:** Console output
- **Production:** Check web server error logs

### Database Performance

Monitor slow queries:
```sql
-- Enable slow query log
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 2;
```

## Backup Strategy

### Daily Automated Backup

Create a backup script `backup.bat`:

```batch
@echo off
set BACKUP_DIR=C:\backups\school-management
set TIMESTAMP=%date:~-4,4%%date:~-10,2%%date:~-7,2%_%time:~0,2%%time:~3,2%%time:~6,2%

mkdir "%BACKUP_DIR%" 2>nul

mysqldump -h localhost -P 4306 -u root -p1212 school_management > "%BACKUP_DIR%\backup_%TIMESTAMP%.sql"

echo Backup completed: backup_%TIMESTAMP%.sql
```

Schedule this script to run daily using Windows Task Scheduler.

## Scaling Considerations

For high-traffic scenarios:

1. **Load Balancing:** Use multiple backend instances behind a load balancer
2. **Database Replication:** Set up master-slave replication
3. **Caching Layer:** Implement Redis/Memcached
4. **CDN:** Use CDN for static assets
5. **Database Connection Pooling:** Configure connection pooling

## Support

For issues, refer to:
- `README.md` - General documentation
- `FRONTEND_INTEGRATION.md` - Frontend integration guide
- API Documentation in `README.md`

## Next Steps

After successful deployment:

1. Create your first academic year
2. Set up classes and subjects
3. Register teachers
4. Enroll students
5. Start using the promotion system at end of year

## License

MIT License - See LICENSE file
