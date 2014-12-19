<?php

class CPTP_Module_Permalink_Test extends WP_UnitTestCase {

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
	 * @dataProvider structure_provider
	 */
	public function test_url_to_postid_cpt( $structure ) {

		$user_id = $this->factory->user->create();
		update_option($this->post_type."_structure", $structure );

		register_taxonomy( $this->taxonomy, $this->post_type,  array( "public" => true , "rewrite" => array("slug" => rand_str( 12 ) )));
		register_post_type( $this->post_type, array( "public" => true , 'taxonomies' => array('category') ) );

		$id = $this->factory->post->create( array( 'post_type' => $this->post_type ,"post_author" => $user_id ) );
		wp_set_post_terms( $id, rand_str( 12 ) , $this->taxonomy );

		$cat = wp_insert_term( rand_str( 12 ), "category" );
		wp_set_post_categories( $id, array($cat["term_id"]) );

		$this->assertEquals( $id, url_to_postid( get_permalink( $id ) ) );

		$this->go_to( get_permalink( $id ) );
		$this->assertTrue( is_single() );
		$this->assertEquals( $this->post_type, get_post_type() );

		$this->factory->comment->create_post_comments( $id, 50 );
		$this->go_to(get_permalink( $id )."comment-page-5" );
		$this->assertEquals( get_query_var( "cpage"), 5 );

	}


	public function structure_provider() {
		return array(
			array("/%year%/%monthnum%/%day%/%post_id%/"),
			array("/%year%/%monthnum%/%day%/%postname%/"),
			array("/%ctax%/%post_id%/"),
			array("/%author%/%postname%/"),
			array("/%category%/%post_id%/"),
		);
	}


	public function test_url_to_postid_cpt_hierarchial_term_post_id() {

		update_option($this->post_type."_structure", "/%".$this->taxonomy."%/%post_id%/" );
		register_taxonomy( $this->taxonomy, $this->post_type,  array( "public" => true ,"hierarchial" => true) );
		register_post_type( $this->post_type, array( "public" => true ) );

		$id = $this->factory->post->create( array( 'post_type' => $this->post_type ) );

		$term_id = 0;
		for ($i=0; $i < 10; $i++) {
			$slug = rand_str( 12 );
			$term = wp_insert_term( $slug, $this->taxonomy, array("parent" => $term_id, "slug" => $slug) );
			$term_id = $term["term_id"];
		}
		wp_set_post_terms( $id, get_term( $term_id, $this->taxonomy )->slug, $this->taxonomy );

		$this->assertEquals( $id, url_to_postid( get_permalink( $id ) ) );
	}

	public function test_url_cpt_hierarchial_url_to_that_all() {

		update_option($this->post_type."_structure", "/%".$this->taxonomy."%/%postname%/" );
		register_taxonomy( $this->taxonomy, $this->post_type,  array( "public" => true ,"hierarchial" => true) );
		register_post_type( $this->post_type, array( "public" => true ) );

		$id = $this->factory->post->create( array( 'post_type' => $this->post_type ,"post_name" => "foo") );
		$id_all_term = $this->factory->post->create( array( 'post_type' => $this->post_type ,"post_name" => "foo_all_term") );

		$term_id = 0;
		$slug_list = array();
		for ($i=0; $i < 10; $i++) {
			$slug = rand_str( 12 );
			$term = wp_insert_term( $slug, $this->taxonomy, array("parent" => $term_id, "slug" => $slug) );
			$term_id = $term["term_id"];
			$slug_list[] = get_term( $term_id, $this->taxonomy )->slug;

		}
		wp_set_post_terms( $id, get_term( $term_id, $this->taxonomy )->slug, $this->taxonomy );
		wp_set_post_terms( $id_all_term, $slug_list, $this->taxonomy );

		$this->assertEquals( get_permalink( $id ), str_replace("foo_all_term", "foo", get_permalink( $id_all_term ))  );

	}


}

