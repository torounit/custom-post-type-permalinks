<?php

/**
 *
 * Reflush Rewrite Rules
 *
 * @package Custom_Post_Type_Permalinks
 * @since 0.9.4
 * */

class CPTP_Module_FlushRules extends CPTP_Module {


	public function add_hook() {
		add_action( 'init', array( $this, 'update_rules' ) );
		add_action( 'add_option_cptp_version', array( $this, 'update_rules' ) );
		add_action( 'update_option_cptp_version', array( $this, 'update_rules' ), 20 );
		add_action( 'wp_loaded', array( __CLASS__, 'dequeue_flush_rules' ), 200 );
	}



	/**
	 *
	 * Add hook flush_rules
	 *
	 * @since 0.7.9
	 */
	public function update_rules() {

		$post_types = CPTP_Util::get_post_types();
		foreach ( $post_types as $post_type ) {
			add_action( 'update_option_'.$post_type.'_structure', array( __CLASS__, 'queue_flush_rules' ), 10, 2 );
		}
		add_action( 'update_option_no_taxonomy_structure', array( __CLASS__, 'queue_flush_rules' ), 10, 2 );
	}


	/**
	 *
	 * dequeue flush rules
	 *
	 * @since 0.9
	 */

	public static function dequeue_flush_rules() {
		if ( get_option( 'queue_flush_rules' ) ) {
			flush_rewrite_rules();
			update_option( 'queue_flush_rules', 0 );

		}
	}


	/**
	 * Flush rules
	 *
	 * @since 0.7.9
	 */

	public static function queue_flush_rules() {
		update_option( 'queue_flush_rules', 1 );
	}

	/**
	 * uninstall hooks
	 *
	 * @staitc
	 */
	public static function uninstall_hook() {
		delete_option( 'queue_flush_rules' );
	}

	/**
	 * fire on activate
	 */
	public function activation_hook() {
		CPTP_Module_FlushRules::queue_flush_rules();
	}
}
