<?php

/**
 *
 * Migration.
 *
 * @package Custom_Post_Type_Permalinks
 * @since 0.9.7
 *
 * */

class CPTP_Module_Migration extends CPTP_Module {

	public function add_hook() {
		add_action( 'update_option_cptp_version', array( $this, 'template_loader_setting' ), 10 );
	}

	public function template_loader_setting( $oldvalue ) {
		if ( version_compare( $oldvalue, '0.9.7', '<' ) ) {
			add_option( 'cptp_change_template_loader', true );
		}
	}

}