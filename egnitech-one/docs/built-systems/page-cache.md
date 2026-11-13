# Page cache and purging

Code: `inc/fastcgi-cache-purger.php` (purging, the admin-bar menu, cache size) and the **FastCGI Cache** tab in `inc/admin-options.php`. The cache itself is nginx's `fastcgi_cache`. It is set up in each site's nginx config, outside this repo.

## What the theme does and does not do

- **The theme never caches anything. It only deletes nginx's cache files.** The **FastCGI Cache** switch in Theme Options does not turn caching on or off. It tells the theme that nginx is caching, so content changes should purge.
- **One copy of a page is served to every logged-out visitor.** **Never** render anything per visitor in PHP (cart count, greeting, chosen colour scheme, recently viewed, an A/B variant): the first visitor's version is stored and served to everyone. Per-visitor state belongs in the browser. The dark-mode script and the add-to-cart cookie both work this way.
- **A content change purges three URLs only:** the changed post's own address, the home page, and its parent's address (`egnitech_one_purge_post_on_transition`). Category, tag, shop, search, author and paginated pages, and other pages that list the changed post, are **not** purged. They show the old content until nginx's own expiry (`fastcgi_cache_valid` in the site's config).
- **Nothing is purged when** a comment or product review is approved, or when stock or price changes without the product being saved (an order reducing stock, for example). A menu, template or template part saved in the Site Editor clears the home page only, because those are posts too.
- **Theme Options purges the whole site only when the Dark Mode switch changes.** Every other setting (header, logo, footer text, custom scripts, sticky header, gallery layout) leaves cached pages as they were. On a site with a Global Styles record, any save also clears the home page as a side effect of the width sync (`theme-options.md`), which makes a change look live when it is not. After changing a setting that alters page HTML, use **FastCGI Cache > Clear Entire Cache** in the admin bar.

## Traps

- **The purger rebuilds nginx's cache key by hand** (`egnitech_one_purge_url`): scheme, `GET`, host and path, hashed, in a `levels=1:2` folder layout. If a server's `fastcgi_cache_key` or `levels` differs, per-URL purging finds no file and **fails silently**: nothing checks its return value. Change the nginx key and this function together.
- **Purging works only as the web server's user.** The cache files belong to the user nginx and PHP-FPM run as. WP-CLI run as the SSH login cannot delete them, so `wp post update …` or `wp option update egnitech_one_dark_mode_enabled …` changes the database and leaves every cached page stale. Clear the cache from the admin bar after any such command.
- **`http_purge` mode sends a `PURGE` request to the page's own address.** The nginx example printed on the settings screen handles a `/purge/…` path instead, which the code never requests. Following the screen does not make this mode work. `filesystem` is the mode the code and the screen agree on.
- **Clear Entire Cache matches files by finding the site's hostname anywhere in the cache key** (`egnitech_one_purge_all_site_cache`). In a cache folder shared by several sites, a hostname that sits inside another's (`example.com` inside `shop.example.com`) deletes both. **Never** point two sites at one cache folder; the default path already assumes one folder per site.
- **Clear Entire Cache ignores the FastCGI Cache switch; per-URL purging obeys it.** With the switch off, content edits purge nothing, but a Dark Mode change still empties the folder.
- **A blank Cache Directory Path does not mean "use the default".** The default applies only while the setting has never been saved. A saved empty value makes every purge answer "Cache directory does not exist".
- **The cache size is counted by opening every file in the cache folder.** Any purge throws the stored figure away, so the next page an administrator loads, front end or admin, recounts it during that page load. On a large cache, this is why the first admin page after publishing is slow.

## When a change "is not showing"

1. Check it logged out. On the EgniTech servers nginx skips the cache for logged-in users and for POST requests, so an editor always sees fresh pages.
2. Read the response header nginx adds there: `curl -sI <url>` shows `X-FastCGI-Cache` as `HIT`, `MISS` or `BYPASS`.
3. A stylesheet or script edit that is not showing is this same cache: the stored page still links to the file's old address (`assets-and-versioning.md`).
