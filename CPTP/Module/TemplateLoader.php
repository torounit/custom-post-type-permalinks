<?php

/**
 *
 * Template loader.
 *
 * @package Custom_Post_Type_Permalinks
 * @since 0.9.7
 *
 * */

class CPTP_Module_TemplateLoader extends CPTP_Module {

	public function add_hook() {
		add_filter( 'template_include', array( $this, 'template_include' ), 10 );
	}

	public function template_include( $template ) {
		if ( get_option( 'cptp_change_template_loader' ) ) {

			if ( ! ( is_post_type_archive() and is_tax() ) ) {
				return $template;
			}

			if ( ! ( get_post_type_archive_template() and get_taxonomy_template() ) ) {
				return $template;
			}

			if ( get_post_type_archive_template() ) {
				return get_taxonomy_template();
			}
		}

		return $template;
	}

}