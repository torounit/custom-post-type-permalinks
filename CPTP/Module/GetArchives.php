<?php
/**
 * Fix: wp_get_archives fix for custom post
 * Ex:wp_get_archives('&post_type='.get_query_var( 'post_type' ));
 *
 * @package Custom_Post_Type_Permalinks
 */

/**
 * Get Archives fix.
 *
 * @since 0.9.4
 * */
class CPTP_Module_GetArchives extends CPTP_Module {

	/**
	 * Register hooks.
	 */
	public function add_hook() {
		if ( get_option( 'permalink_structure', '' ) !== '' ) {
			add_filter( 'getarchives_join', array( $this, 'getarchives_join' ), 10, 2 );
			add_filter( 'getarchives_where', array( $this, 'getarchives_where' ), 10, 2 );
			add_filter( 'get_archives_link', array( $this, 'get_archives_link' ), 20, 1 );
		}
	}

	/**
	 * Argument in wp_get_archives.
	 *
	 * @var array
	 */
	public $get_archives_where_r;


	/**
	 * Get archive where.
	 *
	 * @param string $where SQL where.
	 * @param array  $r Argument in wp_get_archives.
	 *
	 * @return mixed|string
	 */
	public function getarchives_where( $where, $r ) {
		$this->get_archives_where_r = $r;
		if ( isset( $r['post_type'] ) ) {
			if ( ! in_array( $r['post_type'], CPTP_Util::get_post_types(), true ) ) {
				return $where;
			}

			$post_type = get_post_type_object( $r['post_type'] );
			if ( ! $post_type ) {
				return $where;
			}

			if ( ! $post_type->has_archive ) {
				return $where;
			}

			$where = str_replace( '\'post\'', '\'' . $r['post_type'] . '\'', $where );
		}

		if ( isset( $r['taxonomy'] ) && is_array( $r['taxonomy'] ) ) {
			global $wpdb;
			$where = $where . " AND $wpdb->term_taxonomy.taxonomy = '" . $r['taxonomy']['name'] . "' AND $wpdb->term_taxonomy.term_id = '" . $r['taxonomy']['termid'] . "'";
		}

		return $where;
	}

	/**
	 * Get_archive_join
	 *
	 * @author Steve
	 * @since 0.8
	 * @version 1.0
	 *
	 * @param string $join SQL JOIN.
	 * @param array  $r Argument in wp_get_archives.
	 *
	 * @return string
	 */
	public function getarchives_join( $join, $r ) {
		global $wpdb;
		$this->get_archives_where_r = $r;
		if ( isset( $r['taxonomy'] ) && is_array( $r['taxonomy'] ) ) {
			$join = $join . " INNER JOIN $wpdb->term_relationships ON ($wpdb->posts.ID = $wpdb->term_relationships.object_id) INNER JOIN $wpdb->term_taxonomy ON ($wpdb->term_relationships.term_taxonomy_id = $wpdb->term_taxonomy.term_taxonomy_id)";
		}

		return $join;
	}


	/**
	 * Filter: get_arcihves_link
	 *
	 * @version 2.2 03/27/14
	 *
	 * @param string $html archive <li> html.
	 *
	 * @return string
	 */
	public function get_archives_link( $html ) {
		global $wp_rewrite;

		if ( ! isset( $this->get_archives_where_r['post_type'] ) ) {
			return $html;
		}

		if ( ! in_array( $this->get_archives_where_r['post_type'], CPTP_Util::get_post_types(), true ) ) {
			return $html;
		}

		if ( 'post' === $this->get_archives_where_r['post_type'] ) {
			return $html;
		}

		$post_type = get_post_type_object( $this->get_archives_where_r['post_type'] );
		if ( ! $post_type ) {
			return $html;
		}

		if ( ! $post_type->has_archive ) {
			return $html;
		}

		$c = isset( $this->get_archives_where_r['taxonomy'] ) && is_array( $this->get_archives_where_r['taxonomy'] ) ? $this->get_archives_where_r['taxonomy'] : '';
		$t = $this->get_archives_where_r['post_type'];

		$this->get_archives_where_r['post_type'] = isset( $this->get_archives_where_r['post_type_slug'] ) ? $this->get_archives_where_r['post_type_slug'] : $t; // [steve] [*** bug fixing]

		if ( isset( $this->get_archives_where_r['post_type'] ) && 'postbypost' !== $this->get_archives_where_r['type'] ) {
			$blog_url = rtrim( home_url(), '/' );

			// remove front.
			$front = substr( $wp_rewrite->front, 1 );
			$html  = str_replace( $front, '', $html );

			$blog_url = preg_replace( '/https?:\/\//', '', $blog_url );
			$ret_link = str_replace( $blog_url, $blog_url . '/%link_dir%', $html );

			if ( empty( $c ) ) {
				if ( isset( $post_type->rewrite['slug'] ) ) {
					$link_dir = $post_type->rewrite['slug'];
				} else {
					$link_dir = $this->get_archives_where_r['post_type'];
				}
			} else {
				$c['name'] = ( 'category' === $c['name'] && get_option( 'category_base' ) ) ? get_option( 'category_base' ) : $c['name'];
				$link_dir  = $post_type->rewrite['slug'] . '/' . $c['name'] . '/' . $c['termslug'];
			}

			$ret_link = str_replace( '%link_dir%/date/', '%link_dir%/', $ret_link );

			if ( ! strstr( $html, '/date/' ) ) {
				$link_dir = $link_dir . CPTP_Util::get_date_front( $post_type );
			}

			if ( $post_type->rewrite['with_front'] ) {
				$link_dir = $front . $link_dir;
			}

			$ret_link = str_replace( '%link_dir%', $link_dir, $ret_link );
			$ret_link = str_replace( '?post_type=' . $this->get_archives_where_r['post_type'], '', $ret_link );
		} else {
			$ret_link = $html;
		}
		$this->get_archives_where_r['post_type'] = $t;
		return $ret_link;
	}
}
