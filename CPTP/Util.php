<?php

/**
 *
 * Utilty Class
 *
 * @package Custom_Post_Type_Permalinks
 * @since 0.9.4
 *
 * */

class CPTP_Util {

	private function __construct() {
	}

	public static function get_post_types() {
		return get_post_types( array( '_builtin' => false, 'publicly_queryable' => true, 'show_ui' => true ) );
	}

	public static function get_taxonomies( $objects = false ) {
		if ( $objects ) {
			$output = 'objects';
		}
		else {
			$output = 'names';
		}
		return get_taxonomies( array( 'show_ui' => true, '_builtin' => false ), $output );
	}


	/**
	 *
	 * Get Custom Taxonomies parents.
	 * @version 1.0
	 *
	 */
	public static function get_taxonomy_parents( $id, $taxonomy = 'category', $link = false, $separator = '/', $nicename = false, $visited = array() ) {
		$chain = '';
		$parent = get_term( $id, $taxonomy );
		if ( is_wp_error( $parent ) ) {
			return $parent;
		}

		if ( $nicename ){
			$name = $parent->slug;
		}else {
			$name = $parent->name;
		}

		if ( $parent->parent && ( $parent->parent != $parent->term_id ) && ! in_array( $parent->parent, $visited ) ) {
			$visited[] = $parent->parent;
			$chain .= CPTP_Util::get_taxonomy_parents( $parent->parent, $taxonomy, $link, $separator, $nicename, $visited );
		}
		if ( $link ) {
			$chain .= '<a href="' . get_term_link( $parent->term_id, $taxonomy ) . '" title="' . esc_attr( sprintf( __( 'View all posts in %s' ), $parent->name ) ) . '">'.esc_html( $name ).'</a>' .esc_html( $separator );
		}else {
			$chain .= $name.$separator;
		}
		return $chain;
	}

	/**
	 * Get permalink structure.
	 *
	 * @since 0.9.6
	 * @param string|object $post_type post type name. / object post type object.
	 * @return string post type structure.
	 */
	public static function get_permalink_structure( $post_type ) {
		if ( is_string( $post_type ) ) {
			$pt_object = get_post_type_object( $post_type );
		}
		else {
			$pt_object = $post_type;
		}

		if ( ! empty( $pt_object->cptp_permalink_structure ) ) {
			$structure = $pt_object->cptp_permalink_structure;
		}
		else {

			$structure = get_option( $pt_object->name.'_structure' );
		}

		return apply_filters( 'CPTP_'.$pt_object->name.'_structure', $structure );
	}


	/**
	 * Get permalink structure front for date archive.
	 *
	 * @since 1.0.0
	 * @param string $post_type post type name.
	 * @return string
	 */
	public static function get_date_front( $post_type ) {
		$structure = CPTP_Util::get_permalink_structure( $post_type );

		$front = '';

		preg_match_all( '/%.+?%/', $structure, $tokens );
		$tok_index = 1;
		foreach ( (array) $tokens[0] as $token ) {
			if ( '%post_id%' == $token && ($tok_index <= 3) ) {
				$front = '/date';
				break;
			}
			$tok_index++;
		}

		return $front;

	}



}
