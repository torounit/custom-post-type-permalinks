<?php


/**
 *
 * CPTP_Permalink
 *
 * Override Permalinks
 * @package Custom_Post_Type_Permalinks
 * @since 0.9.4
 *
 * */


class CPTP_Module_Permalink extends CPTP_Module {


	public function add_hook() {
		if(get_option( "permalink_structure") != "") {
			add_filter( 'post_type_link', array( $this,'post_type_link'), 10, 4 );
			add_filter( 'term_link', array( $this,'term_link'), 10, 3 );
			add_filter( 'attachment_link', array( $this, 'attachment_link'), 20 , 2);
		}
	}

	/**
	 *
	 * Fix permalinks output.
	 *
	 * @param String $post_link
	 * @param Object $post 投稿情報
	 * @param String $leavename 記事編集画面でのみ渡される
	 *
	 * @version 2.0
	 *
	 */
	public function post_type_link( $post_link, $post, $leavename ) {
		global $wp_rewrite;
		$draft_or_pending = isset( $post->post_status ) && in_array( $post->post_status, array( 'draft', 'pending', 'auto-draft' ) );
		if( $draft_or_pending and !$leavename )
			return $post_link;



		$post_type = $post->post_type;
		$permalink = $wp_rewrite->get_extra_permastruct( $post_type );

		$permalink = str_replace( '%post_id%', $post->ID, $permalink );
		$permalink = str_replace( '%'.$post_type.'_slug%', get_post_type_object( $post_type )->rewrite['slug'], $permalink );



		//親ページが有るとき。
		$parentsDirs = "";
		if( !$leavename ){
			$postId = $post->ID;
			while ($parent = get_post($postId)->post_parent) {
				$parentsDirs = get_post($parent)->post_name."/".$parentsDirs;
				$postId = $parent;
			}
		}

		$permalink = str_replace( '%'.$post_type.'%', $parentsDirs.'%'.$post_type.'%', $permalink );

		if( !$leavename ){
			$permalink = str_replace( '%'.$post_type.'%', $post->post_name, $permalink );
		}

		//%post_id%/attachment/%attachement_name%;
		//画像の編集ページでのリンク
		if( isset($_GET["post"]) && $_GET["post"] != $post->ID ) {
			$parent_structure = trim( CPTP_Util::get_permalink_structure( $this->post_type ), "/");
			$parent_dirs = explode( "/", $parent_structure );
			if(is_array($parent_dirs)) {
				$last_dir = array_pop( $parent_dirs );
			}
			else {
				$last_dir = $parent_dirs;
			}

			if( "%post_id%" == $parent_structure or "%post_id%" == $last_dir ) {
				$permalink = $permalink."/attachment/";
			};
		}

		$search = array();
		$replace = array();

		$replace_tag = $this->create_taxonomy_replace_tag( $post->ID , $permalink );
		$search = $search + $replace_tag["search"];
		$replace = $replace + $replace_tag["replace"];


		$user = get_userdata( $post->post_author );
		if(isset($user->user_nicename)) {
			$permalink = str_replace( "%author%", $user->user_nicename, $permalink );
		}

		$post_date = strtotime( $post->post_date );
		$permalink = str_replace(array(
			"%year%",
			"%monthnum%",
			"%day%",
			"%hour%",
			"%minute%",
			"%second%"
		), array(
			date("Y",$post_date),
			date("m",$post_date),
			date("d",$post_date),
			date("H",$post_date),
			date("i",$post_date),
			date("s",$post_date)
		), $permalink );
		$permalink = str_replace($search, $replace, $permalink);
		$permalink = rtrim( home_url(),"/")."/".ltrim( $permalink ,"/" );

		return $permalink;
	}



	/**
	 *
	 * create %tax% -> term
	 *
	 * */
	private function create_taxonomy_replace_tag( $post_id , $permalink ) {
		$search = array();
		$replace = array();

		$taxonomies = CPTP_Util::get_taxonomies( true );

		//%taxnomomy% -> parent/child
		//運用でケアすべきかも。

		foreach ( $taxonomies as $taxonomy => $objects ) {
			$term = null;
			if ( strpos($permalink, "%$taxonomy%") !== false ) {
				$terms = wp_get_post_terms( $post_id, $taxonomy, array('orderby' => 'term_id'));

				if( $terms and !is_wp_error($terms) ) {

					$parents = array_map( array(__CLASS__, 'get_term_parent'), $terms); //親の一覧
					$newTerms = array();
					foreach ($terms as $key => $term) {
						if( !in_array($term->term_id, $parents )) {
							$newTerms[] = $term;
						}
					}


					//このブロックだけで良いはず。
					$term_obj = reset($newTerms); //最初のOBjectのみを対象。
					$term_slug = $term_obj->slug;

					if(isset($term_obj->parent) and $term_obj->parent != 0) {
						$term_slug = CPTP_Util::get_taxonomy_parents( $term_obj->parent,$taxonomy, false, '/', true ) . $term_slug;
					}

				}


				if( isset($term_slug) ) {
					$search[] = "%$taxonomy%";
					$replace[] = $term_slug;
				}

			}
		}
		return array("search" => $search, "replace" => $replace );
	}

	private static function get_term_parent($term) {
		if(isset($term->parent) and $term->parent > 0) {
			return $term->parent;
		}
	}



	/**
	 *
	 * fix attachment output
	 *
	 * @version 1.0
	 * @since 0.8.2
	 *
	 */

	public function attachment_link( $link , $postID ) {
		$post = get_post( $postID );
		if (!$post->post_parent){
			return $link;
		}
		$post_parent = get_post( $post->post_parent );
		$permalink = CPTP_Util::get_permalink_structure( $post_parent->post_type );
		$post_type = get_post_type_object( $post_parent->post_type );

		if( $post_type->_builtin == false ) {
			if(strpos( $permalink, "%postname%" ) < strrpos( $permalink, "%post_id%" ) && strrpos( $permalink, "attachment/" ) === FALSE ){
				$link = str_replace($post->post_name , "attachment/".$post->post_name, $link);
			}
		}

		return $link;
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

		if( get_option('no_taxonomy_structure') ) {
			return  $termlink;
		}

		$taxonomy = get_taxonomy($taxonomy);
		if( $taxonomy->_builtin )
			return $termlink;

		if( empty($taxonomy) )
			return $termlink;

		$wp_home = rtrim( home_url(), '/' );

		if(in_array(get_post_type(), $taxonomy->object_type)){
			$post_type = get_post_type();
		}
		else {
			$post_type = $taxonomy->object_type[0];
		}
		$post_type_obj = get_post_type_object($post_type);
		$slug = $post_type_obj->rewrite['slug'];
		$with_front = $post_type_obj->rewrite['with_front'];
		$front = substr( $wp_rewrite->front, 1 );
		$termlink = str_replace($front,"",$termlink);

		if( $with_front ) {
			$slug = $front.$slug;
		}

		$termlink = str_replace( $wp_home, $wp_home."/".$slug, $termlink );

		if ( ! $taxonomy->rewrite['hierarchical'] ) {
			$termlink = str_replace( $term->slug.'/', CPTP_Util::get_taxonomy_parents( $term->term_id,$taxonomy->name, false, '/', true ), $termlink );
		}

		return $termlink;
	}

}
