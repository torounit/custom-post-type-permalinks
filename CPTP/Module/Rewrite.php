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
	 * Taxonomy rules for the URL of the term link, where the rewrite rules are under another slug.
	 *
	 * These are not rewrite rules. They are only used for a request that is not found.
	 *
	 * @see CPTP_Module_Rewrite::resolve_term_link()
	 *
	 * @var array[] Arrays of `taxonomy` and `redirect`, keyed by the regex of the rule.
	 */
	private $term_link_rules = array();

	/**
	 * Taxonomy rules to add to $term_link_rules, once the post type is registered.
	 *
	 * @var array[] Arguments of add_term_link_rules(), keyed by post type, then by taxonomy.
	 */
	private $pending_taxonomy_rules = array();

	/**
	 * The rule in $term_link_rules that the request is being parsed again with, as regex => redirect.
	 *
	 * @var string[]|null
	 */
	private $resolving_rule = null;

	/**
	 * Add Actions.
	 */
	public function add_hook() {
		add_action( 'parse_request', array( $this, 'parse_request' ) );
		add_action( 'registered_post_type', array( $this, 'register_post_type_rules' ), 10, 2 );
		add_action( 'registered_post_type', array( $this, 'register_pending_taxonomy_rules' ), 10, 2 );
		add_action( 'registered_taxonomy', array( $this, 'register_taxonomy_rules' ), 10, 3 );
		add_filter( 'pre_handle_404', array( $this, 'resolve_term_link' ), 10, 2 );
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

			/*
			 * When has_archive is a string, the rules are under that slug, but
			 * CPTP_Module_Permalink::term_link() builds the link with the rewrite slug.
			 * Keep the rules under the has_archive slug, and resolve the link below.
			 */
			$rewrite_slug = null;
			if ( ! empty( $post_type_obj->has_archive ) && is_string( $post_type_obj->has_archive ) ) {
				if ( $post_type_obj->has_archive !== $slug ) {
					$rewrite_slug = $slug;
				}

				$slug = $post_type_obj->has_archive;
			}

			if ( ! empty( $post_type_obj->rewrite['with_front'] ) ) {
				$front = substr( $wp_rewrite->front, 1 );
				$slug  = $front . $slug;
				if ( null !== $rewrite_slug ) {
					$rewrite_slug = $front . $rewrite_slug;
				}
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

			$post_type_query = get_option( 'add_post_type_for_tax' ) ? "&post_type={$post_type}" : '';
			foreach ( $rules as $rule ) {
				$regex    = sprintf( $rule['regex'], "{$slug}/{$taxonomy_slug}" );
				$redirect = $rule['redirect'] . $post_type_query;
				add_rewrite_rule( $regex, $redirect, 'top' );
			}

			if ( null !== $rewrite_slug ) {
				$this->add_term_link_rules( $taxonomy, $rules, "{$rewrite_slug}/{$taxonomy_slug}", $post_type_query );
			}

			/*
			 * The post type is not registered yet, so the rules above are under its name,
			 * but CPTP_Module_Permalink::term_link() builds the link with its rewrite slug.
			 * Resolve the link when the post type is registered.
			 */
			if ( ! $post_type_obj ) {
				$this->pending_taxonomy_rules[ $post_type ][ $taxonomy ] = array( $rules, $taxonomy_slug, $post_type_query );
			}

			do_action( 'CPTP_registered_' . $taxonomy . '_rules', $object_type, $args, $taxonomy_slug );
		endforeach;
	}


	/**
	 * Resolve the term link under the rewrite slug of a post type that is registered after its taxonomy.
	 *
	 * @param string       $post_type Post type.
	 * @param WP_Post_Type $args      Arguments used to register the post type.
	 */
	public function register_pending_taxonomy_rules( $post_type, $args ) {
		global $wp_rewrite;

		if ( empty( $this->pending_taxonomy_rules[ $post_type ] ) ) {
			return;
		}

		$pending = $this->pending_taxonomy_rules[ $post_type ];
		unset( $this->pending_taxonomy_rules[ $post_type ] );

		// CPTP_Module_Permalink::term_link() adds the slug of the post type only when both are set.
		if ( ! isset( $args->rewrite['slug'] ) || ! isset( $args->rewrite['with_front'] ) ) {
			return;
		}

		$rewrite_slug = $args->rewrite['slug'];
		if ( $args->rewrite['with_front'] ) {
			$rewrite_slug = substr( $wp_rewrite->front, 1 ) . $rewrite_slug;
		}

		if ( empty( $rewrite_slug ) ) {
			return;
		}

		foreach ( $pending as $taxonomy => $rule_args ) {
			list( $rules, $taxonomy_slug, $post_type_query ) = $rule_args;
			$this->add_term_link_rules( $taxonomy, $rules, "{$rewrite_slug}/{$taxonomy_slug}", $post_type_query );
		}
	}

	/**
	 * Remember taxonomy rules under the rewrite slug of a post type, for the URL of the term link.
	 *
	 * @see CPTP_Module_Rewrite::resolve_term_link()
	 *
	 * @param string  $taxonomy        Taxonomy name.
	 * @param array[] $rules           Rules, as an array of `regex` with a placeholder for the base, and `redirect`.
	 * @param string  $base            Rewrite slug of the post type, followed by the slug of the taxonomy.
	 * @param string  $post_type_query Query string to add to the redirect of each rule.
	 */
	private function add_term_link_rules( $taxonomy, $rules, $base, $post_type_query ) {
		foreach ( $rules as $rule ) {
			$this->term_link_rules[ sprintf( $rule['regex'], $base ) ] = array(
				'taxonomy' => $taxonomy,
				'redirect' => $rule['redirect'] . $post_type_query,
			);
		}
	}

	/**
	 * Resolve a request for the URL of a term link, when the rewrite rules did not find anything for it.
	 *
	 * Rewrite rules under the rewrite slug would have to be above the rules of the post type,
	 * and would take URLs that those rules resolve: the pages, feed, embed and attachments
	 * of a post whose slug is the taxonomy slug. So the rewrite rules are left as they are,
	 * and only a request that is not found is parsed again, with the rule for the term link first.
	 * Every request that the rewrite rules resolve is answered exactly as before.
	 *
	 * @param bool     $preempt  Whether to short-circuit default header status handling.
	 * @param WP_Query $wp_query WordPress Query object.
	 *
	 * @return bool
	 */
	public function resolve_term_link( $preempt, $wp_query ) {
		global $wp;

		if ( false !== $preempt || null !== $this->resolving_rule || ! $this->term_link_rules ) {
			return $preempt;
		}

		if ( empty( $wp->request ) || ! self::is_not_found( $wp_query ) ) {
			return $preempt;
		}

		$this->resolving_rule = $this->find_term_link_rule( $wp->request );
		if ( null === $this->resolving_rule ) {
			return $preempt;
		}

		add_filter( 'option_rewrite_rules', array( $this, 'prepend_resolving_rule' ) );

		$wp->matched_rule  = null;
		$wp->matched_query = null;
		$wp->parse_request( $wp->extra_query_vars );

		remove_filter( 'option_rewrite_rules', array( $this, 'prepend_resolving_rule' ) );

		$wp->query_posts();
		$this->resolving_rule = null;

		return $preempt;
	}

	/**
	 * Whether WP::handle_404() is going to answer the query with a 404, or the query found nothing to show.
	 *
	 * @param WP_Query $wp_query WordPress Query object.
	 *
	 * @return bool
	 */
	private static function is_not_found( $wp_query ) {
		if ( is_admin() || $wp_query->is_robots() || $wp_query->is_favicon() ) {
			return false;
		}

		if ( $wp_query->is_404() ) {
			return true;
		}

		if ( $wp_query->posts ) {
			return false;
		}

		// The archive of an author, a term or a post type, the home and a search are not a 404 without posts.
		return ! $wp_query->get_queried_object() && ! $wp_query->is_home() && ! $wp_query->is_search();
	}

	/**
	 * Find the rule in $term_link_rules that matches the request and whose term exists.
	 *
	 * @param string $request Requested path, as in WP::$request.
	 *
	 * @return string[]|null The rule as regex => redirect, or null.
	 */
	private function find_term_link_rule( $request ) {
		foreach ( $this->term_link_rules as $regex => $rule ) {
			if ( ! preg_match( "#^$regex#", $request, $matches ) && ! preg_match( "#^$regex#", urldecode( $request ), $matches ) ) {
				continue;
			}

			$taxonomy = get_taxonomy( $rule['taxonomy'] );
			if ( ! $taxonomy ) {
				continue;
			}

			// WP_Query takes several terms, separated by a comma or a plus sign.
			foreach ( preg_split( '/[,+]/', $matches[1] ) as $term_slug ) {
				if ( $taxonomy->hierarchical ) {
					$term_slug = wp_basename( $term_slug );
				}

				if ( '' !== $term_slug && get_term_by( 'slug', $term_slug, $taxonomy->name ) ) {
					return array( $regex => $rule['redirect'] );
				}
			}
		}

		return null;
	}

	/**
	 * Put the rule for the term link above the rewrite rules.
	 *
	 * @param array|mixed $rules Rewrite rules.
	 *
	 * @return array|mixed
	 */
	public function prepend_resolving_rule( $rules ) {
		if ( ! is_array( $rules ) || null === $this->resolving_rule ) {
			return $rules;
		}

		return array_merge( $this->resolving_rule, $rules );
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
