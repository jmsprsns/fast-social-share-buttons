<?php
/**
 * Plugin Name:       Fast Social Share Buttons
 * Plugin URI:        https://www.contentpowered.com
 * Description:       Lightweight social share buttons that load only where they're used: inline, floating sidebar and mobile bar, with near-zero PageSpeed impact.
 * Version:           1.1.0
 * Requires at least: 6.3
 * Requires PHP:      7.2
 * Author:            Content Powered
 * Author URI:        https://www.contentpowered.com/wordpress-plugins/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       fast-social-share-buttons
 * Update URI:        https://github.com/jmsprsns/fast-social-share-buttons
 *
 * @package Fast_Social_Share_Buttons
 */

defined( 'ABSPATH' ) || exit;

// Version and repo are read from the header above, so a release only needs
// "Version" bumped and the GitHub repo lives in one place ("Update URI").
// The Update URI header also stops WordPress.org offering a same-slug plugin
// as an "update" that would overwrite this one.
$fast_social_share_buttons_headers = get_file_data(
	__FILE__,
	array(
		'version' => 'Version',
		'repo'    => 'Update URI',
	)
);

define( 'FAST_SOCIAL_SHARE_BUTTONS_VERSION', $fast_social_share_buttons_headers['version'] );
define( 'FAST_SOCIAL_SHARE_BUTTONS_REPO', $fast_social_share_buttons_headers['repo'] );
define( 'FAST_SOCIAL_SHARE_BUTTONS_FILE', __FILE__ );
define( 'FAST_SOCIAL_SHARE_BUTTONS_DIR', plugin_dir_path( __FILE__ ) );
define( 'FAST_SOCIAL_SHARE_BUTTONS_URL', plugin_dir_url( __FILE__ ) );

unset( $fast_social_share_buttons_headers );

require_once FAST_SOCIAL_SHARE_BUTTONS_DIR . 'includes/class-fast-social-share-buttons-icons.php';
require_once FAST_SOCIAL_SHARE_BUTTONS_DIR . 'includes/class-fast-social-share-buttons-updater.php';
require_once FAST_SOCIAL_SHARE_BUTTONS_DIR . 'includes/class-fast-social-share-buttons-menu.php';
require_once FAST_SOCIAL_SHARE_BUTTONS_DIR . 'includes/class-fast-social-share-buttons-feedback.php';
require_once FAST_SOCIAL_SHARE_BUTTONS_DIR . 'includes/class-fast-social-share-buttons-admin.php';
require_once FAST_SOCIAL_SHARE_BUTTONS_DIR . 'includes/class-fast-social-share-buttons-networks.php';
require_once FAST_SOCIAL_SHARE_BUTTONS_DIR . 'includes/class-fast-social-share-buttons-renderer.php';
require_once FAST_SOCIAL_SHARE_BUTTONS_DIR . 'includes/class-fast-social-share-buttons-frontend.php';
require_once FAST_SOCIAL_SHARE_BUTTONS_DIR . 'includes/class-fast-social-share-buttons-shortcodes.php';
require_once FAST_SOCIAL_SHARE_BUTTONS_DIR . 'includes/class-fast-social-share-buttons-block.php';
require_once FAST_SOCIAL_SHARE_BUTTONS_DIR . 'includes/class-fast-social-share-buttons-widget.php';
require_once FAST_SOCIAL_SHARE_BUTTONS_DIR . 'includes/class-fast-social-share-buttons-meta-box.php';
require_once FAST_SOCIAL_SHARE_BUTTONS_DIR . 'includes/class-fast-social-share-buttons-plugin.php';

// Built right away, outside any hook, so WP-Cron background updates and
// remote management tools see new releases too (not only logged-in admins).
Fast_Social_Share_Buttons_Updater::init();

/**
 * Boot the plugin once all other plugins have loaded.
 *
 * @return Fast_Social_Share_Buttons_Plugin
 */
function fast_social_share_buttons_plugin() {
	return Fast_Social_Share_Buttons_Plugin::instance();
}
add_action( 'plugins_loaded', 'fast_social_share_buttons_plugin' );

/**
 * Template tag: share buttons wherever a theme wants them.
 *
 * Example: echo fast_social_share_buttons_render( array( 'layout' => 'column' ) );
 *
 * @param array $args Same options as the [fast_social_share] shortcode
 *                    (networks, shape, colors, size, labels, align, layout, heading).
 * @return string Button markup, already escaped.
 */
function fast_social_share_buttons_render( $args = array() ) {
	return fast_social_share_buttons_plugin()->renderer()->render( $args );
}
