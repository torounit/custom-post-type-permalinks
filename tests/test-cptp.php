<?php

class CPTPTest extends WP_UnitTestCase {



    public function setUp() {
        global $wp_rewrite;
        parent::setUp();

        $wp_rewrite->init();
        $wp_rewrite->set_permalink_structure( '/%year%/%monthnum%/%day%/%postname%/' );
        create_initial_taxonomies();
        $wp_rewrite->flush_rules();
        cptp_init_instance();
    }


    public function test_url_to_postid_cpt_year_monthnum_day_post_id () {
        delete_option( 'rewrite_rules' );
        $post_type = rand_str( 12 );
        register_post_type( $post_type, array( "public" => true ) );

        update_option($post_type."_structure", "/%year%/%monthnum%/%day%/%post_id%/" );
        $id = $this->factory->post->create( array( 'post_type' => $post_type ) );
        $this->assertEquals( $id, url_to_postid( get_permalink( $id ) ) );

        _unregister_post_type( $post_type );
    }

    public function test_url_to_postid_cpt_year_monthnum_day_postname () {
        delete_option( 'rewrite_rules' );
        $post_type = rand_str( 12 );
        update_option($post_type."_structure", "/%year%/%monthnum%/%day%/%postname%/" );
        register_post_type( $post_type, array( "public" => true ) );

        $id = $this->factory->post->create( array( 'post_type' => $post_type ) );
        $this->assertEquals( $id, url_to_postid( get_permalink( $id ) ) );

        _unregister_post_type( $post_type );
    }

    public function test_url_to_postid_cpt_term_post_id () {
        delete_option( 'rewrite_rules' );
        $post_type = rand_str( 12 );
        $taxonomy = rand_str( 12 );
        update_option($post_type."_structure", "/%".$taxonomy."%/%post_id%/" );
        register_taxonomy( $taxonomy, $post_type,  array( "public" => true ) );
        register_post_type( $post_type, array( "public" => true ) );
        $id = $this->factory->post->create( array( 'post_type' => $post_type ) );
        wp_set_post_terms( $id, rand_str( 12 ) , $taxonomy );

        $this->assertEquals( $id, url_to_postid( get_permalink( $id ) ) );

        _unregister_post_type( $post_type );
        _unregister_taxonomy( $taxonomy, $post_type );
    }

}

