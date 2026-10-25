<?php
/**
 * Builds the share button markup used everywhere: above and below content,
 * the floating sidebar and mobile bar, shortcodes, the block, the widget and
 * the settings-page preview.
 *
 * Markup is deliberately lean: icons are <use> references to one shared SVG
 * sprite, styling comes from a few short classes, and share links are plain
 * links that work before (or without) JavaScript.
 *
 * @package Fast_Social_Share_Buttons
 */

defined( 'ABSPATH' ) || exit;

/**
 * Markup builder.
 */
final class Fast_Social_Share_Buttons_Renderer {

	/**
	 * Plugin instance.
	 *
	 * @var Fast_Social_Share_Buttons_Plugin
	 */
	private $plugin;

	/**
	 * Share data per post ID, cached for the request.
	 *
	 * @var array<int,array<string,string>>
	 */
	private $share_data = array();

	/**
	 * Constructor.
	 *
	 * @param Fast_Social_Share_Buttons_Plugin $plugin Plugin instance.
	 */
	public function __construct( Fast_Social_Share_Buttons_Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Render a group of share buttons.
	 *
	 * @param array $args {
	 *     Optional. Anything left out comes from the settings.
	 *
	 *     @type string       $context  above, below, side, bar, shortcode or preview.
	 *     @type string[]|string $networks Network keys, as an array or comma-separated.
	 *     @type int          $post_id  Post to share. Defaults to the current post or page.
	 *     @type string       $shape    square, rounded, pill or circle.
	 *     @type string       $colors   brand, hover, outline or mono.
	 *     @type string       $size     s, m or l.
	 *     @type string       $labels   auto or never.
	 *     @type string       $align    stretch (fill the width), left, center or right.
	 *     @type string       $layout   row or column.
	 *     @type string       $heading  Text shown before the buttons.
	 * }
	 * @return string HTML, or '' when there's nothing to show.
	 */
	public function render( $args = array() ) {
		$settings = $this->plugin->get_settings();
		$args     = is_array( $args ) ? $args : array();
		$context  = isset( $args['context'] ) ? (string) $args['context'] : 'shortcode';
		$floating = in_array( $context, array( 'side', 'bar' ), true );
		$preview  = 'preview' === $context;

		$networks = Fast_Social_Share_Buttons_Networks::filter( isset( $args['networks'] ) && '' !== $args['networks'] && array() !== $args['networks'] ? $args['networks'] : $settings['networks'] );

		if ( ! $networks ) {
			return '';
		}

		$opt = array();
		foreach ( array( 'shape', 'colors', 'size', 'labels', 'align', 'layout' ) as $key ) {
			$value       = isset( $args[ $key ] ) ? $args[ $key ] : ( isset( $settings[ $key ] ) ? $settings[ $key ] : '' );
			$opt[ $key ] = Fast_Social_Share_Buttons_Plugin::is_choice( $key, $value ) ? $value : ( isset( $settings[ $key ] ) ? $settings[ $key ] : '' );
		}

		$post_id = isset( $args['post_id'] ) ? absint( $args['post_id'] ) : $this->current_post_id();
		$data    = $this->share_data( $post_id );

		// Circles only fit an icon; floating buttons never show labels.
		$circle = 'circle' === $opt['shape'];
		$labels = ( $circle || $floating ) ? 'never' : $opt['labels'];

		$classes = array( 'fssb' );

		if ( 'side' === $context ) {
			$classes[] = 'fssb-side';
			$classes[] = 'right' === $settings['float_position'] ? 'fssb-side-right' : '';
		} elseif ( 'bar' === $context ) {
			$classes[] = 'fssb-bar';
		} else {
			$classes[] = 'fssb-inline';
			if ( in_array( $context, array( 'above', 'below' ), true ) ) {
				$classes[] = 'fssb-' . $context;
			}
			if ( 'stretch' !== $opt['align'] ) {
				$classes[] = 'fssb-compact';
				$classes[] = 'left' === $opt['align'] ? '' : 'fssb-' . $opt['align'];
			}
			if ( 'column' === $opt['layout'] ) {
				$classes[] = 'fssb-col';
			}
		}

		$shape_class = array(
			'square'  => 'fssb-sq',
			'pill'    => 'fssb-pl',
			'circle'  => 'fssb-ci',
			'rounded' => '',
		);
		$size_class  = array(
			's' => 'fssb-s',
			'l' => 'fssb-lg',
			'm' => '',
		);

		$classes[] = 'bar' === $context ? '' : $shape_class[ $opt['shape'] ];
		$classes[] = 'brand' === $opt['colors'] ? '' : 'fssb-' . $opt['colors'];
		$classes[] = $floating ? '' : $size_class[ $opt['size'] ];
		$classes[] = 'never' === $labels ? 'fssb-nolabel' : '';

		$attrs = sprintf(
			' class="%1$s" data-fssb-url="%2$s" data-fssb-title="%3$s"',
			esc_attr( implode( ' ', array_filter( $classes ) ) ),
			esc_url( $data['url'] ),
			esc_attr( $data['title'] )
		);

		// Button count and label width drive the equal-width grid (see front.css).
		if ( ! $floating ) {
			$attrs .= sprintf( ' style="--fssb-n:%1$d;--fssb-lw:%2$sem"', count( $networks ), self::label_width( $networks, $settings ) );
		}

		if ( $floating ) {
			$attrs .= ' role="region" aria-label="' . esc_attr__( 'Share this page', 'fast-social-share-buttons' ) . '"';
		}

		$html = '<div' . $attrs . '>';

		$heading = isset( $args['heading'] ) ? (string) $args['heading'] : ( in_array( $context, array( 'above', 'below', 'preview' ), true ) ? (string) $settings['heading'] : '' );
		if ( '' !== $heading && ! $floating ) {
			$html .= '<span class="fssb-h">' . esc_html( $heading ) . '</span>';
		}

		foreach ( $networks as $key ) {
			$html .= $this->button( $key, $data, $settings );
		}

		$html .= '</div>';

		/**
		 * Filter a group of share buttons.
		 *
		 * @param string   $html     Markup.
		 * @param string   $context  above, below, side, bar, shortcode or preview.
		 * @param string[] $networks Network keys.
		 * @param int      $post_id  Post being shared (0 for a non-post page).
		 */
		$html = apply_filters( 'fast_social_share_buttons_html', $html, $context, $networks, $post_id );

		// The settings page loads its own styles and icons.
		return $preview ? $html : $this->plugin->frontend()->assets_for( $networks ) . $html;
	}

	/**
	 * One button.
	 *
	 * @param string               $key      Network key.
	 * @param array<string,string> $data     Share data.
	 * @param array<string,mixed>  $settings Settings.
	 * @return string
	 */
	private function button( $key, array $data, array $settings ) {
		$network = Fast_Social_Share_Buttons_Networks::get( $key );
		$type    = isset( $network['type'] ) ? $network['type'] : 'popup';
		$label   = 'action' === $settings['label_style'] ? $network['action'] : $network['name'];
		$shared  = $this->tracked_url( $data['url'], $key, $settings );

		$inner = sprintf(
			'<svg class="fssb-i" aria-hidden="true" focusable="false"><use href="#fssb-%1$s"></use></svg><span class="fssb-l">%2$s</span>',
			esc_attr( $key ),
			esc_html( $label )
		);

		$common = sprintf(
			'class="fssb-b fssb-%1$s" data-fssb="%1$s" title="%2$s"',
			esc_attr( $key ),
			esc_attr( $network['title'] )
		);

		// Copy, print and native share are actions, not links.
		if ( in_array( $type, array( 'copy', 'print', 'native' ), true ) ) {
			return sprintf(
				'<button type="button" %1$s data-fssb-act="%2$s"%3$s>%4$s</button>',
				$common,
				esc_attr( $type ),
				'print' !== $type && $shared !== $data['url'] ? ' data-fssb-url="' . esc_url( $shared ) . '"' : '',
				$inner
			);
		}

		$data['url'] = $shared;
		$href        = Fast_Social_Share_Buttons_Networks::share_url( $key, $data );

		if ( 'self' === $type ) {
			$extra = '';
		} else {
			$extra = ' target="_blank" rel="nofollow noopener noreferrer"';
			if ( 'popup' === $type || 'mastodon' === $type ) {
				$extra .= ' data-fssb-act="' . esc_attr( $type ) . '"';
			}
		}

		return sprintf( '<a %1$s href="%2$s"%3$s>%4$s</a>', $common, esc_url( $href ), $extra, $inner );
	}

	/**
	 * A "click to tweet" quote box.
	 *
	 * @param string $quote   Text shown in the box.
	 * @param string $tweet   Text that goes in the post. Defaults to the quote.
	 * @param int    $post_id Post to link to. Defaults to the current post.
	 * @return string
	 */
	public function quote( $quote, $tweet = '', $post_id = null ) {
		$quote = trim( wp_strip_all_tags( (string) $quote ) );

		if ( '' === $quote ) {
			return '';
		}

		$settings = $this->plugin->get_settings();
		$post_id  = null === $post_id ? $this->current_post_id() : absint( $post_id );
		$data     = $this->share_data( $post_id );

		$data['text'] = '' !== trim( (string) $tweet ) ? trim( wp_strip_all_tags( (string) $tweet ) ) : $quote;
		$data['url']  = $this->tracked_url( $data['url'], 'x', $settings );

		return $this->plugin->frontend()->assets_for( array( 'x' ) ) . sprintf(
			'<div class="fssb-ctt"><a class="fssb-ctt-a" href="%1$s" data-fssb="x" data-fssb-act="popup" target="_blank" rel="nofollow noopener noreferrer"><span class="fssb-ctt-q">%2$s</span><span class="fssb-ctt-c">%3$s<svg class="fssb-i" aria-hidden="true" focusable="false"><use href="#fssb-x"></use></svg></span></a></div>',
			esc_url( Fast_Social_Share_Buttons_Networks::share_url( 'x', $data ) ),
			esc_html( $quote ),
			esc_html__( 'Click to post', 'fast-social-share-buttons' )
		);
	}

	/**
	 * A hidden SVG sprite holding the given icons.
	 *
	 * @param string[] $keys Network keys (and "check").
	 * @return string
	 */
	public function sprite( array $keys ) {
		$symbols = '';

		foreach ( $keys as $key ) {
			$symbols .= Fast_Social_Share_Buttons_Networks::symbol( $key );
		}

		if ( '' === $symbols ) {
			return '';
		}

		return '<svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false" style="position:absolute;width:0;height:0;overflow:hidden">' . $symbols . '</svg>';
	}

	/**
	 * The URL, title and text a post (or the current page) is shared with.
	 *
	 * @param int $post_id Post ID, or 0 for the current page.
	 * @return array<string,string> url, title, text, desc, image, via.
	 */
	public function share_data( $post_id ) {
		$post_id = absint( $post_id );

		if ( isset( $this->share_data[ $post_id ] ) ) {
			return $this->share_data[ $post_id ];
		}

		$data = array(
			'url'   => '',
			'title' => '',
			'text'  => '',
			'desc'  => '',
			'image' => '',
			'via'   => (string) $this->plugin->get_setting( 'x_username' ),
		);

		$post = $post_id ? get_post( $post_id ) : null;

		if ( $post ) {
			$meta = Fast_Social_Share_Buttons_Meta_Box::get_meta( $post->ID );

			$data['url']   = (string) get_permalink( $post );
			$data['title'] = self::plain( get_the_title( $post ) );
			$data['text']  = '' !== $meta['text'] ? $meta['text'] : $data['title'];
			$data['desc']  = '' !== $meta['pin_desc'] ? $meta['pin_desc'] : ( has_excerpt( $post ) ? self::plain( $post->post_excerpt ) : $data['title'] );

			$image         = $meta['pin_image'] ? wp_get_attachment_image_url( $meta['pin_image'], 'full' ) : get_the_post_thumbnail_url( $post, 'full' );
			$data['image'] = $image ? (string) $image : '';
		} else {
			global $wp;

			$path          = isset( $wp->request ) ? (string) $wp->request : '';
			$data['url']   = '' === $path ? home_url( '/' ) : home_url( user_trailingslashit( $path ) );
			$data['title'] = self::plain( wp_get_document_title() );
			$data['text']  = $data['title'];
			$data['desc']  = $data['title'];
		}

		/**
		 * Filter what a post is shared with.
		 *
		 * @param array<string,string> $data    url, title, text, desc, image, via.
		 * @param int                  $post_id Post ID, or 0 for a non-post page.
		 */
		$this->share_data[ $post_id ] = apply_filters( 'fast_social_share_buttons_share_data', $data, $post_id );

		return $this->share_data[ $post_id ];
	}

	/**
	 * The post being viewed, or the post in the loop on archive pages.
	 *
	 * @return int 0 when not on a post.
	 */
	public function current_post_id() {
		if ( in_the_loop() ) {
			return (int) get_the_ID();
		}

		return is_singular() ? (int) get_queried_object_id() : 0;
	}

	/**
	 * Add UTM parameters to the shared URL, when that's switched on.
	 *
	 * @param string              $url      URL being shared.
	 * @param string              $network  Network key.
	 * @param array<string,mixed> $settings Settings.
	 * @return string
	 */
	private function tracked_url( $url, $network, array $settings ) {
		if ( empty( $settings['utm'] ) || '' === $url || 'print' === $network ) {
			return $url;
		}

		return add_query_arg(
			array(
				'utm_source'   => rawurlencode( $network ),
				'utm_medium'   => 'social',
				'utm_campaign' => rawurlencode( (string) $settings['utm_campaign'] ),
			),
			$url
		);
	}

	/**
	 * Fixed button width, in em, that fits the longest label in a group: icon,
	 * gap and padding (about 2.9em) plus roughly 0.6em per character. Only used
	 * by the left/centre/right layouts; the script still switches to icons if
	 * a label turns out not to fit.
	 *
	 * @param string[]            $networks Network keys.
	 * @param array<string,mixed> $settings Settings.
	 * @return string Width in em, e.g. "8.3".
	 */
	private static function label_width( array $networks, array $settings ) {
		$longest = 0;

		foreach ( $networks as $key ) {
			$network = Fast_Social_Share_Buttons_Networks::get( $key );
			$label   = 'action' === $settings['label_style'] ? $network['action'] : $network['name'];
			$length  = function_exists( 'mb_strlen' ) ? mb_strlen( $label ) : strlen( $label );
			$longest = max( $longest, $length );
		}

		return number_format( max( 6, 2.9 + 0.6 * $longest ), 1, '.', '' );
	}

	/**
	 * Plain text from a title or excerpt: no tags, no HTML entities.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	private static function plain( $text ) {
		return trim( html_entity_decode( wp_strip_all_tags( (string) $text ), ENT_QUOTES, 'UTF-8' ) );
	}
}
