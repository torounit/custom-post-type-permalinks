<?php
/**
 * Add Rewrite Rules
 *
 * @package Custom_Post_Type_Permalinks
 */

/**
 * Add CPTP_Module_Rewrite Rules
 *
 * @version 1.0.3
 * @since 0.9.4
 * */
class CPTP_Module_Rewrite extends CPTP_Module {

	/**
	 * Add Actions.
	 */
	public function add_hook() {
		add_action( 'parse_request', array( $this, 'parse_request' ) );
		add_action( 'registered_post_type', array( $this, 'register_post_type_rules' ), 10, 2 );
		add_action( 'registered_taxonomy', array( $this, 'register_taxonomy_rules' ), 10, 3 );
		add_filter( 'random_rewrite_rules', array( $this, 'random_rewrite_rules' ) );
	}

	/**
	 * Register_post_type_rules
	 * add rewrite tag for Custom Post Type.
	 *
	 * @version 1.1
	 * @since 0.9
	 *
	 * @param string       $post_type Post type.
	 * @param WP_Post_Type $args      Arguments used to register the post type.
	 */
	public function register_post_type_rules( $post_type, $args ) {

		/**
		 * WP_Rewrite.
		 *
		 * @var WP_Rewrite $wp_rewrite
		 */
		global $wp_rewrite;

		if ( $args->_builtin ) {
			return;
		}

		if ( false === $args->rewrite ) {
			return;
		}

		if ( ! in_array( $post_type, CPTP_Util::get_post_types(), true ) ) {
			return;
		}

		$permalink = CPTP_Util::get_permalink_structure( $post_type );

		if ( ! $permalink ) {
			$permalink = CPTP_DEFAULT_PERMALINK;
		}

		$slug_placeholder = self::get_slug_placeholder( $post_type );
		$permalink        = $slug_placeholder . $permalink;
		$permalink        = str_replace( '%postname%', '%' . $post_type . '%', $permalink );

		add_rewrite_tag( $slug_placeholder, '(' . $args->rewrite['slug'] . ')', 'post_type=' . $post_type . '&slug=' );

		$taxonomies = CPTP_Util::get_taxonomies( true );
		foreach ( $taxonomies as $taxonomy => $objects ) :
			$wp_rewrite->add_rewrite_tag( "%$taxonomy%", '(.+?)', "$taxonomy=" );
		endforeach;

		$rewrite_args = $args->rewrite;
		if ( ! is_array( $rewrite_args ) ) {
			$rewrite_args = array(
				'with_front' => $args->rewrite,
			);
		}

		$slug = $args->rewrite['slug'];
		if ( $args->has_archive ) {
			if ( is_string( $args->has_archive ) ) {
				$slug = $args->has_archive;
			}

			if ( $args->rewrite['with_front'] ) {
				$slug = substr( $wp_rewrite->front, 1 ) . $slug;
			}

			if ( CPTP_Util::get_post_type_date_archive_support( $post_type ) ) {
				$date_front = CPTP_Util::get_date_front( $post_type );
				add_rewrite_rule( $slug . $date_front . '/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/feed/(feed|rdf|rss|rss2|atom)/?$', 'index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&feed=$matches[4]&post_type=' . $post_type, 'top' );
				add_rewrite_rule( $slug . $date_front . '/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/(feed|rdf|rss|rss2|atom)/?$', 'index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&feed=$matches[4]&post_type=' . $post_type, 'top' );
				add_rewrite_rule( $slug . $date_front . '/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/page/?([0-9]{1,})/?$', 'index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&paged=$matches[4]&post_type=' . $post_type, 'top' );
				add_rewrite_rule( $slug . $date_front . '/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/?$', 'index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&post_type=' . $post_type, 'top' );
				add_rewrite_rule( $slug . $date_front . '/([0-9]{4})/([0-9]{1,2})/feed/(feed|rdf|rss|rss2|atom)/?$', 'index.php?year=$matches[1]&monthnum=$matches[2]&feed=$matches[3]&post_type=' . $post_type, 'top' );
				add_rewrite_rule( $slug . $date_front . '/([0-9]{4})/([0-9]{1,2})/(feed|rdf|rss|rss2|atom)/?$', 'index.php?year=$matches[1]&monthnum=$matches[2]&feed=$matches[3]&post_type=' . $post_type, 'top' );
				add_rewrite_rule( $slug . $date_front . '/([0-9]{4})/([0-9]{1,2})/page/?([0-9]{1,})/?$', 'index.php?year=$matches[1]&monthnum=$matches[2]&paged=$matches[3]&post_type=' . $post_type, 'top' );
				add_rewrite_rule( $slug . $date_front . '/([0-9]{4})/([0-9]{1,2})/?$', 'index.php?year=$matches[1]&monthnum=$matches[2]&post_type=' . $post_type, 'top' );
				add_rewrite_rule( $slug . $date_front . '/([0-9]{4})/feed/(feed|rdf|rss|rss2|atom)/?$', 'index.php?year=$matches[1]&feed=$matches[2]&post_type=' . $post_type, 'top' );
				add_rewrite_rule( $slug . $date_front . '/([0-9]{4})/(feed|rdf|rss|rss2|atom)/?$', 'index.php?year=$matches[1]&feed=$matches[2]&post_type=' . $post_type, 'top' );
				add_rewrite_rule( $slug . $date_front . '/([0-9]{4})/page/?([0-9]{1,})/?$', 'index.php?year=$matches[1]&paged=$matches[2]&post_type=' . $post_type, 'top' );
				add_rewrite_rule( $slug . $date_front . '/([0-9]{4})/?$', 'index.php?year=$matches[1]&post_type=' . $post_type, 'top' );
			}

			if ( CPTP_Util::get_post_type_author_archive_support( $post_type ) ) {
				add_rewrite_rule( $slug . '/author/([^/]+)/page/?([0-9]{1,})/?$', 'index.php?author_name=$matches[1]&paged=$matches[2]&post_type=' . $post_type, 'top' );
				add_rewrite_rule( $slug . '/author/([^/]+)/?$', 'index.php?author_name=$matches[1]&post_type=' . $post_type, 'top' );
			}

			if ( in_array( 'category', (array) $args->taxonomies, true ) ) {
				$category_base = get_option( 'category_base' );
				if ( ! $category_base ) {
					$category_base = 'category';
				}

				add_rewrite_rule( $slug . '/' . $category_base . '/([^/]+)/page/?([0-9]{1,})/?$', 'index.php?category_name=$matches[1]&paged=$matches[2]&post_type=' . $post_type, 'top' );
				add_rewrite_rule( $slug . '/' . $category_base . '/([^/]+)/?$', 'index.php?category_name=$matches[1]&post_type=' . $post_type, 'top' );
			}

			do_action( 'CPTP_registered_' . $post_type . '_rules', $args, $slug );
		}

		$rewrite_args['walk_dirs'] = false;
		add_permastruct( $post_type, $permalink, $rewrite_args );
	}


	/**
	 * Register_taxonomy_rules
	 *
	 * @param string       $taxonomy        Taxonomy slug.
	 * @param array|string $object_type     Object type or array of object types.
	 * @param array        $args            Array of taxonomy registration arguments.
	 *
	 * @return void
	 */
	public function register_taxonomy_rules( $taxonomy, $object_type, $args ) {
		global $wp_rewrite;

		/* for 4.7 */
		$args = (array) $args;

		if ( CPTP_Util::get_no_taxonomy_structure() ) {
			return;
		}

		if ( ! empty( $args['_builtin'] ) ) {
			return;
		}

		if ( false === $args['rewrite'] ) {
			return;
		}

		$post_types = $args['object_type'];
		foreach ( $post_types as $post_type ) :
			$post_type_obj = get_post_type_object( $post_type );
			if ( ! empty( $post_type_obj->rewrite['slug'] ) ) {
				$slug = $post_type_obj->rewrite['slug'];
			} else {
				$slug = $post_type;
			}

			if ( ! empty( $post_type_obj->has_archive ) && is_string( $post_type_obj->has_archive ) ) {
				$slug = $post_type_obj->has_archive;
			}

			if ( ! empty( $post_type_obj->rewrite['with_front'] ) ) {
				$slug = substr( $wp_rewrite->front, 1 ) . $slug;
			}

			if ( 'category' === $taxonomy ) {
				$cb            = get_option( 'category_base' );
				$taxonomy_slug = ( $cb ) ? $cb : $taxonomy;
				$taxonomy_key  = 'category_name';
			} else {
				// Edit by [Xiphe].
				if ( isset( $args['rewrite']['slug'] ) ) {
					$taxonomy_slug = $args['rewrite']['slug'];
				} else {
					$taxonomy_slug = $taxonomy;
				}
				// [Xiphe] stop
				$taxonomy_key = $taxonomy;
			}

			$rules = array(
				// feed.
				array(
					'regex'    => '%s/(.+?)/feed/(feed|rdf|rss|rss2|atom)/?$',
					'redirect' => "index.php?{$taxonomy_key}=\$matches[1]&feed=\$matches[2]",
				),
				array(
					'regex'    => '%s/(.+?)/(feed|rdf|rss|rss2|atom)/?$',
					'redirect' => "index.php?{$taxonomy_key}=\$matches[1]&feed=\$matches[2]",
				),
				// year.
				array(
					'regex'    => '%s/(.+?)/date/([0-9]{4})/?$',
					'redirect' => "index.php?{$taxonomy_key}=\$matches[1]&year=\$matches[2]",
				),
				array(
					'regex'    => '%s/(.+?)/date/([0-9]{4})/page/?([0-9]{1,})/?$',
					'redirect' => "index.php?{$taxonomy_key}=\$matches[1]&year=\$matches[2]&paged=\$matches[3]",
				),
				// monthnum.
				array(
					'regex'    => '%s/(.+?)/date/([0-9]{4})/([0-9]{1,2})/?$',
					'redirect' => "index.php?{$taxonomy_key}=\$matches[1]&year=\$matches[2]&monthnum=\$matches[3]",
				),
				array(
					'regex'    => '%s/(.+?)/date/([0-9]{4})/([0-9]{1,2})/page/?([0-9]{1,})/?$',
					'redirect' => "index.php?{$taxonomy_key}=\$matches[1]&year=\$matches[2]&monthnum=\$matches[3]&paged=\$matches[4]",
				),
				// day.
				array(
					'regex'    => '%s/(.+?)/date/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/?$',
					'redirect' => "index.php?{$taxonomy_key}=\$matches[1]&year=\$matches[2]&monthnum=\$matches[3]&day=\$matches[4]",
				),
				array(
					'regex'    => '%s/(.+?)/date/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/page/?([0-9]{1,})/?$',
					'redirect' => "index.php?{$taxonomy_key}=\$matches[1]&year=\$matches[2]&monthnum=\$matches[3]&day=\$matches[4]&paged=\$matches[5]",
				),
				// paging.
				array(
					'regex'    => '%s/(.+?)/page/?([0-9]{1,})/?$',
					'redirect' => "index.php?{$taxonomy_key}=\$matches[1]&paged=\$matches[2]",
				),
				// tax archive.
				array(
					'regex'    => '%s/(.+?)/?$',
					'redirect' => "index.php?{$taxonomy_key}=\$matches[1]",
				),
			);

			// no post_type slug.
			foreach ( $rules as $rule ) {
				$regex    = sprintf( $rule['regex'], "{$taxonomy_slug}" );
				$redirect = $rule['redirect'];
				add_rewrite_rule( $regex, $redirect, 'top' );
			}

			if ( get_option( 'add_post_type_for_tax' ) ) {
				foreach ( $rules as $rule ) {
					$regex    = sprintf( $rule['regex'], "{$slug}/{$taxonomy_slug}" );
					$redirect = $rule['redirect'] . "&post_type={$post_type}";
					add_rewrite_rule( $regex, $redirect, 'top' );
				}
			} else {
				foreach ( $rules as $rule ) {
					$regex    = sprintf( $rule['regex'], "{$slug}/{$taxonomy_slug}" );
					$redirect = $rule['redirect'];
					add_rewrite_rule( $regex, $redirect, 'top' );
				}
			}

			do_action( 'CPTP_registered_' . $taxonomy . '_rules', $object_type, $args, $taxonomy_slug );
		endforeach;
	}


	/**
	 * Add the random content rules of the archives under the post type slug.
	 *
	 * WordPress 7.2 redirects an archive path prefixed with the random base to a
	 * random post from that archive, for example `/random/category/news/`. This adds
	 * the same for the author, category and term archives of this plugin:
	 *
	 * - `/random/{post-type-slug}/author/{author}/`
	 * - `/random/{post-type-slug}/{category-base}/{category}/`
	 * - `/random/{post-type-slug}/{taxonomy-slug}/{term}/`
	 *
	 * As in core, the post type slug has no front, date archives have no rule and
	 * only randomable post types receive rules. The `random` query variable works
	 * on the archives without a rule, for example `/{post-type-slug}/2026/?random`.
	 *
	 * The rules are under the random base only and follow every rule added to the
	 * top, so the rules of post types and taxonomies keep precedence over them.
	 *
	 * @param array<string, string> $rules Random content rules, keyed by their regex.
	 *
	 * @return array<string, string>
	 */
	public function random_rewrite_rules( $rules ) {
		/**
		 * WP_Rewrite.
		 *
		 * @var WP_Rewrite $wp_rewrite
		 */
		global $wp_rewrite;

		if ( ! function_exists( 'wp_is_random_content_redirect_enabled' ) || ! wp_is_random_content_redirect_enabled() ) {
			return $rules;
		}

		$random_base = $wp_rewrite->root . $wp_rewrite->get_random_base();
		$post_types  = get_post_types( array( 'randomable' => true ), 'objects' );
		$cptp_rules  = array();

		// Author and category archives, see register_post_type_rules().
		foreach ( array_intersect_key( $post_types, CPTP_Util::get_post_types() ) as $post_type => $post_type_obj ) {
			if ( ! $post_type_obj->has_archive ) {
				continue;
			}

			$base  = $random_base . '/' . self::get_archive_slug( $post_type_obj );
			$query = $wp_rewrite->preg_index( 1 ) . '&post_type=' . $post_type . '&random=1';

			if ( CPTP_Util::get_post_type_author_archive_support( $post_type_obj ) ) {
				$cptp_rules[ $base . '/author/([^/]+)/?$' ] = $wp_rewrite->index . '?author_name=' . $query;
			}

			if ( in_array( 'category', (array) $post_type_obj->taxonomies, true ) ) {
				$category_base = get_option( 'category_base' );
				if ( ! $category_base ) {
					$category_base = 'category';
				}

				$cptp_rules[ $base . '/' . $category_base . '/([^/]+)/?$' ] = $wp_rewrite->index . '?category_name=' . $query;
			}
		}

		// Term archives, see register_taxonomy_rules().
		$taxonomies = array();
		if ( ! CPTP_Util::get_no_taxonomy_structure() ) {
			$taxonomies = get_taxonomies(
				array(
					'_builtin'           => false,
					'publicly_queryable' => true,
				),
				'objects'
			);
		}

		foreach ( $taxonomies as $taxonomy => $taxonomy_obj ) {
			if ( false === $taxonomy_obj->rewrite ) {
				continue;
			}

			$taxonomy_slug = isset( $taxonomy_obj->rewrite['slug'] ) ? $taxonomy_obj->rewrite['slug'] : $taxonomy;

			foreach ( array_intersect_key( $post_types, array_flip( $taxonomy_obj->object_type ) ) as $post_type => $post_type_obj ) {
				$query = $wp_rewrite->preg_index( 1 );
				if ( get_option( 'add_post_type_for_tax' ) ) {
					$query .= '&post_type=' . $post_type;
				}

				$regex                = $random_base . '/' . self::get_archive_slug( $post_type_obj ) . '/' . $taxonomy_slug . '/(.+?)/?$';
				$cptp_rules[ $regex ] = $wp_rewrite->index . '?' . $taxonomy . '=' . $query . '&random=1';
			}
		}

		// Rules added to the top and the random rules of core keep their query.
		$cptp_rules = array_diff_key( $cptp_rules, $wp_rewrite->extra_rules_top, $rules );

		return array_merge( $cptp_rules, $rules );
	}

	/**
	 * Returns the slug of the archives of a post type, without the front.
	 *
	 * @param WP_Post_Type $post_type_obj Post type object.
	 *
	 * @return string
	 */
	private static function get_archive_slug( $post_type_obj ) {
		if ( is_string( $post_type_obj->has_archive ) ) {
			return $post_type_obj->has_archive;
		}

		if ( ! empty( $post_type_obj->rewrite['slug'] ) ) {
			return $post_type_obj->rewrite['slug'];
		}

		return $post_type_obj->name;
	}

	/**
	 *
	 * Fix taxonomy = parent/child => taxonomy => child
	 *
	 * @since 0.9.3
	 *
	 * @param WP $obj WP instance.
	 */
	public function parse_request( $obj ) {
		$taxes = CPTP_Util::get_taxonomies( true );
		foreach ( $taxes as $key => $tax ) {
			$name = $tax->name;
			if ( $tax->hierarchical ) {
				if ( isset( $obj->query_vars[ $name ] ) && is_string( $obj->query_vars[ $name ] ) ) {
					if ( false !== strpos( $obj->query_vars[ $name ], '/' ) ) {
						$query_vars = explode( '/', $obj->query_vars[ $name ] );
						if ( is_array( $query_vars ) ) {
							$obj->query_vars[ $name ] = array_pop( $query_vars );
						}
					}
				}
			}
		}
	}

	/**
	 * Returns the slug placeholder user in the permalink structure.
	 *
	 * @param string $post_type The post type.
	 *
	 * @return string
	 */
	public static function get_slug_placeholder( $post_type ) {
		return '%' . $post_type . '_slug%';
	}
}
