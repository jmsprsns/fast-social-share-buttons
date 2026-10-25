<?php
/**
 * Runs when the plugin is deleted from the Plugins screen: remove its data.
 *
 * @package Fast_Social_Share_Buttons
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'fast_social_share_buttons_settings' );
delete_option( 'widget_fast_social_share_buttons' );

// Per-post options from the Social Sharing box.
foreach ( array( 'hide', 'text', 'pin_image', 'pin_desc' ) as $fast_social_share_buttons_field ) {
	delete_post_meta_by_key( '_fast_social_share_buttons_' . $fast_social_share_buttons_field );
}

// Plugin Update Checker's cached update state.
delete_site_option( 'external_updates-fast-social-share-buttons' );
