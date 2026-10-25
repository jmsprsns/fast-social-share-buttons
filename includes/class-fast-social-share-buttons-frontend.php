<?php
/**
 * Front end: decides where buttons appear and loads assets only on pages
 * that actually show them.
 *
 * Performance notes:
 * - No CSS or JS at all on pages without buttons.
 * - The stylesheet (a few KB) is inlined in <head>: no extra request, nothing
 *   render-blocking to download.
 * - The script is small, loaded with defer in the footer, and has no
 *   dependencies (no jQuery).
 * - Icons are one SVG sprite holding only the networks in use.
 * - The floating sidebar and mobile bar are inert <template>s. The script
 *   builds them only when they're about to be shown, so they add nothing to
 *   the initial DOM, layout or paint.
 * - No share counts, so no third-party API calls and no extra queries.
 *
 * @package Fast_Social_Share_Buttons
 */

defined( 'ABSPATH' ) || exit;

/**
 * Front-end controller.
 */
final class Fast_Social_Share_Buttons_Frontend {

	/**
	 * Script and style handle.
	 */
	const HANDLE = 'fast-social-share-buttons';

	/**
	 * Plugin instance.
	 *
	 * @var Fast_Social_Share_Buttons_Plugin
	 */
	private $plugin;

	/**
	 * Automatic placements for this request (above, below, floating), or null
	 * until worked out.
	 *
	 * @var string[]|null
	 */
	private $placements = null;

	/**
	 * Whether the script has been enqueued.
	 *
	 * @var bool
	 */
	private $enqueued = false;

	/**
	 * Whether the stylesheet is on the page (or queued for <head>).
	 *
	 * @var bool
	 */
	private $css_done = false;

	/**
	 * Icons the page needs, as keys.
	 *
	 * @var array<string,bool>
	 */
	private $icons = array();

	/**
	 * Icons already printed in a sprite, as keys.
	 *
	 * @var array<string,bool>
	 */
	private $printed = array();

	/**
	 * Constructor.
	 *
	 * @param Fast_Social_Share_Buttons_Plugin $plugin Plugin instance.
	 */
	public function __construct( Fast_Social_Share_Buttons_Plugin $plugin ) {
		$this->plugin = $plugin;

		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue' ) );
		add_filter( 'the_content', array( $this, 'filter_content' ), 20 );
		add_action( 'wp_body_open', array( $this, 'print_sprite' ), 5 );
		add_action( 'wp_footer', array( $this, 'print_footer' ), 5 );
	}

	/**
	 * Where buttons go automatically on this request.
	 *
	 * @return string[] Any of above, below, floating.
	 */
	public function placements() {
		if ( null !== $this->placements ) {
			return $this->placements;
		}

		$this->placements = array();

		if ( ! is_singular() || is_feed() || is_embed() ) {
			return $this->placements;
		}

		$post = get_queried_object();

		if ( ! $post instanceof WP_Post || post_password_required( $post ) || Fast_Social_Share_Buttons_Meta_Box::is_hidden( $post->ID ) ) {
			return $this->placements;
		}

		$all     = (array) $this->plugin->get_setting( 'placements' );
		$by_type = isset( $all[ $post->post_type ] ) ? (array) $all[ $post->post_type ] : array();

		foreach ( array( 'above', 'below', 'floating' ) as $placement ) {
			if ( ! empty( $by_type[ $placement ] ) ) {
				$this->placements[] = $placement;
			}
		}

		/**
		 * Filter where buttons appear on a post.
		 *
		 * @param string[] $placements Any of above, below, floating.
		 * @param WP_Post  $post       The post being viewed.
		 */
		$this->placements = array_values( (array) apply_filters( 'fast_social_share_buttons_placements', $this->placements, $post ) );

		return $this->placements;
	}

	/**
	 * Load assets only on pages that show buttons.
	 *
	 * Buttons added in ways we can't see coming (a widget, a template part)
	 * load them on the spot instead; see {@see require_assets()}.
	 *
	 * @return void
	 */
	public function maybe_enqueue() {
		$post = is_singular() ? get_queried_object() : null;

		if ( $this->placements() ) {
			$networks = (array) $this->plugin->get_setting( 'networks' );

			if ( $this->plugin->get_setting( 'pin_hover' ) ) {
				$networks[] = 'pinterest';
			}

			$this->use_icons( $networks );
			$this->require_assets();
		} elseif ( $post instanceof WP_Post && $this->content_has_buttons( $post ) ) {
			$this->require_assets();
		}
	}

	/**
	 * Whether a post's content has one of our shortcodes or blocks.
	 *
	 * @param WP_Post $post Post.
	 * @return bool
	 */
	private function content_has_buttons( WP_Post $post ) {
		if ( has_block( Fast_Social_Share_Buttons_Block::NAME, $post ) ) {
			return true;
		}

		foreach ( Fast_Social_Share_Buttons_Shortcodes::tags() as $tag ) {
			if ( has_shortcode( $post->post_content, $tag ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Make sure the stylesheet and script are on the page.
	 *
	 * Before <head> is printed, the CSS goes into <head>. After that (a widget
	 * or shortcode we didn't see coming), it's returned so the caller can print
	 * it right before the buttons, instead of loading late in the footer.
	 *
	 * @return string A <style> tag to print, or ''.
	 */
	public function require_assets() {
		$style = '';

		if ( ! $this->css_done ) {
			$this->css_done = true;

			if ( did_action( 'wp_head' ) && ! doing_action( 'wp_head' ) ) {
				$style = '<style id="' . self::HANDLE . '-css">' . $this->css() . '</style>';
			} else {
				wp_register_style( self::HANDLE, false, array(), FAST_SOCIAL_SHARE_BUTTONS_VERSION );
				wp_enqueue_style( self::HANDLE );
				wp_add_inline_style( self::HANDLE, $this->css() );
			}
		}

		if ( ! $this->enqueued ) {
			$this->enqueued = true;

			wp_enqueue_script(
				self::HANDLE,
				FAST_SOCIAL_SHARE_BUTTONS_URL . 'assets/front' . self::suffix() . '.js',
				array(),
				FAST_SOCIAL_SHARE_BUTTONS_VERSION,
				array(
					'in_footer' => true,
					'strategy'  => 'defer',
				)
			);

			wp_add_inline_script( self::HANDLE, 'var fssbConfig=' . wp_json_encode( $this->script_config() ) . ';', 'before' );
		}

		return $style;
	}

	/**
	 * Everything a group of buttons needs to look right wherever it lands.
	 *
	 * On a normal page that's the stylesheet and script (if they aren't loaded
	 * yet) and the icons, which go in the sprite. A block preview in the editor
	 * has no page around it, so it gets its styles and icons inline.
	 *
	 * @param string[] $icons Network keys the buttons use.
	 * @return string Markup to print before the buttons, often ''.
	 */
	public function assets_for( array $icons ) {
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			if ( in_array( 'copy', $icons, true ) ) {
				$icons[] = 'check';
			}

			return '<style>' . $this->css() . '</style>' . $this->plugin->renderer()->sprite( $icons );
		}

		$this->use_icons( $icons );

		return $this->require_assets();
	}

	/**
	 * Add the buttons above and below the main post content.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public function filter_content( $content ) {
		if ( ! $this->is_main_content() ) {
			return $content;
		}

		$placements = $this->placements();
		$renderer   = $this->plugin->renderer();
		$before     = '';
		$after      = '';

		if ( in_array( 'above', $placements, true ) ) {
			$before = $renderer->render( array( 'context' => 'above' ) );
		}

		if ( in_array( 'below', $placements, true ) ) {
			$after = $renderer->render( array( 'context' => 'below' ) );
		}

		// Marks the content area for the Pinterest image button.
		if ( $this->plugin->get_setting( 'pin_hover' ) ) {
			$before = '<span class="fssb-pin-scope" hidden></span>' . $before;
		}

		return $before . $content . $after;
	}

	/**
	 * Whether the_content is running for the main post on its own page, not
	 * for an excerpt, a widget, a related-posts list or a <head> tag.
	 *
	 * @return bool
	 */
	private function is_main_content() {
		if ( ! $this->placements() || is_admin() || wp_doing_ajax() ) {
			return false;
		}

		if ( doing_filter( 'get_the_excerpt' ) || doing_action( 'wp_head' ) ) {
			return false;
		}

		return in_the_loop() && is_main_query() && (int) get_the_ID() === (int) get_queried_object_id();
	}

	/**
	 * Note icons the page needs; they're printed in the sprite.
	 *
	 * @param string[] $keys Network keys.
	 * @return void
	 */
	public function use_icons( array $keys ) {
		foreach ( $keys as $key ) {
			$this->icons[ $key ] = true;

			// The copy button flashes a tick.
			if ( 'copy' === $key ) {
				$this->icons['check'] = true;
			}
		}
	}

	/**
	 * Print the icons needed so far. Runs at the top of <body> (so icons are
	 * there before the buttons that use them) and again in the footer for any
	 * added later.
	 *
	 * @return void
	 */
	public function print_sprite() {
		$todo = array_keys( array_diff_key( $this->icons, $this->printed ) );

		if ( ! $todo ) {
			return;
		}

		echo $this->plugin->renderer()->sprite( $todo ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from the bundled icon set.

		$this->printed += array_fill_keys( $todo, true );
	}

	/**
	 * Footer: remaining icons, then the floating sidebar and mobile bar as
	 * inert templates the script builds on demand.
	 *
	 * @return void
	 */
	public function print_footer() {
		$floating = in_array( 'floating', $this->placements(), true );
		$side     = '';
		$bar      = '';

		if ( $floating ) {
			$renderer = $this->plugin->renderer();
			$post_id  = (int) get_queried_object_id();
			$side     = $renderer->render(
				array(
					'context' => 'side',
					'post_id' => $post_id,
				)
			);

			if ( $this->plugin->get_setting( 'mobile_bar' ) ) {
				$bar = $renderer->render(
					array(
						'context' => 'bar',
						'post_id' => $post_id,
					)
				);
			}
		}

		$this->print_sprite();

		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup is escaped by the renderer.
		if ( '' !== $side ) {
			echo '<template id="fssb-tpl-side">' . $side . '</template>';
		}

		if ( '' !== $bar ) {
			echo '<template id="fssb-tpl-bar">' . $bar . '</template>';
		}
		// phpcs:enable
	}

	/**
	 * The stylesheet, plus the breakpoint rules that depend on settings.
	 *
	 * @return string
	 */
	public function css() {
		static $css = null;

		if ( null === $css ) {
			$file = FAST_SOCIAL_SHARE_BUTTONS_DIR . 'assets/front' . self::suffix() . '.css';
			$css  = is_readable( $file ) ? (string) file_get_contents( $file ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

			$breakpoint = absint( $this->plugin->get_setting( 'breakpoint' ) );
			$css       .= sprintf(
				'@media (max-width:%1$dpx){.fssb-side{display:none}}@media (min-width:%2$dpx){.fssb-bar{display:none}}',
				max( 0, $breakpoint - 1 ),
				$breakpoint
			);
		}

		return $css;
	}

	/**
	 * Settings the script needs.
	 *
	 * @return array<string,mixed>
	 */
	private function script_config() {
		$settings = $this->plugin->get_settings();
		$post_id  = is_singular() ? (int) get_queried_object_id() : 0;

		return array(
			'url'   => $post_id ? $this->plugin->renderer()->share_data( $post_id )['url'] : '',
			'bp'    => absint( $settings['breakpoint'] ),
			'float' => $settings['float_trigger'],
			'pin'   => ! empty( $settings['pin_hover'] ) ? absint( $settings['pin_min_width'] ) : 0,
			'i18n'  => array(
				'copied'   => __( 'Link copied', 'fast-social-share-buttons' ),
				'copy'     => __( 'Copy this link:', 'fast-social-share-buttons' ),
				'mastodon' => __( 'Your Mastodon server (for example mastodon.social):', 'fast-social-share-buttons' ),
				'save'     => __( 'Save', 'fast-social-share-buttons' ),
			),
		);
	}

	/**
	 * ".min" unless SCRIPT_DEBUG is on.
	 *
	 * @return string
	 */
	private static function suffix() {
		return defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
	}
}
