# Custom scripts (header and footer code)

Code: `inc/custom-scripts.php` (front-end output), the **Advanced** tab in `inc/admin-options.php`, `assets/js/admin-options.js`. Every snippet is stored in one JSON string, the option `egnitech_one_custom_scripts`.

- **Snippets are printed exactly as typed, unescaped, on every front-end page.** That is the feature. The only gate is at save time (see the permission traps in `theme-options.md`). **Never** fill this option from anything but that screen.
- **"After DOM" and "Delayed" snippets are inserted by JavaScript, not printed in the page.** Use **Normal** for anything that must run before the page paints (consent defaults, anti-flicker snippets), that calls `document.write`, or that carries a `<noscript>` fallback. None of those work when inserted late.
- **"Delayed" is a fixed wait counted from the moment the footer script runs.** It does not wait for the visitor to interact, and it does not wait for the page to finish loading.
- **Snippets of one kind and place are joined into a single block.** Inside a block they keep the order of the list. Across kinds the order is fixed: a Normal snippet always runs before an After DOM one, whatever the list says.
- **Snippets are part of the cached page.** Adding one, editing one or switching one off reaches visitors only as cached pages expire or are cleared (`page-cache.md`).
