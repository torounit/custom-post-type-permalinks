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
		add_action( 'parse_request', array( $this, "parse_request") );
		add_action( 'registered_post_type', array( $this,'registered_post_type'), 10, 2 );
		add_action( 'registered_taxonomy', array( $this,'registered_taxonomy'), 10, 3 );
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


	public function registered_taxonomy( $taxonomy, $object_type, $args ) {

		if(get_option('no_taxonomy_structure')) {
			return false;
		}
		if($args["_builtin"]) {
			return false;
		}

		global $wp_rewrite;

		$taxonomyObject = $args;
		$post_types = $args["object_type"];
		foreach ($post_types as $post_type):
			$post_type_obj = get_post_type_object($post_type);
			$slug = $post_type_obj->rewrite['slug'];


			if(!$slug) {
				$slug = $post_type;
			}

			if(is_string($post_type_obj->has_archive)) {
				$slug = $post_type_obj->has_archive;
			};

			if($post_type_obj->rewrite['with_front']) {
				$slug = substr( $wp_rewrite->front, 1 ).$slug;
			}

			if ( $taxonomy == 'category' ){
				$taxonomypat = ($cb = get_option('category_base')) ? $cb : $taxonomy;
				$tax = 'category_name';
			} else {
				// Edit by [Xiphe]
				if (isset($args["rewrite"]['slug'])) {
					$taxonomypat = $args["rewrite"]['slug'];
				} else {
					$taxonomypat = $taxonomy;
				}
				// [Xiphe] stop

				$tax = $taxonomy;
			}


			//add taxonomy slug
			add_rewrite_rule( $slug.'/'.$taxonomypat.'/(.+?)/page/?([0-9]{1,})/?$', 'index.php?'.$tax.'=$matches[1]&paged=$matches[2]&post_type='.$post_type, 'top' );
			add_rewrite_rule( $slug.'/'.$taxonomypat.'/(.+?)/feed/(feed|rdf|rss|rss2|atom)/?$', 'index.php?'.$tax.'=$matches[1]&feed=$matches[2]&post_type='.$post_type, 'top' );
			add_rewrite_rule( $slug.'/'.$taxonomypat.'/(.+?)/(feed|rdf|rss|rss2|atom)/?$', 'index.php?'.$tax.'=$matches[1]&feed=$matches[2]&post_type='.$post_type, 'top' );
			add_rewrite_rule( $slug.'/'.$taxonomypat.'/(.+?)/?$', 'index.php?'.$tax.'=$matches[1]&post_type='.$post_type, 'top' );  // modified by [steve] [*** bug fixing]

			// below rules were added by [steve]
			add_rewrite_rule( $taxonomypat.'/(.+?)/date/([0-9]{4})/([0-9]{1,2})/?$', 'index.php?'.$tax.'=$matches[1]&year=$matches[2]&monthnum=$matches[3]&post_type='.$post_type, 'top' );
			add_rewrite_rule( $taxonomypat.'/(.+?)/date/([0-9]{4})/([0-9]{1,2})/page/?([0-9]{1,})/?$', 'index.php?'.$tax.'=$matches[1]&year=$matches[2]&monthnum=$matches[3]&paged=$matches[4]&post_type='.$post_type, 'top' );
			add_rewrite_rule( $slug.'/'.$taxonomypat.'/(.+?)/date/([0-9]{4})/([0-9]{1,2})/?$', 'index.php?'.$tax.'=$matches[1]&year=$matches[2]&monthnum=$matches[3]&post_type='.$post_type, 'top' );
			add_rewrite_rule( $slug.'/'.$taxonomypat.'/(.+?)/date/([0-9]{4})/([0-9]{1,2})/page/?([0-9]{1,})/?$', 'index.php?'.$tax.'=$matches[1]&year=$matches[2]&monthnum=$matches[3]&paged=$matches[4]&post_type='.$post_type, 'top' );

		endforeach;

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
					$obj->query_vars[$tax] = array_pop(explode("/", $obj->query_vars[$tax]));
				}
			}
		}
	}
}
