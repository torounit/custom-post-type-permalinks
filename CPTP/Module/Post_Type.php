<?php
/**
 * Post Type Settings.
 *
 * @package Custom_Post_Type_Permalinks
 */

/**
 * CPTP_Post_Type
 *
 * @since 4.0.0
 * */
class CPTP_Post_Type extends CPTP_Module {

	/**
	 * Entry point.
	 */
	public function add_hook() {
		add_filter( 'register_post_type_args', array( $this, 'register_post_type_args' ), 10, 2 );
	}


	/**
	 * Filters the arguments for registering a post type.
	 *
	 * @since 4.0.0
	 *
	 * @param array $args Array of arguments for registering a post type.
	 * @param string $post_type Post type key.
	 *
	 * @return array
	 */
	public function register_post_type_args( $args, $post_type ) {
		$cptp = array();
		if ( ! empty( $args['cptp'] ) ) {
			$cptp = $args['cptp'];
		}

		$defaults     = array(
			'active'              => true,
			'permalink_structure' => '',
			'date_archive'        => true,
		);

		$args['cptp'] = array_merge( $defaults, $cptp );

		if ( ! empty( $args['cptp_permalink_structure'] ) ) {
			if ( '' === $args['cptp']['permalink_structure'] ) {
				$args['cptp']['permalink_structure'] = $args['cptp_permalink_structure'];
			}
		}

		return $args;
	}
}
