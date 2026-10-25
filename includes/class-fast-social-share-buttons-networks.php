<?php
/**
 * The social networks: names, colours, share links and icons.
 *
 * Icons are inlined here (not read from disk) so building a page never
 * touches the filesystem. Brand icons are from Simple Icons (CC0); utility
 * icons are from Lucide (ISC). Colours are each brand's own, darkened where
 * needed so white labels pass WCAG AA contrast (4.5:1).
 *
 * @package Fast_Social_Share_Buttons
 */

defined( 'ABSPATH' ) || exit;

/**
 * Network registry.
 */
final class Fast_Social_Share_Buttons_Networks {

	/**
	 * Networks switched on for new installs, in display order.
	 */
	const DEFAULTS = array( 'facebook', 'x', 'linkedin', 'pinterest', 'email', 'copy' );

	/**
	 * Every network, filtered so developers can add, remove or relabel them.
	 *
	 * Each entry has:
	 * - name:   brand name, shown in settings and as the default label
	 * - action: short verb label ("Share", "Pin"), used when labels are set to actions
	 * - title:  tooltip and accessible description
	 * - color:  button colour
	 * - url:    share link template ({url}, {title}, {text}, {desc}, {image}, {&via})
	 * - type:   popup (default), tab (new tab), self (mailto/sms), copy, print, native, mastodon
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function all() {
		static $networks = null;

		if ( null === $networks ) {
			$networks = (array) apply_filters( 'fast_social_share_buttons_networks', self::definitions() );
		}

		return $networks;
	}

	/**
	 * One network's definition.
	 *
	 * @param string $key Network key.
	 * @return array<string,mixed>|null
	 */
	public static function get( $key ) {
		$networks = self::all();

		return isset( $networks[ $key ] ) ? $networks[ $key ] : null;
	}

	/**
	 * Keep only known networks, without duplicates, in the given order.
	 *
	 * @param mixed $keys Network keys.
	 * @return string[]
	 */
	public static function filter( $keys ) {
		$keys = is_array( $keys ) ? $keys : explode( ',', (string) $keys );
		$keys = array_map( 'sanitize_key', array_map( 'trim', $keys ) );

		return array_values( array_unique( array_intersect( $keys, array_keys( self::all() ) ) ) );
	}

	/**
	 * Build a network's share link.
	 *
	 * @param string               $key  Network key.
	 * @param array<string,string> $data Share data: url, title, text, desc, image, via.
	 * @return string Unescaped URL, or '' for buttons that aren't links.
	 */
	public static function share_url( $key, array $data ) {
		$network = self::get( $key );

		if ( ! $network || empty( $network['url'] ) ) {
			return '';
		}

		$template = $network['url'];

		// Pinterest needs an image; without one, let it pick from the page.
		if ( '' === $data['image'] && ! empty( $network['url_no_image'] ) ) {
			$template = $network['url_no_image'];
		}

		$replace = array( '{&via}' => '' !== $data['via'] ? '&via=' . rawurlencode( $data['via'] ) : '' );

		foreach ( array( 'url', 'title', 'text', 'desc', 'image' ) as $field ) {
			$replace[ '{' . $field . '}' ] = rawurlencode( isset( $data[ $field ] ) ? (string) $data[ $field ] : '' );
		}

		return strtr( $template, $replace );
	}

	/**
	 * A network's icon as an SVG <symbol>.
	 *
	 * @param string $key Network key (or "check").
	 * @return string
	 */
	public static function symbol( $key ) {
		$network = self::get( $key );
		$icons   = self::icons();

		if ( $network && ! empty( $network['icon'] ) ) {
			$icon = (array) $network['icon'];
		} elseif ( isset( $icons[ $key ] ) ) {
			$icon = $icons[ $key ];
		} else {
			return '';
		}

		$stroke = ! empty( $icon[0] ) ? ' fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"' : '';

		return '<symbol id="fssb-' . esc_attr( $key ) . '" viewBox="0 0 24 24"' . $stroke . '>' . $icon[1] . '</symbol>';
	}

	/**
	 * Network definitions.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	private static function definitions() {
		$share = __( 'Share', 'fast-social-share-buttons' );
		$post  = __( 'Post', 'fast-social-share-buttons' );
		$send  = __( 'Send', 'fast-social-share-buttons' );

		$networks = array(
			'facebook'   => array( 'Facebook', $share, '#0866ff', 'https://www.facebook.com/sharer/sharer.php?u={url}' ),
			'x'          => array( 'X', $post, '#000000', 'https://x.com/intent/tweet?text={text}&url={url}{&via}' ),
			'linkedin'   => array( 'LinkedIn', $share, '#0a66c2', 'https://www.linkedin.com/sharing/share-offsite/?url={url}' ),
			'pinterest'  => array( 'Pinterest', __( 'Pin', 'fast-social-share-buttons' ), '#e60023', 'https://www.pinterest.com/pin/create/button/?url={url}&media={image}&description={desc}' ),
			'reddit'     => array( 'Reddit', $share, '#d63a00', 'https://www.reddit.com/submit?url={url}&title={title}' ),
			'whatsapp'   => array( 'WhatsApp', $send, '#178540', 'https://api.whatsapp.com/send?text={text}%20{url}', 'tab' ),
			'telegram'   => array( 'Telegram', $send, '#167caf', 'https://t.me/share/url?url={url}&text={text}', 'tab' ),
			'bluesky'    => array( 'Bluesky', $post, '#0171e5', 'https://bsky.app/intent/compose?text={text}%20{url}' ),
			'threads'    => array( 'Threads', $post, '#000000', 'https://www.threads.com/intent/post?text={text}%20{url}' ),
			'mastodon'   => array( 'Mastodon', $post, '#5e5fff', 'https://mastodon.social/share?text={text}%20{url}', 'mastodon' ),
			'tumblr'     => array( 'Tumblr', $post, '#36465d', 'https://www.tumblr.com/widgets/share/tool?canonicalUrl={url}&title={title}&caption={desc}' ),
			'hackernews' => array( 'Hacker News', __( 'Submit', 'fast-social-share-buttons' ), '#c44f00', 'https://news.ycombinator.com/submitlink?u={url}&t={title}' ),
			'flipboard'  => array( 'Flipboard', __( 'Flip', 'fast-social-share-buttons' ), '#e12828', 'https://share.flipboard.com/bookmarklet/popout?v=2&url={url}&title={title}' ),
			'buffer'     => array( 'Buffer', $share, '#231f20', 'https://buffer.com/add?url={url}&text={text}' ),
			'vk'         => array( 'VK', $share, '#006fed', 'https://vk.com/share.php?url={url}&title={title}' ),
			'xing'       => array( 'XING', $share, '#006567', 'https://www.xing.com/social/share/spi?url={url}' ),
			'line'       => array( 'LINE', $send, '#04873a', 'https://social-plugins.line.me/lineit/share?url={url}' ),
			'sms'        => array( __( 'SMS', 'fast-social-share-buttons' ), __( 'Text', 'fast-social-share-buttons' ), '#12863d', 'sms:?&body={text}%20{url}', 'self', __( 'Share by text message', 'fast-social-share-buttons' ) ),
			'email'      => array( __( 'Email', 'fast-social-share-buttons' ), __( 'Email', 'fast-social-share-buttons' ), '#6b7280', 'mailto:?subject={title}&body={url}', 'self', __( 'Share by email', 'fast-social-share-buttons' ) ),
			'copy'       => array( __( 'Copy link', 'fast-social-share-buttons' ), __( 'Copy', 'fast-social-share-buttons' ), '#475569', '', 'copy', __( 'Copy link', 'fast-social-share-buttons' ) ),
			'print'      => array( __( 'Print', 'fast-social-share-buttons' ), __( 'Print', 'fast-social-share-buttons' ), '#64748b', '', 'print', __( 'Print this page', 'fast-social-share-buttons' ) ),
			'share'      => array( __( 'More', 'fast-social-share-buttons' ), $share, '#1f2937', '', 'native', __( 'More sharing options', 'fast-social-share-buttons' ) ),
		);

		foreach ( $networks as $key => $n ) {
			$networks[ $key ] = array(
				'name'   => $n[0],
				'action' => $n[1],
				'color'  => $n[2],
				'url'    => $n[3],
				'type'   => isset( $n[4] ) ? $n[4] : 'popup',
				/* translators: %s: social network name. */
				'title'  => isset( $n[5] ) ? $n[5] : sprintf( __( 'Share on %s', 'fast-social-share-buttons' ), $n[0] ),
			);
		}

		$networks['pinterest']['url_no_image'] = 'https://www.pinterest.com/pin/find/?url={url}';

		return $networks;
	}

	/**
	 * Built-in icons: array( uses stroke?, inner SVG ).
	 *
	 * @return array<string,array{0:bool,1:string}>
	 */
	private static function icons() {
		return array(
			'facebook'   => array( false, '<path d="M9.101 23.691v-7.98H6.627v-3.667h2.474v-1.58c0-4.085 1.848-5.978 5.858-5.978.401 0 .955.042 1.468.103a8.68 8.68 0 0 1 1.141.195v3.325a8.623 8.623 0 0 0-.653-.036 26.805 26.805 0 0 0-.733-.009c-.707 0-1.259.096-1.675.309a1.686 1.686 0 0 0-.679.622c-.258.42-.374.995-.374 1.752v1.297h3.919l-.386 2.103-.287 1.564h-3.246v8.245C19.396 23.238 24 18.179 24 12.044c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.628 3.874 10.35 9.101 11.647Z"/>' ),
			'x'          => array( false, '<path d="M14.234 10.162 22.977 0h-2.072l-7.591 8.824L7.251 0H.258l9.168 13.343L.258 24H2.33l8.016-9.318L16.749 24h6.993zm-2.837 3.299-.929-1.329L3.076 1.56h3.182l5.965 8.532.929 1.329 7.754 11.09h-3.182z"/>' ),
			'linkedin'   => array( false, '<path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452z"/>' ),
			'pinterest'  => array( false, '<path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.162-.105-.949-.199-2.403.041-3.439.219-.937 1.406-5.957 1.406-5.957s-.359-.72-.359-1.781c0-1.663.967-2.911 2.168-2.911 1.024 0 1.518.769 1.518 1.688 0 1.029-.653 2.567-.992 3.992-.285 1.193.6 2.165 1.775 2.165 2.128 0 3.768-2.245 3.768-5.487 0-2.861-2.063-4.869-5.008-4.869-3.41 0-5.409 2.562-5.409 5.199 0 1.033.394 2.143.889 2.741.099.12.112.225.085.345-.09.375-.293 1.199-.334 1.363-.053.225-.172.271-.401.165-1.495-.69-2.433-2.878-2.433-4.646 0-3.776 2.748-7.252 7.92-7.252 4.158 0 7.392 2.967 7.392 6.923 0 4.135-2.607 7.462-6.233 7.462-1.214 0-2.354-.629-2.758-1.379l-.749 2.848c-.269 1.045-1.004 2.352-1.498 3.146 1.123.345 2.306.535 3.55.535 6.607 0 11.985-5.365 11.985-11.987C23.97 5.39 18.592.026 11.985.026L12.017 0z"/>' ),
			'reddit'     => array( false, '<path d="M12 0C5.373 0 0 5.373 0 12c0 3.314 1.343 6.314 3.515 8.485l-2.286 2.286C.775 23.225 1.097 24 1.738 24H12c6.627 0 12-5.373 12-12S18.627 0 12 0Zm4.388 3.199c1.104 0 1.999.895 1.999 1.999 0 1.105-.895 2-1.999 2-.946 0-1.739-.657-1.947-1.539v.002c-1.147.162-2.032 1.15-2.032 2.341v.007c1.776.067 3.4.567 4.686 1.363.473-.363 1.064-.58 1.707-.58 1.547 0 2.802 1.254 2.802 2.802 0 1.117-.655 2.081-1.601 2.531-.088 3.256-3.637 5.876-7.997 5.876-4.361 0-7.905-2.617-7.998-5.87-.954-.447-1.614-1.415-1.614-2.538 0-1.548 1.255-2.802 2.803-2.802.645 0 1.239.218 1.712.585 1.275-.79 2.881-1.291 4.64-1.365v-.01c0-1.663 1.263-3.034 2.88-3.207.188-.911.993-1.595 1.959-1.595Zm-8.085 8.376c-.784 0-1.459.78-1.506 1.797-.047 1.016.64 1.429 1.426 1.429.786 0 1.371-.369 1.418-1.385.047-1.017-.553-1.841-1.338-1.841Zm7.406 0c-.786 0-1.385.824-1.338 1.841.047 1.017.634 1.385 1.418 1.385.785 0 1.473-.413 1.426-1.429-.046-1.017-.721-1.797-1.506-1.797Zm-3.703 4.013c-.974 0-1.907.048-2.77.135-.147.015-.241.168-.183.305.483 1.154 1.622 1.964 2.953 1.964 1.33 0 2.47-.81 2.953-1.964.057-.137-.037-.29-.184-.305-.863-.087-1.795-.135-2.769-.135Z"/>' ),
			'whatsapp'   => array( false, '<path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>' ),
			'telegram'   => array( false, '<path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>' ),
			'bluesky'    => array( false, '<path d="M5.202 2.857C7.954 4.922 10.913 9.11 12 11.358c1.087-2.247 4.046-6.436 6.798-8.501C20.783 1.366 24 .213 24 3.883c0 .732-.42 6.156-.667 7.037-.856 3.061-3.978 3.842-6.755 3.37 4.854.826 6.089 3.562 3.422 6.299-5.065 5.196-7.28-1.304-7.847-2.97-.104-.305-.152-.448-.153-.327 0-.121-.05.022-.153.327-.568 1.666-2.782 8.166-7.847 2.97-2.667-2.737-1.432-5.473 3.422-6.3-2.777.473-5.899-.308-6.755-3.369C.42 10.04 0 4.615 0 3.883c0-3.67 3.217-2.517 5.202-1.026"/>' ),
			'threads'    => array( false, '<path d="M12.186 24h-.007c-3.581-.024-6.334-1.205-8.184-3.509C2.35 18.44 1.5 15.586 1.472 12.01v-.017c.03-3.579.879-6.43 2.525-8.482C5.845 1.205 8.6.024 12.18 0h.014c2.746.02 5.043.725 6.826 2.098 1.677 1.29 2.858 3.13 3.509 5.467l-2.04.569c-1.104-3.96-3.898-5.984-8.304-6.015-2.91.022-5.11.936-6.54 2.717C4.307 6.504 3.616 8.914 3.589 12c.027 3.086.718 5.496 2.057 7.164 1.43 1.783 3.631 2.698 6.54 2.717 2.623-.02 4.358-.631 5.8-2.045 1.647-1.613 1.618-3.593 1.09-4.798-.31-.71-.873-1.3-1.634-1.75-.192 1.352-.622 2.446-1.284 3.272-.886 1.102-2.14 1.704-3.73 1.79-1.202.065-2.361-.218-3.259-.801-1.063-.689-1.685-1.74-1.752-2.964-.065-1.19.408-2.285 1.33-3.082.88-.76 2.119-1.207 3.583-1.291a13.853 13.853 0 0 1 3.02.142c-.126-.742-.375-1.332-.75-1.757-.513-.586-1.308-.883-2.359-.89h-.029c-.844 0-1.992.232-2.721 1.32L7.734 7.847c.98-1.454 2.568-2.256 4.478-2.256h.044c3.194.02 5.097 1.975 5.287 5.388.108.046.216.094.321.142 1.49.7 2.58 1.761 3.154 3.07.797 1.82.871 4.79-1.548 7.158-1.85 1.81-4.094 2.628-7.277 2.65Zm1.003-11.69c-.242 0-.487.007-.739.021-1.836.103-2.98.946-2.916 2.143.067 1.256 1.452 1.839 2.784 1.767 1.224-.065 2.818-.543 3.086-3.71a10.5 10.5 0 0 0-2.215-.221z"/>' ),
			'mastodon'   => array( false, '<path d="M23.268 5.313c-.35-2.578-2.617-4.61-5.304-5.004C17.51.242 15.792 0 11.813 0h-.03c-3.98 0-4.835.242-5.288.309C3.882.692 1.496 2.518.917 5.127.64 6.412.61 7.837.661 9.143c.074 1.874.088 3.745.26 5.611.118 1.24.325 2.47.62 3.68.55 2.237 2.777 4.098 4.96 4.857 2.336.792 4.849.923 7.256.38.265-.061.527-.132.786-.213.585-.184 1.27-.39 1.774-.753a.057.057 0 0 0 .023-.043v-1.809a.052.052 0 0 0-.02-.041.053.053 0 0 0-.046-.01 20.282 20.282 0 0 1-4.709.545c-2.73 0-3.463-1.284-3.674-1.818a5.593 5.593 0 0 1-.319-1.433.053.053 0 0 1 .066-.054c1.517.363 3.072.546 4.632.546.376 0 .75 0 1.125-.01 1.57-.044 3.224-.124 4.768-.422.038-.008.077-.015.11-.024 2.435-.464 4.753-1.92 4.989-5.604.008-.145.03-1.52.03-1.67.002-.512.167-3.63-.024-5.545zm-3.748 9.195h-2.561V8.29c0-1.309-.55-1.976-1.67-1.976-1.23 0-1.846.79-1.846 2.35v3.403h-2.546V8.663c0-1.56-.617-2.35-1.848-2.35-1.112 0-1.668.668-1.67 1.977v6.218H4.822V8.102c0-1.31.337-2.35 1.011-3.12.696-.77 1.608-1.164 2.74-1.164 1.311 0 2.302.5 2.962 1.498l.638 1.06.638-1.06c.66-.999 1.65-1.498 2.96-1.498 1.13 0 2.043.395 2.74 1.164.675.77 1.012 1.81 1.012 3.12z"/>' ),
			'tumblr'     => array( false, '<path d="M14.563 24c-5.093 0-7.031-3.756-7.031-6.411V9.747H5.116V6.648c3.63-1.313 4.512-4.596 4.71-6.469C9.84.051 9.941 0 9.999 0h3.517v6.114h4.801v3.633h-4.82v7.47c.016 1.001.375 2.371 2.207 2.371h.09c.631-.02 1.486-.205 1.936-.419l1.156 3.425c-.436.636-2.4 1.374-4.156 1.404h-.178l.011.002z"/>' ),
			'hackernews' => array( false, '<path d="M0 24V0h24v24H0zM6.951 5.896l4.112 7.708v5.064h1.583v-4.972l4.148-7.799h-1.749l-2.457 4.875c-.372.745-.688 1.434-.688 1.434s-.297-.708-.651-1.434L8.831 5.896h-1.88z"/>' ),
			'flipboard'  => array( false, '<path d="M0 0v24h24V0H0zm19.2 9.6h-4.8v4.8H9.6v4.8H4.8V4.8h14.4v4.8z"/>' ),
			'buffer'     => array( false, '<path d="M1.371 5.476L11.943 0l10.686 5.476-10.686 5.495zm3.36 4.81l7.212 3.547 7.288-3.547 3.398 1.655-10.686 5.202L1.371 11.94zm0 6.171l7.212 3.911 7.288-3.91 3.398 1.815L11.943 24 1.371 18.273z"/>' ),
			'vk'         => array( false, '<path d="m9.489.004.729-.003h3.564l.73.003.914.01.433.007.418.011.403.014.388.016.374.021.36.025.345.03.333.033c1.74.196 2.933.616 3.833 1.516.9.9 1.32 2.092 1.516 3.833l.034.333.029.346.025.36.02.373.025.588.012.41.013.644.009.915.004.98-.001 3.313-.003.73-.01.914-.007.433-.011.418-.014.403-.016.388-.021.374-.025.36-.03.345-.033.333c-.196 1.74-.616 2.933-1.516 3.833-.9.9-2.092 1.32-3.833 1.516l-.333.034-.346.029-.36.025-.373.02-.588.025-.41.012-.644.013-.915.009-.98.004-3.313-.001-.73-.003-.914-.01-.433-.007-.418-.011-.403-.014-.388-.016-.374-.021-.36-.025-.345-.03-.333-.033c-1.74-.196-2.933-.616-3.833-1.516-.9-.9-1.32-2.092-1.516-3.833l-.034-.333-.029-.346-.025-.36-.02-.373-.025-.588-.012-.41-.013-.644-.009-.915-.004-.98.001-3.313.003-.73.01-.914.007-.433.011-.418.014-.403.016-.388.021-.374.025-.36.03-.345.033-.333c.196-1.74.616-2.933 1.516-3.833.9-.9 2.092-1.32 3.833-1.516l.333-.034.346-.029.36-.025.373-.02.588-.025.41-.012.644-.013.915-.009ZM6.79 7.3H4.05c.13 6.24 3.25 9.99 8.72 9.99h.31v-3.57c2.01.2 3.53 1.67 4.14 3.57h2.84c-.78-2.84-2.83-4.41-4.11-5.01 1.28-.74 3.08-2.54 3.51-4.98h-2.58c-.56 1.98-2.22 3.78-3.8 3.95V7.3H10.5v6.92c-1.6-.4-3.62-2.34-3.71-6.92Z"/>' ),
			'xing'       => array( false, '<path d="M18.188 0c-.517 0-.741.325-.927.66 0 0-7.455 13.224-7.702 13.657.015.024 4.919 9.023 4.919 9.023.17.308.436.66.967.66h3.454c.211 0 .375-.078.463-.22.089-.151.089-.346-.009-.536l-4.879-8.916c-.004-.006-.004-.016 0-.022L22.139.756c.095-.191.097-.387.006-.535C22.056.078 21.894 0 21.686 0h-3.498zM3.648 4.74c-.211 0-.385.074-.473.216-.09.149-.078.339.02.531l2.34 4.05c.004.01.004.016 0 .021L1.86 16.051c-.099.188-.093.381 0 .529.085.142.239.234.45.234h3.461c.518 0 .766-.348.945-.667l3.734-6.609-2.378-4.155c-.172-.315-.434-.659-.962-.659H3.648v.016z"/>' ),
			'line'       => array( false, '<path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>' ),
			'sms'        => array( true, '<path d="M2.992 16.342a2 2 0 0 1 .094 1.167l-1.065 3.29a1 1 0 0 0 1.236 1.168l3.413-.998a2 2 0 0 1 1.099.092 10 10 0 1 0-4.777-4.719"/>' ),
			'email'      => array( true, '<path d="m22 7-8.991 5.727a2 2 0 0 1-2.009 0L2 7"/><rect x="2" y="4" width="20" height="16" rx="2"/>' ),
			'copy'       => array( true, '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>' ),
			'print'      => array( true, '<path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 9V3a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v6"/><rect x="6" y="14" width="12" height="8" rx="1"/>' ),
			'share'      => array( true, '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" x2="15.42" y1="13.51" y2="17.49"/><line x1="15.41" x2="8.59" y1="6.51" y2="10.49"/>' ),
			'check'      => array( true, '<path d="M20 6 9 17l-5-5"/>' ),
		);
	}
}
