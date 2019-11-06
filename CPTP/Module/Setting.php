<?php
/**
 * Management Setting.
 *
 * @package Custom_Post_Type_Permalinks
 */

/**
 * For load plugin.
 *
 * @since 0.9.4
 * */
class CPTP_Module_Setting extends CPTP_Module {

	/**
	 * Module hooks.
	 */
	public function add_hook() {
		$this->update_version();
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'upgrader_process_complete', array( $this, 'upgrader_process_complete' ), 10, 2 );
	}

	/**
	 * Save CPTP version.
	 *
	 * @since 0.8.6
	 */
	public function update_version() {
		update_option( 'cptp_version', CPTP_VERSION );
	}

	/**
	 * After update complete.
	 *
	 * @since 3.0.0
	 *
	 * @param object $wp_upgrader WP_Upgrader instance.
	 * @param array  $options Extra information about performed upgrade.
	 */
	public function upgrader_process_complete( $wp_upgrader, $options ) {
		if ( empty( $options['plugins'] ) ) {
			return;
		}

		if ( ! is_array( $options['plugins'] ) ) {
			return;
		}

		if ( 'update' === $options['action'] && 'plugin' === $options['type'] ) {
			$plugin_path = plugin_basename( CPTP_PLUGIN_FILE );
			if ( in_array( $plugin_path, $options['plugins'], true ) ) {
				// for update code.
				add_option( 'no_taxonomy_structure', false );
			}
		}
	}

	/**
	 * Load textdomain
	 *
	 * @since 0.6.2
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'custom-post-type-permalinks', false, 'custom-post-type-permalinks/language' );
	}
}
