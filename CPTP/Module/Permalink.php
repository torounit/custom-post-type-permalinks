<?php
/**
 * Override Output Permalinks
 *
 * @package Custom_Post_Type_Permalinks
 */

/**
 * CPTP_Module_Permalink
 *
 * @since 0.9.4
 * */
class CPTP_Module_Permalink extends CPTP_Module {


	/**
	 * Add Filter Hooks.
	 */
	public function add_hook() {
		add_filter(
			'post_type_link',
			array( $this, 'post_type_link' ),
			apply_filters( 'cptp_post_type_link_priority', 0 ),
			4
		);

		add_filter(
			'term_link',
			array( $this, 'term_link' ),
			apply_filters( 'cptp_term_link_priority', 0 ),
			3
		);

		add_filter(
			'attachment_link',
			array( $this, 'attachment_link' ),
			apply_filters( 'cptp_attachment_link_priority', 20 ),
			2
		);

		add_filter(
			'wpml_st_post_type_link_filter_original_slug',
			array( $this, 'replace_post_slug_with_placeholder' ),
			10,
			3
		);
	}


	/**
	 *
	 * Fix permalinks output.
	 *
	 * @param String  $post_link link url.
	 * @param WP_Post $post post object.
	 * @param String  $leavename for edit.php.
	 *
	 * @return string
	 * @version 2.0
	 */
	public function post_type_link( $post_link, $post, $leavename ) {
		/**
		 * WP_Rewrite.
		 *
		 * @var WP_Rewrite $wp_rewrite
		 */
		global $wp_rewrite;

		if ( ! $wp_rewrite->using_permalinks() ) {
			return $post_link;
		}

		$draft_or_pending = isset( $post->post_status ) && in_array(
			$post->post_status,
			array(
				'draft',
				'pending',
				'auto-draft',
			),
			true
		);
		if ( $draft_or_pending && ! $leavename ) {
			return $post_link;
		}

		$post_type = $post->post_type;
		$pt_object = get_post_type_object( $post_type );

		if ( false === $pt_object->rewrite ) {
			return $post_link;
		}

		if ( ! in_array( $post->post_type, CPTP_Util::get_post_types(), true ) ) {
			return $post_link;
		}

		$permalink = $wp_rewrite->get_extra_permastruct( $post_type );

		$permalink = str_replace( '%post_id%', $post->ID, $permalink );
		$permalink = str_replace( CPTP_Module_Rewrite::get_slug_placeholder( $post_type ), $pt_object->rewrite['slug'], $permalink );

		// has parent.
		$parentsDirs = '';
		if ( $pt_object->hierarchical ) {
			if ( ! $leavename ) {
				$postId = $post->ID;
				while ( $parent = get_post( $postId )->post_parent ) {
					$parentsDirs = get_post( $parent )->post_name . '/' . $parentsDirs;
					$postId      = $parent;
				}
			}
		}

		$permalink = str_replace( '%' . $post_type . '%', $parentsDirs . '%' . $post_type . '%', $permalink );

		if ( ! $leavename ) {
			$permalink = str_replace( '%' . $post_type . '%', $post->post_name, $permalink );
		}

		// %post_id%/attachment/%attachement_name%;
		$id = filter_input( INPUT_GET, 'post' );
		if ( 'attachment' === $post->post_type ) {
			if ( null !== $id && intval( $id ) !== $post->ID ) {
				$parent_structure = trim( CPTP_Util::get_permalink_structure( $post->post_type ), '/' );
				$parent_dirs      = explode( '/', $parent_structure );
				if ( is_array( $parent_dirs ) ) {
					$last_dir = array_pop( $parent_dirs );
				} else {
					$last_dir = $parent_dirs;
				}

				if ( '%post_id%' === $parent_structure || '%post_id%' === $last_dir ) {
					$permalink = untrailingslashit( $permalink ) . '/attachment/';
				}
			}
		}

		$search  = array();
		$replace = array();

		$replace_tag = $this->create_taxonomy_replace_tag( $post->ID, $permalink );
		$search      = $search + $replace_tag['search'];
		$replace     = $replace + $replace_tag['replace'];

		// from get_permalink.
		$category = '';
		if ( false !== strpos( $permalink, '%category%' ) ) {
			$categories = get_the_category( $post->ID );
			if ( $categories ) {
				$categories      = CPTP_Util::sort_terms( $categories );
				$category_object = reset( $categories );
				// phpcs:ignore
				$category_object = apply_filters( 'post_link_category', $category_object, $categories, $post );

				/**
				 * Filters the category for a post of a custom post type.
				 *
				 * @since 3.4.0
				 *
				 * @param WP_Term   $category_object Selected category.
				 * @param WP_Term[] $categories      Categories set in post.
				 * @param WP_Post   $post            Post object.
				 */
				$category_object = apply_filters( 'cptp_post_link_category', $category_object, $categories, $post );
				$category_object = get_term( $category_object, 'category' );
				$category        = $category_object->slug;
				if ( $category_object->parent ) {
					$parent   = $category_object->parent;
					$category = get_category_parents( $parent, false, '/', true ) . $category;
				}
			}
			// show default category in permalinks, without
			// having to assign it explicitly.
			if ( empty( $category ) ) {
				$default_category = get_term( get_option( 'default_category' ), 'category' );
				$category         = is_wp_error( $default_category ) ? '' : $default_category->slug;
			}
		}

		$author = '';
		if ( false !== strpos( $permalink, '%author%' ) ) {
			$authordata = get_userdata( $post->post_author );
			$author     = $authordata->user_nicename;
		}

		$post_date = strtotime( $post->post_date );
		$permalink = str_replace(
			array(
				'%year%',
				'%monthnum%',
				'%day%',
				'%hour%',
				'%minute%',
				'%second%',
				'%category%',
				'%author%',
			),
			array(
				gmdate( 'Y', $post_date ),
				gmdate( 'm', $post_date ),
				gmdate( 'd', $post_date ),
				gmdate( 'H', $post_date ),
				gmdate( 'i', $post_date ),
				gmdate( 's', $post_date ),
				$category,
				$author,
			),
			$permalink
		);
		$permalink = str_replace( $search, $replace, $permalink );
		$permalink = home_url( $permalink );

		return $permalink;
	}


	/**
	 * Create %tax% -> term
	 *
	 * @param int    $post_id post id.
	 * @param string $permalink permalink uri.
	 *
	 * @return array
	 */
	private function create_taxonomy_replace_tag( $post_id, $permalink ) {
		$search  = array();
		$replace = array();

		$post       = get_post( $post_id );
		$taxonomies = CPTP_Util::get_taxonomies( true );

		// %taxnomomy% -> parent/child
		foreach ( $taxonomies as $taxonomy => $objects ) {
			if ( false !== strpos( $permalink, '%' . $taxonomy . '%' ) ) {
				$terms = get_the_terms( $post_id, $taxonomy );

				if ( $terms && ! is_wp_error( $terms ) ) {
					$parents  = array_map( array( __CLASS__, 'get_term_parent' ), $terms );
					$newTerms = array();
					foreach ( $terms as $key => $term ) {
						if ( ! in_array( $term->term_id, $parents, true ) ) {
							$newTerms[] = $term;
						}
					}

					$term_obj = reset( $newTerms );

					/**
					 * Filters the term for a post of a custom post type.
					 *
					 * @since 3.4.0
					 *
					 * @param WP_Term     $term_obj Selected term.
					 * @param WP_Term[]   $terms    Terms set in post.
					 * @param WP_Taxonomy $taxonomy Taxonomy object.
					 * @param WP_Post     $post     Post object.
					 */
					$term_obj  = apply_filters( 'cptp_post_link_term', $term_obj, $terms, $taxonomy, $post );
					$term_slug = $term_obj->slug;

					if ( isset( $term_obj->parent ) && $term_obj->parent ) {
						$term_slug = CPTP_Util::get_taxonomy_parents_slug( $term_obj->parent, $taxonomy, '/', true ) . $term_slug;
					}
				}

				if ( isset( $term_slug ) ) {
					$search[]  = '%' . $taxonomy . '%';
					$replace[] = $term_slug;
				}
			}
		}

		return array(
			'search'  => $search,
			'replace' => $replace,
		);
	}

	/**
	 * Get parent from term Object
	 *
	 * @param WP_Term $term Term Object.
	 *
	 * @return mixed
	 */
	private static function get_term_parent( $term ) {
		if ( isset( $term->parent ) && $term->parent > 0 ) {
			return $term->parent;
		}
	}


	/**
	 * Fix attachment output
	 *
	 * @param string $link permalink URI.
	 * @param int    $post_id Post ID.
	 *
	 * @return string
	 * @since 0.8.2
	 *
	 * @version 1.0
	 */
	public function attachment_link( $link, $post_id ) {
		/**
		 * WP_Rewrite.
		 *
		 * @var WP_Rewrite $wp_rewrite
		 */
		global $wp_rewrite;

		if ( ! $wp_rewrite->using_permalinks() ) {
			return $link;
		}

		$post = get_post( $post_id );
		if ( ! $post->post_parent ) {
			return $link;
		}

		$post_parent = get_post( $post->post_parent );
		if ( ! $post_parent ) {
			return $link;
		}

		$pt_object = get_post_type_object( $post_parent->post_type );

		if ( empty( $pt_object->rewrite ) ) {
			return $link;
		}

		if ( ! in_array( $post->post_type, CPTP_Util::get_post_types(), true ) ) {
			return $link;
		}

		$permalink = CPTP_Util::get_permalink_structure( $post_parent->post_type );
		$post_type = get_post_type_object( $post_parent->post_type );

		if ( empty( $post_type->_builtin ) ) {
			if ( strpos( $permalink, '%postname%' ) < strrpos( $permalink, '%post_id%' ) && false === strrpos( $link, 'attachment/' ) ) {
				$link = str_replace( $post->post_name, 'attachment/' . $post->post_name, $link );
			}
		}

		return $link;
	}

	/**
	 * Fix taxonomy link outputs.
	 *
	 * @param string $termlink link URI.
	 * @param Object $term Term Object.
	 * @param Object $taxonomy Taxonomy Object.
	 *
	 * @return string
	 * @since 0.6
	 * @version 1.0
	 */
	public function term_link( $termlink, $term, $taxonomy ) {
		/**
		 * WP_Rewrite.
		 *
		 * @var WP_Rewrite $wp_rewrite
		 */
		global $wp_rewrite;

		if ( ! $wp_rewrite->using_permalinks() ) {
			return $termlink;
		}

		if ( CPTP_Util::get_no_taxonomy_structure() ) {
			return $termlink;
		}

		$taxonomy = get_taxonomy( $taxonomy );

		if ( empty( $taxonomy ) ) {
			return $termlink;
		}

		if ( $taxonomy->_builtin ) {
			return $termlink;
		}

		if ( ! $taxonomy->public ) {
			return $termlink;
		}

		$wp_home = rtrim( home_url(), '/' );

		if ( in_array( get_post_type(), $taxonomy->object_type, true ) ) {
			$post_type = get_post_type();
		} else {
			$post_type = $taxonomy->object_type[0];
		}

		$front    = substr( $wp_rewrite->front, 1 );
		$termlink = str_replace( $front, '', $termlink );// remove front.

		$post_type_obj = get_post_type_object( $post_type );

		if ( empty( $post_type_obj ) ) {
			return $termlink;
		}

		if ( ! isset( $post_type_obj->rewrite['slug'] ) || ! isset( $post_type_obj->rewrite['with_front'] ) ) {
			return $termlink;
		}

		$slug       = $post_type_obj->rewrite['slug'];
		$with_front = $post_type_obj->rewrite['with_front'];

		if ( $with_front ) {
			$slug = $front . $slug;
		}

		if ( ! empty( $slug ) ) {
			$termlink = str_replace( $wp_home, $wp_home . '/' . $slug, $termlink );
		}

		if ( false !== $taxonomy->rewrite && ! $taxonomy->rewrite['hierarchical'] ) {
			$termlink = str_replace( $term->slug . '/', CPTP_Util::get_taxonomy_parents_slug( $term->term_id, $taxonomy->name, '/', true ), $termlink );
		}

		return $termlink;
	}

	/**
	 * This filter is needed for WPML's compatibility. It will return
	 * the slug placeholder instead of the original CPT slug.
	 *
	 * @param string  $original_slug The original CPT slug.
	 * @param string  $post_link     The post link.
	 * @param WP_Post $post          The post.
	 *
	 * @return string
	 */
	public function replace_post_slug_with_placeholder( $original_slug, $post_link, $post ) {
		if ( ! in_array( $post->post_type, CPTP_Util::get_post_types(), true ) ) {
			return $original_slug;
		}

		return CPTP_Module_Rewrite::get_slug_placeholder( $post->post_type );
	}
}
