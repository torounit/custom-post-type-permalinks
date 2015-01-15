<?php



/**
 *
 * Options.
 *
 * @package Custom_Post_Type_Permalinks
 * @since 0.9.6
 *
 * */

class CPTP_Module_Option extends CPTP_Module {

	public function add_hook() {
		add_action( 'admin_init', array( $this, 'save_options' ), 30 );
		register_uninstall_hook( CPTP_PLUGIN_FILE, array( __CLASS__, 'uninstall_hook' ) );
	}

	public function save_options() {

		if ( isset( $_POST['submit'] ) and isset( $_POST['_wp_http_referer'] ) ){
			if ( false !== strpos( $_POST['_wp_http_referer'], 'options-permalink.php' ) ) {
				$post_types = CPTP_Util::get_post_types();
				foreach ( $post_types as $post_type ):

					$structure = trim( esc_attr( $_POST[ $post_type.'_structure' ] ) );#get setting

					#default permalink structure
					if ( ! $structure ) {
						$structure = CPTP_DEFAULT_PERMALINK;
					}

					$structure = str_replace( '//', '/', '/'.$structure );# first "/"
					#last "/"
					$lastString = substr( trim( esc_attr( $_POST['permalink_structure'] ) ), -1 );
					$structure = rtrim( $structure, '/' );

					if ( $lastString == '/' ) {
						$structure = $structure.'/';
					}

					update_option( $post_type.'_structure', $structure );
				endforeach;
			}

			if ( ! isset( $_POST['no_taxonomy_structure'] ) ) {
				$set = true;
			}else {
				$set = false;
			}
			update_option( 'no_taxonomy_structure', $set );


			if ( ! isset( $_POST['cptp_change_template_loader'] ) ) {
				$set = true;
			}else {
				$set = false;
			}
			update_option( 'cptp_change_template_loader', $set );

		}
	}

	public static function uninstall_hook() {
		foreach ( CPTP_Util::get_post_types() as $post_type ) {
			delete_option( $post_type.'_structure' );
		}

		delete_option( 'no_taxonomy_structure' );
	}


}