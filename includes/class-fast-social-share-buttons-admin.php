<?php
/**
 * Admin side: the Content Powered > Fast Social Share Buttons page and the Plugins-screen link.
 *
 * @package Fast_Social_Share_Buttons
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings page controller.
 */
final class Fast_Social_Share_Buttons_Admin {

	/**
	 * Settings page slug, also used as the settings group.
	 */
	const PAGE = 'fast-social-share-buttons';

	/**
	 * Lucide icon shown beside the menu item and page title.
	 */
	const ICON = 'share-2';

	/**
	 * Settings tabs: id => Lucide icon.
	 */
	const TABS = array(
		'networks'  => 'list-checks',
		'placement' => 'panels-top-left',
		'style'     => 'palette',
		'advanced'  => 'settings-2',
	);

	/**
	 * Plugin instance.
	 *
	 * @var Fast_Social_Share_Buttons_Plugin
	 */
	private $plugin;

	/**
	 * Hook suffix of the settings page.
	 *
	 * @var string
	 */
	private $hook_suffix = '';

	/**
	 * Constructor.
	 *
	 * @param Fast_Social_Share_Buttons_Plugin $plugin Plugin instance.
	 */
	public function __construct( Fast_Social_Share_Buttons_Plugin $plugin ) {
		$this->plugin = $plugin;

		Fast_Social_Share_Buttons_Menu::init();
		Fast_Social_Share_Buttons_Feedback::init();

		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_head', array( $this, 'print_menu_icon_style' ) );

		add_filter(
			'plugin_action_links_' . plugin_basename( FAST_SOCIAL_SHARE_BUTTONS_FILE ),
			array( $this, 'add_settings_link' )
		);
	}

	/**
	 * Add a "Settings" link to the plugin's row on the Plugins screen.
	 *
	 * @param array $links Existing action links (Deactivate, etc.).
	 * @return array
	 */
	public function add_settings_link( $links ) {
		if ( ! is_array( $links ) ) {
			return $links;
		}

		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=' . self::PAGE ) ),
			esc_html__( 'Settings', 'fast-social-share-buttons' )
		);

		array_unshift( $links, $settings_link );

		return $links;
	}

	/**
	 * Add the page under the shared Content Powered menu.
	 *
	 * @return void
	 */
	public function register_menu() {
		$icon       = Fast_Social_Share_Buttons_Icons::get( self::ICON, 17, 'fast-social-share-buttons-menu-icon' );
		$menu_title = $icon . '<span class="fast-social-share-buttons-menu-label">' . esc_html__( 'Fast Social Share Buttons', 'fast-social-share-buttons' ) . '</span>';

		$this->hook_suffix = add_submenu_page(
			Fast_Social_Share_Buttons_Menu::SLUG,
			__( 'Fast Social Share Buttons', 'fast-social-share-buttons' ),
			$menu_title,
			'manage_options',
			self::PAGE,
			array( $this, 'render_page' )
		);

		if ( $this->hook_suffix ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
			add_filter( 'admin_body_class', array( $this, 'body_class' ) );
		}
	}

	/**
	 * Mark the settings page's <body>, so its header can run edge to edge.
	 *
	 * @param string $classes Space-separated body classes.
	 * @return string
	 */
	public function body_class( $classes ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		return $screen && $screen->id === $this->hook_suffix ? $classes . ' fssb-settings-page' : $classes;
	}

	/**
	 * Register the settings, sections and fields shown on the page.
	 *
	 * Each section is a box on one of the tabs; the tabs are switched in the
	 * browser, so one form saves every tab at once.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			self::PAGE,
			Fast_Social_Share_Buttons_Plugin::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => Fast_Social_Share_Buttons_Plugin::defaults(),
			)
		);

		foreach ( $this->sections() as $id => $section ) {
			add_settings_section( 'fssb_' . $id, $section['title'], '__return_false', self::PAGE );
		}

		// Networks and automatic placement have their own full-width layouts
		// (see sections()); these are the rows of the other boxes.
		$fields = array(
			// Floating.
			array( 'float_position', __( 'Sidebar side', 'fast-social-share-buttons' ), 'render_cards', 'floating' ),
			array( 'float_trigger', __( 'Show floating buttons', 'fast-social-share-buttons' ), 'render_radios', 'floating' ),
			array(
				'mobile_bar',
				__( 'Phones and tablets', 'fast-social-share-buttons' ),
				'render_checkbox',
				'floating',
				array( 'label' => __( 'Show a share bar along the bottom of the screen instead of the sidebar', 'fast-social-share-buttons' ) ),
			),
			array(
				'breakpoint',
				__( 'Sidebar from', 'fast-social-share-buttons' ),
				'render_number',
				'floating',
				array(
					'min'         => 320,
					'max'         => 3000,
					'suffix'      => __( 'px wide', 'fast-social-share-buttons' ),
					'description' => __( 'Narrower screens get the bottom bar (or nothing, if it\'s off).', 'fast-social-share-buttons' ),
				),
			),

			// Style.
			array( 'shape', __( 'Shape', 'fast-social-share-buttons' ), 'render_cards', 'style' ),
			array( 'colors', __( 'Colours', 'fast-social-share-buttons' ), 'render_cards', 'style' ),
			array( 'size', __( 'Size', 'fast-social-share-buttons' ), 'render_cards', 'style' ),
			array(
				'labels',
				__( 'Labels', 'fast-social-share-buttons' ),
				'render_cards',
				'style',
				array( 'description' => __( 'Labels show only when every one fits; otherwise every button switches to its icon, so a row never mixes the two.', 'fast-social-share-buttons' ) ),
			),
			array( 'label_style', __( 'Label text', 'fast-social-share-buttons' ), 'render_radios', 'style' ),
			array(
				'align',
				__( 'Alignment', 'fast-social-share-buttons' ),
				'render_cards',
				'style',
				array( 'description' => __( 'Fill shares the row equally between the buttons. Left, centre and right use fixed-width buttons sized to the longest label.', 'fast-social-share-buttons' ) ),
			),
			array(
				'heading',
				__( 'Heading', 'fast-social-share-buttons' ),
				'render_text',
				'style',
				array(
					'placeholder' => __( 'Share this:', 'fast-social-share-buttons' ),
					'description' => __( 'Optional text above the in-content buttons.', 'fast-social-share-buttons' ),
				),
			),

			// Advanced.
			array(
				'x_username',
				__( 'X username', 'fast-social-share-buttons' ),
				'render_text',
				'advanced',
				array(
					'prefix'      => '@',
					'description' => __( 'Adds "via @username" to posts shared on X.', 'fast-social-share-buttons' ),
				),
			),
			array(
				'utm',
				__( 'Link tracking', 'fast-social-share-buttons' ),
				'render_checkbox',
				'advanced',
				array( 'label' => __( 'Add UTM parameters to shared links (utm_source is the network, utm_medium is "social")', 'fast-social-share-buttons' ) ),
			),
			array( 'utm_campaign', __( 'UTM campaign', 'fast-social-share-buttons' ), 'render_text', 'advanced' ),
			array(
				'pin_hover',
				__( 'Pinterest on images', 'fast-social-share-buttons' ),
				'render_checkbox',
				'advanced',
				array( 'label' => __( 'Show a Pinterest "Save" button when hovering over images in posts', 'fast-social-share-buttons' ) ),
			),
			array(
				'pin_min_width',
				__( 'Smallest image', 'fast-social-share-buttons' ),
				'render_number',
				'advanced',
				array(
					'min'    => 50,
					'max'    => 2000,
					'suffix' => __( 'px wide gets the Pinterest button', 'fast-social-share-buttons' ),
				),
			),
			array(
				'sw_compat',
				__( 'Social Warfare', 'fast-social-share-buttons' ),
				'render_checkbox',
				'advanced',
				array( 'label' => __( 'Handle Social Warfare\'s [social_warfare] and [clickToTweet] shortcodes, so old posts keep working after switching', 'fast-social-share-buttons' ) ),
			),
		);

		foreach ( $fields as $field ) {
			$args = isset( $field[4] ) ? $field[4] : array();

			$args['key']    = $field[0];
			$args['legend'] = $field[1];
			if ( in_array( $field[2], array( 'render_text', 'render_number' ), true ) ) {
				$args['label_for'] = 'fssb-' . $field[0];
			}

			add_settings_field( 'fssb_' . $field[0], $field[1], array( $this, $field[2] ), self::PAGE, 'fssb_' . $field[3], $args );
		}
	}

	/**
	 * Clean the submitted settings before they are saved.
	 *
	 * @param mixed $input Raw form values.
	 * @return array<string,mixed>
	 */
	public function sanitize( $input ) {
		$input    = is_array( $input ) ? $input : array();
		$defaults = Fast_Social_Share_Buttons_Plugin::defaults();
		$clean    = array();

		$clean['networks'] = isset( $input['networks'] ) ? Fast_Social_Share_Buttons_Networks::filter( (array) $input['networks'] ) : array();

		$clean['placements'] = array();
		foreach ( Fast_Social_Share_Buttons_Meta_Box::post_types() as $type ) {
			foreach ( array( 'above', 'below', 'floating' ) as $placement ) {
				if ( ! empty( $input['placements'][ $type ][ $placement ] ) ) {
					$clean['placements'][ $type ][ $placement ] = 1;
				}
			}
		}

		foreach ( array_keys( Fast_Social_Share_Buttons_Plugin::choices() ) as $key ) {
			if ( isset( $defaults[ $key ] ) ) {
				$value         = isset( $input[ $key ] ) ? $input[ $key ] : '';
				$clean[ $key ] = Fast_Social_Share_Buttons_Plugin::is_choice( $key, $value ) ? $value : $defaults[ $key ];
			}
		}

		foreach ( array( 'mobile_bar', 'utm', 'pin_hover', 'sw_compat' ) as $key ) {
			$clean[ $key ] = empty( $input[ $key ] ) ? 0 : 1;
		}

		$clean['heading']       = isset( $input['heading'] ) ? sanitize_text_field( $input['heading'] ) : '';
		$clean['breakpoint']    = isset( $input['breakpoint'] ) ? min( 3000, max( 320, absint( $input['breakpoint'] ) ) ) : $defaults['breakpoint'];
		$clean['pin_min_width'] = isset( $input['pin_min_width'] ) ? min( 2000, max( 50, absint( $input['pin_min_width'] ) ) ) : $defaults['pin_min_width'];
		$clean['x_username']    = isset( $input['x_username'] ) ? substr( preg_replace( '/[^A-Za-z0-9_]/', '', (string) $input['x_username'] ), 0, 15 ) : '';
		$clean['utm_campaign']  = isset( $input['utm_campaign'] ) ? sanitize_title( $input['utm_campaign'] ) : '';

		if ( '' === $clean['utm_campaign'] ) {
			$clean['utm_campaign'] = $defaults['utm_campaign'];
		}

		return $clean;
	}

	/**
	 * Network list: tick to show, drag (or use the arrows) to reorder.
	 *
	 * @return void
	 */
	public function render_networks() {
		$enabled = (array) $this->plugin->get_setting( 'networks' );
		$all     = Fast_Social_Share_Buttons_Networks::all();
		$ordered = array_merge( Fast_Social_Share_Buttons_Networks::filter( $enabled ), array_diff( array_keys( $all ), $enabled ) );
		$name    = Fast_Social_Share_Buttons_Plugin::OPTION . '[networks][]';
		?>
		<ul class="fssb-networks" data-fssb-networks>
			<?php foreach ( $ordered as $key ) : ?>
				<?php $network = $all[ $key ]; ?>
				<li class="fssb-network" data-fssb-network="<?php echo esc_attr( $key ); ?>">
					<span class="fssb-network-handle" aria-hidden="true"><?php echo Fast_Social_Share_Buttons_Icons::get( 'grip-vertical', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Bundled SVG. ?></span>
					<label>
						<input type="checkbox" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $enabled, true ) ); ?>>
						<span class="fssb-network-icon fssb-<?php echo esc_attr( $key ); ?>"><svg class="fssb-i" aria-hidden="true" focusable="false"><use href="#fssb-<?php echo esc_attr( $key ); ?>"></use></svg></span>
						<span class="fssb-network-name"><?php echo esc_html( $network['name'] ); ?></span>
					</label>
					<span class="fssb-network-move">
						<button type="button" class="button-link" data-fssb-move="-1" aria-label="<?php /* translators: %s: network name. */ echo esc_attr( sprintf( __( 'Move %s up', 'fast-social-share-buttons' ), $network['name'] ) ); ?>"><?php echo Fast_Social_Share_Buttons_Icons::get( 'chevron-up', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Bundled SVG. ?></button>
						<button type="button" class="button-link" data-fssb-move="1" aria-label="<?php /* translators: %s: network name. */ echo esc_attr( sprintf( __( 'Move %s down', 'fast-social-share-buttons' ), $network['name'] ) ); ?>"><?php echo Fast_Social_Share_Buttons_Icons::get( 'chevron-down', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Bundled SVG. ?></button>
					</span>
				</li>
			<?php endforeach; ?>
		</ul>
		<p class="description"><?php esc_html_e( '"More" opens the phone or browser\'s own share menu (where there isn\'t one, it copies the link).', 'fast-social-share-buttons' ); ?></p>
		<?php
	}

	/**
	 * Post type × placement grid.
	 *
	 * @return void
	 */
	public function render_placements() {
		$saved   = (array) $this->plugin->get_setting( 'placements' );
		$columns = array(
			'above'    => __( 'Above content', 'fast-social-share-buttons' ),
			'below'    => __( 'Below content', 'fast-social-share-buttons' ),
			'floating' => __( 'Floating', 'fast-social-share-buttons' ),
		);
		?>
		<table class="widefat striped fssb-placements">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Content type', 'fast-social-share-buttons' ); ?></th>
					<?php foreach ( $columns as $label ) : ?>
						<th scope="col"><?php echo esc_html( $label ); ?></th>
					<?php endforeach; ?>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( Fast_Social_Share_Buttons_Meta_Box::post_types() as $type ) : ?>
					<?php
					$object = get_post_type_object( $type );
					$label  = $object ? $object->labels->name : $type;
					?>
					<tr>
						<th scope="row"><?php echo esc_html( $label ); ?></th>
						<?php foreach ( $columns as $placement => $column ) : ?>
							<td>
								<input type="checkbox"
									name="<?php echo esc_attr( Fast_Social_Share_Buttons_Plugin::OPTION . '[placements][' . $type . '][' . $placement . ']' ); ?>"
									value="1"
									aria-label="<?php echo esc_attr( $label . ': ' . $column ); ?>"
									<?php checked( ! empty( $saved[ $type ][ $placement ] ) ); ?>>
							</td>
						<?php endforeach; ?>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<p class="description"><?php esc_html_e( 'Untick a content type to place its buttons by hand instead, with the shortcode or block (see Manual placement below). To skip a single post, use the Social Sharing box on its edit screen.', 'fast-social-share-buttons' ); ?></p>
		<p class="description"><?php esc_html_e( 'Either way, the plugin\'s CSS and JavaScript only load on pages that actually show buttons.', 'fast-social-share-buttons' ); ?></p>
		<?php
	}

	/**
	 * Radio buttons for a multiple-choice setting.
	 *
	 * @param array $args Field arguments; "key" is the setting.
	 * @return void
	 */
	public function render_radios( $args ) {
		$key     = $args['key'];
		$choices = Fast_Social_Share_Buttons_Plugin::choices();
		$current = (string) $this->plugin->get_setting( $key );
		?>
		<fieldset class="fssb-radios">
			<legend class="screen-reader-text"><?php echo esc_html( isset( $args['legend'] ) ? $args['legend'] : $key ); ?></legend>
			<?php foreach ( $choices[ $key ] as $value => $label ) : ?>
				<label>
					<input type="radio" name="<?php echo esc_attr( Fast_Social_Share_Buttons_Plugin::OPTION . '[' . $key . ']' ); ?>" value="<?php echo esc_attr( $value ); ?>" <?php checked( $current, $value ); ?>>
					<?php echo esc_html( $label ); ?>
				</label>
			<?php endforeach; ?>
		</fieldset>
		<?php
	}

	/**
	 * Visual option cards for a multiple-choice setting: radio buttons with a
	 * small drawing of each option.
	 *
	 * @param array $args Field arguments: key, legend, description.
	 * @return void
	 */
	public function render_cards( $args ) {
		$key     = $args['key'];
		$choices = Fast_Social_Share_Buttons_Plugin::choices();
		$current = (string) $this->plugin->get_setting( $key );
		?>
		<fieldset class="fssb-cards">
			<legend class="screen-reader-text"><?php echo esc_html( $args['legend'] ); ?></legend>
			<?php foreach ( $choices[ $key ] as $value => $label ) : ?>
				<label class="fssb-card">
					<input type="radio" name="<?php echo esc_attr( Fast_Social_Share_Buttons_Plugin::OPTION . '[' . $key . ']' ); ?>" value="<?php echo esc_attr( $value ); ?>" <?php checked( $current, $value ); ?>>
					<span class="fssb-card-visual fssb-v-<?php echo esc_attr( $key ); ?> fssb-v-<?php echo esc_attr( $key . '-' . $value ); ?>" aria-hidden="true"><i></i><i></i><i></i></span>
					<span class="fssb-card-label"><?php echo esc_html( $label ); ?></span>
				</label>
			<?php endforeach; ?>
		</fieldset>
		<?php
		$this->render_description( $args );
	}

	/**
	 * A checkbox setting.
	 *
	 * @param array $args Field arguments: key, label.
	 * @return void
	 */
	public function render_checkbox( $args ) {
		printf(
			'<label><input type="checkbox" name="%1$s[%2$s]" value="1" %3$s> %4$s</label>',
			esc_attr( Fast_Social_Share_Buttons_Plugin::OPTION ),
			esc_attr( $args['key'] ),
			checked( 1, (int) $this->plugin->get_setting( $args['key'] ), false ),
			esc_html( $args['label'] )
		);
	}

	/**
	 * A text setting.
	 *
	 * @param array $args Field arguments: key, placeholder, prefix, description.
	 * @return void
	 */
	public function render_text( $args ) {
		if ( ! empty( $args['prefix'] ) ) {
			echo '<span class="fssb-prefix">' . esc_html( $args['prefix'] ) . '</span>';
		}

		printf(
			'<input type="text" class="regular-text" id="fssb-%1$s" name="%2$s[%1$s]" value="%3$s" placeholder="%4$s">',
			esc_attr( $args['key'] ),
			esc_attr( Fast_Social_Share_Buttons_Plugin::OPTION ),
			esc_attr( (string) $this->plugin->get_setting( $args['key'] ) ),
			esc_attr( isset( $args['placeholder'] ) ? $args['placeholder'] : '' )
		);

		$this->render_description( $args );
	}

	/**
	 * A number setting.
	 *
	 * @param array $args Field arguments: key, min, max, suffix, description.
	 * @return void
	 */
	public function render_number( $args ) {
		printf(
			'<input type="number" class="small-text" id="fssb-%1$s" name="%2$s[%1$s]" value="%3$d" min="%4$d" max="%5$d" step="1"> %6$s',
			esc_attr( $args['key'] ),
			esc_attr( Fast_Social_Share_Buttons_Plugin::OPTION ),
			absint( $this->plugin->get_setting( $args['key'] ) ),
			absint( $args['min'] ),
			absint( $args['max'] ),
			esc_html( isset( $args['suffix'] ) ? $args['suffix'] : '' )
		);

		$this->render_description( $args );
	}

	/**
	 * Help text under a field.
	 *
	 * @param array $args Field arguments.
	 * @return void
	 */
	private function render_description( $args ) {
		if ( ! empty( $args['description'] ) ) {
			echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
		}
	}

	/**
	 * Manual placement: the shortcode (with its options), block, click to
	 * tweet, widget and template tag, each ready to copy.
	 *
	 * @return void
	 */
	public function render_manual() {
		$choices  = Fast_Social_Share_Buttons_Plugin::choices();
		$networks = array_keys( Fast_Social_Share_Buttons_Networks::all() );
		$options  = array(
			'networks' => implode( ', ', $networks ),
			'shape'    => implode( ', ', array_keys( $choices['shape'] ) ),
			'colors'   => implode( ', ', array_keys( $choices['colors'] ) ),
			'size'     => implode( ', ', array_keys( $choices['size'] ) ),
			'labels'   => implode( ', ', array_keys( $choices['labels'] ) ),
			'align'    => implode( ', ', array_keys( $choices['align'] ) ),
			'layout'   => implode( ', ', array_keys( $choices['layout'] ) ),
			'heading'  => __( 'any text', 'fast-social-share-buttons' ),
		);
		?>
		<table class="form-table fssb-manual" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Shortcode', 'fast-social-share-buttons' ); ?></th>
				<td>
					<?php $this->render_copy( '[fast_social_share]' ); ?>
					<p class="description"><?php esc_html_e( 'Paste into any post or page (or a Shortcode block). Add options to change one placement without touching the settings:', 'fast-social-share-buttons' ); ?></p>
					<?php $this->render_copy( '[fast_social_share networks="facebook,x,linkedin" shape="pill" align="center"]' ); ?>
					<table class="fssb-options">
						<?php foreach ( $options as $option => $values ) : ?>
							<tr>
								<td><code><?php echo esc_html( $option ); ?></code></td>
								<td><?php echo esc_html( $values ); ?></td>
							</tr>
						<?php endforeach; ?>
					</table>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Block', 'fast-social-share-buttons' ); ?></th>
				<td><?php esc_html_e( 'Search for "Social Share Buttons" in the block inserter. Works in posts, pages, templates and widget areas, with the same options in the block\'s sidebar.', 'fast-social-share-buttons' ); ?></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Click to tweet', 'fast-social-share-buttons' ); ?></th>
				<td>
					<?php $this->render_copy( '[fast_click_to_tweet tweet="What gets posted"]The quote shown on the page[/fast_click_to_tweet]' ); ?>
					<p class="description"><?php esc_html_e( 'A quote box that opens X with the text ready to post. Leave out tweet="..." to post the quote itself.', 'fast-social-share-buttons' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Widget', 'fast-social-share-buttons' ); ?></th>
				<td><?php esc_html_e( 'Use the block in any widget area. Sites on the classic widgets screen get a "Social Share Buttons" widget, horizontal or stacked.', 'fast-social-share-buttons' ); ?></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Theme template', 'fast-social-share-buttons' ); ?></th>
				<td>
					<?php $this->render_copy( '<?php echo fast_social_share_buttons_render(); ?>' ); ?>
					<p class="description"><?php esc_html_e( 'Takes the same options as the shortcode, as an array:', 'fast-social-share-buttons' ); ?> <code>array( 'layout' => 'column' )</code></p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * A line of code with a Copy button.
	 *
	 * @param string $code Code to show and copy.
	 * @return void
	 */
	private function render_copy( $code ) {
		printf(
			'<div class="fssb-copy-field"><code>%1$s</code><button type="button" class="button button-small" data-fssb-copy="%2$s">%3$s</button></div>',
			esc_html( $code ),
			esc_attr( $code ),
			esc_html__( 'Copy', 'fast-social-share-buttons' )
		);
	}

	/**
	 * Tiny inline style so the menu icon sits neatly beside its label, even
	 * when a long plugin name wraps onto a second line.
	 *
	 * @return void
	 */
	public function print_menu_icon_style() {
		echo '<style id="fast-social-share-buttons-menu-icon-style">'
			. '#adminmenu a:has(>.fast-social-share-buttons-menu-icon){display:flex;align-items:flex-start;gap:7px}'
			. '#adminmenu .fast-social-share-buttons-menu-icon{flex:none;margin-top:1px;opacity:.7}'
			. '#adminmenu li.current .fast-social-share-buttons-menu-icon,#adminmenu a:hover .fast-social-share-buttons-menu-icon,#adminmenu a:focus .fast-social-share-buttons-menu-icon{opacity:1}'
			. '</style>';
	}

	/**
	 * Load the settings-page styles and scripts, plus the front-end button
	 * styles for the preview.
	 *
	 * @param string $hook Current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_assets( $hook ) {
		if ( $hook !== $this->hook_suffix ) {
			return;
		}

		wp_enqueue_style( 'fast-social-share-buttons-admin', FAST_SOCIAL_SHARE_BUTTONS_URL . 'assets/admin.css', array(), FAST_SOCIAL_SHARE_BUTTONS_VERSION );
		wp_add_inline_style( 'fast-social-share-buttons-admin', $this->plugin->frontend()->css() );

		wp_enqueue_script( 'fast-social-share-buttons-admin', FAST_SOCIAL_SHARE_BUTTONS_URL . 'assets/admin.js', array( 'jquery-ui-sortable' ), FAST_SOCIAL_SHARE_BUTTONS_VERSION, true );

		$labels = array();
		foreach ( Fast_Social_Share_Buttons_Networks::all() as $key => $network ) {
			$labels[ $key ] = array(
				'name'   => $network['name'],
				'action' => $network['action'],
			);
		}

		wp_localize_script(
			'fast-social-share-buttons-admin',
			'fast_social_share_buttons_admin',
			array(
				'option'   => Fast_Social_Share_Buttons_Plugin::OPTION,
				'networks' => $labels,
				'copied'   => __( 'Copied', 'fast-social-share-buttons' ),
				/* translators: 1: networks switched on, 2: all networks. */
				'count'    => __( '%1$s of %2$s on', 'fast-social-share-buttons' ),
			)
		);
	}

	/**
	 * The page's boxes, in order: the tab each one is on, its icon, title and
	 * intro, and (for boxes that aren't a plain form table) what draws it.
	 *
	 * @return array<string,array<string,string>>
	 */
	private function sections() {
		return array(
			'networks'  => array(
				'panel'  => 'networks',
				'icon'   => 'list-checks',
				'title'  => __( 'Networks', 'fast-social-share-buttons' ),
				'intro'  => __( 'Tick the networks to show, then drag them (or use the arrows) into order.', 'fast-social-share-buttons' ),
				'render' => 'render_networks',
			),
			'placement' => array(
				'panel'  => 'placement',
				'icon'   => 'panels-top-left',
				'title'  => __( 'Automatic placement', 'fast-social-share-buttons' ),
				'intro'  => __( 'Where buttons appear on their own, for each type of content.', 'fast-social-share-buttons' ),
				'render' => 'render_placements',
			),
			'manual'    => array(
				'panel'  => 'placement',
				'icon'   => 'code',
				'title'  => __( 'Manual placement', 'fast-social-share-buttons' ),
				'intro'  => __( 'Put share buttons exactly where you want them, on any post, page or template. Use these alongside the automatic placements above, or untick those and place buttons only by hand. Anything you leave out uses the settings on the other tabs.', 'fast-social-share-buttons' ),
				'render' => 'render_manual',
			),
			'floating'  => array(
				'panel' => 'placement',
				'icon'  => 'panel-left',
				'title' => __( 'Floating buttons', 'fast-social-share-buttons' ),
				'intro' => __( 'For content types with Floating ticked: a sidebar on wide screens and a share bar along the bottom on phones.', 'fast-social-share-buttons' ),
			),
			'style'     => array(
				'panel' => 'style',
				'icon'  => 'palette',
				'title' => __( 'Style', 'fast-social-share-buttons' ),
				'intro' => __( 'How the buttons look. The preview at the top updates as you go.', 'fast-social-share-buttons' ),
			),
			'advanced'  => array(
				'panel' => 'advanced',
				'icon'  => 'settings-2',
				'title' => __( 'Advanced', 'fast-social-share-buttons' ),
				'intro' => '',
			),
		);
	}

	/**
	 * Render the settings page: a header with the tabs, then WordPress's
	 * two-column box layout (settings on the left, Save and status on the right).
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$all_networks = array_keys( Fast_Social_Share_Buttons_Networks::all() );
		$renderer     = $this->plugin->renderer();

		// Every network, in list order; the script hides the unticked ones.
		$preview = $renderer->render(
			array(
				'context'  => 'preview',
				'post_id'  => 0,
				'networks' => array_merge( (array) $this->plugin->get_setting( 'networks' ), $all_networks ),
			)
		);
		?>
		<div class="fssb-header">
			<div class="fssb-header-title">
				<span class="fssb-header-icon"><?php echo Fast_Social_Share_Buttons_Icons::get( self::ICON, 20 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Bundled SVG. ?></span>
				<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
				<span class="fssb-version"><?php echo esc_html( 'v' . FAST_SOCIAL_SHARE_BUTTONS_VERSION ); ?></span>
			</div>
			<nav class="fssb-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Settings sections', 'fast-social-share-buttons' ); ?>">
				<?php foreach ( self::TABS as $id => $icon ) : ?>
					<a href="#<?php echo esc_attr( $id ); ?>" class="fssb-tab" role="tab" data-fssb-tab="<?php echo esc_attr( $id ); ?>">
						<?php echo Fast_Social_Share_Buttons_Icons::get( $icon, 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Bundled SVG. ?>
						<?php echo esc_html( $this->tab_label( $id ) ); ?>
					</a>
				<?php endforeach; ?>
			</nav>
		</div>

		<div class="wrap fssb-wrap">
			<?php echo $renderer->sprite( array_merge( $all_networks, array( 'check' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Bundled icons. ?>

			<hr class="wp-header-end">

			<?php
			// Pages outside the Settings menu have to show "Settings saved." themselves.
			settings_errors();
			?>

			<form action="options.php" method="post" class="fssb-form">
				<?php settings_fields( self::PAGE ); ?>

				<div id="poststuff">
					<div id="post-body" class="metabox-holder columns-2">
						<div id="post-body-content">
							<div class="postbox fssb-preview-box">
								<div class="postbox-header">
									<h2 class="hndle">
										<?php echo Fast_Social_Share_Buttons_Icons::get( 'eye', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Bundled SVG. ?>
										<?php esc_html_e( 'Preview', 'fast-social-share-buttons' ); ?>
									</h2>
									<span class="fssb-box-meta"><?php esc_html_e( 'Updates as you change settings', 'fast-social-share-buttons' ); ?></span>
								</div>
								<div class="inside">
									<div class="fssb-preview" data-fssb-preview>
										<?php echo $preview; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by the renderer. ?>
									</div>
								</div>
							</div>

							<?php
							foreach ( $this->sections() as $id => $section ) {
								$this->render_section( $id, $section );
							}
							?>
						</div>

						<div id="postbox-container-1" class="postbox-container">
							<div class="fssb-sidebar">
								<?php $this->render_sidebar(); ?>
							</div>
						</div>
					</div>
					<br class="clear">
				</div>
			</form>
		</div>
		<?php
	}

	/**
	 * One settings box.
	 *
	 * @param string               $id      Section ID.
	 * @param array<string,string> $section Section details from {@see sections()}.
	 * @return void
	 */
	private function render_section( $id, array $section ) {
		?>
		<div class="postbox fssb-box" id="fssb-<?php echo esc_attr( $id ); ?>" data-fssb-panel="<?php echo esc_attr( $section['panel'] ); ?>">
			<div class="postbox-header">
				<h2 class="hndle">
					<?php echo Fast_Social_Share_Buttons_Icons::get( $section['icon'], 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Bundled SVG. ?>
					<?php echo esc_html( $section['title'] ); ?>
				</h2>
				<?php if ( 'networks' === $id ) : ?>
					<span class="fssb-box-meta" data-fssb-count></span>
				<?php endif; ?>
			</div>
			<div class="inside">
				<?php if ( '' !== $section['intro'] ) : ?>
					<p class="fssb-intro"><?php echo esc_html( $section['intro'] ); ?></p>
				<?php endif; ?>

				<?php
				if ( ! empty( $section['render'] ) ) {
					call_user_func( array( $this, $section['render'] ) );
				} else {
					echo '<table class="form-table" role="presentation">';
					do_settings_fields( self::PAGE, 'fssb_' . $id );
					echo '</table>';
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Sidebar: Save, what the plugin loads, and where updates come from.
	 *
	 * @return void
	 */
	private function render_sidebar() {
		$css = $this->gzipped_size( 'assets/front.min.css' );
		$js  = $this->gzipped_size( 'assets/front.min.js' );
		?>
		<div class="postbox fssb-save-box">
			<div class="postbox-header">
				<h2 class="hndle">
					<?php echo Fast_Social_Share_Buttons_Icons::get( 'save', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Bundled SVG. ?>
					<?php esc_html_e( 'Save', 'fast-social-share-buttons' ); ?>
				</h2>
			</div>
			<div class="inside">
				<p><?php esc_html_e( 'Settings on every tab are saved together.', 'fast-social-share-buttons' ); ?></p>
				<?php submit_button( __( 'Save Changes', 'fast-social-share-buttons' ), 'primary large', 'submit', false ); ?>
			</div>
		</div>

		<div class="postbox">
			<div class="postbox-header">
				<h2 class="hndle">
					<?php echo Fast_Social_Share_Buttons_Icons::get( 'zap', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Bundled SVG. ?>
					<?php esc_html_e( 'Performance', 'fast-social-share-buttons' ); ?>
				</h2>
			</div>
			<div class="inside">
				<ul class="fssb-checklist">
					<li><?php echo Fast_Social_Share_Buttons_Icons::get( 'check', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Bundled SVG. ?><span><?php esc_html_e( 'Loads only on pages that show buttons', 'fast-social-share-buttons' ); ?></span></li>
					<li><?php echo Fast_Social_Share_Buttons_Icons::get( 'check', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Bundled SVG. ?><span>
						<?php
						/* translators: %s: file size, e.g. "1.8 KB". */
						echo esc_html( sprintf( __( '%s of CSS, inlined (no extra request)', 'fast-social-share-buttons' ), $css ) );
						?>
					</span></li>
					<li><?php echo Fast_Social_Share_Buttons_Icons::get( 'check', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Bundled SVG. ?><span>
						<?php
						/* translators: %s: file size, e.g. "2.4 KB". */
						echo esc_html( sprintf( __( '%s of JavaScript, deferred', 'fast-social-share-buttons' ), $js ) );
						?>
					</span></li>
					<li><?php echo Fast_Social_Share_Buttons_Icons::get( 'check', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Bundled SVG. ?><span><?php esc_html_e( 'No third-party requests or layout shift', 'fast-social-share-buttons' ); ?></span></li>
				</ul>
				<p class="description"><?php esc_html_e( 'Sizes are gzipped.', 'fast-social-share-buttons' ); ?></p>
			</div>
		</div>

		<?php
		$this->render_updates_box();
	}

	/**
	 * Gzipped size of a bundled file, e.g. "1.8 KB".
	 *
	 * @param string $file Path relative to the plugin folder.
	 * @return string
	 */
	private function gzipped_size( $file ) {
		$path = FAST_SOCIAL_SHARE_BUTTONS_DIR . $file;
		$data = is_readable( $path ) ? (string) file_get_contents( $path ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$size = function_exists( 'gzencode' ) ? strlen( (string) gzencode( $data, 9 ) ) : strlen( $data );

		return number_format_i18n( $size / 1024, 1 ) . ' KB';
	}

	/**
	 * Translated tab label.
	 *
	 * @param string $id Tab ID.
	 * @return string
	 */
	private function tab_label( $id ) {
		$labels = array(
			'networks'  => __( 'Networks', 'fast-social-share-buttons' ),
			'placement' => __( 'Placement', 'fast-social-share-buttons' ),
			'style'     => __( 'Style', 'fast-social-share-buttons' ),
			'advanced'  => __( 'Advanced', 'fast-social-share-buttons' ),
		);

		return isset( $labels[ $id ] ) ? $labels[ $id ] : $id;
	}

	/**
	 * Show where updates come from, so it's easy to confirm the repo is wired up.
	 *
	 * @return void
	 */
	private function render_updates_box() {
		$repo_label = preg_replace( '#^https?://[^/]+/#', '', untrailingslashit( FAST_SOCIAL_SHARE_BUTTONS_REPO ) );
		$check_url  = Fast_Social_Share_Buttons_Updater::check_now_url();
		?>
		<div class="postbox fssb-updates-box">
			<div class="postbox-header">
				<h2 class="hndle">
					<?php echo Fast_Social_Share_Buttons_Icons::get( 'refresh-cw', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Bundled SVG. ?>
					<?php esc_html_e( 'Updates', 'fast-social-share-buttons' ); ?>
				</h2>
			</div>
			<div class="inside">
				<p>
					<?php
					/* translators: %s: installed plugin version. */
					printf( esc_html__( 'Installed version: %s', 'fast-social-share-buttons' ), '<strong>' . esc_html( FAST_SOCIAL_SHARE_BUTTONS_VERSION ) . '</strong>' );
					?>
				</p>

				<?php if ( ! Fast_Social_Share_Buttons_Updater::has_repo() ) : ?>
					<p>
						<?php
						printf(
							/* translators: 1: header name, 2: main plugin file. */
							esc_html__( 'No repo set. Add it to the %1$s header in %2$s to turn on automatic updates.', 'fast-social-share-buttons' ),
							'<code>Update URI</code>',
							'<code>' . esc_html( plugin_basename( FAST_SOCIAL_SHARE_BUTTONS_FILE ) ) . '</code>'
						);
						?>
					</p>
				<?php elseif ( Fast_Social_Share_Buttons_Updater::is_dev_copy() ) : ?>
					<p>
						<?php
						printf(
							/* translators: 1: repo, 2: constant name. */
							esc_html__( 'Releases come from %1$s, but update checks are off on this development copy so an update can\'t overwrite your work. Define %2$s as false in wp-config.php to test updates here.', 'fast-social-share-buttons' ),
							'<a href="' . esc_url( FAST_SOCIAL_SHARE_BUTTONS_REPO ) . '" target="_blank" rel="noopener">' . esc_html( $repo_label ) . '</a>',
							'<code>FAST_SOCIAL_SHARE_BUTTONS_DEV_MODE</code>'
						);
						?>
					</p>
				<?php else : ?>
					<p>
						<?php
						printf(
							/* translators: %s: repo. */
							esc_html__( 'Updates come from the latest release of %s.', 'fast-social-share-buttons' ),
							'<a href="' . esc_url( FAST_SOCIAL_SHARE_BUTTONS_REPO ) . '" target="_blank" rel="noopener">' . esc_html( $repo_label ) . '</a>'
						);
						?>
					</p>
					<?php if ( $check_url ) : ?>
						<p><a class="button" href="<?php echo esc_url( $check_url ); ?>"><?php esc_html_e( 'Check for updates', 'fast-social-share-buttons' ); ?></a></p>
					<?php endif; ?>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}
