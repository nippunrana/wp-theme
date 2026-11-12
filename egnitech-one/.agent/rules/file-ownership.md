---
trigger: always_on
description: File ownership and permissions for theme files on the VPS
---

# File Ownership Rule

The agent logs in over SSH as `ubuntu` (with passwordless sudo), **not** `root`. Nginx and PHP-FPM run as `www-data`. Both users must be able to write theme files: `ubuntu` for edits and git, `www-data` for the WordPress dashboard and file-manager plugins (deleting a theme, editing files).

## Target state
- The EgniTech theme folders and the repo files beside them: owner `ubuntu`, group `www-data`, group-writable, setgid on every folder.
- New files from a normal edit or `git pull` land correctly on their own (setgid gives the group, `ubuntu`'s umask `002` gives group write). Nothing to run afterwards.

## Bans
- **Never** `chown -R www-data:www-data` theme files. That was the old root-login rule; it locks `ubuntu` and git out.
- **Never** leave an `ubuntu`-owned theme folder without group write. `www-data` can then no longer delete or replace files inside it, and the dashboard fails silently ("Theme could not be deleted").
- **Never** give `www-data` write access to the repo's `.git` folder. A hook or config entry planted there by a compromised plugin would run as `ubuntu` — and so with sudo — on the next git command.
- **Never** create or edit theme files through `sudo`; they end up owned by `root`.

## Trap
Files and folders created from the WordPress dashboard or a file-manager plugin are owned by `www-data:www-data` without group write. `ubuntu` cannot edit them, and `git pull` fails inside such folders. Repair with the `fix-ownership` workflow (`.agent/workflows/fix-ownership.md`).
