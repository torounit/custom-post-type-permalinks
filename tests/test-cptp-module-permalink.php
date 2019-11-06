<?php

class CPTP_Module_Permalink_Test extends WP_UnitTestCase {

	public $post_type;
	public $taxonomy;

	public function setUp() {
		/* @var WP_Rewrite $wp_rewrite */
		global $wp_rewrite;
		parent::setUp();

		delete_option( 'category_base' );
		add_option( 'category_base', rand_str( 12 ) );
		delete_option( 'tag_base' );
		add_option( 'tag_base', rand_str( 12 ) );

		$wp_rewrite->init();
		$wp_rewrite->set_permalink_structure( '/%year%/%monthnum%/%day%/%postname%/' );
		delete_option( 'no_taxonomy_structure' );
		add_option( 'no_taxonomy_structure', false );

		create_initial_taxonomies();


		update_option( 'page_comments', true );
		update_option( 'comments_per_page', 5 );
		update_option( 'posts_per_page', 5 );
		$this->post_type = 'cpt';
		$this->taxonomy  = 'ctax';
	}

	public function tearDown() {
		_unregister_post_type( $this->post_type );
		_unregister_taxonomy( $this->taxonomy );
	}


	/**
	 * Permalink structure provider.
	 *
	 * @return array
	 */
	public function structure_provider() {
		return array(
			array( '/%post_id%/' ),
			array( '/%postname%/' ),
			array( '/%year%/%monthnum%/%day%/%post_id%/' ),
			array( '/%year%/%monthnum%/%day%/%postname%/' ),
			array( '/%author%/%post_id%/' ),
			array( '/%author%/%postname%/' ),
			array( '/%ctax%/%post_id%/' ),
			array( '/%ctax%/%postname%/' ),
			array( '/%category%/%post_id%/' ),
			array( '/%category%/%postname%/' ),
			array( '/%category%/%ctax%/%post_id%/' ),
			array( '/%category%/%ctax%/%postname%/' ),
			array( '/%ctax%/%category%/%post_id%/' ),
			array( '/%ctax%/%category%/%postname%/' ),
			array( '/%ctax%/%author%/%year%/%monthnum%/%day%/%category%/%post_id%/' ),
			array( '/%ctax%/%author%/%year%/%monthnum%/%day%/%category%/%postname%/' ),
		);
	}

	/**
	 * Test permalink
	 *
	 * @test
	 * @group permalink
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @dataProvider structure_provider
	 *
	 * @param string $structure permalink structure.
	 */
	public function test_url_to_postid_cpt( $structure ) {
		update_option( $this->post_type . '_structure', $structure );

		register_taxonomy( $this->taxonomy, $this->post_type, array(
			'public'  => true,
			'rewrite' => array(
				'slug' => rand_str( 12 ),
			),
		) );

		register_post_type( $this->post_type, array(
			'public'     => true,
			'taxonomies' => array( 'category' ),
		) );

		$user_id = $this->factory->user->create();

		$id = 0;
		for ( $i = 0; $i < 4; $i ++ ) {
			$id = $this->factory->post->create( array(
				'post_type'   => $this->post_type,
				'post_author' => $user_id,
				'post_parent' => $id,
			) );
		}

		$term_id = $this->factory->term->create( array(
			'taxonomy' => $this->taxonomy,
		) );
		wp_set_post_terms( $id, array( $term_id ), $this->taxonomy );

		$cat_id = $this->factory->category->create();
		wp_set_post_categories( $id, array( $cat_id ) );

		$file          = DIR_TESTDATA . '/images/canola.jpg';
		$attachment_id = $this->factory->attachment->create_object( $file, $id, array(
			'post_mime_type' => 'image/jpeg',
			'menu_order'     => rand( 1, 100 ),
		) );

		do_action( 'wp_loaded' );
		/* @var WP_Rewrite $wp_rewrite */
		global $wp_rewrite;
		$wp_rewrite->flush_rules();

		$this->assertEquals( $id, url_to_postid( get_permalink( $id ) ) );
		$this->go_to( get_permalink( $id ) );
		$this->assertTrue( is_single() );
		$this->assertEquals( $this->post_type, get_post_type() );
		$this->factory->comment->create_post_comments( $id, 15 );
		$this->go_to( get_permalink( $id ) . 'comment-page-2' );
		$this->assertEquals( get_query_var( 'cpage' ), 2 );

		$this->assertEquals( $attachment_id, url_to_postid( get_attachment_link( $attachment_id ) ) );
		$this->go_to( get_attachment_link( $attachment_id ) );
		$this->assertTrue( is_attachment() );
	}


	/**
	 *
	 * @test
	 * @group permalink
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @dataProvider structure_provider
	 *
	 * @param string $structure permalink structure.
	 */
	public function test_url_to_postid_hierarchial_cpt( $structure ) {
		update_option( $this->post_type . '_structure', $structure );

		register_taxonomy( $this->taxonomy, $this->post_type, array(
			'public'  => true,
			'rewrite' => array(
				'slug' => rand_str( 12 ),
			),
		) );
		register_post_type( $this->post_type, array(
			'public'       => true,
			'hierarchical' => true,
			'taxonomies'   => array(
				'category',
			),
		) );

		$user_id = $this->factory->user->create();

		$id = 0;
		for ( $i = 0; $i < 4; $i ++ ) {
			$id = $this->factory->post->create( array(
				'post_type'   => $this->post_type,
				'post_author' => $user_id,
				'post_parent' => $id,
			) );
		}

		$term_id = $this->factory->term->create( array(
			'taxonomy' => $this->taxonomy,
		) );
		wp_set_post_terms( $id, array( $term_id ), $this->taxonomy );

		$cat_id = $this->factory->category->create();
		wp_set_post_categories( $id, array( $cat_id ) );

		$file          = DIR_TESTDATA . '/images/canola.jpg';
		$attachment_id = $this->factory->attachment->create_object( $file, $id, array(
			'post_mime_type' => 'image/jpeg',
			'menu_order'     => rand( 1, 100 ),
		) );

		do_action( 'wp_loaded' );
		/* @var WP_Rewrite $wp_rewrite */
		global $wp_rewrite;
		$wp_rewrite->flush_rules();

		$this->assertEquals( $id, url_to_postid( get_permalink( $id ) ) );
		$this->go_to( get_permalink( $id ) );
		$this->assertTrue( is_single() );
		$this->assertEquals( $this->post_type, get_post_type() );

		$this->factory->comment->create_post_comments( $id, 25 );
		$this->go_to( get_permalink( $id ) . 'comment-page-5' );
		$this->assertEquals( get_query_var( 'cpage' ), 5 );

		$this->assertEquals( $attachment_id, url_to_postid( get_attachment_link( $attachment_id ) ) );
		$this->go_to( get_attachment_link( $attachment_id ) );
		$this->assertTrue( is_attachment() );
	}


	/**
	 *
	 * @test
	 * @group permalink
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @dataProvider structure_provider
	 *
	 * @param string $structure permalink structure.
	 */
	public function test_url_to_postid_cpt_hierarchial_term( $structure ) {
		update_option( $this->post_type . '_structure', $structure );
		register_taxonomy( $this->taxonomy, $this->post_type, array(
			'public'      => true,
			'hierarchial' => true,
		) );
		register_post_type( $this->post_type, array(
			'public' => true,
		) );

		$user_id = $this->factory->user->create();
		$id      = $this->factory->post->create( array(
			'post_type'   => $this->post_type,
			'post_name'   => rand_str( 12 ),
			'post_author' => $user_id,
		) );

		$cat_id = $this->factory->category->create();
		wp_set_post_categories( $id, array( $cat_id ) );

		$file          = DIR_TESTDATA . '/images/canola.jpg';
		$attachment_id = $this->factory->attachment->create_object( $file, $id, array(
			'post_mime_type' => 'image/jpeg',
			'menu_order'     => rand( 1, 100 ),
		) );

		$term_id   = 0;
		$slug_list = array();
		for ( $i = 0; $i < 4; $i ++ ) {
			$term_id     = $this->factory->term->create( array(
				'taxonomy' => $this->taxonomy,
				'parent'   => $term_id,
			) );
			$slug_list[] = get_term( $term_id, $this->taxonomy )->slug;
		}

		wp_set_post_terms( $id, get_term( $term_id, $this->taxonomy )->slug, $this->taxonomy );

		do_action( 'wp_loaded' );
		/* @var WP_Rewrite $wp_rewrite */
		global $wp_rewrite;
		$wp_rewrite->flush_rules();

		$single_term_link = get_permalink( $id );
		$this->assertEquals( $id, url_to_postid( get_permalink( $id ) ) );

		// 全てのタームにチェックが付いていた場合。
		wp_set_post_terms( $id, $slug_list, $this->taxonomy );
		$this->assertEquals( $id, url_to_postid( get_permalink( $id ) ) );
		$this->assertEquals( get_permalink( $id ), $single_term_link );

		$this->assertEquals( $attachment_id, url_to_postid( get_attachment_link( $attachment_id ) ) );
		$this->go_to( get_attachment_link( $attachment_id ) );
		$this->assertTrue( is_attachment() );
	}

	/**
	 * Test Private Post Type
	 *
	 * @test
	 * @group permalink
	 * @group #81
	 * @issue #81
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @dataProvider structure_provider
	 *
	 * @param string $structure permalink structure.
	 */
	public function test_to_private_post_type( $structure ) {
		update_option( $this->post_type . '_structure', $structure );

		register_taxonomy( $this->taxonomy, $this->post_type, array(
			'public'  => true,
			'rewrite' => array(
				'slug' => rand_str( 12 ),
			),
		) );

		register_post_type( $this->post_type, array(
			'public'     => false,
			'taxonomies' => array( 'category' ),
		) );

		$user_id = $this->factory->user->create();

		$id = 0;
		$id = $this->factory->post->create( array(
			'post_type'   => $this->post_type,
			'post_author' => $user_id,
			'post_parent' => $id,
		) );

		$term_id = $this->factory->term->create( array(
			'taxonomy' => $this->taxonomy,
		) );
		wp_set_post_terms( $id, array( $term_id ), $this->taxonomy );

		$cat_id = $this->factory->category->create();
		wp_set_post_categories( $id, array( $cat_id ) );

		$file          = DIR_TESTDATA . '/images/canola.jpg';
		$attachment_id = $this->factory->attachment->create_object( $file, $id, array(
			'post_mime_type' => 'image/jpeg',
			'menu_order'     => rand( 1, 100 ),
			'post_title'     => 'canola',
		) );

		do_action( 'wp_loaded' );
		/* @var WP_Rewrite $wp_rewrite */
		global $wp_rewrite;
		$wp_rewrite->flush_rules();
		$permastruct = $wp_rewrite->get_extra_permastruct( $this->post_type );
		$this->assertEquals( '/cpt/%cpt%', $permastruct );
		$post_link = home_url( user_trailingslashit( str_replace( '%' . $this->post_type . '%', get_post( $id )->post_name, $permastruct ) ) );
		$this->assertEquals( $post_link, get_permalink( $id ) );
		$this->go_to( get_permalink( $id ) );
		$this->assertFalse( is_single() );

		$attachment_link = user_trailingslashit( trailingslashit( $post_link ) . get_post( $attachment_id )->post_name );
		$this->assertEquals( $attachment_id, url_to_postid( get_attachment_link( $attachment_id ) ) );
		$this->assertEquals( $attachment_link, get_attachment_link( $attachment_id ) );
		$this->go_to( get_attachment_link( $attachment_id ) );
		$this->assertTrue( is_attachment() );
	}

	/**
	 * Test Private Post Type
	 *
	 * @test
	 * @group permalink
	 * @group #76
	 * @issue #76
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @dataProvider structure_provider
	 *
	 * @param string $structure permalink structure.
	 */
	public function test_to_disable_post_type( $structure ) {
		update_option( $this->post_type . '_structure', $structure );

		add_filter( 'CPTP_is_rewrite_supported_by_' . $this->post_type, '__return_false' );

		register_taxonomy( $this->taxonomy, $this->post_type, array(
			'public'  => true,
			'rewrite' => array(
				'slug' => rand_str( 12 ),
			),
		) );

		register_post_type( $this->post_type, array(
			'public'     => true,
			'taxonomies' => array( 'category' ),
		) );

		$user_id = $this->factory->user->create();

		$id = 0;
		$id = $this->factory->post->create( array(
			'post_type'   => $this->post_type,
			'post_author' => $user_id,
			'post_parent' => $id,
		) );

		$term_id = $this->factory->term->create( array(
			'taxonomy' => $this->taxonomy,
		) );
		wp_set_post_terms( $id, array( $term_id ), $this->taxonomy );

		$cat_id = $this->factory->category->create();
		wp_set_post_categories( $id, array( $cat_id ) );

		$file          = DIR_TESTDATA . '/images/canola.jpg';
		$attachment_id = $this->factory->attachment->create_object( $file, $id, array(
			'post_mime_type' => 'image/jpeg',
			'menu_order'     => rand( 1, 100 ),
			'post_title'     => 'canola',
		) );

		do_action( 'wp_loaded' );
		/* @var WP_Rewrite $wp_rewrite */
		global $wp_rewrite;
		$wp_rewrite->flush_rules();
		$permastruct = $wp_rewrite->get_extra_permastruct( $this->post_type );
		$this->assertEquals( '/cpt/%cpt%', $permastruct );
		$post_link = home_url( user_trailingslashit( str_replace( '%' . $this->post_type . '%', get_post( $id )->post_name, $permastruct ) ) );
		$this->assertEquals( $post_link, get_permalink( $id ) );
		$this->go_to( get_permalink( $id ) );
		$this->assertTrue( is_single() );

		$attachment_link = user_trailingslashit( trailingslashit( $post_link ) . get_post( $attachment_id )->post_name );
		$this->assertEquals( $attachment_id, url_to_postid( get_attachment_link( $attachment_id ) ) );
		$this->assertEquals( $attachment_link, get_attachment_link( $attachment_id ) );
		$this->go_to( get_attachment_link( $attachment_id ) );
		$this->assertTrue( is_attachment() );
	}


}

