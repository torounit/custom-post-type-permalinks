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

	public function add_hook() {
		if(get_option( "fix_hierarchical_taxonomy_permalink")) {
			add_action( 'parse_request', array( $this, "parse_request") );
		}
		add_action( 'registered_post_type', array( $this,'registered_post_type'), 10, 2 );
	}


	/**
	 *
	 * registered_post_type
	 *  ** add rewrite tag for Custom Post Type.
	 * @version 1.1
	 * @since 0.9
	 *
	 */

	public function registered_post_type( $post_type, $args ) {

		global $wp_post_types, $wp_rewrite;

		if( $args->_builtin or !$args->publicly_queryable or !$args->show_ui ){
			return false;
		}
		$permalink = get_option( $post_type.'_structure' );

		if( !$permalink ) {
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
		if( !is_array($rewrite_args) ) {
			$rewrite_args  = array( 'with_front' => $args->rewrite );
		}

		$rewrite_args["walk_dirs"] = false;
		add_permastruct( $post_type, $permalink, $rewrite_args);



		$slug = $args->rewrite['slug'];
		if( $args->has_archive ){
			if( is_string( $args->has_archive ) ){
				$slug = $args->has_archive;
			};

			if($args->rewrite['with_front']) {
				$slug = substr( $wp_rewrite->front, 1 ).$slug;
			}

			add_rewrite_rule( $slug.'/date/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/feed/(feed|rdf|rss|rss2|atom)/?$', 'index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&feed=$matches[4]&post_type='.$post_type, 'top' );
			add_rewrite_rule( $slug.'/date/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/(feed|rdf|rss|rss2|atom)/?$', 'index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&feed=$matches[4]&post_type='.$post_type, 'top' );
			add_rewrite_rule( $slug.'/date/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/page/?([0-9]{1,})/?$', 'index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&paged=$matches[4]&post_type='.$post_type, 'top' );
			add_rewrite_rule( $slug.'/date/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/?$', 'index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&post_type='.$post_type, 'top' );
			add_rewrite_rule( $slug.'/date/([0-9]{4})/([0-9]{1,2})/feed/(feed|rdf|rss|rss2|atom)/?$', 'index.php?year=$matches[1]&monthnum=$matches[2]&feed=$matches[3]&post_type='.$post_type, 'top' );
			add_rewrite_rule( $slug.'/date/([0-9]{4})/([0-9]{1,2})/(feed|rdf|rss|rss2|atom)/?$', 'index.php?year=$matches[1]&monthnum=$matches[2]&feed=$matches[3]&post_type='.$post_type, 'top' );
			add_rewrite_rule( $slug.'/date/([0-9]{4})/([0-9]{1,2})/page/?([0-9]{1,})/?$', 'index.php?year=$matches[1]&monthnum=$matches[2]&paged=$matches[3]&post_type='.$post_type, 'top' );
			add_rewrite_rule( $slug.'/date/([0-9]{4})/([0-9]{1,2})/?$', 'index.php?year=$matches[1]&monthnum=$matches[2]&post_type='.$post_type, 'top' );
			add_rewrite_rule( $slug.'/date/([0-9]{4})/feed/(feed|rdf|rss|rss2|atom)/?$', 'index.php?year=$matches[1]&feed=$matches[2]&post_type='.$post_type, 'top' );
			add_rewrite_rule( $slug.'/date/([0-9]{4})/(feed|rdf|rss|rss2|atom)/?$', 'index.php?year=$matches[1]&feed=$matches[2]&post_type='.$post_type, 'top' );
			add_rewrite_rule( $slug.'/date/([0-9]{4})/page/?([0-9]{1,})/?$', 'index.php?year=$matches[1]&paged=$matches[2]&post_type='.$post_type, 'top' );
			add_rewrite_rule( $slug.'/date/([0-9]{4})/?$', 'index.php?year=$matches[1]&post_type='.$post_type, 'top' );
			add_rewrite_rule( $slug.'/author/([^/]+)/?$', 'index.php?author_name=$matches[1]&post_type='.$post_type, 'top' );
		}

	}





	/**
	 *
	 * Fix taxonomy = parent/child => taxonomy => child
	 * @since 0.9.3
	 *
	 */
	public function parse_request($obj) {
		$taxes = CPTP_Util::get_taxonomies();
		foreach ($taxes as $key => $tax) {
			if(isset($obj->query_vars[$tax])) {
				if(strpos( $obj->query_vars[$tax] ,"/") !== false ) {
					$query_vars = explode("/", $obj->query_vars[$tax]);
					if(is_array($query_vars)) {
						$obj->query_vars[$tax] = array_pop($query_vars);
					}
				}
			}
		}
	}
}
