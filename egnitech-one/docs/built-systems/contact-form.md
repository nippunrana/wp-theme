# Contact form

Code: `inc/blocks/contact-form.php` (the block and its markup), `inc/blocks/contact-form/` (its script and styles), `inc/contact-form-submit.php` (checks, saving, email), `inc/contact-messages.php` (the Messages screen and the privacy tools), `patterns/contact-us.php` (the Contact Us page layout), and the **Contact Form** card under Theme Options > Integrations.

## Decisions

- **The form is printed into cached pages. Never add a nonce, a timestamp or anything about the visitor to it.** Every logged-out visitor gets the same copy (`page-cache.md`). A nonce would expire within a day, and from then on every message would be refused. The Turnstile token proves a person sent it. The only part that changes between two builds of a page is a form's sealed address list.
- **The handler checks the fields first and Turnstile last. Never reorder them.** Checking the token uses it up, so a typo reported after that would make the visitor's corrected retry fail. For the same reason the script resets the widget after every answer except a field error (`turnstile.md`).
- **It posts to `admin-post.php`, not the REST API.** That works without JavaScript, and security plugins that block the REST API for visitors do not affect it. After a deploy, send a test message from the live site while logged out. A server rule that guards `/wp-admin/` (basic auth, an IP allow-list) blocks every message, and nothing in the theme can detect that.
- **Messages are saved as `private`, never `publish`.** The page-cache purger acts on any post type entering `publish`, so publishing would clear the home page on every message.
- **Never send anything to the visitor's address, and never take a recipient from the request.** Do not add an auto-reply, a "send me a copy" option or a Cc. Bots use them to send spam through the site. The visitor goes in Reply-To, as the address alone: `wp_mail()` splits Reply-To at commas, and a name can contain one. Mail goes out from the site's own address (From Email under SMTP Settings), never as the visitor: that fails the receiving server's DMARC check and lands in spam.
- **A block's own "Send messages to" addresses are encrypted into the page together with the page's ID, and are used only while that page is published.** The encryption keeps the addresses out of the page source. The page check stops an address sealed in an editor preview (page 0) or on a draft from mailing anyone. New salts only make older pages fall back to the Theme Options list.
- **A message is saved before it is emailed, and the visitor is told it was sent once it is saved**, even if the email then fails. The Messages list shows the failure and its reason. Nothing else records a failed send (`smtp.md`).
- **Spam:**
  - A filled-in trap field gets the normal thank-you, and nothing is saved.
  - A match in Disallowed Comment Keys (Settings > Discussion) is saved but not emailed.
  - Three or more links are still emailed, with "[Possible spam]" in the subject. **Never** hold back the email on the link count alone: customers paste product links.
- **No per-IP limit.** It is not known whether `REMOTE_ADDR` on these servers is the visitor or a proxy such as Cloudflare. A limit on a proxy's address would lock everyone out. **Never** add one until nginx restores the visitor's address (`set_real_ip_from` with `real_ip_header CF-Connecting-IP`). **Never** read `CF-Connecting-IP` or `X-Forwarded-For` in PHP: anyone who reaches the server directly can forge them.
- **With Turnstile off, only the trap field and Disallowed Comment Keys stop bots.** `egnitech_one_turnstile_verify()` returns true while Turnstile is off.
- **Messages are for administrators only.** Every capability of the post type maps to `manage_options`. **Never** fall back to `capability_type => 'post'`: Editors and Shop Managers can read private posts, and would see every visitor's details.
- **Every form requires the consent box** (owner's decision, 19 Nov 2026). The statement the visitor ticked is saved with the message.

## Traps

- **Inside the inserted Contact Us layout, the form's settings appear only after Edit pattern.** WordPress 7.0 makes an inserted theme pattern content-only, and a block registered from PHP alone shows no settings there. On its own, the block shows them.
