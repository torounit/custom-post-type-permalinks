<?php
/**
 * Taxonomy Settings.
 *
 * @package Custom_Post_Type_Permalinks
 */

/**
 * CPTP_Taxonomy
 *
 * @since 4.0.0
 * */
class CPTP_Taxonomy extends CPTP_Module {

	/**
	 * Entry point.
	 */
	public function add_hook() {
		add_filter( 'register_taxonomy_args',  array( $this, 'register_taxonomy_args' ), 10, 3 );
	}


	/**
	 * Filters the arguments for registering a taxonomy.
	 *
	 * @since 4.0.0
	 *
	 * @param array    $args        Array of arguments for registering a taxonomy.
	 * @param string   $name        Taxonomy key.
	 * @param string[] $object_type Array of names of object types for the taxonomy.
	 *
	 * @return array
	 */
	public function register_taxonomy_args( $args, $name, $object_type ) {
		$cptp = array();
		if ( ! empty( $args['cptp'] ) ) {
			$cptp = $args['cptp'];
		}
		$defaults     = array(
			'active'       => true,
			'date_archive' => true,
		);
		$args['cptp'] = array_merge( $defaults, $cptp );

		return $args;
	}

}


