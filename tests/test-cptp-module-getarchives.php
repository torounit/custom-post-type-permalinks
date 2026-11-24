<?php

class CPTP_Module_GetArchives_Test extends WP_UnitTestCase {

	public $post_type;
	public $taxonomy;

	public function set_up() {
		/** @var WP_Rewrite $wp_rewrite */
		global $wp_rewrite;

		parent::set_up();

		delete_option( 'no_taxonomy_structure' );
		add_option( 'no_taxonomy_structure', false );

		$wp_rewrite->init();
		$wp_rewrite->set_permalink_structure( '/%postname%/' );
		create_initial_taxonomies();

		$this->post_type = cptp_rand_str();
		$this->taxonomy  = cptp_rand_str();
	}

	public function tear_down() {
		_unregister_post_type( $this->post_type );
		_unregister_taxonomy( $this->taxonomy, $this->post_type );
	}

	/**
	 * Registers the test post type with posts in December 2012, and flushes the rules.
	 *
	 * CPTP_Module_GetArchives::add_hook() only adds its filters when a permalink
	 * structure is set. The plugin is loaded before set_up() sets one, so the
	 * filters are added here.
	 *
	 * @param array $args Arguments for register_post_type().
	 */
	private function register_post_type_with_posts( $args ) {
		CPTP::get_instance()->modules['get_archives']->add_hook();

		register_post_type(
			$this->post_type,
			array_merge(
				array(
					'public' => true,
				),
				$args
			)
		);

		$this->factory->post->create_many(
			2,
			array(
				'post_type' => $this->post_type,
				'post_date' => '2012-12-12',
			)
		);

		do_action( 'wp_loaded' );
		/** @var WP_Rewrite $wp_rewrite */
		global $wp_rewrite;
		$wp_rewrite->flush_rules();
	}

	/**
	 * Returns the URLs of the links that wp_get_archives() outputs for the test post type.
	 *
	 * @param string $type Type of archive: yearly, monthly or daily.
	 * @param array  $args More arguments for wp_get_archives().
	 *
	 * @return string[]
	 */
	private function get_archive_urls( $type, $args = array() ) {
		$html = wp_get_archives(
			array_merge(
				array(
					'post_type' => $this->post_type,
					'type'      => $type,
					'echo'      => false,
				),
				$args
			)
		);

		preg_match_all( '/href=([\'"])(.*?)\1/', $html, $matches );

		return $matches[2];
	}

	/**
	 * Asserts that a URL is a date archive of the test post type with posts.
	 *
	 * @param string $url     URL.
	 * @param string $is_date Conditional tag of the date archive: is_year, is_month or is_day.
	 */
	private function assert_post_type_date_archive( $url, $is_date ) {
		$this->go_to( $url );
		$this->assertQueryTrue( 'is_archive', 'is_post_type_archive', 'is_date', $is_date );
		$this->assertSame( $this->post_type, get_query_var( 'post_type' ) );
		$this->assertTrue( have_posts() );
	}

	public function data_archive_types() {
		return array(
			'yearly'  => array( 'yearly', '/2012/', 'is_year' ),
			'monthly' => array( 'monthly', '/2012/12/', 'is_month' ),
			'daily'   => array( 'daily', '/2012/12/12/', 'is_day' ),
		);
	}

	/**
	 * When `has_archive` is a string, the date archive rules are under that slug
	 * instead of the rewrite slug, so the links of wp_get_archives() must be too.
	 *
	 * @test
	 * @group get_archives
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @dataProvider data_archive_types
	 *
	 * @param string $type    Type of archive.
	 * @param string $date    Date part of the link.
	 * @param string $is_date Conditional tag of the date archive.
	 */
	public function test_links_use_has_archive_slug( $type, $date, $is_date ) {
		$archive_slug = cptp_rand_str();
		$this->register_post_type_with_posts(
			array(
				'has_archive' => $archive_slug,
				'rewrite'     => array(
					'slug' => cptp_rand_str(),
				),
			)
		);

		$urls = $this->get_archive_urls( $type );

		$this->assertSame( array( home_url( $archive_slug . $date ) ), $urls );
		$this->assert_post_type_date_archive( $urls[0], $is_date );
	}

	/**
	 * The links have the front of the permalink structure before the
	 * `has_archive` slug when the post type is `with_front`, like the rules.
	 *
	 * @test
	 * @group get_archives
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @dataProvider data_archive_types
	 *
	 * @param string $type    Type of archive.
	 * @param string $date    Date part of the link.
	 * @param string $is_date Conditional tag of the date archive.
	 */
	public function test_links_use_has_archive_slug_with_front( $type, $date, $is_date ) {
		/** @var WP_Rewrite $wp_rewrite */
		global $wp_rewrite;
		$front = cptp_rand_str();
		$wp_rewrite->set_permalink_structure( '/' . $front . '/%postname%/' );

		$archive_slug = cptp_rand_str();
		$this->register_post_type_with_posts(
			array(
				'has_archive' => $archive_slug,
				'rewrite'     => array(
					'slug' => cptp_rand_str(),
				),
			)
		);

		$urls = $this->get_archive_urls( $type );

		$this->assertSame( array( home_url( $front . '/' . $archive_slug . $date ) ), $urls );
		$this->assert_post_type_date_archive( $urls[0], $is_date );
	}

	/**
	 * The links have the date front of the post type after the `has_archive` slug, like the rules.
	 *
	 * @test
	 * @group get_archives
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @dataProvider data_archive_types
	 *
	 * @param string $type    Type of archive.
	 * @param string $date    Date part of the link.
	 * @param string $is_date Conditional tag of the date archive.
	 */
	public function test_links_use_has_archive_slug_with_date_front( $type, $date, $is_date ) {
		update_option( $this->post_type . '_structure', '/%post_id%/' );

		$archive_slug = cptp_rand_str();
		$this->register_post_type_with_posts(
			array(
				'has_archive' => $archive_slug,
				'rewrite'     => array(
					'slug' => cptp_rand_str(),
				),
			)
		);

		$urls = $this->get_archive_urls( $type );

		$this->assertSame( array( home_url( $archive_slug . '/date' . $date ) ), $urls );
		$this->assert_post_type_date_archive( $urls[0], $is_date );
	}

	/**
	 * The weekly links are the post type archive with a query string, so they
	 * must be under the `has_archive` slug too.
	 *
	 * @test
	 * @group get_archives
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_weekly_links_use_has_archive_slug() {
		$archive_slug = cptp_rand_str();
		$this->register_post_type_with_posts(
			array(
				'has_archive' => $archive_slug,
				'rewrite'     => array(
					'slug' => cptp_rand_str(),
				),
			)
		);

		$urls = $this->get_archive_urls( 'weekly' );

		$this->assertCount( 1, $urls );
		$this->assertStringStartsWith( home_url( $archive_slug . '/?m=2012' ), $urls[0] );

		$this->go_to( html_entity_decode( $urls[0] ) );
		$this->assertQueryTrue( 'is_archive', 'is_post_type_archive', 'is_date', 'is_year' );
		$this->assertSame( $this->post_type, get_query_var( 'post_type' ) );
		$this->assertTrue( have_posts() );
	}

	/**
	 * Registers the test post type with a date front, a taxonomy and a term that the posts have.
	 *
	 * The taxonomy rules for a date archive have `/date/` before the date, so
	 * the links of a term only match them when the post type has a date front.
	 *
	 * @param bool|string $has_archive  `has_archive` of the post type.
	 * @param string      $rewrite_slug Rewrite slug of the post type.
	 *
	 * @return array The `taxonomy` argument for wp_get_archives().
	 */
	private function register_post_type_with_term( $has_archive, $rewrite_slug ) {
		update_option( $this->post_type . '_structure', '/%post_id%/' );

		$this->register_post_type_with_posts(
			array(
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
				'public' => true,
			)
		);

		$term_id  = $this->factory->term->create(
			array(
				'taxonomy' => $this->taxonomy,
			)
		);
		$term_obj = get_term( $term_id, $this->taxonomy );
		$post_ids = get_posts(
			array(
				'post_type' => $this->post_type,
				'fields'    => 'ids',
			)
		);
		foreach ( $post_ids as $post_id ) {
			wp_set_object_terms( $post_id, $term_obj->slug, $this->taxonomy );
		}

		/** @var WP_Rewrite $wp_rewrite */
		global $wp_rewrite;
		$wp_rewrite->flush_rules();

		return array(
			'name'     => $this->taxonomy,
			'termid'   => $term_obj->term_id,
			'termslug' => $term_obj->slug,
		);
	}

	/**
	 * Asserts that a URL is a date archive of a term with posts.
	 *
	 * @param string $url     URL.
	 * @param string $is_date Conditional tag of the date archive: is_year, is_month or is_day.
	 * @param int    $term_id Term ID.
	 */
	private function assert_term_date_archive( $url, $is_date, $term_id ) {
		$this->go_to( $url );
		$this->assertQueryTrue( 'is_archive', 'is_tax', 'is_date', $is_date );
		$this->assertSame( $term_id, get_queried_object_id() );
		$this->assertTrue( have_posts() );
	}

	/**
	 * With the `taxonomy` argument, the links are the date archive of a term.
	 * The taxonomy rules are under the `has_archive` slug when it is a string,
	 * so the links must be too.
	 *
	 * @test
	 * @group get_archives
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @dataProvider data_archive_types
	 *
	 * @param string $type    Type of archive.
	 * @param string $date    Date part of the link.
	 * @param string $is_date Conditional tag of the date archive.
	 */
	public function test_term_links_use_has_archive_slug( $type, $date, $is_date ) {
		$archive_slug = cptp_rand_str();
		$taxonomy     = $this->register_post_type_with_term( $archive_slug, cptp_rand_str() );

		$urls = $this->get_archive_urls( $type, array( 'taxonomy' => $taxonomy ) );

		$this->assertSame(
			array( home_url( $archive_slug . '/' . $taxonomy['name'] . '/' . $taxonomy['termslug'] . '/date' . $date ) ),
			$urls
		);
		$this->assert_term_date_archive( $urls[0], $is_date, $taxonomy['termid'] );
	}

	/**
	 * With the `taxonomy` argument, the links of a post type whose `has_archive`
	 * is `true` must stay exactly as they were.
	 *
	 * @test
	 * @group get_archives
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @dataProvider data_archive_types
	 *
	 * @param string $type    Type of archive.
	 * @param string $date    Date part of the link.
	 * @param string $is_date Conditional tag of the date archive.
	 */
	public function test_term_links_are_unchanged_when_has_archive_is_true( $type, $date, $is_date ) {
		$rewrite_slug = cptp_rand_str();
		$taxonomy     = $this->register_post_type_with_term( true, $rewrite_slug );

		$urls = $this->get_archive_urls( $type, array( 'taxonomy' => $taxonomy ) );

		$this->assertSame(
			array( home_url( $rewrite_slug . '/' . $taxonomy['name'] . '/' . $taxonomy['termslug'] . '/date' . $date ) ),
			$urls
		);
		$this->assert_term_date_archive( $urls[0], $is_date, $taxonomy['termid'] );
	}

	/**
	 * The weekly links of a post type whose `has_archive` is `true` must stay as they were.
	 *
	 * @test
	 * @group get_archives
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_weekly_links_are_unchanged_when_has_archive_is_true() {
		$rewrite_slug = cptp_rand_str();
		$this->register_post_type_with_posts(
			array(
				'has_archive' => true,
				'rewrite'     => array(
					'slug' => $rewrite_slug,
				),
			)
		);

		$urls = $this->get_archive_urls( 'weekly' );

		$this->assertCount( 1, $urls );
		$this->assertStringStartsWith( home_url( $rewrite_slug . '/?m=2012' ), $urls[0] );

		$this->go_to( html_entity_decode( $urls[0] ) );
		$this->assertQueryTrue( 'is_archive', 'is_post_type_archive', 'is_date', 'is_year' );
	}

	/**
	 * The links of a post type whose `has_archive` is `true` are under the
	 * rewrite slug, and must stay exactly as they were.
	 *
	 * @test
	 * @group get_archives
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @dataProvider data_archive_types
	 *
	 * @param string $type    Type of archive.
	 * @param string $date    Date part of the link.
	 * @param string $is_date Conditional tag of the date archive.
	 */
	public function test_links_are_unchanged_when_has_archive_is_true( $type, $date, $is_date ) {
		$rewrite_slug = cptp_rand_str();
		$this->register_post_type_with_posts(
			array(
				'has_archive' => true,
				'rewrite'     => array(
					'slug' => $rewrite_slug,
				),
			)
		);

		$urls = $this->get_archive_urls( $type );

		$this->assertSame( array( home_url( $rewrite_slug . $date ) ), $urls );
		$this->assert_post_type_date_archive( $urls[0], $is_date );
	}

	/**
	 * Without a rewrite slug, the links are under the post type name.
	 *
	 * @test
	 * @group get_archives
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @dataProvider data_archive_types
	 *
	 * @param string $type    Type of archive.
	 * @param string $date    Date part of the link.
	 * @param string $is_date Conditional tag of the date archive.
	 */
	public function test_links_are_unchanged_without_rewrite_slug( $type, $date, $is_date ) {
		$this->register_post_type_with_posts(
			array(
				'has_archive' => true,
			)
		);

		$urls = $this->get_archive_urls( $type );

		$this->assertSame( array( home_url( $this->post_type . $date ) ), $urls );
		$this->assert_post_type_date_archive( $urls[0], $is_date );
	}

	/**
	 * The links are unchanged when `has_archive` is the same string as the rewrite slug.
	 *
	 * @test
	 * @group get_archives
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @dataProvider data_archive_types
	 *
	 * @param string $type    Type of archive.
	 * @param string $date    Date part of the link.
	 * @param string $is_date Conditional tag of the date archive.
	 */
	public function test_links_are_unchanged_when_has_archive_is_the_rewrite_slug( $type, $date, $is_date ) {
		$slug = cptp_rand_str();
		$this->register_post_type_with_posts(
			array(
				'has_archive' => $slug,
				'rewrite'     => array(
					'slug' => $slug,
				),
			)
		);

		$urls = $this->get_archive_urls( $type );

		$this->assertSame( array( home_url( $slug . $date ) ), $urls );
		$this->assert_post_type_date_archive( $urls[0], $is_date );
	}

	/**
	 * With `with_front` and `has_archive` set to `true`, the links must stay as they were.
	 *
	 * @test
	 * @group get_archives
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @dataProvider data_archive_types
	 *
	 * @param string $type    Type of archive.
	 * @param string $date    Date part of the link.
	 * @param string $is_date Conditional tag of the date archive.
	 */
	public function test_links_are_unchanged_with_front_when_has_archive_is_true( $type, $date, $is_date ) {
		/** @var WP_Rewrite $wp_rewrite */
		global $wp_rewrite;
		$front = cptp_rand_str();
		$wp_rewrite->set_permalink_structure( '/' . $front . '/%postname%/' );

		$rewrite_slug = cptp_rand_str();
		$this->register_post_type_with_posts(
			array(
				'has_archive' => true,
				'rewrite'     => array(
					'slug' => $rewrite_slug,
				),
			)
		);

		$urls = $this->get_archive_urls( $type );

		$this->assertSame( array( home_url( $front . '/' . $rewrite_slug . $date ) ), $urls );
		$this->assert_post_type_date_archive( $urls[0], $is_date );
	}
}
