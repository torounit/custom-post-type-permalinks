<?php



/**
 *
 * Add Rewrite Rules
 *
 * @package Custom_Post_Type_Permalinks
 * @since 0.9.4
 *
 * */

class CPTP_Module_Rewrite extends CPTP_Module {

	/** @var  Array */
	private $post_type_args;
	/** @var  Array */
	private $taxonomy_args;

	public function add_hook() {
		add_action( 'parse_request', array( $this, 'parse_request' ) );

		add_action( 'registered_post_type', array( $this, 'registered_post_type' ), 10, 2 );
		add_action( 'registered_taxonomy', array( $this, 'registered_taxonomy' ), 10, 3 );

		add_action( 'wp_loaded', array( $this, 'add_rewrite_rules'), 100 );
	}


	public function add_rewrite_rules() {

		foreach( $this->post_type_args as $args ) {
			call_user_func_array( array( $this, 'register_post_type_rules' ), $args );
		}

		foreach( $this->taxonomy_args as $args ) {
			call_user_func_array( array( $this, 'register_taxonomy_rules' ), $args );
		}

	}

	public function registered_post_type( $post_type, $args ) {
		$this->post_type_args[] = func_get_args();
	}

	public function registered_taxonomy(  $taxonomy, $object_type, $args ) {
		$this->taxonomy_args[] = func_get_args();
	}


	/**
	 *
	 * registered_post_type
	 *  ** add rewrite tag for Custom Post Type.
	 * @version 1.1
	 * @since 0.9
	 *
	 */

	public function register_post_type_rules( $post_type, $args ) {

		global $wp_post_types, $wp_rewrite;

		if ( $args->_builtin or ! $args->publicly_queryable or ! $args->show_ui ){
			return false;
		}
		$permalink = CPTP_Util::get_permalink_structure( $post_type );

		if ( ! $permalink ) {
			$permalink = CPTP_DEFAULT_PERMALINK;
		}

		$permalink = '%'.$post_type.'_slug%'.$permalink;
		$permalink = str_replace( '%postname%', '%'.$post_type.'%', $permalink );

		add_rewrite_tag( '%'.$post_type.'_slug%', '('.$args->rewrite['slug'].')','post_type='.$post_type.'&slug=' );

		$taxonomies = CPTP_Util::get_taxonomies( true );
		foreach ( $taxonomies as $taxonomy => $objects ):
			$wp_rewrite->add_rewrite_tag( "%$taxonomy%", '(.+?)', "$taxonomy=" );
		endforeach;

		$rewrite_args = $args->rewrite;
		if ( ! is_array( $rewrite_args ) ) {
			$rewrite_args  = array( 'with_front' => $args->rewrite );
		}

		$rewrite_args['walk_dirs'] = false;
		add_permastruct( $post_type, $permalink, $rewrite_args );


		$slug = $args->rewrite['slug'];
		if ( $args->has_archive ) {
			if ( is_string( $args->has_archive ) ) {
				$slug = $args->has_archive;
			};

			if ( $args->rewrite['with_front'] ) {
				$slug = substr( $wp_rewrite->front, 1 ).$slug;
			}

			$date_front = CPTP_Util::get_date_front( $post_type );

			add_rewrite_rule( $slug. $date_front. '/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/feed/(feed|rdf|rss|rss2|atom)/?$', 'index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&feed=$matches[4]&post_type='.$post_type, 'top' );
			add_rewrite_rule( $slug. $date_front. '/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/(feed|rdf|rss|rss2|atom)/?$', 'index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&feed=$matches[4]&post_type='.$post_type, 'top' );
			add_rewrite_rule( $slug. $date_front. '/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/page/?([0-9]{1,})/?$', 'index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&paged=$matches[4]&post_type='.$post_type, 'top' );
			add_rewrite_rule( $slug. $date_front. '/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/?$', 'index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&post_type='.$post_type, 'top' );
			add_rewrite_rule( $slug. $date_front. '/([0-9]{4})/([0-9]{1,2})/feed/(feed|rdf|rss|rss2|atom)/?$', 'index.php?year=$matches[1]&monthnum=$matches[2]&feed=$matches[3]&post_type='.$post_type, 'top' );
			add_rewrite_rule( $slug. $date_front. '/([0-9]{4})/([0-9]{1,2})/(feed|rdf|rss|rss2|atom)/?$', 'index.php?year=$matches[1]&monthnum=$matches[2]&feed=$matches[3]&post_type='.$post_type, 'top' );
			add_rewrite_rule( $slug. $date_front. '/([0-9]{4})/([0-9]{1,2})/page/?([0-9]{1,})/?$', 'index.php?year=$matches[1]&monthnum=$matches[2]&paged=$matches[3]&post_type='.$post_type, 'top' );
			add_rewrite_rule( $slug. $date_front. '/([0-9]{4})/([0-9]{1,2})/?$', 'index.php?year=$matches[1]&monthnum=$matches[2]&post_type='.$post_type, 'top' );
			add_rewrite_rule( $slug. $date_front. '/([0-9]{4})/feed/(feed|rdf|rss|rss2|atom)/?$', 'index.php?year=$matches[1]&feed=$matches[2]&post_type='.$post_type, 'top' );
			add_rewrite_rule( $slug. $date_front. '/([0-9]{4})/(feed|rdf|rss|rss2|atom)/?$', 'index.php?year=$matches[1]&feed=$matches[2]&post_type='.$post_type, 'top' );
			add_rewrite_rule( $slug. $date_front. '/([0-9]{4})/page/?([0-9]{1,})/?$', 'index.php?year=$matches[1]&paged=$matches[2]&post_type='.$post_type, 'top' );
			add_rewrite_rule( $slug. $date_front. '/([0-9]{4})/?$', 'index.php?year=$matches[1]&post_type='.$post_type, 'top' );
			add_rewrite_rule( $slug.'/author/([^/]+)/page/?([0-9]{1,})/?$', 'index.php?author_name=$matches[1]&paged=$matches[2]&post_type='.$post_type, 'top' );
			add_rewrite_rule( $slug.'/author/([^/]+)/?$', 'index.php?author_name=$matches[1]&post_type='.$post_type, 'top' );
			add_rewrite_rule( $slug.'/'.get_option( 'category_base' ).'/([^/]+)/page/?([0-9]{1,})/?$', 'index.php?category_name=$matches[1]&paged=$matches[2]&post_type='.$post_type, 'top' );
			add_rewrite_rule( $slug.'/'.get_option( 'category_base' ).'/([^/]+)/?$', 'index.php?category_name=$matches[1]&post_type='.$post_type, 'top' );

			do_action( 'CPTP_registered_'.$post_type.'_rules', $args, $slug );
		}
	}


	public function register_taxonomy_rules( $taxonomy, $object_type, $args ) {

		if ( get_option( 'no_taxonomy_structure' ) ) {
			return false;
		}
		if ( $args['_builtin'] ) {
			return false;
		}

		global $wp_rewrite;

		$post_types = $args['object_type'];
		foreach ( $post_types as $post_type ):
			$post_type_obj = get_post_type_object( $post_type );
			if ( ! empty( $post_type_obj->rewrite['slug'] ) ) {
				$slug = $post_type_obj->rewrite['slug'];
			}
			else {
				$slug = $post_type;
			}

			if ( ! empty( $post_type_obj->has_archive ) && is_string( $post_type_obj->has_archive ) ) {
				$slug = $post_type_obj->has_archive;
			};


			if ( ! empty( $post_type_obj->rewrite['with_front'] ) ) {
				$slug = substr( $wp_rewrite->front, 1 ).$slug;
			}

			if ( 'category' == $taxonomy ) {
				$taxonomy_slug = ( $cb = get_option( 'category_base' ) ) ? $cb : $taxonomy;
				$taxonomy_key = 'category_name';
			} else {
				// Edit by [Xiphe]
				if ( isset( $args['rewrite']['slug'] ) ) {
					$taxonomy_slug = $args['rewrite']['slug'];
				} else {
					$taxonomy_slug = $taxonomy;
				}
				// [Xiphe] stop

				$taxonomy_key = $taxonomy;
			}

			//add taxonomy slug
			add_rewrite_rule( $slug.'/'.$taxonomy_slug.'/(.+?)/page/?([0-9]{1,})/?$', 'index.php?'.$taxonomy_key.'=$matches[1]&paged=$matches[2]&post_type='.$post_type, 'top' );
			add_rewrite_rule( $slug.'/'.$taxonomy_slug.'/(.+?)/feed/(feed|rdf|rss|rss2|atom)/?$', 'index.php?'.$taxonomy_key.'=$matches[1]&feed=$matches[2]&post_type='.$post_type, 'top' );
			add_rewrite_rule( $slug.'/'.$taxonomy_slug.'/(.+?)/(feed|rdf|rss|rss2|atom)/?$', 'index.php?'.$taxonomy_key.'=$matches[1]&feed=$matches[2]&post_type='.$post_type, 'top' );
			add_rewrite_rule( $slug.'/'.$taxonomy_slug.'/(.+?)/?$', 'index.php?'.$taxonomy_key.'=$matches[1]&post_type='.$post_type, 'top' );  // modified by [steve] [*** bug fixing]

			// below rules were added by [steve]
			add_rewrite_rule( $taxonomy_slug.'/(.+?)/date/([0-9]{4})/([0-9]{1,2})/?$', 'index.php?'.$taxonomy_key.'=$matches[1]&year=$matches[2]&monthnum=$matches[3]', 'top' );
			add_rewrite_rule( $taxonomy_slug.'/(.+?)/date/([0-9]{4})/([0-9]{1,2})/page/?([0-9]{1,})/?$', 'index.php?'.$taxonomy_key.'=$matches[1]&year=$matches[2]&monthnum=$matches[3]&paged=$matches[4]', 'top' );

			add_rewrite_rule( $slug.'/'.$taxonomy_slug.'/(.+?)/date/([0-9]{4})/([0-9]{1,2})/?$', 'index.php?'.$taxonomy_key.'=$matches[1]&year=$matches[2]&monthnum=$matches[3]&post_type='.$post_type, 'top' );
			add_rewrite_rule( $slug.'/'.$taxonomy_slug.'/(.+?)/date/([0-9]{4})/([0-9]{1,2})/page/?([0-9]{1,})/?$', 'index.php?'.$taxonomy_key.'=$matches[1]&year=$matches[2]&monthnum=$matches[3]&paged=$matches[4]&post_type='.$post_type, 'top' );

			do_action( 'CPTP_registered_'.$taxonomy.'_rules', $object_type, $args, $taxonomy_slug );

		endforeach;
	}



	/**
	 *
	 * Fix taxonomy = parent/child => taxonomy => child
	 * @since 0.9.3
	 *
	 */
	public function parse_request( $obj ) {
		$taxes = CPTP_Util::get_taxonomies();
		foreach ( $taxes as $key => $tax ) {
			if ( isset( $obj->query_vars[ $tax ] ) ) {
				if ( false !== strpos( $obj->query_vars[ $tax ] ,'/' ) ) {
					$query_vars = explode( '/', $obj->query_vars[ $tax ] );
					if ( is_array( $query_vars ) ) {
						$obj->query_vars[ $tax ] = array_pop( $query_vars );
					}
				}
			}
		}
	}
}
