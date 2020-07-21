<?php
/**
 * Options.
 *
 * Save Options.
 *
 * @package Custom_Post_Type_Permalinks
 * */

/**
 * Class CPTP_Module_Option
 *
 * @since 0.9.6
 */
class CPTP_Module_Option extends CPTP_Module {

	/**
	 * Add Actions.
	 */
	public function add_hook() {
		add_action( 'init', array( $this, 'set_default_option' ), 1 );
		add_action( 'admin_init', array( $this, 'save_options' ), 30 );
	}

	/**
	 * Set default option values.
	 */
	public function set_default_option() {
		add_option( 'no_taxonomy_structure', true );
		add_option( 'add_post_type_for_tax', false );
	}

	/**
	 * Save Options.
	 *
	 * @return bool
	 */
	public function save_options() {
		if ( ! filter_input( INPUT_POST, 'submit' ) ) {
			return false;
		}

		if ( ! wp_verify_nonce( filter_input( INPUT_POST, '_wpnonce' ), 'update-permalink' ) ) {
			return false;
		}

		if ( false === strpos( filter_input( INPUT_POST, '_wp_http_referer' ), 'options-permalink.php' ) ) {
			return false;
		}

		$post_types = CPTP_Util::get_post_types();

		foreach ( $post_types as $post_type ) :
			$structure = trim( esc_attr( filter_input( INPUT_POST, $post_type . '_structure' ) ) ); // get setting.

			// default permalink structure.
			if ( ! $structure ) {
				$structure = CPTP_DEFAULT_PERMALINK;
			}

			$structure = str_replace( '//', '/', '/' . $structure );// first "/"
			// last "/".
			$lastString = substr( trim( esc_attr( filter_input( INPUT_POST, 'permalink_structure' ) ) ), - 1 );
			$structure  = rtrim( $structure, '/' );

			if ( '/' === $lastString ) {
				$structure = $structure . '/';
			}

			update_option( $post_type . '_structure', $structure );
		endforeach;
		$no_taxonomy_structure = ! filter_input( INPUT_POST, 'no_taxonomy_structure' );
		$add_post_type_for_tax = filter_input( INPUT_POST, 'add_post_type_for_tax' );

		update_option( 'no_taxonomy_structure', ! ! $no_taxonomy_structure );
		update_option( 'add_post_type_for_tax', ! ! $add_post_type_for_tax );
		update_option( 'cptp_permalink_checked', CPTP_VERSION );
	}

	/**
	 * Fire on uninstall. delete options.
	 *
	 * @static
	 */
	public static function uninstall_hook() {
		foreach ( CPTP_Util::get_post_types() as $post_type ) {
			delete_option( $post_type . '_structure' );
		}

		delete_option( 'no_taxonomy_structure' );
	}
}
