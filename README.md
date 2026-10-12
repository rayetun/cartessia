# Cartessia — Accessible Product Blocks for WooCommerce

Accessible‑by‑default WooCommerce blocks — **variation swatches, quick view, live product customizer, delivery date picker, and wishlist** — built on the WordPress **Interactivity API** and server‑side rendering, to **WCAG 2.2 Level AA**.

No jQuery, no framework runtime, no accessibility overlay. Each block ships multiple design presets, is HPOS‑safe (WooCommerce CRUD only), cache‑friendly, and collects no data.

> 🛒 **On WordPress.org:** <https://wordpress.org/plugins/cartessia/>

## The blocks

| Block | What it does |
|-------|--------------|
| 🎨 Variation Swatches | Colour / image / button swatches with real radio‑group semantics and accessible names |
| 🪟 Quick View | Accessible native `<dialog>` modal (Centered / Side / Compact presets) |
| 🧰 Live Product Customizer | Custom text + priced options, screen‑reader‑announced total, HMAC‑signed pricing |
| 📅 Delivery Date Picker | ARIA‑grid calendar, server‑enforced rules, native date‑input fallback |
| 💗 Wishlist | `aria-pressed` toggle + display block; guest (localStorage) and member (user meta) storage |

## Development

Built with the official [`@wordpress/scripts`](https://www.npmjs.com/package/@wordpress/scripts) toolchain.

```bash
npm install      # install dev dependencies
npm run build    # compile src/ → build/
npm run start    # watch mode
npm run plugin-zip  # build a distributable zip
```

The complete, unminified source lives in `src/`; `build/` is compiled from it and committed so the plugin runs without a build step.

## Deployment

Releases publish to the WordPress.org SVN automatically via GitHub Actions:

- **`.github/workflows/deploy.yml`** — on a published GitHub Release, deploys the plugin to SVN `trunk` and creates a `tags/<version>`. Tag the release with the bare version number (e.g. `1.0.1`) matching `readme.txt` **Stable tag** and the plugin header **Version**.
- **`.github/workflows/assets.yml`** — on pushes that change `.wordpress-org/**` or `readme.txt`, updates the live listing's icon, banner, screenshots and readme (no release needed).

Both require the `SVN_USERNAME` and `SVN_PASSWORD` repository secrets (your WordPress.org account). WordPress.org directory assets live in [`.wordpress-org/`](.wordpress-org/).

## License

GPL‑2.0‑or‑later.
