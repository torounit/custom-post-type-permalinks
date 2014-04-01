<?php



/**
 *
 * Add Rewrite Rules
 *
 * @package Custom_Post_Type_Permalinks
 * @since 0.9.6
 *
 * */

class CPTP_Module_HierarchicalTaxonomy extends CPTP_Module {

	public function add_hook() {
		if(get_option( "fix_hierarchical_taxonomy_permalink")) {
			add_action( 'parse_request', array( $this, "parse_request") );
			add_filter( 'term_link', array( $this,'term_link'), 10, 3 );

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


	/**
	 *
	 * Fix taxonomy link outputs.
	 * @since 0.6
	 * @version 1.0
	 *
	 */
	public function term_link( $termlink, $term, $taxonomy ) {
		global $wp_rewrite;

		$taxonomy = get_taxonomy($taxonomy);
		if( $taxonomy->_builtin )
			return $termlink;

		if( empty($taxonomy) )
			return $termlink;


		if ( ! $taxonomy->rewrite['hierarchical'] ) {
			$termlink = str_replace( $term->slug.'/', CPTP_Util::get_taxonomy_parents( $term->term_id,$taxonomy->name, false, '/', true ), $termlink );
		}

		return $termlink;
	}



}
