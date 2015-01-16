<?php

class CPTP_Module_Setting_Test extends WP_UnitTestCase {

    public function setUp() {
        global $wp_rewrite;
        parent::setUp();
        update_option( 'cptp_version', '0.9.6');

        $wp_rewrite->init();
        $wp_rewrite->set_permalink_structure( '/%year%/%monthnum%/%day%/%postname%/' );
        create_initial_taxonomies();
        $wp_rewrite->flush_rules();

        do_action("plugins_loaded");
        delete_option( 'rewrite_rules' );

    }

    /**
     * @test
     */
    public function test_cptp_version() {
        $this->assertEquals( CPTP_VERSION, get_option( 'cptp_version' ) );
    }

}

