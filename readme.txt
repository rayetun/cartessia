=== Cartessia — Accessible Product Blocks for WooCommerce ===
Contributors:      rayetun
Donate link:       https://wise.com/pay/me/mdrayhanu2
Tags:              variation swatches, quick view, wishlist, woocommerce, accessibility
Requires at least: 6.5
Tested up to:      7.1
Requires PHP:      7.4
Stable tag:        1.0.0
License:           GPLv2 or later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Accessible WooCommerce blocks — variation swatches, quick view, product customizer, delivery date picker, and wishlist. WCAG 2.2 AA, no overlay.

== Description ==

🛍️ **Cartessia** adds the interactive **WooCommerce** blocks shoppers expect — **variation swatches**, a **product quick view**, a **live product customizer**, a **delivery date picker**, and a **wishlist** — and builds every one of them to be **keyboard-operable and screen-reader-correct against WCAG 2.2 Level AA** out of the box.

Most third-party swatch, quick-view, and wishlist plugins quietly break keyboard support and mislead screen readers — colour swatches with no name, modals that trap or lose focus, "Add to wishlist" toggles that say nothing when saved. Cartessia is the opposite: **accessible is the definition of done, not a paid tier**. Each block ships several **design presets**, so it looks right with zero custom CSS, and inherits your theme's colours and typography.

Cartessia is built the modern WordPress way — the **Interactivity API**, **Block API v3** (`block.json`), and **server-side rendering**. No jQuery, no framework runtime on your shop pages, and each block's script loads only where it's used. Every block renders on the server and is only *enhanced* by a little JavaScript, so it holds up for screen readers, search engines, and slow connections.

= 🧱 The Blocks =

* 🎨 **Variation Swatches** — turn WooCommerce variation dropdowns into **colour, image, and button swatches**. A real radio-group (arrow-key navigable, selection announced), never colour alone — every swatch keeps an accessible name. It drives WooCommerce's own variation form, so add-to-cart, price, and stock stay correct, and a **no-JS fallback** keeps the native dropdowns working.
* 🪟 **Quick View** — open product details in an **accessible modal** without leaving the page. Built on the native `<dialog>` element: focus moved in and **returned** to the trigger, Escape always closes, background made inert — no focus-trap hacks. Swatches work inside the modal and add-to-cart goes through the WooCommerce **Store API**. Three presets: **Centered, Side, Compact**.
* 🧰 **Live Product Customizer** — let customers **personalise a product** with a custom-text field and priced add-on options, with the **live total announced to screen readers**. Prices are **server-authoritative** (HMAC-signed), so the browser can't tamper with them, and the customer's choices carry through to the **cart, checkout, and the order** (and its emails).
* 📅 **Accessible Delivery Date Picker** — let customers choose a **delivery date** on the product page. A real **ARIA grid calendar** with full keyboard support, disabled dates announced, and locale-aware month and weekday names plus your site's start-of-week — with a native `<input type="date">` fallback. Your rules (**minimum lead time, booking window, disabled weekdays**) are enforced **on the server**, and the chosen date is saved to the cart and the order. Three presets: **Calendar, Dropdown, Popover**.
* 💗 **Wishlist** — a **save-for-later** toggle for any product. It uses `aria-pressed` and changes its name to "Remove …" when saved (**never colour alone**). **Guests** are saved in their browser; **logged-in customers** are saved to their account and follow them across devices. A companion **Wishlist** display block lists saved products with a remove button on each. Three presets: **Heart, Outline, Text**.

Every block on a page shares one Interactivity API store, so selecting a swatch or saving to the wishlist updates everywhere live, without a reload.

= ♿ Accessible By Default =

* ⌨️ **Full keyboard operation** — every control is a real button, radio, or grid cell, reachable and operable without a mouse
* 🔊 **Screen-reader correct** — selections, totals, chosen dates, and saved state are announced; decorative icons are hidden
* 🎯 **Target sizes** — interactive controls meet the WCAG 2.2 **24×24px** minimum
* 🌗 **Respects the visitor** — honours `prefers-color-scheme` (dark mode) and `prefers-reduced-motion`
* 👁️ **Visible focus** — a clear focus ring on every interactive element
* 🌐 **RTL** — full right-to-left layout support

= 🔒 Trustworthy Commerce =

* 🧾 **Server-authoritative pricing** — custom-option prices and delivery rules are **HMAC-signed**; the server re-validates every add-to-cart, so a customer can't forge a price or bypass a delivery rule from the browser
* 🏬 **HPOS-safe** — reads and writes everything through **WooCommerce CRUD** and declares High-Performance Order Storage compatibility; it never touches order tables directly
* ⚡ **Cache-friendly** — personalised state (like wishlist membership) hydrates in the browser, so nothing user-specific is baked into cached HTML
* 🚫 **No data collection** — no telemetry, no phone-home, no account, no external services

= 🚫 What Cartessia Will Not Do =

* ❌ **No accessibility overlay** — no floating widget, no "profiles". Cartessia builds correct markup instead of layering a widget over broken markup
* ❌ **No fake urgency** — no invented "12 people are viewing this" or countdown scarcity in the free blocks
* ❌ **No jQuery bloat** — built on core APIs, with per-block scripts that load only where used
* ❌ **No lock-in** — deactivate and your products, orders, and media are untouched

Want accessible blocks for the *rest* of your site — modals, sliders, tables, mega menus, and a full accessibility toolkit? See the sibling plugin **Rayetun Accessible Blocks & Accessibility Toolkit**.

== Installation ==

1. Make sure **WooCommerce** is installed and active — Cartessia lists it as a required plugin.
2. Upload the `cartessia` folder to `/wp-content/plugins/`, or install via **Plugins → Add New**.
3. Activate **Cartessia** through the **Plugins** screen.
4. Add any Cartessia block from the block inserter — look for the **Cartessia** category — on a product page, shop template, or any post or page.
5. For colour and image swatches, set them once per attribute value at **Products → Attributes → (your attribute) → Configure terms**, then edit a term (e.g. *Black*, *Sky*) and use the **Swatch colour** / **Swatch image** fields.

No configuration is required to get started.

== Frequently Asked Questions ==

= 🛒 Does Cartessia require WooCommerce? =

Yes. Cartessia is declared as dependent on WooCommerce. If WooCommerce is not active, Cartessia's blocks stay hidden and it never causes a fatal error.

= ♿ Is it really accessible, and to which WCAG version? =

Yes — **WCAG 2.2 Level AA** is the target for every block: keyboard operation, focus management, screen-reader announcements, visible focus, and 24×24px target sizes. Automated checks are a floor, not a certificate, so each block is also exercised with a real screen reader. No plugin can make an entire *site* compliant — your content and design decide most of that — but Cartessia owns the part a plugin can: correct, accessible interactive components.

= 🎨 How do I set swatch colours and images? =

Swatches read from the **attribute term**, not the product. Go to **Products → Attributes**, click **Configure terms** next to your attribute (e.g. *Color*), then **Edit** a term such as *Black* or *Sky*. You'll find a **Swatch colour** picker and a **Swatch image** media field there. An image takes precedence over a colour; leave both empty for a text/button swatch.

= 🧩 Which products does each block work with? =

Variation Swatches and Quick View are for **variable products** (they use your global product attributes). The Live Product Customizer and Delivery Date Picker are for **simple products**. The Wishlist works with any product.

= 🔒 Can a customer fake the price of a custom option or bypass a delivery rule? =

No. Option prices and delivery rules are **HMAC-signed** and re-validated on the server on every add-to-cart, so values tampered with in the browser are rejected. The server is always the source of truth for price.

= ⚡ Is it fast? What is it built on? =

Built on official core APIs — the **Interactivity API**, **Block API v3** (`block.json`), and **server-side rendering**. There's no jQuery and no extra framework runtime on your visitors' pages, and each block's script loads only where it's used. Because every block is rendered on the server and only enhanced by JavaScript, it works even before that script loads.

= 🌗 Does it support dark mode and reduced motion? =

Yes. Blocks honour the visitor's `prefers-color-scheme` (dark mode) and skip non-essential animation for anyone who requests `prefers-reduced-motion`.

= 💾 Where is wishlist data stored? =

For **guests**, in their own browser (localStorage) — nothing is sent anywhere. For **logged-in customers**, in their WordPress user account, so a wishlist follows them across devices. The two merge when a guest logs in.

= 🏬 Is it compatible with High-Performance Order Storage (HPOS)? =

Yes. Cartessia reads and writes all order data through WooCommerce CRUD and declares HPOS compatibility.

= 📤 Does Cartessia send my data anywhere? =

No. There is no telemetry, no phone-home, no account, and no external services. Everything runs on your own site.

= ♻️ What happens if I uninstall Cartessia? =

Cartessia's blocks stop rendering and its options are removed cleanly. Your products, orders, media, and attribute terms are your own WooCommerce data and are never touched.

= 💬 Where do I get support? =

Post in the [WordPress.org support forum](https://wordpress.org/support/plugin/cartessia/).

== Screenshots ==

1. Variation Swatches on a product — accessible colour, image, and button styles, each with a name.
2. Swatch settings on the attribute term screen — the Swatch colour picker and Swatch image media field.
3. Quick View — the Centered modal preset with working add-to-cart and swatches inside.
4. Quick View design presets — Side and Compact.
5. Live Product Customizer — a custom-text field and priced options with the live total announced.
6. Accessible Delivery Date Picker — the Calendar preset with disabled dates and keyboard focus.
7. Delivery Date Picker design presets — Dropdown and Popover.
8. Wishlist button states — Heart, Outline, and Text presets, with the saved ("Remove") state.
9. Wishlist block — saved products in an accessible grid with a remove button on each.
10. Block settings in the editor — pick a design preset and options for each block.

== Changelog ==

= 🎉 1.0.0 =
* Initial release — five accessible WooCommerce blocks, each to WCAG 2.2 AA with multiple design presets:
* New: 🎨 **Variation Swatches** — colour/image/button swatches with real radio-group semantics, roving-tabindex keyboard support, accessible names (never colour alone), and a no-JS fallback. Colours and images set per attribute term.
* New: 🪟 **Quick View** — an accessible product modal on the native `<dialog>` element (focus moved in and returned, Escape to close, background inert), with in-modal swatches and Store API add-to-cart. Presets: Centered, Side, Compact.
* New: 🧰 **Live Product Customizer** — custom-text and priced add-on options with a screen-reader-announced live total; HMAC-signed, server-authoritative pricing; choices carried into cart, checkout, and the order.
* New: 📅 **Accessible Delivery Date Picker** — an ARIA-grid calendar with a native date-input fallback, full keyboard support, announced disabled dates, locale-aware month/weekday names and start-of-week, and server-enforced lead-time/window/disabled-weekday rules saved to the order. Presets: Calendar, Dropdown, Popover.
* New: 💗 **Wishlist** — an `aria-pressed` save toggle (name changes to "Remove …" when saved) with guest (localStorage) and logged-in (user account) storage, plus a companion Wishlist display block. Presets: Heart, Outline, Text.
* Built on the WordPress Interactivity API, Block API v3, and server-side rendering. HPOS-safe (WooCommerce CRUD only), cache-friendly, RTL-ready, dark-mode and reduced-motion aware. No telemetry and no external services.

== Upgrade Notice ==

= 1.0.0 =
First public release of Cartessia — five accessible WooCommerce blocks (variation swatches, quick view, product customizer, delivery date picker, and wishlist).

== Development ==

Cartessia is open source and built with the official [@wordpress/scripts](https://www.npmjs.com/package/@wordpress/scripts) toolchain. The complete, unminified source for every block ships in the plugin's `src/` directory; the files in `build/` are compiled from it. Nothing is obfuscated.

To build from source:

`npm install && npm run build`

That regenerates everything in `build/`. No build step is needed to *use* the plugin — the compiled output is included.
