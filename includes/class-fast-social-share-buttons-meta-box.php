<?php
/**
 * Per-post sharing options: hide the buttons, custom share text, and a
 * Pinterest image and description.
 *
 * @package Fast_Social_Share_Buttons
 */

defined( 'ABSPATH' ) || exit;

/**
 * "Social Sharing" meta box.
 */
final class Fast_Social_Share_Buttons_Meta_Box {

	/**
	 * Post meta keys, by field.
	 */
	const META = array(
		'hide'      => '_fast_social_share_buttons_hide',
		'text'      => '_fast_social_share_buttons_text',
		'pin_image' => '_fast_social_share_buttons_pin_image',
		'pin_desc'  => '_fast_social_share_buttons_pin_desc',
	);

	/**
	 * Nonce action and field name.
	 */
	const NONCE = 'fast_social_share_buttons_meta';

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

		add_action( 'add_meta_boxes', array( $this, 'add' ) );
		add_action( 'save_post', array( $this, 'save' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * A post's sharing options.
	 *
	 * @param int $post_id Post ID.
	 * @return array{hide:bool,text:string,pin_image:int,pin_desc:string}
	 */
	public static function get_meta( $post_id ) {
		return array(
			'hide'      => (bool) get_post_meta( $post_id, self::META['hide'], true ),
			'text'      => (string) get_post_meta( $post_id, self::META['text'], true ),
			'pin_image' => absint( get_post_meta( $post_id, self::META['pin_image'], true ) ),
			'pin_desc'  => (string) get_post_meta( $post_id, self::META['pin_desc'], true ),
		);
	}

	/**
	 * Whether the buttons are switched off for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public static function is_hidden( $post_id ) {
		return (bool) get_post_meta( $post_id, self::META['hide'], true );
	}

	/**
	 * Post types that get the meta box: every public one with an editor screen.
	 *
	 * @return string[]
	 */
	public static function post_types() {
		$types = get_post_types(
			array(
				'public'  => true,
				'show_ui' => true,
			)
		);

		unset( $types['attachment'] );

		return array_values( $types );
	}

	/**
	 * Add the meta box.
	 *
	 * @return void
	 */
	public function add() {
		add_meta_box(
			'fast-social-share-buttons',
			__( 'Social Sharing', 'fast-social-share-buttons' ),
			array( $this, 'render' ),
			self::post_types(),
			'side',
			'low'
		);
	}

	/**
	 * Load the image picker on post edit screens.
	 *
	 * @param string $hook Current admin page.
	 * @return void
	 */
	public function enqueue( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$screen = get_current_screen();

		if ( ! $screen || ! in_array( $screen->post_type, self::post_types(), true ) ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_script( 'fast-social-share-buttons-meta-box', FAST_SOCIAL_SHARE_BUTTONS_URL . 'assets/meta-box.js', array(), FAST_SOCIAL_SHARE_BUTTONS_VERSION, true );
		wp_localize_script(
			'fast-social-share-buttons-meta-box',
			'fssbMetaBox',
			array(
				'title'  => __( 'Pinterest image', 'fast-social-share-buttons' ),
				'button' => __( 'Use this image', 'fast-social-share-buttons' ),
			)
		);
	}

	/**
	 * Meta box contents.
	 *
	 * @param WP_Post $post Post being edited.
	 * @return void
	 */
	public function render( $post ) {
		$meta  = self::get_meta( $post->ID );
		$type  = get_post_type_object( $post->post_type );
		$image = $meta['pin_image'] ? wp_get_attachment_image_url( $meta['pin_image'], 'medium' ) : '';

		wp_nonce_field( self::NONCE, self::NONCE );
		?>
		<p>
			<label>
				<input type="checkbox" name="fssb_hide" value="1" <?php checked( $meta['hide'] ); ?>>
				<?php
				/* translators: %s: post type name, e.g. "post". */
				printf( esc_html__( 'Hide share buttons on this %s', 'fast-social-share-buttons' ), esc_html( $type ? strtolower( $type->labels->singular_name ) : 'post' ) );
				?>
			</label>
		</p>
		<p class="description">
			<?php
			printf(
				/* translators: %s: the shortcode. */
				esc_html__( 'To place buttons yourself, add the Social Share Buttons block or %s anywhere in the content.', 'fast-social-share-buttons' ),
				'<code>[fast_social_share]</code>'
			);
			?>
		</p>

		<p>
			<label for="fssb-text"><strong><?php esc_html_e( 'Share text', 'fast-social-share-buttons' ); ?></strong></label>
			<textarea id="fssb-text" name="fssb_text" class="widefat" rows="3" maxlength="500" placeholder="<?php echo esc_attr( wp_strip_all_tags( get_the_title( $post ) ) ); ?>"><?php echo esc_textarea( $meta['text'] ); ?></textarea>
			<span class="description"><?php esc_html_e( 'Used by X, Bluesky, Threads, WhatsApp and other networks that take text. Defaults to the title.', 'fast-social-share-buttons' ); ?></span>
		</p>

		<div class="fssb-pin-image">
			<p><strong><?php esc_html_e( 'Pinterest image', 'fast-social-share-buttons' ); ?></strong></p>
			<input type="hidden" name="fssb_pin_image" value="<?php echo esc_attr( $meta['pin_image'] ? (string) $meta['pin_image'] : '' ); ?>" data-fssb-pin-input>
			<p data-fssb-pin-preview <?php echo $image ? '' : 'hidden'; ?>>
				<img src="<?php echo esc_url( $image ? $image : '' ); ?>" alt="" style="max-width:100%;height:auto">
			</p>
			<p>
				<button type="button" class="button" data-fssb-pin-choose><?php esc_html_e( 'Choose image', 'fast-social-share-buttons' ); ?></button>
				<button type="button" class="button-link button-link-delete" data-fssb-pin-remove <?php echo $image ? '' : 'hidden'; ?>><?php esc_html_e( 'Remove', 'fast-social-share-buttons' ); ?></button>
			</p>
			<p class="description"><?php esc_html_e( 'A tall image (2:3) works best. Defaults to the featured image.', 'fast-social-share-buttons' ); ?></p>
		</div>

		<p>
			<label for="fssb-pin-desc"><strong><?php esc_html_e( 'Pinterest description', 'fast-social-share-buttons' ); ?></strong></label>
			<textarea id="fssb-pin-desc" name="fssb_pin_desc" class="widefat" rows="3" maxlength="500"><?php echo esc_textarea( $meta['pin_desc'] ); ?></textarea>
		</p>
		<?php
	}

	/**
	 * Save the meta box fields.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @return void
	 */
	public function save( $post_id, $post ) {
		if ( ! isset( $_POST[ self::NONCE ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE ] ) ), self::NONCE ) ) {
			return;
		}

		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( ! in_array( $post->post_type, self::post_types(), true ) ) {
			return;
		}

		$values = array(
			'hide'      => empty( $_POST['fssb_hide'] ) ? '' : '1',
			'text'      => isset( $_POST['fssb_text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['fssb_text'] ) ) : '',
			'pin_image' => isset( $_POST['fssb_pin_image'] ) ? absint( $_POST['fssb_pin_image'] ) : 0,
			'pin_desc'  => isset( $_POST['fssb_pin_desc'] ) ? sanitize_textarea_field( wp_unslash( $_POST['fssb_pin_desc'] ) ) : '',
		);

		foreach ( $values as $field => $value ) {
			if ( '' === $value || 0 === $value ) {
				delete_post_meta( $post_id, self::META[ $field ] );
			} else {
				update_post_meta( $post_id, self::META[ $field ], $value );
			}
		}
	}
}
