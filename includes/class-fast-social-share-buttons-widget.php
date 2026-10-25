<?php
/**
 * Classic "Social Share Buttons" widget, for themes and sites still on the
 * classic widgets screen. The block editor's widget screen uses the block.
 *
 * @package Fast_Social_Share_Buttons
 */

defined( 'ABSPATH' ) || exit;

/**
 * Share buttons widget.
 */
class Fast_Social_Share_Buttons_Widget extends WP_Widget {

	/**
	 * Widget ID base.
	 */
	const ID_BASE = 'fast_social_share_buttons';

	/**
	 * Register the widget.
	 */
	public function __construct() {
		parent::__construct(
			self::ID_BASE,
			__( 'Social Share Buttons', 'fast-social-share-buttons' ),
			array(
				'description'                 => __( 'Share buttons for the page being viewed.', 'fast-social-share-buttons' ),
				'customize_selective_refresh' => true,
			)
		);

		// The block does this job in the block widget editor.
		add_filter( 'widget_types_to_hide_from_legacy_widget_block', array( __CLASS__, 'hide_from_block_editor' ) );
	}

	/**
	 * Hide this widget from the Legacy Widget block, which would duplicate the block.
	 *
	 * @param string[] $types Widget ID bases.
	 * @return string[]
	 */
	public static function hide_from_block_editor( $types ) {
		$types[] = self::ID_BASE;

		return $types;
	}

	/**
	 * Output the widget.
	 *
	 * @param array $args     Sidebar arguments.
	 * @param array $instance Widget settings.
	 * @return void
	 */
	public function widget( $args, $instance ) {
		$instance = $this->defaults( $instance );
		$html     = fast_social_share_buttons_plugin()->renderer()->render(
			array(
				'context' => 'shortcode',
				'layout'  => $instance['layout'],
				'labels'  => $instance['labels'],
			)
		);

		if ( '' === $html ) {
			return;
		}

		$title = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );

		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Sidebar wrappers come from the theme; buttons are escaped by the renderer.
		echo $args['before_widget'];

		if ( '' !== $title ) {
			echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
		}

		echo $html;
		echo $args['after_widget'];
		// phpcs:enable
	}

	/**
	 * Widget settings form.
	 *
	 * @param array $instance Widget settings.
	 * @return string
	 */
	public function form( $instance ) {
		$instance = $this->defaults( $instance );
		$choices  = Fast_Social_Share_Buttons_Plugin::choices();
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'fast-social-share-buttons' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $instance['title'] ); ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'layout' ) ); ?>"><?php esc_html_e( 'Layout:', 'fast-social-share-buttons' ); ?></label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'layout' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'layout' ) ); ?>">
				<?php foreach ( $choices['layout'] as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $instance['layout'], $value ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'labels' ) ); ?>"><?php esc_html_e( 'Labels:', 'fast-social-share-buttons' ); ?></label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'labels' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'labels' ) ); ?>">
				<?php foreach ( $choices['labels'] as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $instance['labels'], $value ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p class="description">
			<?php
			printf(
				/* translators: %s: link to the plugin settings page. */
				esc_html__( 'Networks and styling come from the %s.', 'fast-social-share-buttons' ),
				'<a href="' . esc_url( admin_url( 'admin.php?page=' . Fast_Social_Share_Buttons_Admin::PAGE ) ) . '">' . esc_html__( 'plugin settings', 'fast-social-share-buttons' ) . '</a>'
			);
			?>
		</p>
		<?php
		return '';
	}

	/**
	 * Save the widget settings.
	 *
	 * @param array $new_instance New settings.
	 * @param array $old_instance Previous settings.
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		$new_instance = $this->defaults( $new_instance );

		return array(
			'title'  => sanitize_text_field( $new_instance['title'] ),
			'layout' => Fast_Social_Share_Buttons_Plugin::is_choice( 'layout', $new_instance['layout'] ) ? $new_instance['layout'] : 'row',
			'labels' => Fast_Social_Share_Buttons_Plugin::is_choice( 'labels', $new_instance['labels'] ) ? $new_instance['labels'] : 'auto',
		);
	}

	/**
	 * Fill in missing settings.
	 *
	 * @param mixed $instance Widget settings.
	 * @return array{title:string,layout:string,labels:string}
	 */
	private function defaults( $instance ) {
		return wp_parse_args(
			is_array( $instance ) ? $instance : array(),
			array(
				'title'  => __( 'Share', 'fast-social-share-buttons' ),
				'layout' => 'column',
				'labels' => 'auto',
			)
		);
	}
}
