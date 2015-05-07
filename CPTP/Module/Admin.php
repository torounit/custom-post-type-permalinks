<?php


/**
 *
 * Admin Page View.
 *
 * @package Custom_Post_Type_Permalinks
 * @since 0.9.4
 *
 * */
class CPTP_Module_Admin extends CPTP_Module {

	public function add_hook() {
		add_action( 'admin_init', array( $this, 'settings_api_init' ), 30 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_css_js' ) );
		add_action( 'admin_footer', array( $this, 'pointer_js' ) );
	}


	/**
	 *
	 * Setting Init
	 * @since 0.7
	 *
	 */
	public function settings_api_init() {
		add_settings_section( 'cptp_setting_section',
			__( 'Permalink Setting for custom post type', 'cptp' ),
			array( $this, 'setting_section_callback_function' ),
			'permalink'
		);

		$post_types = CPTP_Util::get_post_types();

		foreach ( $post_types as $post_type ) {

			add_settings_field(
				$post_type . '_structure',
				$post_type,
				array( $this, 'setting_structure_callback_function' ),
				'permalink',
				'cptp_setting_section',
				array( 'label_for' => $post_type . '_structure', 'post_type' => $post_type )
			);
			register_setting( 'permalink', $post_type . '_structure' );
		}

		add_settings_field(
			'no_taxonomy_structure',
			__( 'Use custom permalink of custom taxonomy archive.', 'cptp' ),
			array( $this, 'setting_no_tax_structure_callback_function' ),
			'permalink',
			'cptp_setting_section',
			array( 'label_for' => 'no_taxonomy_structure' )
		);

		register_setting( 'permalink', 'no_taxonomy_structure' );

		add_settings_field(
			'add_post_type_for_tax',
			__( 'Add post_type query for custom taxonomy archive.', 'cptp' ),
			array( $this, 'add_post_type_for_tax_callback_function' ),
			'permalink',
			'cptp_setting_section',
			array( 'label_for' => 'add_post_type_for_tax' )
		);

		register_setting( 'permalink', 'no_taxonomy_structure' );

	}

	public function setting_section_callback_function() {
		?>
		<p><?php _e( 'Setting permalinks of custom post type.', 'cptp' ); ?><br/>
			<?php _e( "The tags you can use is WordPress Structure Tags and '%\"custom_taxonomy_slug\"%'. (e.g. %actors%)", 'cptp' ); ?>
			<br/>
			<?php _e( "%\"custom_taxonomy_slug\"% is replaced the taxonomy's term.'.", 'cptp' ); ?></p>

		<p><?php _e( "Presence of the trailing '/' is unified into a standard permalink structure setting.", 'cptp' ); ?>
		<p><?php _e( 'If <code>has_archive</code> is true, add permalinks for custom post type archive.', 'cptp' ); ?>
			<?php _e( "If you don't entered permalink structure, permalink is configured /%postname%/'.", 'cptp' ); ?>
		</p>
	<?php
	}

	public function setting_structure_callback_function( $option ) {

		$post_type  = $option['post_type'];
		$name       = $option['label_for'];
		$pt_object  = get_post_type_object( $post_type );
		$slug       = $pt_object->rewrite['slug'];
		$with_front = $pt_object->rewrite['with_front'];


		$value = CPTP_Util::get_permalink_structure( $post_type );

		$disabled = false;
		if ( isset( $pt_object->cptp_permalink_structure ) and $pt_object->cptp_permalink_structure ) {
			$disabled = true;
		}

		if ( ! $value ) {
			$value = CPTP_DEFAULT_PERMALINK;
		}

		global $wp_rewrite;
		$front = substr( $wp_rewrite->front, 1 );
		if ( $front and $with_front ) {
			$slug = $front . $slug;
		}
		?>
		<p>
			<code><?php echo home_url() . '/' . esc_html( $slug );?></code>
			<input name="<?php echo esc_attr( $name );?>" id="<?php echo esc_attr( $name );?>" type="text" class="regular-text code " value="<?php echo esc_attr( $value ) ;?>" <?php  disabled( $disabled, true, true );?> />
		</p>
		<p>has_archive: <code><?php echo $pt_object->has_archive ? 'true' : 'false';?></code> / with_front: <code><?php echo $pt_object->rewrite['with_front'] ? 'true' : 'false';?></code></p>
		<?php
	}


	public function setting_no_tax_structure_callback_function() {
		echo '<input name="no_taxonomy_structure" id="no_taxonomy_structure" type="checkbox" value="1" class="code" ' . checked( false, get_option( 'no_taxonomy_structure' ), false ) . ' /> ';
		$txt = __( "If you check,The custom taxonomy's permalinks is <code>%s/post_type/taxonomy/term</code>.", 'cptp' );
		printf( $txt, home_url() );
	}


	public function add_post_type_for_tax_callback_function() {
		echo '<input name="add_post_type_for_tax" id="add_post_type_for_tax" type="checkbox" value="1" class="code" ' . checked( true, get_option( 'add_post_type_for_tax' ), false ) . ' /> ';
		_e( "custom taxonomy archive also works as post type archive.", 'cptp' );
		_e( "There are cases when template to be loaded is changed.", 'cptp' );
	}


	/**
	 *
	 * enqueue CSS and JS
	 * @since 0.8.5
	 *
	 */
	public function enqueue_css_js() {
		wp_enqueue_style( 'wp-pointer' );
		wp_enqueue_script( 'wp-pointer' );
	}


	/**
	 *
	 * add js for pointer
	 * @since 0.8.5
	 */
	public function pointer_js() {
		if ( ! is_network_admin() ) {
			$dismissed = explode( ',', get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ) );
			if ( false === array_search( 'cptp_pointer0871', $dismissed ) ) {
				$content = __( "<h3>Custom Post Type Permalinks</h3><p>From <a href='options-permalink.php'>Permalinks</a>, set a custom permalink for each post type.</p>", 'cptp' );
				?>
				<script type="text/javascript">
					jQuery(function ($) {

						$("#menu-settings .wp-has-submenu").pointer({
							content: "<?php echo $content;?>",
							position: {"edge": "left", "align": "center"},
							close: function () {
								$.post('admin-ajax.php', {
									action: 'dismiss-wp-pointer',
									pointer: 'cptp_pointer0871'
								})

							}
						}).pointer("open");
					});
				</script>
			<?php
			}
		}
	}
}

