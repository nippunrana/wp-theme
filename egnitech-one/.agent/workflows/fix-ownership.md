---
description: Fix file ownership and permissions for the WordPress themes directory
---

# Fix File Ownership & Permissions

Run these commands to fix ownership and permissions for all theme files so that Nginx and PHP-FPM (`www-data`) can properly serve and manage them.

## Steps

// turbo-all

1. Fix ownership of the entire themes directory:
```bash
chown -R www-data:www-data /var/www/dosiqai.com/html/wp-content/themes/
```

2. Fix directory permissions (755):
```bash
find /var/www/dosiqai.com/html/wp-content/themes/ -type d -exec chmod 755 {} \;
```

3. Fix file permissions (644):
```bash
find /var/www/dosiqai.com/html/wp-content/themes/ -type f -exec chmod 644 {} \;
```

4. Verify the fix:
```bash
ls -la /var/www/dosiqai.com/html/wp-content/themes/
```
