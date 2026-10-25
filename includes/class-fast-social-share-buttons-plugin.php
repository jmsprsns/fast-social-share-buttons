<?php
/**
 * Main plugin orchestrator: owns the settings and boots every component.
 *
 * @package Fast_Social_Share_Buttons
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main plugin singleton.
 */
final class Fast_Social_Share_Buttons_Plugin {

	/**
	 * Option key holding the plugin's settings array.
	 */
	const OPTION = 'fast_social_share_buttons_settings';

	/**
	 * Single shared instance.
	 *
	 * @var Fast_Social_Share_Buttons_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Settings with defaults filled in, cached for the request.
	 *
	 * @var array<string,mixed>|null
	 */
	private $settings = null;

	/**
	 * Builds the button markup.
	 *
	 * @var Fast_Social_Share_Buttons_Renderer
	 */
	private $renderer;

	/**
	 * Decides where buttons go and loads the assets.
	 *
	 * @var Fast_Social_Share_Buttons_Frontend
	 */
	private $frontend;

	/**
	 * Get (and lazily create) the shared instance.
	 *
	 * @return Fast_Social_Share_Buttons_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Wire everything up.
	 */
	private function __construct() {
		$this->renderer = new Fast_Social_Share_Buttons_Renderer( $this );
		$this->frontend = new Fast_Social_Share_Buttons_Frontend( $this );

		new Fast_Social_Share_Buttons_Shortcodes( $this );
		new Fast_Social_Share_Buttons_Block( $this );

		add_action( 'widgets_init', array( $this, 'register_widget' ) );
		add_action( 'update_option_' . self::OPTION, array( $this, 'flush_settings' ) );

		if ( is_admin() ) {
			new Fast_Social_Share_Buttons_Admin( $this );
			new Fast_Social_Share_Buttons_Meta_Box( $this );
		}
	}

	/**
	 * The markup builder.
	 *
	 * @return Fast_Social_Share_Buttons_Renderer
	 */
	public function renderer() {
		return $this->renderer;
	}

	/**
	 * The front-end controller.
	 *
	 * @return Fast_Social_Share_Buttons_Frontend
	 */
	public function frontend() {
		return $this->frontend;
	}

	/**
	 * Register the classic widget.
	 *
	 * @return void
	 */
	public function register_widget() {
		register_widget( 'Fast_Social_Share_Buttons_Widget' );
	}

	/**
	 * Default value for every setting.
	 *
	 * @return array<string,mixed>
	 */
	public static function defaults() {
		return array(
			// Networks, in display order.
			'networks'       => Fast_Social_Share_Buttons_Networks::DEFAULTS,

			// Where buttons go, per post type: above, below, floating.
			'placements'     => array(
				'post' => array(
					'above'    => 1,
					'below'    => 1,
					'floating' => 1,
				),
			),

			// Look.
			'shape'          => 'rounded',
			'colors'         => 'brand',
			'size'           => 'm',
			'labels'         => 'auto',
			'label_style'    => 'name',
			'align'          => 'stretch',
			'heading'        => '',

			// Floating sidebar and mobile bar.
			'float_position' => 'left',
			'float_trigger'  => 'auto',
			'mobile_bar'     => 1,
			'breakpoint'     => 1024,

			// Extras.
			'x_username'     => '',
			'utm'            => 0,
			'utm_campaign'   => 'social-share',
			'pin_hover'      => 0,
			'pin_min_width'  => 250,
			'sw_compat'      => 1,
		);
	}

	/**
	 * Allowed values (and their labels) for every multiple-choice setting.
	 *
	 * The renderer uses the same list to validate shortcode and block options.
	 *
	 * @return array<string,array<string,string>>
	 */
	public static function choices() {
		return array(
			'shape'          => array(
				'square'  => __( 'Square', 'fast-social-share-buttons' ),
				'rounded' => __( 'Rounded', 'fast-social-share-buttons' ),
				'pill'    => __( 'Pill', 'fast-social-share-buttons' ),
				'circle'  => __( 'Circle (icons only)', 'fast-social-share-buttons' ),
			),
			'colors'         => array(
				'brand'   => __( 'Brand colours', 'fast-social-share-buttons' ),
				'hover'   => __( 'Grey, colour on hover', 'fast-social-share-buttons' ),
				'outline' => __( 'Outline', 'fast-social-share-buttons' ),
				'mono'    => __( 'Dark, colour on hover', 'fast-social-share-buttons' ),
			),
			'size'           => array(
				's' => __( 'Small', 'fast-social-share-buttons' ),
				'm' => __( 'Medium', 'fast-social-share-buttons' ),
				'l' => __( 'Large', 'fast-social-share-buttons' ),
			),
			'labels'         => array(
				'auto'  => __( 'Labels when they fit', 'fast-social-share-buttons' ),
				'never' => __( 'Icons only', 'fast-social-share-buttons' ),
			),
			'label_style'    => array(
				'name'   => __( 'Network name (Facebook, X, Pinterest)', 'fast-social-share-buttons' ),
				'action' => __( 'Action (Share, Post, Pin)', 'fast-social-share-buttons' ),
			),
			'align'          => array(
				'stretch' => __( 'Fill the width', 'fast-social-share-buttons' ),
				'left'    => __( 'Left', 'fast-social-share-buttons' ),
				'center'  => __( 'Centre', 'fast-social-share-buttons' ),
				'right'   => __( 'Right', 'fast-social-share-buttons' ),
			),
			'layout'         => array(
				'row'    => __( 'Horizontal', 'fast-social-share-buttons' ),
				'column' => __( 'Vertical (stacked)', 'fast-social-share-buttons' ),
			),
			'float_position' => array(
				'left'  => __( 'Left', 'fast-social-share-buttons' ),
				'right' => __( 'Right', 'fast-social-share-buttons' ),
			),
			'float_trigger'  => array(
				'auto'   => __( 'When the in-content buttons scroll out of view', 'fast-social-share-buttons' ),
				'always' => __( 'Always', 'fast-social-share-buttons' ),
			),
		);
	}

	/**
	 * Whether a value is allowed for a multiple-choice setting.
	 *
	 * @param string $key   Setting key from {@see choices()}.
	 * @param mixed  $value Value to check.
	 * @return bool
	 */
	public static function is_choice( $key, $value ) {
		$choices = self::choices();

		return isset( $choices[ $key ] ) && is_string( $value ) && isset( $choices[ $key ][ $value ] );
	}

	/**
	 * All settings, with defaults filled in.
	 *
	 * @return array<string,mixed>
	 */
	public function get_settings() {
		if ( null === $this->settings ) {
			$saved          = get_option( self::OPTION, array() );
			$this->settings = wp_parse_args( is_array( $saved ) ? $saved : array(), self::defaults() );
		}

		return $this->settings;
	}

	/**
	 * A single setting.
	 *
	 * @param string $key Setting key from {@see defaults()}.
	 * @return mixed Null for an unknown key.
	 */
	public function get_setting( $key ) {
		$settings = $this->get_settings();

		return isset( $settings[ $key ] ) ? $settings[ $key ] : null;
	}

	/**
	 * Forget the cached settings after they change.
	 *
	 * @return void
	 */
	public function flush_settings() {
		$this->settings = null;
	}
}
