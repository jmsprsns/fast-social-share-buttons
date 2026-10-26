# Fast Social Share Buttons

Lightweight social share buttons that load only where they're used: inline, floating sidebar and mobile bar, with near-zero PageSpeed impact.

By [Content Powered](https://www.contentpowered.com/wordpress-plugins/).

## Features

- **22 networks**: Facebook, X, LinkedIn, Pinterest, Reddit, WhatsApp, Telegram, Bluesky, Threads, Mastodon, Tumblr, Hacker News, Flipboard, Buffer, VK, XING, LINE, SMS, Email, Copy link, Print and More (the phone's or browser's own share menu). Tick the ones you want and drag them into order.
- **Placements per content type**: above content, below content and floating, chosen separately for posts, pages and any public custom post type.
- **Floating sidebar** (left or right) on wide screens and a **bottom share bar** on phones and tablets. By default they only appear while the in-content buttons are scrolled out of view.
- **Smart, equal-width buttons**: in-content buttons share the row equally (three buttons get thirds, six get sixths). Labels show only when every label fits; otherwise all buttons switch to icons. Buttons wrap into equal-width rows only when even icons won't fit. Or pick left, centre or right for fixed-width buttons sized to the longest label.
- **Styles**: square, rounded, pill or circle; brand colours, grey or dark with brand colour on hover, or outline; three sizes; network names or action labels (Share, Post, Pin); optional heading.
- **Block, shortcode, widget and template tag** for placing buttons anywhere.
- **Click to tweet** quote boxes.
- **Pinterest "Save" button** on images (optional), with a per-post Pinterest image and description.
- **Per-post options**: hide the buttons, custom share text, Pinterest image and description.
- **UTM tracking** on shared links (optional) and **via @username** for X.
- **Social Warfare compatibility**: handles `[social_warfare]` and `[clickToTweet]`, so old posts keep working after switching.

Settings live under **Content Powered > Fast Social Share Buttons**, with a live preview.

## Performance

Measured with Lighthouse (mobile) on a long test post in Twenty Twenty-Five: the performance score, LCP, TBT and CLS were the same with the plugin on and off (CLS 0, accessibility 100). What it adds to a page that shows buttons:

| | |
|---|---|
| CSS | 1.8 KB gzipped, inlined in `<head>`: no request, nothing render-blocking |
| JavaScript | 2.4 KB gzipped, one deferred file, no jQuery or other dependencies |
| Icons | One inline SVG sprite with only the networks in use |
| Third parties | None: no tracking scripts, no share-count APIs, no fonts |

How it stays that light:

- **Nothing loads on pages without buttons.** The CSS and script are only added to pages that show buttons (from the placement settings, or a block, shortcode or widget on the page).
- **The floating sidebar and mobile bar are lazy.** They arrive as inert `<template>`s and are only built the first time they need to show, so they add nothing to the initial DOM, layout or paint.
- **No layout shift.** In-content buttons are sized by the CSS grid before any script runs. The label check only swaps labels for icons inside buttons whose size is already fixed.
- **Share links are plain links.** They work before (or without) JavaScript; the script only upgrades them to centred popups on desktop.
- **No share counts**, so no API calls, no cron jobs and no extra database queries.
- **Accessible colours.** Brand colours are darkened where needed so white labels pass WCAG AA (4.5:1) contrast.

## Manual placement

Put buttons exactly where you want them, alongside the automatic placements or instead of them (untick a content type in the placement grid to place its buttons only by hand). Options you leave out come from the settings. The same reference, with copy buttons, is on the settings page under **Placement > Manual placement**.

**Block**: add "Social Share Buttons" to a post, template or widget area.

**Shortcode**:

```
[fast_social_share networks="facebook,x,linkedin" shape="pill" align="center"]
```

Options: `networks` (comma-separated), `shape` (square, rounded, pill, circle), `colors` (brand, hover, outline, mono), `size` (s, m, l), `labels` (auto, never), `align` (stretch, left, center, right), `layout` (row, column) and `heading`.

**Click to tweet**:

```
[fast_click_to_tweet tweet="What gets posted"]The quote shown on the page[/fast_click_to_tweet]
```

**Template tag**:

```php
<?php echo fast_social_share_buttons_render( array( 'layout' => 'column' ) ); ?>
```

**Classic widget**: "Social Share Buttons", horizontal or stacked, for sites still on the classic widgets screen.

## For developers

| Filter | What it does |
|---|---|
| `fast_social_share_buttons_networks` | Add, remove or relabel networks (name, action, title, colour, URL template, type, icon) |
| `fast_social_share_buttons_placements` | Change where buttons appear on a given post |
| `fast_social_share_buttons_share_data` | Change the URL, title, text, description or image a post is shared with |
| `fast_social_share_buttons_html` | Change the markup of a button group |

After editing `assets/front.css` or `assets/front.js`, rebuild the minified copies with `bin/build.sh` (needs Node; `npx` fetches esbuild). Define `SCRIPT_DEBUG` to load the unminified files.

## Automatic updates

Updates come from https://github.com/jmsprsns/fast-social-share-buttons through the bundled [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker) (PUC). Every 12 hours each site checks the repo's latest GitHub release. When there's a newer version, it appears in Dashboard > Updates like any other plugin, with one-click updates and auto-updates.

The repo URL is the `Update URI` header in `fast-social-share-buttons.php`. That header also stops WordPress.org from offering a same-slug plugin as an "update" that would overwrite this one.

### Releasing an update

1. Bump `Version:` in the `fast-social-share-buttons.php` header. It's the only place the version lives.
2. Commit and push to `main`.
3. Publish a release whose tag matches that version:

   ```bash
   gh release create v1.0.1 --title 1.0.1 --generate-notes
   ```

Sites pick it up on their next check. To check right away, use "Check for updates" on the plugin's row in the Plugins screen, or the button on its settings page. The release notes become the changelog in the "View details" popup. The description, requirements and "Tested up to" come from `readme.txt`.

If the tag and the `Version:` header disagree, sites will keep being offered the same update, so bump the header before you tag.

If a repo has no releases, PUC falls back to the highest version tag, then to the `Version:` header on `main`.

### Development copies

Installing an update replaces the whole plugin folder, so update checks are off in any copy with a `.git` folder. That stops an update from wiping your working tree. To test updates in a checkout anyway, or to turn them off somewhere without git, add this to `wp-config.php`:

```php
define( 'FAST_SOCIAL_SHARE_BUTTONS_DEV_MODE', false ); // false = check for updates, true = never check
```

### Private repos

Public repos need nothing extra. For a private repo, create a fine-grained GitHub token limited to that one repo with **Contents: Read-only** access, and add it to each site's `wp-config.php`:

```php
define( 'FAST_SOCIAL_SHARE_BUTTONS_GITHUB_TOKEN', 'github_pat_...' );
```

Never put a token in the plugin's own code: every site that installs the plugin would get a copy.

Unauthenticated GitHub API requests are limited to 60 an hour per server IP. Each check makes a few requests every 12 hours, so this only matters when many sites share one IP. A token raises the limit.

## Structure

```
fast-social-share-buttons.php                   Plugin header, constants, boot, template tag
uninstall.php                                   Deletes settings, widget settings and per-post options
readme.txt                                      WordPress readme; feeds the "View details" popup
includes/
  class-fast-social-share-buttons-plugin.php    Main singleton, settings and choices
  class-fast-social-share-buttons-networks.php  Networks: names, colours, share links, inline icons
  class-fast-social-share-buttons-renderer.php  Button markup for every placement
  class-fast-social-share-buttons-frontend.php  Placement decisions and on-demand asset loading
  class-fast-social-share-buttons-shortcodes.php  [fast_social_share], [fast_click_to_tweet], Social Warfare tags
  class-fast-social-share-buttons-block.php     The Social Share Buttons block
  class-fast-social-share-buttons-widget.php    Classic widget
  class-fast-social-share-buttons-meta-box.php  Per-post Social Sharing box
  class-fast-social-share-buttons-admin.php     Settings page and Plugins-screen link
  class-fast-social-share-buttons-menu.php      Shared top-level "Content Powered" menu
  class-fast-social-share-buttons-feedback.php  Shared Submit Feedback and Report a Bug pages
  class-fast-social-share-buttons-updater.php   GitHub update checks (PUC wiring)
  class-fast-social-share-buttons-icons.php     Inline Lucide icon helper (admin)
blocks/share-buttons/                           block.json and editor script (no build step)
assets/
  front.css, front.js                           Front-end source
  front.min.css, front.min.js                   Built copies that ship (bin/build.sh)
  admin.css, admin.js                           Settings page only
  meta-box.js                                   Pinterest image picker on post edit screens
  icon.svg                                      Icon on the update screens
  content-powered.svg                           Sumo logo for the Content Powered menu
icons/                                          The Lucide icons the settings page uses (plus LICENSE)
plugin-update-checker/                          Plugin Update Checker 5.7 library
bin/build.sh                                    Rebuilds the minified assets (not shipped)
```

## The Content Powered menu

Every Content Powered plugin puts its settings page under one shared top-level **Content Powered** menu, so several installed plugins line up as Content Powered > Fast Social Share Buttons, Content Powered > Link Fixer, and so on.

Each plugin carries the same `class-fast-social-share-buttons-menu.php`. Whichever plugin loads first registers the menu (slug `content-powered`, sumo logo from `assets/content-powered.svg`); the others find it already there and only add their own page. The top-level link opens the first plugin's page. Keep the slug as it is, or plugins made from older copies would end up in a second menu.

## Feedback and bug reports

Every plugin also carries `class-fast-social-share-buttons-feedback.php`, which adds **Submit Feedback** and **Report a Bug** to the bottom of the Content Powered menu. The links appear once however many Content Powered plugins are active: each plugin adds itself to a shared list, and only the newest copy of the class (by its `VERSION` constant, the first loaded on a tie) adds the pages. With more than one plugin active, the form asks which plugin the message is about.

Messages go from the site's server to `https://www.contentpowered.com/wp-json/contentpowered/v1/plugin-feedback/` and show up on contentpowered.com under Our Plugins > Bug Reports and Our Plugins > Feedback. Each message carries a single-use secret, and contentpowered.com fetches its hash back from the site's `admin-ajax.php` to confirm the site really sent it. A site can send one message every 24 hours, and the page shows how long until the next one. The sidebar lists exactly what gets sent.

When you change the file, bump `VERSION` and copy it into the other plugins (with the names swapped), so the newest copy is the one that runs. To send messages to a local copy of contentpowered.com instead, add this to `wp-config.php`:

```php
define( 'CONTENT_POWERED_FEEDBACK_ENDPOINT', 'http://contentpowered/wp-json/contentpowered/v1/plugin-feedback/' );
```

## Credits and licenses

This plugin uses these third-party assets under their own licenses:

- **[Simple Icons](https://simpleicons.org) brand icons** (inlined in `class-fast-social-share-buttons-networks.php`): CC0 1.0 (public domain). Brand names and logos are trademarks of their respective owners. Using one doesn't imply endorsement.
- **[Lucide](https://lucide.dev) icons** (`icons/`, and the email, link, print, message and share icons in the network list): ISC License, Copyright (c) 2026 Lucide Icons and Contributors. Icons derived from [Feather](https://feathericons.com) are under the MIT License, Copyright (c) 2013-present Cole Bemis. Keep `icons/LICENSE` with the plugin.
- **[Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker)** (`plugin-update-checker/`): MIT License, Copyright (c) Janis Elsts. See `plugin-update-checker/license.txt`.

The plugin itself is licensed under GPL-2.0-or-later. See `LICENSE`.
