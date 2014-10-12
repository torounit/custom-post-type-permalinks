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

	private $fixed_taxonomies;

	public function add_hook() {
		$this->fixed_taxonomies = array();
		add_action( 'registered_taxonomy', array( $this, "registered_taxonomy"), 10, 3 );

	}

	public function registered_taxonomy( $taxonomy, $object_type, $args ) {
		if( !isset($this->fixed_taxonomies[$taxonomy]) ) {

			$this->fixed_taxonomies[$taxonomy] = true;

			if( get_option( "fix_hierarchical_taxonomy_permalink") and $args["hierarchical"] ) {
				$args["rewrite"]["hierarchical"] = true;
			}

			register_taxonomy( $taxonomy, $object_type, $args );

		}
	}







}
