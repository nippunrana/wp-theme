---
description: Fix file ownership and permissions for the WordPress themes directory
---

# Fix File Ownership & Permissions

Restores the target state in `.agent/rules/file-ownership.md`: owner `ubuntu`, group `www-data`, group-writable, setgid folders. Never touches `.git`. Replace `<domain>` with the site you are working on.

## Steps

// turbo-all

1. Fix ownership, group write, and setgid on the EgniTech folders and repo files that exist:
```bash
cd /var/www/<domain>/html/wp-content/themes && P=$(ls -d egnitech-one egnitech-one-child .gitignore CLAUDE.md README.md 2>/dev/null) && sudo chown -R ubuntu:www-data $P && sudo chmod -R g+w $P && sudo find $P -type d -exec chmod g+s {} + && sudo chmod g+s .
```

2. Verify — this must print only `locked`:
```bash
cd /var/www/<domain>/html/wp-content/themes && P=$(ls -d egnitech-one egnitech-one-child .gitignore CLAUDE.md README.md 2>/dev/null); sudo -u www-data find $P ! -writable; sudo -u www-data test -w .git && echo "WARNING: .git is writable by www-data" || echo locked
```
