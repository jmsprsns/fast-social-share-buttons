<?php
/**
 * Shortcodes: [fast_social_share] buttons and the [fast_click_to_tweet] quote
 * box, plus Social Warfare's [social_warfare] and [clickToTweet] so content
 * keeps working after switching plugins.
 *
 * @package Fast_Social_Share_Buttons
 */

defined( 'ABSPATH' ) || exit;

/**
 * Shortcode handlers.
 */
final class Fast_Social_Share_Buttons_Shortcodes {

	/**
	 * Share buttons shortcode.
	 */
	const BUTTONS = 'fast_social_share';

	/**
	 * Click-to-tweet quote box shortcode.
	 */
	const QUOTE = 'fast_click_to_tweet';

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

		// Late, so Social Warfare (if it's still active) keeps its own tags.
		add_action( 'init', array( $this, 'register' ), 20 );
	}

	/**
	 * Every shortcode tag this plugin may handle.
	 *
	 * @return string[]
	 */
	public static function tags() {
		return array( self::BUTTONS, self::QUOTE, 'social_warfare', 'clickToTweet' );
	}

	/**
	 * Register the shortcodes.
	 *
	 * @return void
	 */
	public function register() {
		add_shortcode( self::BUTTONS, array( $this, 'buttons' ) );
		add_shortcode( self::QUOTE, array( $this, 'quote' ) );

		if ( ! $this->plugin->get_setting( 'sw_compat' ) ) {
			return;
		}

		if ( ! shortcode_exists( 'social_warfare' ) ) {
			add_shortcode( 'social_warfare', array( $this, 'social_warfare' ) );
		}

		if ( ! shortcode_exists( 'clickToTweet' ) ) {
			add_shortcode( 'clickToTweet', array( $this, 'quote' ) );
		}
	}

	/**
	 * [fast_social_share networks="facebook,x,email" shape="pill" align="center"]
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function buttons( $atts ) {
		$atts = shortcode_atts(
			array(
				'networks' => '',
				'shape'    => '',
				'colors'   => '',
				'size'     => '',
				'labels'   => '',
				'align'    => '',
				'layout'   => '',
				'heading'  => '',
			),
			$atts,
			self::BUTTONS
		);

		$args = array( 'context' => 'shortcode' );

		// The renderer checks every value against the allowed choices.
		foreach ( $atts as $key => $value ) {
			if ( '' !== $value ) {
				$args[ $key ] = 'heading' === $key ? sanitize_text_field( $value ) : $value;
			}
		}

		return $this->plugin->renderer()->render( $args );
	}

	/**
	 * [fast_click_to_tweet tweet="What gets posted"]The quote shown on the page[/fast_click_to_tweet]
	 *
	 * Also accepts quote="..." instead of enclosed text, which is how Social
	 * Warfare's [clickToTweet tweet="..." quote="..."] works.
	 *
	 * @param array|string $atts    Shortcode attributes.
	 * @param string|null  $content Enclosed text.
	 * @return string
	 */
	public function quote( $atts, $content = null ) {
		$atts = shortcode_atts(
			array(
				'quote' => '',
				'tweet' => '',
			),
			$atts,
			self::QUOTE
		);

		$quote = '' !== $atts['quote'] ? $atts['quote'] : (string) $content;

		return $this->plugin->renderer()->quote( $quote, $atts['tweet'] );
	}

	/**
	 * Social Warfare's [social_warfare buttons="Facebook,Twitter,Pinterest"].
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function social_warfare( $atts ) {
		$atts = shortcode_atts( array( 'buttons' => '' ), $atts, 'social_warfare' );
		$args = array( 'context' => 'shortcode' );

		if ( '' !== $atts['buttons'] ) {
			$renamed = array(
				'twitter'    => 'x',
				'google'     => '',
				'googleplus' => '',
				'yummly'     => '',
				'hacker'     => 'hackernews',
			);

			$networks = array();
			foreach ( explode( ',', strtolower( $atts['buttons'] ) ) as $network ) {
				$network    = preg_replace( '/[^a-z]/', '', $network );
				$networks[] = isset( $renamed[ $network ] ) ? $renamed[ $network ] : $network;
			}

			$args['networks'] = $networks;
		}

		return $this->plugin->renderer()->render( $args );
	}
}
