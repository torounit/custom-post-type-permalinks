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


	public function test_url_to_postid_cpt_hierarchial_term_post_id() {
		delete_option( 'rewrite_rules' );
		$post_type = rand_str( 12 );
		$taxonomy = rand_str( 12 );

		update_option($post_type."_structure", "/%".$taxonomy."%/%post_id%/" );
		register_taxonomy( $taxonomy, $post_type,  array( "public" => true ,"hierarchial" => true) );
		register_post_type( $post_type, array( "public" => true ) );

		$id = $this->factory->post->create( array( 'post_type' => $post_type ) );

		$term_id = 0;
		for ($i=0; $i < 10; $i++) {
			$slug = rand_str( 12 );
			$term = wp_insert_term( $slug, $taxonomy, array("parent" => $term_id, "slug" => $slug) );
			$term_id = $term["term_id"];
		}
		wp_set_post_terms( $id, get_term( $term_id, $taxonomy )->slug, $taxonomy );

		$this->assertEquals( $id, url_to_postid( get_permalink( $id ) ) );
		_unregister_post_type( $post_type );
		_unregister_taxonomy( $taxonomy, $post_type );
	}

	public function test_url_cpt_hierarchial_url_to_that_all() {
		delete_option( 'rewrite_rules' );
		$post_type = rand_str( 12 );
		$taxonomy = rand_str( 12 );

		update_option($post_type."_structure", "/%".$taxonomy."%/%postname%/" );
		register_taxonomy( $taxonomy, $post_type,  array( "public" => true ,"hierarchial" => true) );
		register_post_type( $post_type, array( "public" => true ) );

		$id = $this->factory->post->create( array( 'post_type' => $post_type ,"post_name" => "foo") );
		$id_all_term = $this->factory->post->create( array( 'post_type' => $post_type ,"post_name" => "foo_all_term") );

		$term_id = 0;
		$slug_list = array();
		for ($i=0; $i < 10; $i++) {
			$slug = rand_str( 12 );
			$term = wp_insert_term( $slug, $taxonomy, array("parent" => $term_id, "slug" => $slug) );
			$term_id = $term["term_id"];
			$slug_list[] = get_term( $term_id, $taxonomy )->slug;

		}
		wp_set_post_terms( $id, get_term( $term_id, $taxonomy )->slug, $taxonomy );
		wp_set_post_terms( $id_all_term, $slug_list, $taxonomy );

		$this->assertEquals( get_permalink( $id ), str_replace("foo_all_term", "foo", get_permalink( $id_all_term ))  );

		_unregister_post_type( $post_type );
		_unregister_taxonomy( $taxonomy, $post_type );
	}


	public function test_url_to_postid_category_post_id () {
		delete_option( 'rewrite_rules' );
		$post_type = rand_str( 12 );
		$taxonomy = rand_str( 12 );
		update_option($post_type."_structure", "/%category%/%post_id%/" );
		register_post_type( $post_type, array( "public" => true , 'taxonomies' => array('category')) );
		$id = $this->factory->post->create( array( 'post_type' => $post_type ) );
		$term = wp_insert_term( rand_str( 12 ), "category" );
		wp_set_post_categories( $id, array($term["term_id"]) );

		$this->assertEquals( $id, url_to_postid( get_permalink( $id ) ) );

		_unregister_post_type( $post_type );
		_unregister_taxonomy( $taxonomy, $post_type );
	}


	public function test_url_to_postid_cpt_author_postname () {
		delete_option( 'rewrite_rules' );
		$post_type = rand_str( 12 );
		update_option($post_type."_structure", "/%author%/%postname%/" );
		register_post_type( $post_type, array( "public" => true ) );
		$user_id = $this->factory->user->create();
		$id = $this->factory->post->create( array( 'post_type' => $post_type ,"post_author" => $user_id ) );
		$this->assertEquals( $id, url_to_postid( get_permalink( $id ) ) );
		_unregister_post_type( $post_type );
	}


}

