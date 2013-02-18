<?php
/*
Plugin Name: Custom Post Type Permalinks
Plugin URI: http://www.torounit.com
Description:  Add post archives of custom post type and customizable permalinks.
Author: Toro_Unit
Author URI: http://www.torounit.com/plugins/custom-post-type-permalinks/
Version: 0.8.7.5
Text Domain: cptp
License: GPL2 or later
Domain Path: /
*/


/*  Copyright 2012 Toro_Unit (email : mail@torounit.com)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/



/**
 *
 * Custom Post Type Permalinks
 *
 *
 */
class Custom_Post_Type_Permalinks {

	public $version = "0.8.7.1";
	public $default_structure = '/%postname%/';

	/**
	 *
	 * Add Action & filter hooks.
	 * @version 2.0
	 */
	public function add_hook () {

		add_action( 'update_option_cptp_version', array(&$this, 'update_rules') );
		add_action( 'plugins_loaded', array(&$this,'check_version') );

		add_action( 'wp_loaded', array(&$this,'set_archive_rewrite'), 99 );
		add_action( 'wp_loaded', array(&$this,'set_rewrite'), 100 );
		add_action( 'wp_loaded', array(&$this,'add_tax_rewrite') );


		if(get_option( "permalink_structure") != "") {
			add_filter( 'post_type_link', array(&$this,'set_permalink'), 10, 3 );
			add_filter( 'getarchives_join', array(&$this,'get_archives_join'), 10, 2 ); // [steve]
			add_filter( 'getarchives_where', array(&$this,'get_archives_where'), 10 , 2 );
			add_filter( 'get_archives_link', array(&$this,'get_archives_link'), 20, 1 );
			add_filter( 'term_link', array(&$this,'set_term_link'), 10, 3 );
			add_filter( 'attachment_link', array(&$this, 'attachment_link'), 20 , 2);
		}

		add_action( 'init', array(&$this,'load_textdomain') );
		add_action( 'init', array(&$this, 'update_rules') );
		add_action( 'admin_init', array(&$this,'settings_api_init'), 30 );
		add_action( 'admin_enqueue_scripts', array(&$this,'enqueue_css_js') );
		add_action( 'admin_footer', array(&$this,'pointer_js') );


	}


	public function check_version() {
		$version = get_option('cptp_version', 0);
		if($version != $this->version) {
			update_option('cptp_version', $this->version);
		}
	}

	/**
	 *
	 * Get Custom Taxonomies parents.
	 * @version 1.0
	 *
	 */
	private function get_taxonomy_parents( $id, $taxonomy = 'category', $link = false, $separator = '/', $nicename = false, $visited = array() ) {
		$chain = '';
		$parent = &get_term( $id, $taxonomy, OBJECT, 'raw');
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
			$chain .= $this->get_taxonomy_parents( $parent->parent, $taxonomy, $link, $separator, $nicename, $visited );
		}

		if ( $link ) {
			$chain .= '<a href="' . get_term_link( $parent->term_id, $taxonomy ) . '" title="' . esc_attr( sprintf( __( "View all posts in %s" ), $parent->name ) ) . '">'.$name.'</a>' . $separator;
		}else {
			$chain .= $name.$separator;
		}
		return $chain;
	}



	/**
	 *
	 * Add rewrite rules for archives.
	 * @version 1.0
	 *
	 */
	public function set_archive_rewrite() {
		$post_types = get_post_types( array('_builtin'=>false, 'publicly_queryable'=>true,'show_ui' => true) );

		foreach ( $post_types as $post_type ):
			if( !$post_type )
				continue;
			$permalink = get_option( $post_type.'_structure' );
			$post_type_obj = get_post_type_object($post_type);
			$slug = $post_type_obj->rewrite['slug'];
			if( !$slug )
				$slug = $post_type;

			if( is_string( $post_type_obj->has_archive ) ){
				$slug = $post_type_obj->has_archive;
			};

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
			add_rewrite_rule( $slug.'/page/?([0-9]{1,})/?$', 'index.php?paged=$matches[1]&post_type='.$post_type, 'top' );
			add_rewrite_rule( $slug.'/?$', 'index.php?post_type='.$post_type, 'top' );
		endforeach;
	}



	/**
	 *
	 * Add Rewrite rule for single posts.
	 * @version 2.0
	 *
	 *
	 */
	public function set_rewrite() {
		global $wp_rewrite;

		$post_types = get_post_types( array('_builtin'=>false, 'publicly_queryable'=>true,'show_ui' => true) );
		foreach ( $post_types as $post_type ):
			$permalink = get_option( $post_type.'_structure' );

			if( !$permalink ) {
				$permalink = $this->default_structure;
			}

			$permalink = str_replace( '%postname%', '%'.$post_type.'%', $permalink );
			$permalink = str_replace( '%post_id%', '%'.$post_type.'_id%', $permalink );

			$slug = get_post_type_object($post_type)->rewrite['slug'];

			if( !$slug ) {
				$slug = $post_type;
			}

			$permalink = '/'.$slug.'/'.$permalink;

			if( strpos( $permalink , '%'.$post_type.'%' ) === false ) {
				$permalink = $permalink.'/%'.$post_type.'_page%';
			}

			$permalink = str_replace( '//', '/', $permalink );

			$wp_rewrite->add_rewrite_tag( '%post_type%', '([^/]+)', 'post_type=' );
			$wp_rewrite->add_rewrite_tag( '%'.$post_type.'_id%', '([0-9]{1,})','post_type='.$post_type.'&p=' );
			$wp_rewrite->add_rewrite_tag( '%'.$post_type.'_page%', '([0-9]{1,}?)',"page=" );

			$param = array(
				'with_front' => false,
				'ep_mask' => EP_ALL,
				'paged' => true,
				'feed' => true,
				'forcomments' => true,
				'walk_dirs' => true,
				'endpoints' => true,
			);

			//$wp_rewrite->generate_rewrite_rules( $permalink, EP_NONE, true, true, true,true);
			$wp_rewrite->add_permastruct( $post_type, $permalink, $param );
		endforeach;

		$taxonomies = get_taxonomies( array("show_ui" => true, "_builtin" => false), 'objects' );
		foreach ( $taxonomies as $taxonomy => $objects ):
			$wp_rewrite->add_rewrite_tag( "%$taxonomy%", '(.+?)', "$taxonomy=" );
		endforeach;

		$wp_rewrite->use_verbose_page_rules = true;
	}


	/**
	 *
	 * fix attachment output
	 *
	 * @version 1
	 * @since 0.8.2
	 *
	 */


	public function attachment_link( $link , $postID ) {
		$post = get_post( $postID );
		$post_parent = get_post( $post->post_parent );
		$permalink = get_option( $post_parent->post_type.'_structure' );
		$post_type = get_post_type_object( $post_parent->post_type );

		if( $post_type->_builtin == false ) {
			if(strpos( $permalink, "%postname%" ) < strrpos( $permalink, "%post_id%" )){
				$link = str_replace($post->post_name , "attachment/".$post->post_name, $link);
			}
		}

		return $link;
	}



	/**
	 *
	 * Fix permalinks output.
	 *
	 * @param String $post_link
	 * @param Object $post 投稿情報
	 * @param String $leavename 記事編集画面でのみ渡される
	 *
	 * @version 1.3
	 *
	 */
	public function set_permalink( $post_link, $post, $leavename ) {
		global $wp_rewrite;
		$draft_or_pending = isset( $post->post_status ) && in_array( $post->post_status, array( 'draft', 'pending', 'auto-draft' ) );
		if( $draft_or_pending and !$leavename )
			return $post_link;

		$post_type = $post->post_type;
		$permalink = $wp_rewrite->get_extra_permastruct( $post_type );


		$permalink = str_replace( '%post_type%', get_post_type_object($post->post_type)->rewrite['slug'], $permalink );
		$permalink = str_replace( '%'.$post_type.'_id%', $post->ID, $permalink );
		$permalink = str_replace( '%'.$post_type.'_page%', "", $permalink );
		$permalink = str_replace( '%'.$post_type.'_cpage%', "", $permalink );

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
		if(  isset($_GET["post"]) &&$_GET["post"] != $post->ID ) {

			if( "%post_id%" == array_pop(explode("/",get_option( $post_type.'_structure' )))) {
				$permalink = $permalink."/attachment/";
			};
		}


		$taxonomies = get_taxonomies( array('show_ui' => true),'objects' );

		foreach ( $taxonomies as $taxonomy => $objects ) {
			if ( strpos($permalink, "%$taxonomy%") !== false ) {
				$terms = get_the_terms( $post->ID, $taxonomy );

				if ( $terms ) {
					usort($terms, '_usort_terms_by_ID'); // order by ID
					$term = $terms[0]->slug;

					if ( $parent = $terms[0]->parent ) {
						$term = $this->get_taxonomy_parents( $parent,$taxonomy, false, '/', true ) . $term;
					}

				}

				if( isset($term) ) {
					$permalink = str_replace( "%$taxonomy%", $term, $permalink );
				}
			}
		}

		$user = get_userdata( $post->post_author );
		$permalink = str_replace( "%author%", $user->user_nicename, $permalink );

		$post_date = strtotime( $post->post_date );
		$permalink = str_replace( "%year%", 	date("Y",$post_date), $permalink );
		$permalink = str_replace( "%monthnum%", date("m",$post_date), $permalink );
		$permalink = str_replace( "%day%", 		date("d",$post_date), $permalink );
		$permalink = str_replace( "%hour%", 	date("H",$post_date), $permalink );
		$permalink = str_replace( "%minute%", 	date("i",$post_date), $permalink );
		$permalink = str_replace( "%second%", 	date("s",$post_date), $permalink );

		$permalink = str_replace('//', "/", $permalink );

		return $permalink = home_url( user_trailingslashit( $permalink ) );
		//$str = rtrim( preg_replace("/%[a-z,_]*%/","",get_option("permalink_structure")) ,'/');
		//return $permalink = str_replace($str, "", $permalink );

	}



	/**
	 *
	 * wp_get_archives fix for custom post
	 * Ex:wp_get_archives('&post_type='.get_query_var( 'post_type' ));
	 * @version 2.0
	 *
	 */

	public $get_archives_where_r;

	// function modified by [steve]
	public function get_archives_where( $where, $r ) {
		$this->get_archives_where_r = $r;
		if ( isset($r['post_type']) ) {
			$where = str_replace( '\'post\'', '\'' . $r['post_type'] . '\'', $where );
		}

		if(isset($r['taxonomy']) && is_array($r['taxonomy']) ){
			global $wpdb;
			$where = $where . " AND $wpdb->term_taxonomy.taxonomy = '".$r['taxonomy']['name']."' AND $wpdb->term_taxonomy.term_id = '".$r['taxonomy']['termid']."'";
		}

		return $where;
	}



	//function added by [steve]
	/**
	 *
	 * get_archive_join
	 * @author Steve
	 * @since 0.8
	 * @version 1.0
	 *
	 *
	 */
	public function get_archives_join( $join, $r ) {
		global $wpdb;
		$this->get_archives_where_r = $r;
		if(isset($r['taxonomy']) && is_array($r['taxonomy']) )
		$join = $join . " INNER JOIN $wpdb->term_relationships ON ($wpdb->posts.ID = $wpdb->term_relationships.object_id) INNER JOIN $wpdb->term_taxonomy ON ($wpdb->term_relationships.term_taxonomy_id = $wpdb->term_taxonomy.term_taxonomy_id)";

		return $join;
	}



	/**
	 *
	 * get_arcihves_link
	 * @version 2.0
	 *
	 */
	public function get_archives_link( $link ) {
		$c = isset($this->get_archives_where_r['taxonomy']) && is_array($this->get_archives_where_r['taxonomy']) ? $this->get_archives_where_r['taxonomy'] : "";  //[steve]
		$t = $this->get_archives_where_r['post_type']; // [steve] [*** bug fixing]
		$this->get_archives_where_r['post_type'] = isset($this->get_archives_where_r['post_type_slug']) ? $this->get_archives_where_r['post_type_slug'] : $t; // [steve] [*** bug fixing]

		if (isset($this->get_archives_where_r['post_type'])  and  $this->get_archives_where_r['type'] != 'postbypost'){
			$blog_url = rtrim( get_bloginfo("url") ,'/');

			//remove .ext
			$str = preg_replace("/\.[a-z,_]*/","",get_option("permalink_structure"));

			if($str = rtrim( preg_replace("/%[a-z,_]*%/","",$str) ,'/')) { // /archive/%post_id%
				$ret_link = str_replace($str, '/'.'%link_dir%', $link);
			}else{
				$blog_url = preg_replace('/https?:\/\//', '', $blog_url);
				$ret_link = str_replace($blog_url,$blog_url.'/'.'%link_dir%',$link);
			}

			if(empty($c) ){    // [steve]
				$link_dir = $this->get_archives_where_r['post_type'];
			}
			else{   // [steve]
				$c['name'] = ($c['name'] == 'category' && get_option('category_base')) ? get_option('category_base') : $c['name'];
				$link_dir = $this->get_archives_where_r['post_type']."/".$c['name']."/".$c['termslug'];
			}

			if(!strstr($link,'/date/')){
				$link_dir = $link_dir .'/date';
			}

			$ret_link = str_replace('%link_dir%',$link_dir,$ret_link);
			$this->get_archives_where_r['post_type'] = $t; // [steve] reverting post_type to previous value
			return $ret_link;
		}
		$this->get_archives_where_r['post_type'] = $t;	// [steve] reverting post_type to previous value

		return $link;
	}



	/**
	 *
	 * Add rewrite rules for custom taxonomies.
	 * @since 0.6
	 * @version 2.1
	 *
	 */
	public function add_tax_rewrite() {
		if(get_option('no_taxonomy_structure')) {
			return false;
		}


		global $wp_rewrite;
		$taxonomies = get_taxonomies(array( '_builtin' => false));
		$taxonomies['category'] = 'category';

		if(empty($taxonomies)) {
			return false;
		}

		foreach ($taxonomies as $taxonomy) :
			$taxonomyObject = get_taxonomy($taxonomy);
			$post_types = $taxonomyObject->object_type;

			foreach ($post_types as $post_type):
				$post_type_obj = get_post_type_object($post_type);
				$slug = $post_type_obj->rewrite['slug'];
				if(!$slug) {
					$slug = $post_type;
				}

				if(is_string($post_type_obj->has_archive)) {
					$slug = $post_type_obj->has_archive;
				};

				if ( $taxonomy == 'category' ){
					$taxonomypat = ($cb = get_option('category_base')) ? $cb : $taxonomy;
					$tax = 'category_name';
				} else {
					// Edit by [Xiphe]
				 	if (isset($taxonomyObject->rewrite['slug'])) {
				 		$taxonomypat = $taxonomyObject->rewrite['slug'];
				 	} else {
				 		$taxonomypat = $taxonomy;
				 	}
				 	// [Xiphe] stop

				 	$tax = $taxonomy;
				}


				//add taxonomy slug
				add_rewrite_rule( $slug.'/'.$taxonomypat.'/(.+?)/page/?([0-9]{1,})/?$', 'index.php?'.$tax.'=$matches[1]&paged=$matches[2]', 'top' );
				add_rewrite_rule( $slug.'/'.$taxonomypat.'/(.+?)/feed/(feed|rdf|rss|rss2|atom)/?$', 'index.php?'.$tax.'=$matches[1]&feed=$matches[2]', 'top' );
				add_rewrite_rule( $slug.'/'.$taxonomypat.'/(.+?)/(feed|rdf|rss|rss2|atom)/?$', 'index.php?'.$tax.'=$matches[1]&feed=$matches[2]', 'top' );
				add_rewrite_rule( $slug.'/'.$taxonomypat.'/([^/]+)/?$', 'index.php?'.$tax.'=$matches[1]', 'top' );  // modified by [steve] [*** bug fixing]

				// below rules were added by [steve]
				add_rewrite_rule( $taxonomypat.'/([^/]+)/date/([0-9]{4})/([0-9]{1,2})/?$', 'index.php?'.$tax.'=$matches[1]&year=$matches[2]&monthnum=$matches[3]&post_type='.$post_type, 'top' );
				add_rewrite_rule( $taxonomypat.'/([^/]+)/date/([0-9]{4})/([0-9]{1,2})/page/?([0-9]{1,})/?$', 'index.php?'.$tax.'=$matches[1]&year=$matches[2]&monthnum=$matches[3]&paged=$matches[4]&post_type='.$post_type, 'top' );
				add_rewrite_rule( $slug.'/'.$taxonomypat.'/([^/]+)/date/([0-9]{4})/([0-9]{1,2})/?$', 'index.php?'.$tax.'=$matches[1]&year=$matches[2]&monthnum=$matches[3]&post_type='.$post_type, 'top' );
				add_rewrite_rule( $slug.'/'.$taxonomypat.'/([^/]+)/date/([0-9]{4})/([0-9]{1,2})/page/?([0-9]{1,})/?$', 'index.php?'.$tax.'=$matches[1]&year=$matches[2]&monthnum=$matches[3]&paged=$matches[4]&post_type='.$post_type, 'top' );

			endforeach;
		endforeach;
	}




	/**
	 *
	 * Fix taxonomy link outputs.
	 * @since 0.6
	 * @version 1.0
	 *
	 */
	public function set_term_link( $termlink, $term, $taxonomy ) {
		if( get_option('no_taxonomy_structure') ) {
			return  $termlink;
		}

		$taxonomy = get_taxonomy($taxonomy);
		if( $taxonomy->_builtin )
			return $termlink;

		if( empty($taxonomy) )
			return $termlink;

		$wp_home = rtrim( get_option('home'), '/' );

		$post_type = $taxonomy->object_type[0];
		$slug = get_post_type_object($post_type)->rewrite['slug'];


		//$termlink = str_replace( $term->slug.'/', $this->get_taxonomy_parents( $term->term_id,$taxonomy->name, false, '/', true ), $termlink );
		$termlink = str_replace( $wp_home, $wp_home.'/'.$slug, $termlink );
		$str = rtrim( preg_replace("/%[a-z_]*%/","",get_option("permalink_structure")) ,'/');//remove with front
		return str_replace($str, "", $termlink );

	}



	/**
	 *
	 * load textdomain
	 * @since 0.6.2
	 *
	 */
	public function load_textdomain() {
		load_plugin_textdomain('cptp',false,'custom-post-type-permalinks');
	}



	/**
	 *
	 * Add hook flush_rules
	 * @since 0.7.9
	 *
	 */
	public function update_rules() {

		$post_types = get_post_types( array('_builtin'=>false, 'publicly_queryable'=>true, 'show_ui' => true) );
		$type_count = count($post_types);
		$i = 0;
		foreach ($post_types as $post_type):
			add_action('update_option_'.$post_type.'_structure',array(&$this,'flush_rules'),10,2);
		endforeach;
		add_action('update_option_no_taxonomy_structure',array(&$this,'flush_rules'),10,2);
	}



	/**
	 * Flush rules
	 *
	 * @since 0.7.9
	 *
	 */

	public function flush_rules(){
		$this->add_tax_rewrite();
		$this->set_archive_rewrite();
		$this->set_rewrite();
		flush_rewrite_rules();
	}



	/**
	 *
	 * Setting Init
	 * @since 0.7
	 *
	 */
	public function settings_api_init() {
		add_settings_section('cptp_setting_section',
			__("Permalink Setting for custom post type",'cptp'),
			array(&$this,'setting_section_callback_function'),
			'permalink'
		);

		$post_types = get_post_types( array('_builtin'=>false, 'publicly_queryable'=>true, 'show_ui' => true) );
		foreach ($post_types as $post_type):
			if(isset($_POST['submit'])){
				if( strpos($_POST['_wp_http_referer'],'options-permalink.php') !== FALSE ) {

					$structure = trim(esc_attr($_POST[$post_type.'_structure']));#get setting

					#default permalink structure
					if( !$structure )
						$structure = $this->default_structure;

					$structure = str_replace('//','/','/'.$structure);# first "/"

					#last "/"
					$lastString = substr(trim(esc_attr($_POST['permalink_structure'])),-1);
					$structure = rtrim($structure,'/');

					if ( $lastString == '/')
						$structure = $structure.'/';

					update_option($post_type.'_structure', $structure );
				}
			}

			add_settings_field($post_type.'_structure',
				$post_type,
				array(&$this,'setting_structure_callback_function'),
				'permalink',
				'cptp_setting_section',
				$post_type.'_structure'
			);

			register_setting('permalink',$post_type.'_structure');
		endforeach;

		add_settings_field(
			'no_taxonomy_structure',
			__("Use custom permalink of custom taxonomy archive.",'cptp'),
			array(&$this,'setting_no_tax_structure_callback_function'),
			'permalink',
			'cptp_setting_section'
		);

		register_setting('permalink','no_taxonomy_structure');

		if(isset($_POST['submit']) && isset($_POST['_wp_http_referer']) && strpos($_POST['_wp_http_referer'],'options-permalink.php') !== FALSE ) {

			if(!isset($_POST['no_taxonomy_structure'])){
				$set = true;
			}else {
				$set = false;
			}
			update_option('no_taxonomy_structure', $set);
		}
	}

	public function setting_section_callback_function() {
		?>
			<p><?php _e("Setting permalinks of custom post type.",'cptp');?><br />
			<?php _e("The tags you can use is WordPress Structure Tags and '%\"custom_taxonomy_slug\"%'. (e.g. %actors%)",'cptp');?><br />
			<?php _e("%\"custom_taxonomy_slug\"% is replaced the taxonomy's term.'.",'cptp');?></p>

			<p><?php _e("Presence of the trailing '/' is unified into a standard permalink structure setting.",'cptp');?>
			<?php _e("If you don't entered permalink structure, permalink is configured /%postname%/'.",'cptp');?>
			</p>
		<?php
	}

	public function setting_structure_callback_function(  $option  ) {
		$post_type = str_replace('_structure',"" ,$option);
		$slug = get_post_type_object($post_type)->rewrite['slug'];
		if( !$slug )
			$slug = $post_type;

		$value = get_option($option);
		if( !$value )
			$value = $this->default_structure;

		echo '<code>'.home_url().'/'.$slug.'</code> <input name="'.$option.'" id="'.$option.'" type="text" class="regular-text code" value="' . $value .'" />';
	}

	public function setting_no_tax_structure_callback_function(){
		echo '<input name="no_taxonomy_structure" id="no_taxonomy_structure" type="checkbox" value="1" class="code" ' . checked( false, get_option('no_taxonomy_structure'),false) . ' /> ';
		$txt = __("If you check,The custom taxonomy's permalinks is <code>%s/post_type/taxonomy/term</code>.","cptp");
		printf($txt , home_url());
	}



	/**
	 *
	 * enqueue CSS and JS
	 * @since 0.8.5
	 *
	 */
	public function enqueue_css_js() {
		wp_enqueue_style('wp-pointer');
		wp_enqueue_script('wp-pointer');
	}



	/**
	 *
	 * add js for pointer
	 * @since 0.8.5
	 */
	public function pointer_js() {
		if(!is_network_admin()) {
			$dismissed = explode(',', get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ));
			if(array_search('cptp_pointer0871', $dismissed) === false){
				$content = __("<h3>Custom Post Type Permalinks</h3><p>From <a href='options-permalink.php'>Permalinks</a>, set a custom permalink for each post type.</p>", "cptp");
			?>
				<script type="text/javascript">
				jQuery(function($) {

					$("#menu-settings .wp-has-submenu").pointer({
						content: "<?php echo $content;?>",
						position: {"edge":"left","align":"center"},
						close: function() {
							$.post('admin-ajax.php', {
								action:'dismiss-wp-pointer',
								pointer: 'cptp_pointer0871'
							})

						}
					}).pointer("open");
				});
				</script>
			<?php
			}
		}
	}
}

$custom_post_type_permalinks = new Custom_Post_Type_Permalinks;
$custom_post_type_permalinks = apply_filters('custom_post_type_permalinks', $custom_post_type_permalinks);
$custom_post_type_permalinks->add_hook();
?>