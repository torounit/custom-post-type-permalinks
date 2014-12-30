<?php

class CPTP_Module_Rewrite_Test extends WP_UnitTestCase {

	public function setUp() {
		global $wp_rewrite;
		parent::setUp();

		$wp_rewrite->init();
		$wp_rewrite->set_permalink_structure( '/%year%/%monthnum%/%day%/%postname%/' );
		create_initial_taxonomies();
		$wp_rewrite->flush_rules();
		cptp_init_instance();

		update_option( 'page_comments', true );
		update_option( 'comments_per_page', 5 );
		update_option( 'posts_per_page', 5 );
		delete_option( 'rewrite_rules' );
		$this->post_type = "cpt";
		$this->taxonomy = "ctax";

	}

	public function tearDown() {

		_unregister_post_type( $this->post_type );
		_unregister_taxonomy( $this->taxonomy, $this->post_type );

	}

	/**
	 *
	 * @test
	 * @group rewrite
	 */
	public function test_cpt_archive() {
		register_post_type( $this->post_type, array( "public" => true , 'taxonomies' => array('category'), "has_archive" => true ) );
		$this->factory->post->create_many( 10 , array( 'post_type' => $this->post_type, "post_date" => "2012-12-12") );
		$this->go_to( get_post_type_archive_link( $this->post_type ) );
		$this->assertQueryTrue( "is_archive", "is_post_type_archive" );
	}


	/**
	 *
	 * @test
	 * @group rewrite
	 */
	public function test_cpt_date_archive() {
		register_post_type( $this->post_type, array( "public" => true , 'taxonomies' => array('category'), "has_archive" => true ) );
		$post_type_object = get_post_type_object( $this->post_type );

		$this->factory->post->create_many( 10 , array( 'post_type' => $this->post_type, "post_date" => "2012-12-12") );

		$this->go_to( home_url( "/".$post_type_object->rewrite["slug"]."/date/2012" ));
		$this->assertQueryTrue( "is_archive", "is_post_type_archive" , "is_date", "is_year" );

		$this->go_to(next_posts(0,false));
		$this->assertQueryTrue( "is_archive", "is_post_type_archive" , "is_date", "is_year", "is_paged" );


		$this->go_to( home_url( "/".$post_type_object->rewrite["slug"]."/date/2012/12" ));
		$this->assertQueryTrue( "is_archive", "is_post_type_archive" , "is_date", "is_month" );

		$this->go_to(next_posts(0,false));
		$this->assertQueryTrue( "is_archive", "is_post_type_archive" , "is_date", "is_month", "is_paged" );


		$this->go_to( home_url( "/".$post_type_object->rewrite["slug"]."/date/2012/12/12" ));
		$this->assertQueryTrue( "is_archive", "is_post_type_archive" , "is_date", "is_day" );

		$this->go_to(next_posts(0,false));
		$this->assertQueryTrue( "is_archive", "is_post_type_archive" , "is_date", "is_day", "is_paged" );

	}


	/**
	 *
	 * @test
	 * @group rewrite
	 */
	public function test_cpt_author_archive() {
		register_post_type( $this->post_type, array( "public" => true , 'taxonomies' => array('category'), "has_archive" => true ) );
		$post_type_object = get_post_type_object( $this->post_type );

		$user_id = $this->factory->user->create();
		$this->factory->post->create_many( 10 , array( 'post_type' => $this->post_type, "post_date" => "2012-12-12", "post_author" => $user_id ) );

		$user = get_userdata($user_id);
		$user->user_nicename;

		$this->go_to( home_url( "/".$post_type_object->rewrite["slug"]."/author/".$user->user_nicename ));
		$this->assertQueryTrue( "is_archive", "is_post_type_archive", "is_author" );

		$this->go_to(next_posts(0,false));
		$this->assertQueryTrue( "is_archive", "is_post_type_archive", "is_author", "is_paged" );

	}

}