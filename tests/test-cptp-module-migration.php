<?php

class CPTP_Module_Migration_Test extends WP_UnitTestCase {

    public function setUp() {
        global $wp_rewrite;
        parent::setUp();

        $wp_rewrite->init();
        $wp_rewrite->set_permalink_structure( '/%year%/%monthnum%/%day%/%postname%/' );
        create_initial_taxonomies();
        $wp_rewrite->flush_rules();


    }

    /**
     *
     * @test
     * @group migration
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_template_loader_setting_value() {
        do_action("plugins_loaded");
        delete_option( 'rewrite_rules' );

        $this->assertFalse( get_option( 'cptp_change_template_loader' ) );

    }


    /**
     *
     * @test
     * @group migration
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_template_loader_setting_value_for_update_older_0_9_6() {
        update_option( 'cptp_version', '0.9.6');
        do_action("plugins_loaded");
        delete_option( 'rewrite_rules' );

        $this->assertTrue( get_option( 'cptp_change_template_loader' ) );

    }


}

