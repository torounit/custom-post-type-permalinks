<?php
/**
 * Tests for the random content redirects added in WordPress 7.2.
 *
 * WordPress 7.2 redirects `/random/` to a random post. An archive path prefixed
 * with the random base, or an archive URL with the `random` query variable,
 * redirects to a random post from that archive.
 *
 * @package custom-post-type-permalinks
 */

/**
 * @group random
 */
class CPTP_Random_Content_Test extends WP_UnitTestCase {

	/**
	 * Message of the exception thrown in place of the `exit` that follows a redirect.
	 */
	const REDIRECTED = 'CPTP random content redirect';

	public $post_type;
	public $other_post_type;
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

		if ( ! function_exists( 'wp_is_random_content_redirect_enabled' ) ) {
			$this->markTestSkipped( 'Random content redirects require WordPress 7.2.' );
		}

		delete_option( 'no_taxonomy_structure' );
		add_option( 'no_taxonomy_structure', false );

		delete_option( 'category_base' );
		add_option( 'category_base', cptp_rand_str() );
		delete_option( 'tag_base' );
		add_option( 'tag_base', cptp_rand_str() );

		$wp_rewrite->init();
		$wp_rewrite->set_permalink_structure( '/%year%/%monthnum%/%day%/%postname%/' );
		create_initial_taxonomies();

		$this->post_type       = cptp_rand_str();
		$this->other_post_type = cptp_rand_str();
		$this->taxonomy        = cptp_rand_str();

		$this->redirect_location = null;
		add_filter( 'wp_redirect', array( $this, 'capture_redirect' ) );

		// Canonical redirects exit on the action the random redirect is sent on.
		remove_action( 'template_redirect', 'redirect_canonical' );
	}

	public function tear_down() {
		_unregister_post_type( $this->post_type );
		_unregister_post_type( $this->other_post_type );
		_unregister_taxonomy( $this->taxonomy );

		parent::tear_down();
	}

	/**
	 * Records the redirect and ends the request in place of the `exit` that follows it.
	 *
	 * @param string $location Redirect location.
	 *
	 * @throws WP_Tests_Exception Always, caught by request().
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
	private function request( $url ) {
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
	 * the archives of the target author, category, term and year.
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
	private function create_fixture( $post_type_args = array(), $taxonomy_args = array() ) {
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

		// Posts of another post type by the target author in the target category and year.
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
	 * this plugin, and the `random` query variable works on the archives of this plugin.
	 */

	/**
	 * `/random/{post-type-slug}/` redirects to a permalink in the structure of the post type.
	 *
	 * @test
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_random_post_type_url_redirects_to_the_permalink_structure() {
		update_option( $this->post_type . '_structure', '/%year%/%post_id%/' );
		$fixture = $this->create_fixture();
		$this->flush_rules();

		$this->request( $this->random_url( $fixture['slug'] . '/' ) );

		$this->assertNotNull( $this->redirect_location, 'The request did not redirect.' );
		$this->assertMatchesRegularExpression(
			'#^' . preg_quote( home_url( '/' . $fixture['slug'] . '/' ), '#' ) . '201[23]/[0-9]+/$#',
			$this->redirect_location
		);
		$this->assertSame( $this->post_type, get_post_type( url_to_postid( $this->redirect_location ) ) );
		$this->assertRandomPostTypes( array( $this->post_type ) );
	}

	/**
	 * `?random` on the author archive of a post type.
	 *
	 * @test
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_random_query_var_on_author_archive_of_post_type() {
		$fixture = $this->create_fixture();
		$this->flush_rules();

		$this->request( home_url( '/' . $fixture['slug'] . '/author/' . $fixture['author']->user_nicename . '/?random' ) );

		$this->assertRedirectedToOneOf( $fixture['target_ids'] );
		$this->assertRandomPostTypes( array( $this->post_type ) );
	}

	/**
	 * `?random` on the category archive of a post type.
	 *
	 * @test
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_random_query_var_on_category_archive_of_post_type() {
		$fixture = $this->create_fixture();
		$this->flush_rules();

		$this->request( home_url( '/' . $fixture['slug'] . '/' . $fixture['category_base'] . '/' . $fixture['category']->slug . '/?random' ) );

		$this->assertRedirectedToOneOf( $fixture['target_ids'] );
		$this->assertRandomPostTypes( array( $this->post_type ) );
	}

	/**
	 * `?random` on the term archive under the post type slug.
	 *
	 * @test
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_random_query_var_on_term_archive() {
		$fixture = $this->create_fixture();
		$this->flush_rules();

		$this->request( add_query_arg( 'random', '', get_term_link( $fixture['term'] ) ) );

		$this->assertRedirectedToOneOf( $fixture['target_ids'] );
	}

	/**
	 * `?random` on the date archive of a post type.
	 *
	 * @test
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_random_query_var_on_date_archive_of_post_type() {
		$fixture = $this->create_fixture();
		$this->flush_rules();

		$this->request( home_url( '/' . $fixture['slug'] . '/2012/?random' ) );

		$this->assertRedirectedToOneOf( $fixture['target_ids'] );
		$this->assertRandomPostTypes( array( $this->post_type ) );
	}

	/**
	 * Random content redirects only add rules under the random base. Every other
	 * rule, including its position, is the same as without them.
	 *
	 * @test
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_random_rules_do_not_change_the_other_rules() {
		/** @var WP_Rewrite $wp_rewrite */
		global $wp_rewrite;

		$this->create_fixture();
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
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_random_url_of_author_archive_of_post_type() {
		$fixture = $this->create_fixture();
		$this->flush_rules();

		$this->request( $this->random_url( $fixture['slug'] . '/author/' . $fixture['author']->user_nicename . '/' ) );

		$this->assertRedirectedToOneOf( $fixture['target_ids'] );
		$this->assertRandomPostTypes( array( $this->post_type ) );
	}

	/**
	 * `/random/{post-type-slug}/{category-base}/{category}/`.
	 *
	 * @test
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_random_url_of_category_archive_of_post_type() {
		$fixture = $this->create_fixture();
		$this->flush_rules();

		$this->request( $this->random_url( $fixture['slug'] . '/' . $fixture['category_base'] . '/' . $fixture['category']->slug . '/' ) );

		$this->assertRedirectedToOneOf( $fixture['target_ids'] );
		$this->assertRandomPostTypes( array( $this->post_type ) );
	}

	/**
	 * `/random/{post-type-slug}/category/{category}/` when the category base is empty.
	 *
	 * @test
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_random_url_of_category_archive_of_post_type_with_empty_category_base() {
		update_option( 'category_base', '' );
		$fixture = $this->create_fixture();
		$this->flush_rules();

		$this->request( $this->random_url( $fixture['slug'] . '/category/' . $fixture['category']->slug . '/' ) );

		$this->assertRedirectedToOneOf( $fixture['target_ids'] );
		$this->assertRandomPostTypes( array( $this->post_type ) );
	}

	/**
	 * `/random/{post-type-slug}/{taxonomy-slug}/{parent}/{term}/`, the term link
	 * prefixed with the random base.
	 *
	 * @test
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_random_url_of_term_archive() {
		$fixture = $this->create_fixture();
		$this->flush_rules();

		$term_link = get_term_link( $fixture['term'] );
		$this->assertSame( home_url( '/' . $fixture['slug'] . '/' . $fixture['term_path'] ), $term_link );

		$this->request( $this->random_url_of( $term_link ) );

		$this->assertRedirectedToOneOf( $fixture['target_ids'] );
	}

	/**
	 * The random URL of a term archive selects from the post type of the URL when
	 * the term archive does, see the `add_post_type_for_tax` option.
	 *
	 * @test
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_random_url_of_term_archive_with_post_type_query() {
		update_option( 'add_post_type_for_tax', true );
		$fixture = $this->create_fixture();

		// Posts of another post type with the target term.
		register_post_type(
			$this->other_post_type,
			array(
				'public'      => true,
				'has_archive' => true,
			)
		);
		register_taxonomy_for_object_type( $this->taxonomy, $this->other_post_type );
		$other_post_type_ids = $this->factory->post->create_many(
			6,
			array(
				'post_type' => $this->other_post_type,
			)
		);
		foreach ( $other_post_type_ids as $post_id ) {
			wp_set_object_terms( $post_id, array( $fixture['term']->term_id ), $this->taxonomy );
		}
		$this->flush_rules();

		$this->request( $this->random_url_of( get_term_link( $fixture['term'] ) ) );

		$this->assertRedirectedToOneOf( $fixture['target_ids'] );
		$this->assertRandomPostTypes( array( $this->post_type ) );
	}

	/**
	 * Random URLs use the archive slug of the post type without the front of the
	 * permalink structure, as `/random/{post-type-slug}/` of core.
	 *
	 * @test
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_random_url_uses_archive_slug_without_front() {
		/** @var WP_Rewrite $wp_rewrite */
		global $wp_rewrite;

		$wp_rewrite->set_permalink_structure( '/blog/%postname%/' );
		create_initial_taxonomies();

		$archive_slug = cptp_rand_str();
		$fixture      = $this->create_fixture( array( 'has_archive' => $archive_slug ) );
		$this->flush_rules();

		$this->go_to( home_url( '/blog/' . $archive_slug . '/author/' . $fixture['author']->user_nicename . '/' ) );
		$this->assertTrue( is_author() && is_post_type_archive( $this->post_type ), 'The author archive of the post type is not at the expected URL.' );

		$this->request( $this->random_url( $archive_slug . '/' ) );
		$this->assertNotNull( $this->redirect_location, 'The random URL of the post type did not redirect.' );

		$this->request( $this->random_url( $archive_slug . '/author/' . $fixture['author']->user_nicename . '/' ) );
		$this->assertRedirectedToOneOf( $fixture['target_ids'] );

		$this->request( $this->random_url( $archive_slug . '/' . $fixture['category_base'] . '/' . $fixture['category']->slug . '/' ) );
		$this->assertRedirectedToOneOf( $fixture['target_ids'] );

		$this->go_to( home_url( '/blog/' . $archive_slug . '/' . $fixture['term_path'] ) );
		$this->assertTrue( is_tax( $this->taxonomy ), 'The term archive is not at the expected URL.' );

		$this->request( $this->random_url( $archive_slug . '/' . $fixture['term_path'] ) );
		$this->assertRedirectedToOneOf( $fixture['target_ids'] );
	}

	/**
	 * Random URLs use the random base set on the Permalinks screen.
	 *
	 * @test
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_random_url_uses_custom_random_base() {
		/** @var WP_Rewrite $wp_rewrite */
		global $wp_rewrite;

		$random_base = cptp_rand_str();
		$wp_rewrite->set_random_base( $random_base );
		$fixture = $this->create_fixture();
		$this->flush_rules();

		$this->request( home_url( '/' . $random_base . '/' . $fixture['slug'] . '/author/' . $fixture['author']->user_nicename . '/' ) );
		$this->assertRedirectedToOneOf( $fixture['target_ids'] );

		$this->request( home_url( '/' . $random_base . '/' . $fixture['slug'] . '/' . $fixture['term_path'] ) );
		$this->assertRedirectedToOneOf( $fixture['target_ids'] );
	}

	/**
	 * No random URL for an archive that is disabled.
	 *
	 * @test
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_no_random_url_of_disabled_author_archive() {
		$fixture = $this->create_fixture(
			array(
				'cptp' => array(
					'author_archive' => false,
				),
			)
		);
		$this->flush_rules();

		$this->request( $this->random_url( $fixture['slug'] . '/author/' . $fixture['author']->user_nicename . '/' ) );
		$this->assertNotRandom();

		// The other archives of the post type keep their random URLs.
		$this->request( $this->random_url( $fixture['slug'] . '/' . $fixture['category_base'] . '/' . $fixture['category']->slug . '/' ) );
		$this->assertRedirectedToOneOf( $fixture['target_ids'] );
	}

	/**
	 * No random URL for term archives under the post type slug when they are disabled.
	 *
	 * @test
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_no_random_url_of_term_archive_with_no_taxonomy_structure() {
		update_option( 'no_taxonomy_structure', true );
		$fixture = $this->create_fixture();
		$this->flush_rules();

		$this->request( $this->random_url( $fixture['slug'] . '/' . $fixture['term_path'] ) );
		$this->assertNotRandom();

		// The archives of the post type keep their random URLs.
		$this->request( $this->random_url( $fixture['slug'] . '/author/' . $fixture['author']->user_nicename . '/' ) );
		$this->assertRedirectedToOneOf( $fixture['target_ids'] );
	}

	/**
	 * No random URL for the archives of a post type that is not randomable.
	 *
	 * @test
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_no_random_url_of_post_type_that_is_not_randomable() {
		/** @var WP_Rewrite $wp_rewrite */
		global $wp_rewrite;

		$fixture = $this->create_fixture( array( 'randomable' => false ) );
		$this->flush_rules();

		$random_base = $wp_rewrite->root . $wp_rewrite->get_random_base() . '/' . $fixture['slug'] . '/';
		foreach ( array_keys( $wp_rewrite->rewrite_rules() ) as $regex ) {
			$this->assertStringStartsNotWith( $random_base, $regex );
		}

		$this->request( $this->random_url( $fixture['slug'] . '/author/' . $fixture['author']->user_nicename . '/' ) );
		$this->assertNotRandom();

		$this->request( $this->random_url( $fixture['slug'] . '/' . $fixture['term_path'] ) );
		$this->assertNotRandom();
	}

	/**
	 * No rule is added when random content redirects are disabled.
	 *
	 * @test
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_no_random_rules_when_random_content_redirects_are_disabled() {
		/** @var WP_Rewrite $wp_rewrite */
		global $wp_rewrite;

		$fixture = $this->create_fixture();
		$this->flush_rules();

		// The rules exist while random content redirects are enabled.
		$this->request( $this->random_url( $fixture['slug'] . '/author/' . $fixture['author']->user_nicename . '/' ) );
		$this->assertRedirectedToOneOf( $fixture['target_ids'] );

		add_filter( 'wp_enable_random_content_redirect', '__return_false' );
		$this->flush_rules();

		$random_base = $wp_rewrite->root . $wp_rewrite->get_random_base() . '/';
		foreach ( array_keys( $wp_rewrite->rewrite_rules() ) as $regex ) {
			$this->assertStringStartsNotWith( $random_base, $regex );
		}

		$this->request( $this->random_url( $fixture['slug'] . '/author/' . $fixture['author']->user_nicename . '/' ) );
		$this->assertNull( $this->redirect_location, 'The request redirected.' );
	}

	/**
	 * A rule added to the top of the rewrite rules keeps its query when it uses the
	 * regex of a random rule of this plugin.
	 *
	 * @test
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_random_rules_do_not_replace_top_rules() {
		/** @var WP_Rewrite $wp_rewrite */
		global $wp_rewrite;

		$this->create_fixture();
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
