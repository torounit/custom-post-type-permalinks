<?php

class CPTP_UtilTest extends WP_UnitTestCase {

	public function setUp() {
		global $wp_rewrite;
		parent::setUp();

		$wp_rewrite->init();
		$wp_rewrite->set_permalink_structure( '/%year%/%monthnum%/%day%/%postname%/' );
		create_initial_taxonomies();
		$wp_rewrite->flush_rules();
		cptp_init_instance();
	}


	public function test_get_permalink_structure_from_option() {
		$post_type = rand_str( 12 );
		register_post_type( $post_type, array( "public" => true ) );
		update_option($post_type."_structure", "/%year%/%monthnum%/%day%/%post_id%/" );

		$this->assertEquals( CPTP_Util::get_permalink_structure( $post_type ), "/%year%/%monthnum%/%day%/%post_id%/" );

	}

	public function test_get_permalink_structure_from_arguments() {
		$post_type = rand_str( 12 );
		register_post_type( $post_type, array( "public" => true, "cptp_permalink_structure" => "/%year%/%monthnum%/%day%/%post_id%/" ) );
		$this->assertEquals( CPTP_Util::get_permalink_structure( $post_type ), "/%year%/%monthnum%/%day%/%post_id%/" );
	}

}