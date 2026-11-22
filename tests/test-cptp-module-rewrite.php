<?php

class CPTP_Module_Rewrite_Test extends WP_UnitTestCase {

	public $post_type;
	public $taxonomy;

	public function set_up() {
		/** @var WP_Rewrite $wp_rewrite */
		global $wp_rewrite;

		parent::set_up();

		delete_option( 'no_taxonomy_structure' );
		add_option( 'no_taxonomy_structure', false );

		delete_option( 'category_base' );
		add_option( 'category_base', cptp_rand_str() );
		delete_option( 'tag_base' );
		add_option( 'tag_base', cptp_rand_str() );

		$wp_rewrite->init();
		$wp_rewrite->set_permalink_structure( '/%year%/%monthnum%/%day%/%postname%/' );
		create_initial_taxonomies();
		update_option( 'page_comments', true );
		update_option( 'comments_per_page', 5 );
		update_option( 'posts_per_page', 5 );
		$this->post_type = cptp_rand_str();
		$this->taxonomy  = cptp_rand_str();
	}

	public function tear_down() {
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
					'slug' => cptp_rand_str(),
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
	 * Category archive of a post type works when the category base is empty (WordPress default).
	 *
	 * @test
	 * @group rewrite
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_cpt_category_archive_with_empty_category_base() {
		update_option( 'category_base', '' );

		register_post_type(
			$this->post_type,
			array(
				'public'      => true,
				'taxonomies'  => array( 'category' ),
				'has_archive' => true,
			)
		);
		$post_type_object = get_post_type_object( $this->post_type );

		$post_ids = $this->factory->post->create_many(
			10,
			array(
				'post_type' => $this->post_type,
				'post_date' => '2012-12-12',
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
		$wp_rewrite->flush_rules();
		$this->go_to( home_url( '/' . $post_type_object->rewrite['slug'] . '/category/' . $category_obj->slug ) );
		$this->assertQueryTrue( 'is_archive', 'is_post_type_archive', 'is_category' );

		$this->go_to( next_posts( 0, false ) );
		$this->assertQueryTrue( 'is_archive', 'is_post_type_archive', 'is_category', 'is_paged' );
	}

	/**
	 * A post type whose `taxonomies` is a string must not cause a TypeError.
	 *
	 * @see https://github.com/torounit/custom-post-type-permalinks/issues/119
	 *
	 * @test
	 * @group rewrite
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_cpt_category_archive_with_string_taxonomies() {
		/** @var WP_Rewrite $wp_rewrite */
		global $wp_rewrite;

		register_post_type(
			$this->post_type,
			array(
				'public'      => true,
				'has_archive' => true,
			)
		);
		$post_type_object = get_post_type_object( $this->post_type );

		// WordPress core does not normalize `taxonomies`, so a string reaches the hook as is.
		$post_type_object->taxonomies = 'category';
		do_action( 'registered_post_type', $this->post_type, $post_type_object );

		$category_base = get_option( 'category_base', 'category' );
		$this->assertArrayHasKey(
			$post_type_object->rewrite['slug'] . '/' . $category_base . '/([^/]+)/?$',
			$wp_rewrite->extra_rules_top
		);
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
					'slug' => cptp_rand_str(),
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
					'slug' => cptp_rand_str(),
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
					'slug' => cptp_rand_str(),
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

	/**
	 * Creates a term in the test taxonomy that has more posts than one page shows.
	 *
	 * @return WP_Term
	 */
	private function create_term_with_posts() {
		$post_ids = $this->factory->post->create_many(
			10,
			array(
				'post_type' => $this->post_type,
				'post_date' => '2012-12-12',
			)
		);

		$term_id  = $this->factory->term->create(
			array(
				'taxonomy' => $this->taxonomy,
			)
		);
		$term_obj = get_term( $term_id, $this->taxonomy );
		foreach ( $post_ids as $post_id ) {
			wp_set_object_terms( $post_id, $term_obj->slug, $this->taxonomy );
		}

		return $term_obj;
	}

	/**
	 * Asserts that the term archive, its pages, date archive and feed resolve under a base URL.
	 *
	 * @param string  $base_url URL of the term archive, without a trailing slash.
	 * @param WP_Term $term_obj Term.
	 */
	private function assert_term_archive_resolves( $base_url, $term_obj ) {
		$this->go_to( $base_url . '/' );
		$this->assertQueryTrue( 'is_archive', 'is_tax' );
		$this->assertSame( $term_obj->term_id, get_queried_object_id() );

		$this->go_to( $base_url . '/page/2/' );
		$this->assertQueryTrue( 'is_archive', 'is_tax', 'is_paged' );

		$this->go_to( $base_url . '/date/2012' );
		$this->assertQueryTrue( 'is_archive', 'is_tax', 'is_year', 'is_date' );

		$this->go_to( $base_url . '/feed/' );
		$this->assertQueryTrue( 'is_archive', 'is_tax', 'is_feed' );
	}

	/**
	 * Returns the rules that the plugin added under a post type slug for the test taxonomy.
	 *
	 * @return string[]
	 */
	private function get_taxonomy_rules_under_post_type() {
		/** @var WP_Rewrite $wp_rewrite */
		global $wp_rewrite;

		$taxonomy_slug = get_taxonomy( $this->taxonomy )->rewrite['slug'];

		return array_filter(
			array_keys( $wp_rewrite->extra_rules_top ),
			function ( $regex ) use ( $taxonomy_slug ) {
				return false !== strpos( $regex, '/' . $taxonomy_slug . '/' );
			}
		);
	}

	/**
	 * Registers a post type whose `has_archive` slug differs from its rewrite slug,
	 * and then a taxonomy with a term.
	 *
	 * @param string $archive_slug `has_archive` of the post type.
	 * @param string $rewrite_slug Rewrite slug of the post type.
	 *
	 * @return WP_Term
	 */
	private function register_post_type_with_archive_slug( $archive_slug, $rewrite_slug ) {
		register_post_type(
			$this->post_type,
			array(
				'public'      => true,
				'has_archive' => $archive_slug,
				'rewrite'     => array(
					'slug' => $rewrite_slug,
				),
			)
		);
		register_taxonomy(
			$this->taxonomy,
			$this->post_type,
			array(
				'public'  => true,
				'rewrite' => array(
					'slug' => cptp_rand_str(),
				),
			)
		);

		$term_obj = $this->create_term_with_posts();

		do_action( 'wp_loaded' );
		/** @var WP_Rewrite $wp_rewrite */
		global $wp_rewrite;
		$wp_rewrite->flush_rules();

		return $term_obj;
	}

	/**
	 * When `has_archive` is a string, the taxonomy rules are under that slug
	 * instead of the rewrite slug, so the term link must use it too.
	 *
	 * @test
	 * @group rewrite
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_term_link_uses_has_archive_slug() {
		$archive_slug = cptp_rand_str();
		$term_obj     = $this->register_post_type_with_archive_slug( $archive_slug, cptp_rand_str() );
		$taxonomy     = get_taxonomy( $this->taxonomy );
		$term_url     = home_url( $archive_slug . '/' . $taxonomy->rewrite['slug'] . '/' . $term_obj->slug );

		$this->assertEquals( $term_url . '/', get_term_link( $term_obj, $this->taxonomy ) );

		$this->go_to( get_term_link( $term_obj, $this->taxonomy ) );
		$this->assertQueryTrue( 'is_archive', 'is_tax' );
		$this->assertSame( $term_obj->term_id, get_queried_object_id() );

		$this->go_to( next_posts( 0, false ) );
		$this->assertQueryTrue( 'is_archive', 'is_tax', 'is_paged' );
	}

	/**
	 * The URL under the `has_archive` slug resolved before the term link
	 * pointed to it, so it must keep resolving.
	 *
	 * @test
	 * @group rewrite
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_term_archive_under_has_archive_slug() {
		$archive_slug = cptp_rand_str();
		$term_obj     = $this->register_post_type_with_archive_slug( $archive_slug, cptp_rand_str() );
		$taxonomy     = get_taxonomy( $this->taxonomy );

		$this->assert_term_archive_resolves(
			home_url( $archive_slug . '/' . $taxonomy->rewrite['slug'] . '/' . $term_obj->slug ),
			$term_obj
		);
	}

	/**
	 * The term link has the front of the permalink structure before the
	 * `has_archive` slug when the post type is `with_front`, like the rules.
	 *
	 * @test
	 * @group rewrite
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_term_link_uses_has_archive_slug_with_front() {
		/** @var WP_Rewrite $wp_rewrite */
		global $wp_rewrite;
		$front = cptp_rand_str();
		$wp_rewrite->set_permalink_structure( '/' . $front . '/%postname%/' );

		$archive_slug = cptp_rand_str();
		$term_obj     = $this->register_post_type_with_archive_slug( $archive_slug, cptp_rand_str() );
		$taxonomy     = get_taxonomy( $this->taxonomy );
		$term_url     = home_url( $front . '/' . $archive_slug . '/' . $taxonomy->rewrite['slug'] . '/' . $term_obj->slug );

		$this->assertEquals( $term_url . '/', get_term_link( $term_obj, $this->taxonomy ) );

		$this->go_to( get_term_link( $term_obj, $this->taxonomy ) );
		$this->assertQueryTrue( 'is_archive', 'is_tax' );
		$this->assertSame( $term_obj->term_id, get_queried_object_id() );
	}

	/**
	 * A child term of a hierarchical taxonomy must resolve under the `has_archive` slug.
	 *
	 * @test
	 * @group rewrite
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_child_term_link_uses_has_archive_slug() {
		$archive_slug = cptp_rand_str();

		register_post_type(
			$this->post_type,
			array(
				'public'      => true,
				'has_archive' => $archive_slug,
				'rewrite'     => array(
					'slug' => cptp_rand_str(),
				),
			)
		);
		register_taxonomy(
			$this->taxonomy,
			$this->post_type,
			array(
				'public'       => true,
				'hierarchical' => true,
				'rewrite'      => array(
					'slug'         => cptp_rand_str(),
					'hierarchical' => true,
				),
			)
		);
		$taxonomy = get_taxonomy( $this->taxonomy );

		$parent_id = $this->factory->term->create( array( 'taxonomy' => $this->taxonomy ) );
		$child_id  = $this->factory->term->create(
			array(
				'taxonomy' => $this->taxonomy,
				'parent'   => $parent_id,
			)
		);
		$parent    = get_term( $parent_id, $this->taxonomy );
		$child     = get_term( $child_id, $this->taxonomy );
		$post_id   = $this->factory->post->create( array( 'post_type' => $this->post_type ) );
		wp_set_object_terms( $post_id, $child->term_id, $this->taxonomy );

		do_action( 'wp_loaded' );
		/** @var WP_Rewrite $wp_rewrite */
		global $wp_rewrite;
		$wp_rewrite->flush_rules();

		$term_path = $taxonomy->rewrite['slug'] . '/' . $parent->slug . '/' . $child->slug;
		$this->assertEquals( home_url( $archive_slug . '/' . $term_path . '/' ), get_term_link( $child, $this->taxonomy ) );

		$this->go_to( get_term_link( $child, $this->taxonomy ) );
		$this->assertQueryTrue( 'is_archive', 'is_tax' );
		$this->assertSame( $child->term_id, get_queried_object_id() );
	}

	/**
	 * With `add_post_type_for_tax`, the term link must query the post type.
	 *
	 * @test
	 * @group rewrite
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_cpt_query_term_link_uses_has_archive_slug() {
		update_option( 'add_post_type_for_tax', true );

		$term_obj = $this->register_post_type_with_archive_slug( cptp_rand_str(), cptp_rand_str() );

		$this->go_to( get_term_link( $term_obj, $this->taxonomy ) );
		$this->assertQueryTrue( 'is_post_type_archive', 'is_archive', 'is_tax' );
	}

	/**
	 * The rules of a post type that is registered before the taxonomy must
	 * stay exactly as they were: ten rules under the `has_archive` slug when
	 * it is a string, and under the rewrite slug otherwise.
	 *
	 * @test
	 * @group rewrite
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @dataProvider data_has_archive
	 *
	 * @param bool|string $has_archive `has_archive` of the post type.
	 */
	public function test_taxonomy_rules_are_unchanged( $has_archive ) {
		$rewrite_slug = cptp_rand_str();

		register_post_type(
			$this->post_type,
			array(
				'public'      => true,
				'has_archive' => $has_archive,
				'rewrite'     => array(
					'slug' => $rewrite_slug,
				),
			)
		);
		register_taxonomy(
			$this->taxonomy,
			$this->post_type,
			array(
				'public'  => true,
				'rewrite' => array(
					'slug' => cptp_rand_str(),
				),
			)
		);

		$taxonomy_slug = get_taxonomy( $this->taxonomy )->rewrite['slug'];
		$slug          = is_string( $has_archive ) ? $has_archive : $rewrite_slug;
		$rules         = $this->get_taxonomy_rules_under_post_type();

		$this->assertCount( 10, $rules );
		foreach ( $rules as $regex ) {
			$this->assertStringStartsWith( $slug . '/' . $taxonomy_slug . '/', $regex );
		}
	}

	public function data_has_archive() {
		return array(
			'true'   => array( true ),
			'false'  => array( false ),
			'string' => array( 'cptparchive' ),
		);
	}

	/**
	 * Registers a taxonomy before its post type, whose rewrite slug differs
	 * from the post type name, and a term.
	 *
	 * @param string      $rewrite_slug Rewrite slug of the post type.
	 * @param bool|string $has_archive  `has_archive` of the post type.
	 *
	 * @return WP_Term
	 */
	private function register_taxonomy_before_post_type( $rewrite_slug, $has_archive = true ) {
		register_taxonomy(
			$this->taxonomy,
			$this->post_type,
			array(
				'public'  => true,
				'rewrite' => array(
					'slug' => cptp_rand_str(),
				),
			)
		);
		register_post_type(
			$this->post_type,
			array(
				'public'      => true,
				'has_archive' => $has_archive,
				'rewrite'     => array(
					'slug' => $rewrite_slug,
				),
			)
		);

		$term_obj = $this->create_term_with_posts();

		do_action( 'wp_loaded' );
		/** @var WP_Rewrite $wp_rewrite */
		global $wp_rewrite;
		$wp_rewrite->flush_rules();

		return $term_obj;
	}

	/**
	 * The term link is built with the rewrite slug of the post type, so it must
	 * resolve when the taxonomy is registered before the post type.
	 *
	 * @see https://github.com/torounit/custom-post-type-permalinks/issues/85
	 *
	 * @test
	 * @group rewrite
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_term_archive_with_taxonomy_registered_first() {
		$rewrite_slug = cptp_rand_str();
		$term_obj     = $this->register_taxonomy_before_post_type( $rewrite_slug );
		$taxonomy     = get_taxonomy( $this->taxonomy );
		$term_url     = home_url( $rewrite_slug . '/' . $taxonomy->rewrite['slug'] . '/' . $term_obj->slug );

		$this->assertEquals( $term_url . '/', get_term_link( $term_obj, $this->taxonomy ) );

		$this->go_to( get_term_link( $term_obj, $this->taxonomy ) );
		$this->assertQueryTrue( 'is_archive', 'is_tax' );

		$this->go_to( next_posts( 0, false ) );
		$this->assertQueryTrue( 'is_archive', 'is_tax', 'is_paged' );

		$this->assert_term_archive_resolves( $term_url, $term_obj );
	}

	/**
	 * When the taxonomy is registered before the post type, the term link is
	 * under the `has_archive` slug when it is a string, and must resolve.
	 *
	 * @test
	 * @group rewrite
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_term_archive_with_has_archive_slug_and_taxonomy_registered_first() {
		$archive_slug = cptp_rand_str();
		$term_obj     = $this->register_taxonomy_before_post_type( cptp_rand_str(), $archive_slug );
		$taxonomy     = get_taxonomy( $this->taxonomy );
		$term_url     = home_url( $archive_slug . '/' . $taxonomy->rewrite['slug'] . '/' . $term_obj->slug );

		$this->assertEquals( $term_url . '/', get_term_link( $term_obj, $this->taxonomy ) );
		$this->assert_term_archive_resolves( $term_url, $term_obj );
	}

	/**
	 * When the taxonomy is registered before the post type, the rules are under
	 * the post type name. That URL resolved before the term link did, so it
	 * must keep resolving.
	 *
	 * @test
	 * @group rewrite
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_term_archive_under_post_type_name_with_taxonomy_registered_first() {
		$term_obj = $this->register_taxonomy_before_post_type( cptp_rand_str() );
		$taxonomy = get_taxonomy( $this->taxonomy );

		$this->assert_term_archive_resolves(
			home_url( $this->post_type . '/' . $taxonomy->rewrite['slug'] . '/' . $term_obj->slug ),
			$term_obj
		);
	}

	/**
	 * With `add_post_type_for_tax`, both URLs must query the post type
	 * when the taxonomy is registered before the post type.
	 *
	 * @test
	 * @group rewrite
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_cpt_query_term_archive_with_taxonomy_registered_first() {
		update_option( 'add_post_type_for_tax', true );

		$term_obj  = $this->register_taxonomy_before_post_type( cptp_rand_str() );
		$taxonomy  = get_taxonomy( $this->taxonomy );
		$term_path = $taxonomy->rewrite['slug'] . '/' . $term_obj->slug;

		$this->go_to( get_term_link( $term_obj, $this->taxonomy ) );
		$this->assertQueryTrue( 'is_post_type_archive', 'is_archive', 'is_tax' );

		$this->go_to( home_url( $this->post_type . '/' . $term_path . '/' ) );
		$this->assertQueryTrue( 'is_post_type_archive', 'is_archive', 'is_tax' );
	}

	/**
	 * Resolving the term link must not take the URLs of a post whose slug is
	 * the taxonomy slug, because those URLs resolved before.
	 *
	 * @test
	 * @group rewrite
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_post_urls_under_taxonomy_slug_with_taxonomy_registered_first() {
		$rewrite_slug = cptp_rand_str();
		$term_obj     = $this->register_taxonomy_before_post_type( $rewrite_slug );
		$taxonomy     = get_taxonomy( $this->taxonomy );

		$post_id       = $this->factory->post->create(
			array(
				'post_type'    => $this->post_type,
				'post_name'    => $taxonomy->rewrite['slug'],
				'post_content' => 'one<!--nextpage-->two',
			)
		);
		$attachment_id = $this->factory->attachment->create_object(
			'image.jpg',
			$post_id,
			array(
				'post_mime_type' => 'image/jpeg',
				'post_name'      => cptp_rand_str(),
			)
		);
		$post_url      = home_url( $rewrite_slug . '/' . $taxonomy->rewrite['slug'] );
		$this->assertEquals( $post_url . '/', trailingslashit( get_permalink( $post_id ) ) );

		$this->go_to( $post_url . '/2/' );
		$this->assertQueryTrue( 'is_single', 'is_singular' );
		$this->assertSame( $post_id, get_queried_object_id() );
		$this->assertSame( 2, get_query_var( 'page' ) );

		$this->go_to( $post_url . '/feed/' );
		$this->assertQueryTrue( 'is_single', 'is_singular', 'is_feed', 'is_comment_feed' );
		$this->assertSame( $post_id, get_queried_object_id() );

		$this->go_to( $post_url . '/embed/' );
		$this->assertQueryTrue( 'is_single', 'is_singular', 'is_embed' );
		$this->assertSame( $post_id, get_queried_object_id() );

		$this->go_to( get_permalink( $attachment_id ) );
		$this->assertQueryTrue( 'is_single', 'is_singular', 'is_attachment' );
		$this->assertSame( $attachment_id, get_queried_object_id() );

		$this->assertSame( $attachment_id, url_to_postid( get_permalink( $attachment_id ) ) );
		$this->assertSame( $post_id, url_to_postid( $post_url . '/2/' ) );

		// A URL that is neither a term nor a post is still not found.
		$this->go_to( $post_url . '/' . cptp_rand_str() . '/' );
		$this->assertQueryTrue( 'is_404' );

		// The term archive still resolves.
		$this->go_to( get_term_link( $term_obj, $this->taxonomy ) );
		$this->assertQueryTrue( 'is_archive', 'is_tax' );
	}

	/**
	 * Content that already resolves at the URL of a term link must keep that URL.
	 *
	 * @test
	 * @group rewrite
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_existing_content_wins_over_term_link_with_taxonomy_registered_first() {
		$term_obj = $this->register_taxonomy_before_post_type( cptp_rand_str() );
		$taxonomy = get_taxonomy( $this->taxonomy );

		$post_id       = $this->factory->post->create(
			array(
				'post_type' => $this->post_type,
				'post_name' => $taxonomy->rewrite['slug'],
			)
		);
		$attachment_id = $this->factory->attachment->create_object(
			'image.jpg',
			$post_id,
			array(
				'post_mime_type' => 'image/jpeg',
				'post_name'      => $term_obj->slug,
			)
		);

		// The attachment and the term link have the same URL.
		$this->assertEquals( get_term_link( $term_obj, $this->taxonomy ), get_permalink( $attachment_id ) );

		$this->go_to( get_term_link( $term_obj, $this->taxonomy ) );
		$this->assertQueryTrue( 'is_single', 'is_singular', 'is_attachment' );
		$this->assertSame( $attachment_id, get_queried_object_id() );

		// The term archive is still at the URL under the post type name.
		$this->go_to( home_url( $this->post_type . '/' . $taxonomy->rewrite['slug'] . '/' . $term_obj->slug . '/' ) );
		$this->assertQueryTrue( 'is_archive', 'is_tax' );
	}

	/**
	 * A term whose slug is not ASCII must resolve when the taxonomy is
	 * registered before the post type.
	 *
	 * @test
	 * @group rewrite
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_multibyte_term_archive_with_taxonomy_registered_first() {
		$this->register_taxonomy_before_post_type( cptp_rand_str() );

		$term_id  = $this->factory->term->create(
			array(
				'taxonomy' => $this->taxonomy,
				'name'     => '小説',
			)
		);
		$term_obj = get_term( $term_id, $this->taxonomy );
		$post_id  = $this->factory->post->create( array( 'post_type' => $this->post_type ) );
		wp_set_object_terms( $post_id, $term_obj->slug, $this->taxonomy );

		$this->go_to( get_term_link( $term_obj, $this->taxonomy ) );
		$this->assertQueryTrue( 'is_archive', 'is_tax' );
		$this->assertSame( $term_obj->term_id, get_queried_object_id() );
	}

	/**
	 * The term link has the front of the permalink structure when the post type
	 * is `with_front`, so it must resolve when the taxonomy is registered first.
	 *
	 * @test
	 * @group rewrite
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_term_archive_with_front_and_taxonomy_registered_first() {
		/** @var WP_Rewrite $wp_rewrite */
		global $wp_rewrite;
		$front = cptp_rand_str();
		$wp_rewrite->set_permalink_structure( '/' . $front . '/%postname%/' );

		$taxonomy_slug = cptp_rand_str();
		register_taxonomy(
			$this->taxonomy,
			$this->post_type,
			array(
				'public'  => true,
				'rewrite' => array(
					'slug'       => $taxonomy_slug,
					'with_front' => false,
				),
			)
		);
		register_post_type(
			$this->post_type,
			array(
				'public'      => true,
				'has_archive' => true,
			)
		);

		$term_id  = $this->factory->term->create(
			array(
				'taxonomy' => $this->taxonomy,
			)
		);
		$term_obj = get_term( $term_id, $this->taxonomy );
		$post_id  = $this->factory->post->create( array( 'post_type' => $this->post_type ) );
		wp_set_object_terms( $post_id, $term_obj->slug, $this->taxonomy );

		do_action( 'wp_loaded' );
		$wp_rewrite->flush_rules();

		$term_path = $taxonomy_slug . '/' . $term_obj->slug;
		$this->assertEquals( home_url( $front . '/' . $this->post_type . '/' . $term_path . '/' ), get_term_link( $term_obj, $this->taxonomy ) );

		$this->go_to( get_term_link( $term_obj, $this->taxonomy ) );
		$this->assertQueryTrue( 'is_archive', 'is_tax' );
		$this->assertSame( $term_obj->term_id, get_queried_object_id() );

		// The rules without the front were the only ones before.
		$this->go_to( home_url( $this->post_type . '/' . $term_path . '/' ) );
		$this->assertQueryTrue( 'is_archive', 'is_tax' );
		$this->assertSame( $term_obj->term_id, get_queried_object_id() );
	}

	/**
	 * The term link is resolved without rewrite rules, so the rules that the
	 * plugin adds must be the same as before: only under the post type name.
	 *
	 * @test
	 * @group rewrite
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_taxonomy_rules_are_unchanged_with_taxonomy_registered_first() {
		$this->register_taxonomy_before_post_type( cptp_rand_str() );
		$taxonomy_slug = get_taxonomy( $this->taxonomy )->rewrite['slug'];
		$rules         = $this->get_taxonomy_rules_under_post_type();

		$this->assertCount( 10, $rules );
		foreach ( $rules as $regex ) {
			$this->assertStringStartsWith( $this->post_type . '/' . $taxonomy_slug . '/', $regex );
		}
	}
}
