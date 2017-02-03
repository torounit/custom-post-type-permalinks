<?php


/**
 *
 * For load plugin.
 *
 * @package Custom_Post_Type_Permalinks
 * @since 0.9.4
 * */
class CPTP_Module_Setting extends CPTP_Module {

	public function add_hook() {
		$this->update_version();
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'upgrader_process_complete', array( $this, 'upgrader_process_complete' ) );
	}

	/**
	 * check_version
	 *
	 * @since 0.8.6
	 */

	public function update_version() {
		update_option( 'cptp_version', CPTP_VERSION );
	}

	/**
	 * After update complete.
	 *
	 * @since 2.3.0
	 *
	 * @param object $wp_upgrader WP_Upgrader instance.
	 * @param array $options Extra information about performed upgrade.
	 */
	public function upgrader_process_complete( $wp_upgrader, $options ) {

		if ( ! is_array( $options['plugins'] ) ) {
			return;
		}

		$plugin_path = plugin_basename( CPTP_PLUGIN_FILE );
		if ( 'update' == $options['action'] && 'plugin' == $options['type'] ) {
			if ( in_array( $plugin_path, $options['plugins'] ) ) {
				//for update code.
				add_option( 'no_taxonomy_structure', false );
			}
		}
	}

	/**
	 * load textdomain
	 *
	 * @since 0.6.2
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'custom-post-type-permalinks', false, 'custom-post-type-permalinks/language' );
	}
}
