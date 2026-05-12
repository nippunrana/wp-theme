---
trigger: always_on
description: Ensure correct file ownership for Nginx/PHP-FPM compatibility
---

# File Ownership Rule (DYNAMIC)

This server runs **Nginx + PHP-FPM**, both operating under the `www-data:www-data` user/group.

Since the agent connects via **root SSH**, any files created or modified will default to `root:root` ownership. This **breaks WordPress functionality** (e.g., updates, media uploads, plugin settings) and may cause 403 Forbidden or 500 Internal Server Errors.

## Mandatory Steps

**After creating, modifying, or moving ANY file or directory**, you MUST immediately restore correct ownership.

Run this command for the affected path(s):

```bash
chown -R www-data:www-data <absolute-path-to-affected-item>
```

### Permissions Reference
- **Directories**: `755` (`drwxr-xr-x`)
- **Files**: `644` (`-rw-r--r--`)

If you suspect permissions are incorrect (e.g., a new file is not world-readable), also run:
```bash
# For directories
find <path> -type d -exec chmod 755 {} \;
# For files
find <path> -type f -exec chmod 644 {} \;
```

## Environment Specs
- **Web User/Group**: `www-data:www-data`
- **Target Ownership**: Always `www-data:www-data` for all files in web roots (`/var/www/`).
- **Context**: This rule applies to any WordPress site or web application on this server.
