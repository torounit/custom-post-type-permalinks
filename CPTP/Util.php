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
		return get_post_types( array('_builtin'=>false, 'publicly_queryable'=>true, 'show_ui' => true) );
	}

	public static function get_taxonomies( $objects = false ) {
		if( $objects ){
			$output = "objects";
		}
		else {
			$output = "names";
		}
		return get_taxonomies( array("show_ui" => true, "_builtin" => false), $output );
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

		if ( $parent->parent && ( $parent->parent != $parent->term_id ) && !in_array( $parent->parent, $visited ) ) {
			$visited[] = $parent->parent;
			$chain .= CPTP_Util::get_taxonomy_parents( $parent->parent, $taxonomy, $link, $separator, $nicename, $visited );
		}

		if ( $link ) {
			$chain .= '<a href="' . get_term_link( $parent->term_id, $taxonomy ) . '" title="' . esc_attr( sprintf( __( "View all posts in %s" ), $parent->name ) ) . '">'.$name.'</a>' . $separator;
		}else {
			$chain .= $name.$separator;
		}
		return $chain;
	}

	public static function get_permalink_structure( $post_type ) {
		$pt_object = get_post_type_object( $post_type );

		if( isset( $pt_object->cptp_permalink_structure ) and $pt_object->cptp_permalink_structure ) {
			$structure = $pt_object->cptp_permalink_structure;
		}
		else {
			$structure = get_option( $post_type."_structure" );
		}

		return apply_filters( "CPTP_".$post_type."_structure", $structure );
	}



}