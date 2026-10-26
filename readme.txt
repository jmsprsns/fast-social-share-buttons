=== Fast Social Share Buttons ===
Contributors: contentpowered
Author: Content Powered
Author URI: https://www.contentpowered.com/wordpress-plugins/
Plugin URI: https://www.contentpowered.com
Tags: social share, share buttons, social sharing, floating share bar, click to tweet
Requires at least: 6.3
Tested up to: 7.1
Requires PHP: 7.2
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Lightweight social share buttons that load only where they're used: inline, floating sidebar and mobile bar, with near-zero PageSpeed impact.

== Description ==

Share buttons that don't cost you PageSpeed points. Nothing loads on pages without buttons; on pages with them you get about 4 KB (gzipped) of inlined CSS and deferred JavaScript, with no third-party requests and no layout shift.

= Features =

* 22 networks: Facebook, X, LinkedIn, Pinterest, Reddit, WhatsApp, Telegram, Bluesky, Threads, Mastodon, Tumblr, Hacker News, Flipboard, Buffer, VK, XING, LINE, SMS, Email, Copy link, Print and More (the device's own share menu). Pick them and drag them into order.
* Above content, below content and floating, chosen per content type (posts, pages and custom post types).
* A floating sidebar on wide screens and a bottom share bar on phones, shown while the in-content buttons are out of view.
* Equal-width buttons that fill the row, with labels when they fit and icons when they don't. Or fixed-width buttons aligned left, centre or right.
* Square, rounded, pill or circle buttons; brand, grey, dark or outline colours; three sizes.
* A block, shortcode, classic widget and template tag for placing buttons anywhere.
* Click to tweet quote boxes.
* An optional Pinterest "Save" button on images, plus a per-post Pinterest image and description.
* Per-post options: hide the buttons or set custom share text.
* Optional UTM tracking and "via @username" for X.
* Handles Social Warfare's [social_warfare] and [clickToTweet] shortcodes, so old posts keep working after switching.

Find it under Content Powered > Fast Social Share Buttons in the admin menu.

Found a bug or have an idea? Use Content Powered > Report a Bug or Content Powered > Submit Feedback to send it straight to us.

= Shortcodes =

`[fast_social_share networks="facebook,x,linkedin" shape="pill" align="center"]`

Options: networks, shape (square, rounded, pill, circle), colors (brand, hover, outline, mono), size (s, m, l), labels (auto, never), align (stretch, left, center, right), layout (row, column) and heading.

`[fast_click_to_tweet tweet="What gets posted"]The quote shown on the page[/fast_click_to_tweet]`

= Credits =

* Brand icons by [Simple Icons](https://simpleicons.org), CC0 1.0. Brand names and logos are trademarks of their respective owners.
* Icons by [Lucide](https://lucide.dev), used under the ISC License. Icons derived from [Feather](https://feathericons.com) are under the MIT License. The full license text is in `icons/LICENSE`.
* Updates delivered by [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker) by Janis Elsts, MIT License.

== Installation ==

1. Upload the `fast-social-share-buttons` folder to `/wp-content/plugins/`.
2. Activate the plugin through the Plugins screen in WordPress.
3. Go to Content Powered > Fast Social Share Buttons to pick networks and placements.

Updates arrive through the normal WordPress Updates screen, and you can turn on auto-updates from the Plugins screen.

== Frequently Asked Questions ==

= Does it show share counts? =

No. Counts need calls to each network's API (several no longer offer one) and extra database work, which is the opposite of what this plugin is for.

= Will it slow my site down? =

Pages without buttons get nothing at all. Pages with buttons get about 1.8 KB of inlined CSS, a 2.4 KB deferred script (both gzipped) and a small SVG sprite. The floating buttons are only built when they're about to appear.

= Can I hide the buttons on one post? =

Yes. Tick "Hide share buttons" in the Social Sharing box on the post's edit screen.

= Does it work with caching plugins? =

Yes. There's nothing per-visitor in the markup, so cached pages work as they are.

== Changelog ==

See https://github.com/jmsprsns/fast-social-share-buttons/releases for the full release history.
