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
	 * Taxonomy rules added under the rewrite slug of a post type, next to the rules under another slug.
	 *
	 * @var string[] Taxonomy names, keyed by the regex of the rule.
	 */
	private $rewrite_slug_rules = array();

	/**
	 * Taxonomy rules to add under the rewrite slug of a post type, once the post type is registered.
	 *
	 * @var array[] Arguments of add_rewrite_slug_rules(), keyed by post type, then by taxonomy.
	 */
	private $pending_taxonomy_rules = array();

	/**
	 * Whether the request is being parsed again without the rules in $rewrite_slug_rules.
	 *
	 * @var bool
	 */
	private $reparsing = false;

	/**
	 * Add Actions.
	 */
	public function add_hook() {
		add_action( 'parse_request', array( $this, 'reparse_request_without_rewrite_slug_rules' ), 1 );
		add_action( 'parse_request', array( $this, 'parse_request' ) );
		add_action( 'registered_post_type', array( $this, 'register_post_type_rules' ), 10, 2 );
		add_action( 'registered_post_type', array( $this, 'register_pending_taxonomy_rules' ), 10, 2 );
		add_action( 'registered_taxonomy', array( $this, 'register_taxonomy_rules' ), 10, 3 );
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
			 * Keep the rules under the has_archive slug, and add rules for the link below.
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

				// This regex is no longer one that was only added for the term link.
				unset( $this->rewrite_slug_rules[ $regex ] );
			}

			if ( null !== $rewrite_slug ) {
				$this->add_rewrite_slug_rules( $taxonomy, $rules, "{$rewrite_slug}/{$taxonomy_slug}", $post_type_query );
			}

			/*
			 * The post type is not registered yet, so the rules above are under its name,
			 * but CPTP_Module_Permalink::term_link() builds the link with its rewrite slug.
			 * Add rules for the link when the post type is registered.
			 */
			if ( ! $post_type_obj ) {
				$this->pending_taxonomy_rules[ $post_type ][ $taxonomy ] = array( $rules, $taxonomy_slug, $post_type_query );
			}

			do_action( 'CPTP_registered_' . $taxonomy . '_rules', $object_type, $args, $taxonomy_slug );
		endforeach;
	}


	/**
	 * Add the taxonomy rules under the rewrite slug of a post type that is registered after its taxonomy.
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
			$this->add_rewrite_slug_rules( $taxonomy, $rules, "{$rewrite_slug}/{$taxonomy_slug}", $post_type_query );
		}
	}

	/**
	 * Add taxonomy rules under the rewrite slug of a post type, for the URL of the term link.
	 *
	 * A request that matches one of these rules, but is not for a term, is parsed again without them.
	 *
	 * @see CPTP_Module_Rewrite::reparse_request_without_rewrite_slug_rules()
	 *
	 * @param string  $taxonomy        Taxonomy name.
	 * @param array[] $rules           Rules, as an array of `regex` with a placeholder for the base, and `redirect`.
	 * @param string  $base            Rewrite slug of the post type, followed by the slug of the taxonomy.
	 * @param string  $post_type_query Query string to add to the redirect of each rule.
	 */
	private function add_rewrite_slug_rules( $taxonomy, $rules, $base, $post_type_query ) {
		global $wp_rewrite;

		foreach ( $rules as $rule ) {
			$regex = sprintf( $rule['regex'], $base );

			// Never replace a rule that is already registered.
			if ( isset( $wp_rewrite->extra_rules_top[ $regex ] ) ) {
				continue;
			}

			add_rewrite_rule( $regex, $rule['redirect'] . $post_type_query, 'top' );
			$this->rewrite_slug_rules[ $regex ] = $taxonomy;
		}
	}

	/**
	 * Parse the request again without the rules under the rewrite slug, when the request is not for a term.
	 *
	 * The rules under the rewrite slug are above the rules of the post type, so they also match
	 * the URLs of a post whose slug is the taxonomy slug: its pages, feed, embed and attachments.
	 * Those URLs were resolved by the rules below before the rules under the rewrite slug were added,
	 * so hand them back when no term matches.
	 *
	 * @param WP $wp WP instance.
	 */
	public function reparse_request_without_rewrite_slug_rules( $wp ) {
		if ( $this->reparsing || empty( $wp->matched_rule ) || ! isset( $this->rewrite_slug_rules[ $wp->matched_rule ] ) ) {
			return;
		}

		$taxonomy = get_taxonomy( $this->rewrite_slug_rules[ $wp->matched_rule ] );
		if ( ! $taxonomy ) {
			return;
		}

		if ( isset( $wp->query_vars[ $taxonomy->name ] ) ) {
			$term_slug = $wp->query_vars[ $taxonomy->name ];
			if ( ! is_string( $term_slug ) ) {
				return;
			}

			if ( $taxonomy->hierarchical ) {
				$term_slug = wp_basename( $term_slug );
			}

			if ( get_term_by( 'slug', $term_slug, $taxonomy->name ) ) {
				return;
			}
		}

		$this->reparsing = true;
		add_filter( 'option_rewrite_rules', array( $this, 'remove_rewrite_slug_rules' ) );

		$wp->matched_rule  = null;
		$wp->matched_query = null;
		$wp->parse_request( $wp->extra_query_vars );

		remove_filter( 'option_rewrite_rules', array( $this, 'remove_rewrite_slug_rules' ) );
		$this->reparsing = false;
	}

	/**
	 * Remove the rules under the rewrite slug from the rewrite rules.
	 *
	 * @param array|mixed $rules Rewrite rules.
	 *
	 * @return array|mixed
	 */
	public function remove_rewrite_slug_rules( $rules ) {
		if ( ! is_array( $rules ) ) {
			return $rules;
		}

		return array_diff_key( $rules, $this->rewrite_slug_rules );
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
