<?php

class CPTP_Module_Rewrite_Test extends WP_UnitTestCase {

	/**
	 * Message of the exception thrown in place of the `exit` that follows a redirect.
	 */
	const REDIRECTED = 'CPTP random content redirect';

	public $post_type;
	public $taxonomy;

	/**
	 * Location of the redirect sent by the last request, null if it did not redirect.
	 *
	 * @var string|null
	 */
	private $redirect_location;

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

	/*
	 * Random content redirects, added in WordPress 7.2.
	 *
	 * WordPress 7.2 redirects `/random/` to a random post. An archive path prefixed
	 * with the random base, or an archive URL with the `random` query variable,
	 * redirects to a random post from that archive.
	 */

	/**
	 * Skips the test before WordPress 7.2, and records redirects in place of sending them.
	 */
	private function set_up_random_content() {
		if ( ! function_exists( 'wp_is_random_content_redirect_enabled' ) ) {
			$this->markTestSkipped( 'Random content redirects require WordPress 7.2.' );
		}

		$this->redirect_location = null;
		add_filter( 'wp_redirect', array( $this, 'capture_redirect' ) );

		// Canonical redirects exit on the action the random redirect is sent on.
		remove_action( 'template_redirect', 'redirect_canonical' );
	}

	/**
	 * Records the redirect and ends the request in place of the `exit` that follows it.
	 *
	 * @param string $location Redirect location.
	 *
	 * @throws WP_Tests_Exception Always, caught by go_to_until_redirect().
	 */
	public function capture_redirect( $location ) {
		$this->redirect_location = $location;

		throw new WP_Tests_Exception( self::REDIRECTED );
	}

	/**
	 * Requests a URL up to the action the random redirect is sent on.
	 *
	 * @param string $url URL to request.
	 */
	private function go_to_until_redirect( $url ) {
		$this->redirect_location = null;

		try {
			$this->go_to( $url );
			do_action( 'template_redirect' );
		} catch ( WP_Tests_Exception $e ) {
			if ( self::REDIRECTED !== $e->getMessage() ) {
				throw $e;
			}
		}
	}

	/**
	 * Returns the URL of a path prefixed with the random base.
	 *
	 * @param string $path Archive path, without the home URL.
	 *
	 * @return string
	 */
	private function random_url( $path ) {
		/** @var WP_Rewrite $wp_rewrite */
		global $wp_rewrite;

		return home_url( '/' . $wp_rewrite->get_random_base() . '/' . ltrim( $path, '/' ) );
	}

	/**
	 * Returns the random URL of an archive, the archive path prefixed with the random base.
	 *
	 * @param string $archive_url URL of an archive.
	 *
	 * @return string
	 */
	private function random_url_of( $archive_url ) {
		return $this->random_url( substr( $archive_url, strlen( home_url( '/' ) ) ) );
	}

	private function flush_rules() {
		/** @var WP_Rewrite $wp_rewrite */
		global $wp_rewrite;

		do_action( 'wp_loaded' );
		$wp_rewrite->flush_rules();
	}

	/**
	 * Registers the post type and the taxonomy, and creates posts in and out of
	 * the archives of the target author, category and term.
	 *
	 * @param array $post_type_args Arguments of the post type.
	 * @param array $taxonomy_args  Arguments of the taxonomy.
	 *
	 * @return array {
	 *     @type string  $slug         Slug of the post type.
	 *     @type string  $category_base Category base.
	 *     @type WP_User $author       Target author.
	 *     @type WP_Term $category     Target category.
	 *     @type WP_Term $term         Target term, a child term.
	 *     @type string  $term_path    Path of the target term under the post type slug.
	 *     @type int[]   $target_ids   Posts of the post type in every target archive.
	 * }
	 */
	private function create_random_fixture( $post_type_args = array(), $taxonomy_args = array() ) {
		register_post_type(
			$this->post_type,
			array_merge(
				array(
					'public'      => true,
					'taxonomies'  => array( 'category' ),
					'has_archive' => true,
				),
				$post_type_args
			)
		);
		register_taxonomy(
			$this->taxonomy,
			$this->post_type,
			array_merge(
				array(
					'public'       => true,
					'hierarchical' => true,
					'rewrite'      => array(
						'slug' => cptp_rand_str(),
					),
				),
				$taxonomy_args
			)
		);

		$author_id       = $this->factory->user->create();
		$other_author_id = $this->factory->user->create();
		$category_id     = $this->factory->category->create();
		$other_cat_id    = $this->factory->category->create();
		$parent_term_id  = $this->factory->term->create( array( 'taxonomy' => $this->taxonomy ) );
		$term_id         = $this->factory->term->create(
			array(
				'taxonomy' => $this->taxonomy,
				'parent'   => $parent_term_id,
			)
		);
		$other_term_id   = $this->factory->term->create( array( 'taxonomy' => $this->taxonomy ) );

		$target_ids = $this->factory->post->create_many(
			2,
			array(
				'post_type'   => $this->post_type,
				'post_date'   => '2012-12-12',
				'post_author' => $author_id,
			)
		);
		foreach ( $target_ids as $post_id ) {
			wp_set_post_categories( $post_id, array( $category_id ) );
			wp_set_object_terms( $post_id, array( $term_id ), $this->taxonomy );
		}

		// Posts of the post type in none of the target archives.
		$other_ids = $this->factory->post->create_many(
			6,
			array(
				'post_type'   => $this->post_type,
				'post_date'   => '2013-01-01',
				'post_author' => $other_author_id,
			)
		);
		foreach ( $other_ids as $post_id ) {
			wp_set_post_categories( $post_id, array( $other_cat_id ) );
			wp_set_object_terms( $post_id, array( $other_term_id ), $this->taxonomy );
		}

		// Posts of another post type by the target author in the target category.
		$this->factory->post->create_many(
			6,
			array(
				'post_type'     => 'post',
				'post_date'     => '2012-12-12',
				'post_author'   => $author_id,
				'post_category' => array( $category_id ),
			)
		);

		$post_type_object = get_post_type_object( $this->post_type );

		return array(
			'slug'          => $post_type_object->rewrite['slug'],
			'category_base' => get_option( 'category_base' ),
			'author'        => get_userdata( $author_id ),
			'category'      => get_category( $category_id ),
			'term'          => get_term( $term_id, $this->taxonomy ),
			'term_path'     => get_taxonomy( $this->taxonomy )->rewrite['slug'] . '/' . get_term( $parent_term_id, $this->taxonomy )->slug . '/' . get_term( $term_id, $this->taxonomy )->slug . '/',
			'target_ids'    => $target_ids,
		);
	}

	/**
	 * Asserts that the last request redirected to one of the posts.
	 *
	 * @param int[] $post_ids Post IDs.
	 */
	private function assertRedirectedToOneOf( $post_ids ) {
		$this->assertNotNull( $this->redirect_location, 'The request did not redirect.' );
		$this->assertContains( $this->redirect_location, array_map( 'get_permalink', $post_ids ) );
	}

	/**
	 * Asserts that the last request is not a random content request.
	 */
	private function assertNotRandom() {
		/** @var WP_Query $wp_the_query */
		global $wp_the_query;

		$this->assertNull( $this->redirect_location, 'The request redirected.' );
		$this->assertFalse( wp_is_random_content_query( $wp_the_query ) );
	}

	/**
	 * Asserts the post types the last request selected the random post from.
	 *
	 * @param string[] $post_types Post type names.
	 */
	private function assertRandomPostTypes( $post_types ) {
		/** @var WP_Query $wp_the_query */
		global $wp_the_query;

		$this->assertTrue( wp_is_random_content_query( $wp_the_query ) );
		$this->assertSameSets( $post_types, (array) $wp_the_query->get( 'post_type' ) );
	}

	/*
	 * WordPress 7.2 on its own: the random URLs of core redirect to the permalinks of
	 * this plugin, and the rules outside the random base stay the same.
	 */

	/**
	 * `/random/{post-type-slug}/` redirects to a permalink in the structure of the post type.
	 *
	 * @test
	 * @group rewrite
	 * @group random
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_random_post_type_url_redirects_to_the_permalink_structure() {
		$this->set_up_random_content();

		update_option( $this->post_type . '_structure', '/%year%/%post_id%/' );
		$fixture = $this->create_random_fixture();
		$this->flush_rules();

		$this->go_to_until_redirect( $this->random_url( $fixture['slug'] . '/' ) );

		$this->assertNotNull( $this->redirect_location, 'The request did not redirect.' );
		$this->assertMatchesRegularExpression(
			'#^' . preg_quote( home_url( '/' . $fixture['slug'] . '/' ), '#' ) . '201[23]/[0-9]+/$#',
			$this->redirect_location
		);
		$this->assertSame( $this->post_type, get_post_type( url_to_postid( $this->redirect_location ) ) );
		$this->assertRandomPostTypes( array( $this->post_type ) );
	}

	/**
	 * Random content redirects only add rules under the random base. Every other
	 * rule, including its position, is the same as without them.
	 *
	 * @test
	 * @group rewrite
	 * @group random
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_random_rules_do_not_change_the_other_rules() {
		$this->set_up_random_content();

		/** @var WP_Rewrite $wp_rewrite */
		global $wp_rewrite;

		$this->create_random_fixture();
		$this->flush_rules();

		$rules = $wp_rewrite->rewrite_rules();

		add_filter( 'wp_enable_random_content_redirect', '__return_false' );
		$rules_without_random = $wp_rewrite->rewrite_rules();
		remove_filter( 'wp_enable_random_content_redirect', '__return_false' );

		$random_base = $wp_rewrite->root . $wp_rewrite->get_random_base() . '/';
		$other_rules = array();
		foreach ( $rules as $regex => $query ) {
			if ( 0 !== strpos( $regex, $random_base ) ) {
				$other_rules[ $regex ] = $query;
			}
		}

		$this->assertNotEmpty( $rules_without_random );
		$this->assertGreaterThan( count( $rules_without_random ), count( $rules ) );
		$this->assertSame( $rules_without_random, $other_rules );
	}

	/*
	 * Random URLs of the archives of this plugin: the archive path prefixed with
	 * the random base, as the random URLs of core.
	 */

	/**
	 * `/random/{post-type-slug}/author/{author}/`.
	 *
	 * @test
	 * @group rewrite
	 * @group random
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_random_url_of_author_archive_of_post_type() {
		$this->set_up_random_content();

		$fixture = $this->create_random_fixture();
		$this->flush_rules();

		$this->go_to_until_redirect( $this->random_url( $fixture['slug'] . '/author/' . $fixture['author']->user_nicename . '/' ) );

		$this->assertRedirectedToOneOf( $fixture['target_ids'] );
		$this->assertRandomPostTypes( array( $this->post_type ) );
	}

	/**
	 * `/random/{post-type-slug}/{category-base}/{category}/`.
	 *
	 * @test
	 * @group rewrite
	 * @group random
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_random_url_of_category_archive_of_post_type() {
		$this->set_up_random_content();

		$fixture = $this->create_random_fixture();
		$this->flush_rules();

		$this->go_to_until_redirect( $this->random_url( $fixture['slug'] . '/' . $fixture['category_base'] . '/' . $fixture['category']->slug . '/' ) );

		$this->assertRedirectedToOneOf( $fixture['target_ids'] );
		$this->assertRandomPostTypes( array( $this->post_type ) );
	}

	/**
	 * `/random/{post-type-slug}/category/{category}/` when the category base is empty.
	 *
	 * @test
	 * @group rewrite
	 * @group random
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_random_url_of_category_archive_of_post_type_with_empty_category_base() {
		$this->set_up_random_content();

		update_option( 'category_base', '' );
		$fixture = $this->create_random_fixture();
		$this->flush_rules();

		$this->go_to_until_redirect( $this->random_url( $fixture['slug'] . '/category/' . $fixture['category']->slug . '/' ) );

		$this->assertRedirectedToOneOf( $fixture['target_ids'] );
		$this->assertRandomPostTypes( array( $this->post_type ) );
	}

	/**
	 * `/random/{post-type-slug}/{taxonomy-slug}/{parent}/{term}/`, the term link
	 * prefixed with the random base.
	 *
	 * @test
	 * @group rewrite
	 * @group random
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_random_url_of_term_archive() {
		$this->set_up_random_content();

		$fixture = $this->create_random_fixture();
		$this->flush_rules();

		$term_link = get_term_link( $fixture['term'] );
		$this->assertSame( home_url( '/' . $fixture['slug'] . '/' . $fixture['term_path'] ), $term_link );

		$this->go_to_until_redirect( $this->random_url_of( $term_link ) );

		$this->assertRedirectedToOneOf( $fixture['target_ids'] );
	}

	/**
	 * The random URL of a term archive selects from the post type of the URL when
	 * the term archive does, see the `add_post_type_for_tax` option.
	 *
	 * @test
	 * @group rewrite
	 * @group random
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_random_url_of_term_archive_with_post_type_query() {
		$this->set_up_random_content();

		update_option( 'add_post_type_for_tax', true );
		$fixture = $this->create_random_fixture();

		// Posts of another post type with the target term.
		$other_post_type = cptp_rand_str();
		register_post_type(
			$other_post_type,
			array(
				'public'      => true,
				'has_archive' => true,
			)
		);
		register_taxonomy_for_object_type( $this->taxonomy, $other_post_type );
		$other_post_type_ids = $this->factory->post->create_many(
			6,
			array(
				'post_type' => $other_post_type,
			)
		);
		foreach ( $other_post_type_ids as $post_id ) {
			wp_set_object_terms( $post_id, array( $fixture['term']->term_id ), $this->taxonomy );
		}
		$this->flush_rules();

		$this->go_to_until_redirect( $this->random_url_of( get_term_link( $fixture['term'] ) ) );

		$this->assertRedirectedToOneOf( $fixture['target_ids'] );
		$this->assertRandomPostTypes( array( $this->post_type ) );

		_unregister_post_type( $other_post_type );
	}

	/**
	 * Random URLs use the archive slug of the post type without the front of the
	 * permalink structure, as `/random/{post-type-slug}/` of core.
	 *
	 * @test
	 * @group rewrite
	 * @group random
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_random_url_uses_archive_slug_without_front() {
		$this->set_up_random_content();

		/** @var WP_Rewrite $wp_rewrite */
		global $wp_rewrite;

		$wp_rewrite->set_permalink_structure( '/blog/%postname%/' );
		create_initial_taxonomies();

		$archive_slug = cptp_rand_str();
		$fixture      = $this->create_random_fixture( array( 'has_archive' => $archive_slug ) );
		$this->flush_rules();

		$this->go_to( home_url( '/blog/' . $archive_slug . '/author/' . $fixture['author']->user_nicename . '/' ) );
		$this->assertTrue( is_author() && is_post_type_archive( $this->post_type ), 'The author archive of the post type is not at the expected URL.' );

		$this->go_to_until_redirect( $this->random_url( $archive_slug . '/' ) );
		$this->assertNotNull( $this->redirect_location, 'The random URL of the post type did not redirect.' );

		$this->go_to_until_redirect( $this->random_url( $archive_slug . '/author/' . $fixture['author']->user_nicename . '/' ) );
		$this->assertRedirectedToOneOf( $fixture['target_ids'] );

		$this->go_to_until_redirect( $this->random_url( $archive_slug . '/' . $fixture['category_base'] . '/' . $fixture['category']->slug . '/' ) );
		$this->assertRedirectedToOneOf( $fixture['target_ids'] );

		$this->go_to( home_url( '/blog/' . $archive_slug . '/' . $fixture['term_path'] ) );
		$this->assertTrue( is_tax( $this->taxonomy ), 'The term archive is not at the expected URL.' );

		$this->go_to_until_redirect( $this->random_url( $archive_slug . '/' . $fixture['term_path'] ) );
		$this->assertRedirectedToOneOf( $fixture['target_ids'] );
	}

	/**
	 * No random URL for an archive that is disabled.
	 *
	 * @test
	 * @group rewrite
	 * @group random
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_no_random_url_of_disabled_author_archive() {
		$this->set_up_random_content();

		$fixture = $this->create_random_fixture(
			array(
				'cptp' => array(
					'author_archive' => false,
				),
			)
		);
		$this->flush_rules();

		$this->go_to_until_redirect( $this->random_url( $fixture['slug'] . '/author/' . $fixture['author']->user_nicename . '/' ) );
		$this->assertNotRandom();

		// The other archives of the post type keep their random URLs.
		$this->go_to_until_redirect( $this->random_url( $fixture['slug'] . '/' . $fixture['category_base'] . '/' . $fixture['category']->slug . '/' ) );
		$this->assertRedirectedToOneOf( $fixture['target_ids'] );
	}

	/**
	 * No random URL for term archives under the post type slug when they are disabled.
	 *
	 * @test
	 * @group rewrite
	 * @group random
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_no_random_url_of_term_archive_with_no_taxonomy_structure() {
		$this->set_up_random_content();

		update_option( 'no_taxonomy_structure', true );
		$fixture = $this->create_random_fixture();
		$this->flush_rules();

		$this->go_to_until_redirect( $this->random_url( $fixture['slug'] . '/' . $fixture['term_path'] ) );
		$this->assertNotRandom();

		// The archives of the post type keep their random URLs.
		$this->go_to_until_redirect( $this->random_url( $fixture['slug'] . '/author/' . $fixture['author']->user_nicename . '/' ) );
		$this->assertRedirectedToOneOf( $fixture['target_ids'] );
	}

	/**
	 * No random URL for the archives of a post type that is not randomable.
	 *
	 * @test
	 * @group rewrite
	 * @group random
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_no_random_url_of_post_type_that_is_not_randomable() {
		$this->set_up_random_content();

		/** @var WP_Rewrite $wp_rewrite */
		global $wp_rewrite;

		$fixture = $this->create_random_fixture( array( 'randomable' => false ) );
		$this->flush_rules();

		$random_base = $wp_rewrite->root . $wp_rewrite->get_random_base() . '/' . $fixture['slug'] . '/';
		foreach ( array_keys( $wp_rewrite->rewrite_rules() ) as $regex ) {
			$this->assertStringStartsNotWith( $random_base, $regex );
		}

		$this->go_to_until_redirect( $this->random_url( $fixture['slug'] . '/author/' . $fixture['author']->user_nicename . '/' ) );
		$this->assertNotRandom();

		$this->go_to_until_redirect( $this->random_url( $fixture['slug'] . '/' . $fixture['term_path'] ) );
		$this->assertNotRandom();
	}

	/**
	 * No rule is added when random content redirects are disabled.
	 *
	 * @test
	 * @group rewrite
	 * @group random
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_no_random_rules_when_random_content_redirects_are_disabled() {
		$this->set_up_random_content();

		/** @var WP_Rewrite $wp_rewrite */
		global $wp_rewrite;

		$fixture = $this->create_random_fixture();
		$this->flush_rules();

		// The rules exist while random content redirects are enabled.
		$this->go_to_until_redirect( $this->random_url( $fixture['slug'] . '/author/' . $fixture['author']->user_nicename . '/' ) );
		$this->assertRedirectedToOneOf( $fixture['target_ids'] );

		add_filter( 'wp_enable_random_content_redirect', '__return_false' );
		$this->flush_rules();

		$random_base = $wp_rewrite->root . $wp_rewrite->get_random_base() . '/';
		foreach ( array_keys( $wp_rewrite->rewrite_rules() ) as $regex ) {
			$this->assertStringStartsNotWith( $random_base, $regex );
		}

		$this->go_to_until_redirect( $this->random_url( $fixture['slug'] . '/author/' . $fixture['author']->user_nicename . '/' ) );
		$this->assertNull( $this->redirect_location, 'The request redirected.' );
	}

	/**
	 * A rule added to the top of the rewrite rules keeps its query when it uses the
	 * regex of a random rule of this plugin.
	 *
	 * @test
	 * @group rewrite
	 * @group random
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_random_rules_do_not_replace_top_rules() {
		$this->set_up_random_content();

		/** @var WP_Rewrite $wp_rewrite */
		global $wp_rewrite;

		$this->create_random_fixture();
		$this->flush_rules();

		$core_rules = $wp_rewrite->random_rewrite_rules();
		$cptp_rules = array_diff_key( apply_filters( 'random_rewrite_rules', $core_rules ), $core_rules );
		$this->assertNotEmpty( $cptp_rules, 'No random rule was added.' );

		// The random rules of this plugin follow every rule added to the top.
		$regexes  = array_keys( $wp_rewrite->rewrite_rules() );
		$last_top = max( array_keys( array_intersect( $regexes, array_keys( $wp_rewrite->extra_rules_top ) ) ) );
		foreach ( array_keys( $cptp_rules ) as $regex ) {
			$this->assertGreaterThan( $last_top, array_search( $regex, $regexes, true ) );
		}

		// A top rule using the regex of a random rule keeps its query.
		foreach ( array_keys( $cptp_rules ) as $regex ) {
			add_rewrite_rule( $regex, 'index.php?cptp_top_rule=1', 'top' );
		}
		$rules = $wp_rewrite->rewrite_rules();
		foreach ( array_keys( $cptp_rules ) as $regex ) {
			$this->assertSame( 'index.php?cptp_top_rule=1', $rules[ $regex ] );
		}
	}
}
