<?php
/**
 * The "Social Share Buttons" block. Works in posts, templates and widget areas.
 *
 * @package Fast_Social_Share_Buttons
 */

defined( 'ABSPATH' ) || exit;

/**
 * Block registration and rendering.
 */
final class Fast_Social_Share_Buttons_Block {

	/**
	 * Block name.
	 */
	const NAME = 'fast-social-share-buttons/share-buttons';

	/**
	 * Plugin instance.
	 *
	 * @var Fast_Social_Share_Buttons_Plugin
	 */
	private $plugin;

	/**
	 * Constructor.
	 *
	 * @param Fast_Social_Share_Buttons_Plugin $plugin Plugin instance.
	 */
	public function __construct( Fast_Social_Share_Buttons_Plugin $plugin ) {
		$this->plugin = $plugin;

		add_action( 'init', array( $this, 'register' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'editor_data' ) );
	}

	/**
	 * Register the block from its block.json.
	 *
	 * @return void
	 */
	public function register() {
		register_block_type(
			FAST_SOCIAL_SHARE_BUTTONS_DIR . 'blocks/share-buttons',
			array( 'render_callback' => array( $this, 'render' ) )
		);
	}

	/**
	 * Give the editor script the network names and option labels.
	 *
	 * @return void
	 */
	public function editor_data() {
		$networks = array();
		foreach ( Fast_Social_Share_Buttons_Networks::all() as $key => $network ) {
			$networks[ $key ] = $network['name'];
		}

		$choices = Fast_Social_Share_Buttons_Plugin::choices();

		wp_add_inline_script(
			generate_block_asset_handle( self::NAME, 'editorScript' ),
			'window.fssbBlock=' . wp_json_encode(
				array(
					'networks' => $networks,
					'choices'  => array_intersect_key( $choices, array_flip( array( 'shape', 'colors', 'size', 'labels', 'align', 'layout' ) ) ),
				)
			) . ';',
			'before'
		);
	}

	/**
	 * Render the block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render( $attributes ) {
		$args = array( 'context' => 'shortcode' );

		if ( ! empty( $attributes['networks'] ) && is_array( $attributes['networks'] ) ) {
			$args['networks'] = $attributes['networks'];
		}

		foreach ( array( 'shape', 'colors', 'size', 'labels', 'align', 'layout', 'heading' ) as $key ) {
			if ( isset( $attributes[ $key ] ) && '' !== $attributes[ $key ] ) {
				$args[ $key ] = (string) $attributes[ $key ];
			}
		}

		$html = $this->plugin->renderer()->render( $args );

		return '' === $html ? '' : '<div ' . get_block_wrapper_attributes() . '>' . $html . '</div>';
	}
}
