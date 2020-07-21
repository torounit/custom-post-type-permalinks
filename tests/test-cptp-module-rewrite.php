<?php

class CPTP_Module_Rewrite_Test extends WP_UnitTestCase {

	public $post_type;
	public $taxonomy;

	public function setUp() {
		/** @var WP_Rewrite $wp_rewrite */
		global $wp_rewrite;

		parent::setUp();

		delete_option( 'no_taxonomy_structure' );
		add_option( 'no_taxonomy_structure', false );

		delete_option( 'category_base' );
		add_option( 'category_base', rand_str( 12 ) );
		delete_option( 'tag_base' );
		add_option( 'tag_base', rand_str( 12 ) );

		$wp_rewrite->init();
		$wp_rewrite->set_permalink_structure( '/%year%/%monthnum%/%day%/%postname%/' );
		create_initial_taxonomies();
		update_option( 'page_comments', true );
		update_option( 'comments_per_page', 5 );
		update_option( 'posts_per_page', 5 );
		$this->post_type = rand_str( 12 );
		$this->taxonomy  = rand_str( 12 );
	}

	public function tearDown() {
		_unregister_post_type( $this->post_type );
		_unregister_taxonomy( $this->taxonomy, $this->post_type );
	}

	/**
	 *
	 * @test
	 * @group rewrite
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_cpt_archive() {
		register_post_type(
			$this->post_type,
			array(
				'public'      => true,
				'taxonomies'  => array( 'category' ),
				'has_archive' => true,
			)
		);

		do_action( 'wp_loaded' );
		/** @var WP_Rewrite $wp_rewrite */
		global $wp_rewrite;
		$wp_rewrite->flush_rules();

		$this->factory->post->create_many(
			10,
			array(
				'post_type' => $this->post_type,
				'post_date' => '2012-12-12',
			)
		);
		$this->go_to( get_post_type_archive_link( $this->post_type ) );
		$this->assertQueryTrue( 'is_archive', 'is_post_type_archive' );
	}


	/**
	 *
	 * @test
	 * @group rewrite
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_cpt_date_archive() {
		update_option( $this->post_type . '_structure', '/%year%/%monthnum%/%day%/%post_id%/' );

		register_post_type(
			$this->post_type,
			array(
				'public'      => true,
				'taxonomies'  => array( 'category' ),
				'has_archive' => true,
			)
		);
		$post_type_object = get_post_type_object( $this->post_type );

		$this->factory->post->create_many(
			10,
			array(
				'post_type' => $this->post_type,
				'post_date' => '2012-12-12',
			)
		);

		do_action( 'wp_loaded' );
		/** @var WP_Rewrite $wp_rewrite */
		global $wp_rewrite;
		$wp_rewrite->flush_rules();

		$this->go_to( home_url( '/' . $post_type_object->rewrite['slug'] . '/2012' ) );
		$this->assertQueryTrue( 'is_archive', 'is_post_type_archive', 'is_date', 'is_year' );

		$this->go_to( next_posts( 0, false ) );
		$this->assertQueryTrue( 'is_archive', 'is_post_type_archive', 'is_date', 'is_year', 'is_paged' );

		$this->go_to( home_url( '/' . $post_type_object->rewrite['slug'] . '/2012/12' ) );
		$this->assertQueryTrue( 'is_archive', 'is_post_type_archive', 'is_date', 'is_month' );

		$this->go_to( next_posts( 0, false ) );
		$this->assertQueryTrue( 'is_archive', 'is_post_type_archive', 'is_date', 'is_month', 'is_paged' );

		$this->go_to( home_url( '/' . $post_type_object->rewrite['slug'] . '/2012/12/12' ) );
		$this->assertQueryTrue( 'is_archive', 'is_post_type_archive', 'is_date', 'is_day' );

		$this->go_to( next_posts( 0, false ) );
		$this->assertQueryTrue( 'is_archive', 'is_post_type_archive', 'is_date', 'is_day', 'is_paged' );
	}

	/**
	 *
	 * @test
	 * @group rewrite
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_cpt_disable_date_archive() {
		update_option( $this->post_type . '_structure', '/%year%/%monthnum%/%day%/%post_id%/' );

		register_post_type(
			$this->post_type,
			array(
				'public'      => true,
				'taxonomies'  => array( 'category' ),
				'has_archive' => true,
				'cptp'        => array(
					'date_archive' => false,
				),
			)
		);
		$post_type_object = get_post_type_object( $this->post_type );

		$this->factory->post->create_many(
			10,
			array(
				'post_type' => $this->post_type,
				'post_date' => '2012-12-12',
			)
		);

		do_action( 'wp_loaded' );
		/** @var WP_Rewrite $wp_rewrite */
		global $wp_rewrite;
		$wp_rewrite->flush_rules();

		$this->go_to( home_url( '/' . $post_type_object->rewrite['slug'] . '/2012' ) );
		$this->assertQueryTrue( 'is_404' );
	}


	/**
	 *
	 * @test
	 * @group rewrite
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_cpt_date_archive_with_date_front() {
		update_option( $this->post_type . '_structure', '/%year%/%post_id%/' );

		register_post_type(
			$this->post_type,
			array(
				'public'      => true,
				'taxonomies'  => array( 'category' ),
				'has_archive' => true,
			)
		);
		$post_type_object = get_post_type_object( $this->post_type );

		$this->factory->post->create_many(
			10,
			array(
				'post_type' => $this->post_type,
				'post_date' => '2012-12-12',
			)
		);

		do_action( 'wp_loaded' );
		/** @var WP_Rewrite $wp_rewrite */
		global $wp_rewrite;
		$wp_rewrite->flush_rules();

		$this->go_to( home_url( '/' . $post_type_object->rewrite['slug'] . CPTP_Util::get_date_front( $this->post_type ) . '/2012' ) );
		$this->assertQueryTrue( 'is_archive', 'is_post_type_archive', 'is_date', 'is_year' );

		$this->go_to( next_posts( 0, false ) );
		$this->assertQueryTrue( 'is_archive', 'is_post_type_archive', 'is_date', 'is_year', 'is_paged' );

		$this->go_to( home_url( '/' . $post_type_object->rewrite['slug'] . CPTP_Util::get_date_front( $this->post_type ) . '/2012/12' ) );
		$this->assertQueryTrue( 'is_archive', 'is_post_type_archive', 'is_date', 'is_month' );

		$this->go_to( next_posts( 0, false ) );
		$this->assertQueryTrue( 'is_archive', 'is_post_type_archive', 'is_date', 'is_month', 'is_paged' );

		$this->go_to( home_url( '/' . $post_type_object->rewrite['slug'] . CPTP_Util::get_date_front( $this->post_type ) . '/2012/12/12' ) );
		$this->assertQueryTrue( 'is_archive', 'is_post_type_archive', 'is_date', 'is_day' );

		$this->go_to( next_posts( 0, false ) );
		$this->assertQueryTrue( 'is_archive', 'is_post_type_archive', 'is_date', 'is_day', 'is_paged' );
	}


	/**
	 *
	 * @test
	 * @group rewrite
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_cpt_author_archive() {
		register_post_type(
			$this->post_type,
			array(
				'public'      => true,
				'taxonomies'  => array( 'category' ),
				'has_archive' => true,
				'cptp'        => array(
					'author_archive' => false,
				),
			)
		);
		$post_type_object = get_post_type_object( $this->post_type );

		$user_id = $this->factory->user->create();
		$this->factory->post->create_many(
			10,
			array(
				'post_type'   => $this->post_type,
				'post_date'   => '2012-12-12',
				'post_author' => $user_id,
			)
		);

		$user = get_userdata( $user_id );
		$user->user_nicename;

		do_action( 'wp_loaded' );
		/** @var WP_Rewrite $wp_rewrite */
		global $wp_rewrite;
		$wp_rewrite->flush_rules();

		$this->go_to( home_url( '/' . $post_type_object->rewrite['slug'] . '/author/' . $user->user_nicename ) );
		$this->assertQueryTrue( 'is_404' );
	}

	/**
	 *
	 * @test
	 * @group rewrite
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_disable_cpt_author_archive() {
		register_post_type(
			$this->post_type,
			array(
				'public'      => true,
				'taxonomies'  => array( 'category' ),
				'has_archive' => true,
			)
		);
		$post_type_object = get_post_type_object( $this->post_type );

		$user_id = $this->factory->user->create();
		$this->factory->post->create_many(
			10,
			array(
				'post_type'   => $this->post_type,
				'post_date'   => '2012-12-12',
				'post_author' => $user_id,
			)
		);

		$user = get_userdata( $user_id );
		$user->user_nicename;

		do_action( 'wp_loaded' );
		/** @var WP_Rewrite $wp_rewrite */
		global $wp_rewrite;
		$wp_rewrite->flush_rules();

		$this->go_to( home_url( '/' . $post_type_object->rewrite['slug'] . '/author/' . $user->user_nicename ) );
		$this->assertQueryTrue( 'is_archive', 'is_post_type_archive', 'is_author' );

		$this->go_to( next_posts( 0, false ) );
		$this->assertQueryTrue( 'is_archive', 'is_post_type_archive', 'is_author', 'is_paged' );
	}



	/**
	 *
	 * @test
	 * @group rewrite
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_cpt_category_archive() {
		register_taxonomy(
			$this->taxonomy,
			$this->post_type,
			array(
				'public'  => true,
				'rewrite' => array(
					'slug' => rand_str( 12 ),
				),
			)
		);
		register_post_type(
			$this->post_type,
			array(
				'public'      => true,
				'taxonomies'  => array( 'category' ),
				'has_archive' => true,
			)
		);
		$post_type_object = get_post_type_object( $this->post_type );

		$user_id = $this->factory->user->create();

		$post_ids = $this->factory->post->create_many(
			10,
			array(
				'post_type'   => $this->post_type,
				'post_date'   => '2012-12-12',
				'post_author' => $user_id,
			)
		);

		$cat_id = $this->factory->category->create();
		foreach ( $post_ids as $post_id ) {
			wp_set_post_categories( $post_id, array( $cat_id ) );
		}

		$category_obj = get_category( $cat_id );

		do_action( 'wp_loaded' );
		/** @var WP_Rewrite $wp_rewrite */
		global $wp_rewrite;
		$category_base = get_option( 'category_base', 'category' );
		$wp_rewrite->flush_rules();
		$this->go_to( home_url( '/' . $post_type_object->rewrite['slug'] . '/' . $category_base . '/' . $category_obj->slug ) );
		$this->assertQueryTrue( 'is_archive', 'is_post_type_archive', 'is_category' );

		$this->go_to( next_posts( 0, false ) );
		$this->assertQueryTrue( 'is_archive', 'is_post_type_archive', 'is_category', 'is_paged' );
	}


	/**
	 *
	 * @test
	 * @group rewrite
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_term_archive() {
		register_post_type(
			$this->post_type,
			array(
				'public'      => true,
				'taxonomies'  => array( 'category' ),
				'has_archive' => true,
			)
		);
		register_taxonomy(
			$this->taxonomy,
			$this->post_type,
			array(
				'public'  => true,
				'rewrite' => array(
					'slug' => rand_str( 12 ),
				),
			)
		);
		$post_type_object = get_post_type_object( $this->post_type );

		$user_id = $this->factory->user->create();

		$post_ids = $this->factory->post->create_many(
			100,
			array(
				'post_type'   => $this->post_type,
				'post_date'   => '2012-12-12',
				'post_author' => $user_id,
			)
		);

		$term_id = $this->factory->term->create(
			array(
				'taxonomy' => $this->taxonomy,
			)
		);
		foreach ( $post_ids as $post_id ) {
			wp_set_object_terms( $post_id, get_term( $term_id, $this->taxonomy )->slug, $this->taxonomy );
		}

		$term_obj = get_term( $term_id, $this->taxonomy );

		$taxonomy      = get_taxonomy( $this->taxonomy );
		$taxonomy_slug = $taxonomy->rewrite['slug'];

		do_action( 'wp_loaded' );
		/** @var WP_Rewrite $wp_rewrite */
		global $wp_rewrite;
		$wp_rewrite->flush_rules();
		$this->assertEquals( home_url( $post_type_object->rewrite['slug'] . '/' . $taxonomy_slug . '/' . $term_obj->slug . '/' ), get_term_link( $term_obj, $taxonomy ) );
		$this->go_to( get_term_link( $term_obj, $taxonomy ) );
		$this->assertQueryTrue( 'is_archive', 'is_tax' );

		$this->go_to( next_posts( 0, false ) );
		$this->assertQueryTrue( 'is_archive', 'is_tax', 'is_paged' );
	}

	/**
	 *
	 * @test
	 * @group rewrite
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_term_date_archive() {
		register_post_type(
			$this->post_type,
			array(
				'public'      => true,
				'taxonomies'  => array( 'category' ),
				'has_archive' => true,
			)
		);
		register_taxonomy(
			$this->taxonomy,
			$this->post_type,
			array(
				'public'  => true,
				'rewrite' => array(
					'slug' => rand_str( 12 ),
				),
			)
		);
		$post_type_object = get_post_type_object( $this->post_type );

		$user_id = $this->factory->user->create();

		$post_ids = $this->factory->post->create_many(
			100,
			array(
				'post_type'   => $this->post_type,
				'post_date'   => '2012-12-12',
				'post_author' => $user_id,
			)
		);

		$term_id = $this->factory->term->create(
			array(
				'taxonomy' => $this->taxonomy,
			)
		);
		foreach ( $post_ids as $post_id ) {
			wp_set_object_terms( $post_id, get_term( $term_id, $this->taxonomy )->slug, $this->taxonomy );
		}

		$term_obj = get_term( $term_id, $this->taxonomy );

		$taxonomy      = get_taxonomy( $this->taxonomy );
		$taxonomy_slug = $taxonomy->rewrite['slug'];

		do_action( 'wp_loaded' );
		/** @var WP_Rewrite $wp_rewrite */
		global $wp_rewrite;
		$wp_rewrite->flush_rules();
		$this->go_to( home_url( $post_type_object->rewrite['slug'] . '/' . $taxonomy_slug . '/' . $term_obj->slug . '/date/2012' ) );
		$this->assertQueryTrue( 'is_archive', 'is_tax', 'is_year', 'is_date' );

		$this->go_to( next_posts( 0, false ) );
		$this->assertQueryTrue( 'is_archive', 'is_tax', 'is_paged', 'is_year', 'is_date' );
	}

	/**
	 *
	 * @test
	 * @group rewrite
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_cpt_query_term_archive() {
		update_option( 'add_post_type_for_tax', true );
		register_post_type(
			$this->post_type,
			array(
				'public'      => true,
				'taxonomies'  => array( 'category' ),
				'has_archive' => true,
			)
		);
		register_taxonomy(
			$this->taxonomy,
			$this->post_type,
			array(
				'public'  => true,
				'rewrite' => array(
					'slug' => rand_str( 12 ),
				),
			)
		);
		$post_type_object = get_post_type_object( $this->post_type );

		$user_id = $this->factory->user->create();

		$post_ids = $this->factory->post->create_many(
			100,
			array(
				'post_type'   => $this->post_type,
				'post_date'   => '2012-12-12',
				'post_author' => $user_id,
			)
		);

		$term_id = $this->factory->term->create(
			array(
				'taxonomy' => $this->taxonomy,
			)
		);
		foreach ( $post_ids as $post_id ) {
			wp_set_object_terms( $post_id, get_term( $term_id, $this->taxonomy )->slug, $this->taxonomy );
		}

		$term_obj = get_term( $term_id, $this->taxonomy );

		$taxonomy      = get_taxonomy( $this->taxonomy );
		$taxonomy_slug = $taxonomy->rewrite['slug'];

		do_action( 'wp_loaded' );
		/** @var WP_Rewrite $wp_rewrite */
		global $wp_rewrite;
		$wp_rewrite->flush_rules();
		$this->assertEquals( home_url( $post_type_object->rewrite['slug'] . '/' . $taxonomy_slug . '/' . $term_obj->slug . '/' ), get_term_link( $term_obj, $taxonomy ) );
		$this->go_to( get_term_link( $term_obj, $taxonomy ) );
		$this->assertQueryTrue( 'is_post_type_archive', 'is_archive', 'is_tax' );

		$this->go_to( next_posts( 0, false ) );
		$this->assertQueryTrue( 'is_post_type_archive', 'is_archive', 'is_tax', 'is_paged' );
	}

}
