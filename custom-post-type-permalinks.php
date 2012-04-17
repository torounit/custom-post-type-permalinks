<?php
/*
Plugin Name: Custom Post Type Permalinks
Plugin URI: http://www.torounit.com
Description:  Add post archives of custom post type and customizable permalinks.
Author: Toro-Unit
Author URI: http://www.torounit.com/plugins/custom-post-type-permalinks/
Version: 0.7.8
Text Domain: cptp
Domain Path: /
*/



/*  Copyright 2011 Toro_Unit (email : mail@torounit.com)

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

/* This plugin don't support Multisite yet.*/


class Custom_Post_Type_Permalinks {

	static public $default_structure = '/%year%/%monthnum%/%day%/%post_id%/';

	public function  __construct () {
		add_action('wp_loaded',array(&$this,'set_archive_rewrite'),99);
		add_action('wp_loaded', array(&$this,'set_rewrite'),100);
		add_action('wp_loaded', array(&$this,'add_tax_rewrite'));

		if(get_option("permalink_structure") != "") {
			add_filter('post_type_link', array(&$this,'set_permalink'),10,3);

			add_filter('getarchives_where', array(&$this,'get_archives_where'), 10, 2);
			add_filter('get_archives_link', array(&$this,'get_archives_link'),20,1);
			add_filter('term_link', array(&$this,'set_term_link'),10,3);
		}
	}

	public function get_taxonomy_parents( $id, $taxonomy = 'category', $link = false, $separator = '/', $nicename = false, $visited = array() ) {
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

	public function set_archive_rewrite() {
		$post_types = get_post_types( array('_builtin'=>false, 'publicly_queryable'=>true,'show_ui' => true) );

		foreach ( $post_types as $post_type ):
			if( !$post_type ) continue;
			$permalink = get_option( $post_type.'_structure' );
			$slug = get_post_type_object($post_type)->rewrite['slug'];

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
			add_rewrite_rule( $slug.'/author/([^/]+)/?$', 'index.php?author=$matches[1]&post_type='.$post_type, 'top' );
			add_rewrite_rule( $slug.'/?$', 'index.php?post_type='.$post_type, 'top' );

			if( $slug != $post_type ){
				add_rewrite_rule( $post_type.'/date/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/feed/(feed|rdf|rss|rss2|atom)/?$', 'index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&feed=$matches[4]&post_type='.$post_type, 'top' );
				add_rewrite_rule( $post_type.'/date/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/(feed|rdf|rss|rss2|atom)/?$', 'index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&feed=$matches[4]&post_type='.$post_type, 'top' );
				add_rewrite_rule( $post_type.'/date/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/page/?([0-9]{1,})/?$', 'index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&paged=$matches[4]&post_type='.$post_type, 'top' );
				add_rewrite_rule( $post_type.'/date/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/?$', 'index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&post_type='.$post_type, 'top' );
				add_rewrite_rule( $post_type.'/date/([0-9]{4})/([0-9]{1,2})/feed/(feed|rdf|rss|rss2|atom)/?$', 'index.php?year=$matches[1]&monthnum=$matches[2]&feed=$matches[3]&post_type='.$post_type, 'top' );
				add_rewrite_rule( $post_type.'/date/([0-9]{4})/([0-9]{1,2})/(feed|rdf|rss|rss2|atom)/?$', 'index.php?year=$matches[1]&monthnum=$matches[2]&feed=$matches[3]&post_type='.$post_type, 'top' );
				add_rewrite_rule( $post_type.'/date/([0-9]{4})/([0-9]{1,2})/page/?([0-9]{1,})/?$', 'index.php?year=$matches[1]&monthnum=$matches[2]&paged=$matches[3]&post_type='.$post_type, 'top' );
				add_rewrite_rule( $post_type.'/date/([0-9]{4})/([0-9]{1,2})/?$', 'index.php?year=$matches[1]&monthnum=$matches[2]&post_type='.$post_type, 'top' );
				add_rewrite_rule( $post_type.'/date/([0-9]{4})/feed/(feed|rdf|rss|rss2|atom)/?$', 'index.php?year=$matches[1]&feed=$matches[2]&post_type='.$post_type, 'top' );
				add_rewrite_rule( $post_type.'/date/([0-9]{4})/(feed|rdf|rss|rss2|atom)/?$', 'index.php?year=$matches[1]&feed=$matches[2]&post_type='.$post_type, 'top' );
				add_rewrite_rule( $post_type.'/date/([0-9]{4})/page/?([0-9]{1,})/?$', 'index.php?year=$matches[1]&paged=$matches[2]&post_type='.$post_type, 'top' );
				add_rewrite_rule( $post_type.'/date/([0-9]{4})/?$', 'index.php?year=$matches[1]&post_type='.$post_type, 'top' );
				add_rewrite_rule( $post_type.'/author/([0-9]{4})/?$', 'index.php?author=$matches[1]&post_type='.$post_type, 'top' );
				add_rewrite_rule( $post_type.'/?$', 'index.php?post_type='.$post_type, 'top' );
			}
		endforeach;
	}

	public function set_rewrite() {
		global $wp_rewrite;

		$post_types = get_post_types( array('_builtin'=>false, 'publicly_queryable'=>true,'show_ui' => true) );
		foreach ( $post_types as $post_type ):
			$permalink = get_option( $post_type.'_structure' );

			if( !$permalink )
				$permalink = self::$default_structure;

			$permalink = str_replace( '%postname%', '%'.$post_type.'%', $permalink );
			$permalink = str_replace( '%post_id%', '%'.$post_type.'_id%', $permalink );

			$slug = get_post_type_object($post_type)->rewrite['slug'];

			if( !$slug )
				$slug = $post_type;

			$permalink = '/'.$slug.'/'.$permalink;
			$permalink = $permalink.'/%'.$post_type.'_page%';
			$permalink = str_replace( '//', '/', $permalink );

			$wp_rewrite->add_rewrite_tag( '%post_type%', '([^/]+)', 'post_type=' );
			$wp_rewrite->add_rewrite_tag( '%'.$post_type.'_id%', '([0-9]{1,})','post_type='.$post_type.'&p=' );
			$wp_rewrite->add_rewrite_tag( '%'.$post_type.'_page%', '/?([0-9]{1,}?)/?',"page=" );
			//test
			if(is_post_type_hierarchical($post_type)) {
				$wp_rewrite->add_rewrite_tag( '%'.$post_type.'%', '(?:[^/]+/){1}([^/]+)/?','name=' );
			}
			$wp_rewrite->add_permastruct( $post_type, $permalink, false );

		endforeach;

		$taxonomies = get_taxonomies( array("show_ui" => true, "_builtin" => false), 'objects' );
		foreach ( $taxonomies as $taxonomy => $objects ):
			$wp_rewrite->add_rewrite_tag( "%$taxonomy%", '(.+?)', "$taxonomy=" );
		endforeach;

		$wp_rewrite->use_verbose_page_rules = true;
	}

	public function set_permalink( $post_link, $post,$leavename ) {
		global $wp_rewrite;
		$draft_or_pending = isset( $post->post_status ) && in_array( $post->post_status, array( 'draft', 'pending', 'auto-draft' ) );
		if( $draft_or_pending and !$leavename )
			return $post_link;

		$post_type = $post->post_type;
		$permalink = $wp_rewrite->get_extra_permastruct( $post_type );

		$permalink = str_replace( '%post_type%', $post->post_type, $permalink );
		$permalink = str_replace( '%'.$post_type.'_id%', $post->ID, $permalink );
		$permalink = str_replace( '%'.$post_type.'_page%', "", $permalink );

		$parentsDirs = "";
		$postId = $post->ID;
		while ($parent = get_post($postId)->post_parent) {
			$parentsDirs = get_post($parent)->post_name."/".$parentsDirs;
			$postId = $parent;
		}

		$permalink = str_replace( '%'.$post_type.'%', $parentsDirs.'%'.$post_type.'%', $permalink );


		if( !$leavename ){

			$permalink = str_replace( '%'.$post_type.'%', $post->post_name, $permalink );
		}


		$taxonomies = get_taxonomies( array('show_ui' => true),'objects' );

		foreach ( $taxonomies as $taxonomy => $objects ) {

			if ( strpos($permalink, "%$taxonomy%") !== false ) {
				$terms = get_the_terms( $post->ID, $taxonomy );

				if ( $terms ) {
					usort($terms, '_usort_terms_by_ID'); // order by ID
					$term = $terms[0]->slug;

					if ( $parent = $terms[0]->parent )
						$term = $this->get_taxonomy_parents( $parent,$taxonomy, false, '/', true ) . $term;
				}

				if(isset($term)) {
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

		$permalink = home_url( user_trailingslashit( $permalink ) );
		$str = rtrim( preg_replace("/%[a-z,_]*%/","",get_option("permalink_structure")) ,'/');
		return $permalink = str_replace($str, "", $permalink );

	}

	/**
	 *wp_get_archives fix for custom post
	 *Ex:wp_get_archives('&post_type='.get_query_var( 'post_type' ));
	 */

	public $get_archives_where_r;

	public function get_archives_where( $where, $r ) {
		$this->get_archives_where_r = $r;

		if ( isset($r['post_type']) )
			$where = str_replace( '\'post\'', '\'' . $r['post_type'] . '\'', $where );

		return $where;
	}

	public function get_archives_link( $link ) {
		//$slug = get_post_type_object($this->get_archives_where_r['post_type'])->rewrite['slug'];
		if (isset($this->get_archives_where_r['post_type'])  and  $this->get_archives_where_r['type'] != 'postbypost'){
			$blog_url = get_bloginfo("url");


			// /archive/%post_id%
			if($str = rtrim( preg_replace("/%[a-z,_]*%/","",get_option("permalink_structure")) ,'/')) {
				$ret_link = str_replace($str, '/'.'%link_dir%', $link);
			}else{
				$blog_url = rtrim($blog_url,"/");
				$ret_link = str_replace($blog_url,$blog_url.'/'.'%link_dir%',$link);
			}
			$link_dir = $this->get_archives_where_r['post_type'];

			if(!strstr($link,'/date/')){
				$link_dir = $link_dir .'/date';
			}

			$ret_link = str_replace('%link_dir%',$link_dir,$ret_link);

			return $ret_link;

		}

		return $link;


	}



	/**
	 * fix permalink custom taxonomy
	 */
	public function add_tax_rewrite() {
		if(get_option('no_taxonomy_structure'))
			return false;

		global $wp_rewrite;
		$taxonomies = get_taxonomies(array( '_builtin' => false));
		if(empty($taxonomies))
			return false;

		foreach ($taxonomies as $taxonomy) :
			$post_types = get_taxonomy($taxonomy)->object_type;

			foreach ($post_types as $post_type):
				$slug = get_post_type_object($post_type)->rewrite["slug"];
				//add taxonomy slug
				add_rewrite_rule( $slug.'/'.$taxonomy.'/(.+?)/?$', 'index.php?'.$taxonomy.'=$matches[1]', 'top' );
				add_rewrite_rule( $slug.'/'.$taxonomy.'/(.+?)/feed/(feed|rdf|rss|rss2|atom)/?$', 'index.php?'.$taxonomy.'=$matches[1]&feed=$matches[2]', 'top' );
				add_rewrite_rule( $slug.'/'.$taxonomy.'/(.+?)/(feed|rdf|rss|rss2|atom)/?$', 'index.php?'.$taxonomy.'=$matches[1]&feed=$matches[2]', 'top' );
				add_rewrite_rule( $slug.'/'.$taxonomy.'/(.+?)/page/?([0-9]{1,})/?$', 'index.php?'.$taxonomy.'=$matches[1]&paged=$matches[2]', 'top' );

				if( $slug != $post_type ){
					add_rewrite_rule( $post_type.'/'.$taxonomy.'/(.+?)/?$', 'index.php?'.$taxonomy.'=$matches[1]', 'top' );
					add_rewrite_rule( $post_type.'/'.$taxonomy.'/(.+?)/feed/(feed|rdf|rss|rss2|atom)/?$', 'index.php?'.$taxonomy.'=$matches[1]&feed=$matches[2]', 'top' );
					add_rewrite_rule( $post_type.'/'.$taxonomy.'/(.+?)/(feed|rdf|rss|rss2|atom)/?$', 'index.php?'.$taxonomy.'=$matches[1]&feed=$matches[2]', 'top' );
					add_rewrite_rule( $post_type.'/'.$taxonomy.'/(.+?)/page/?([0-9]{1,})/?$', 'index.php?'.$taxonomy.'=$matches[1]&paged=$matches[2]', 'top' );
				}

			endforeach;

		endforeach;
	}

	public function set_term_link( $termlink, $term, $taxonomy ) {
		if( get_option('no_taxonomy_structure') )
			return  $termlink;

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
		$str = rtrim( preg_replace("/%[a-z_]*%/","",get_option("permalink_structure")) ,'/');
		return str_replace($str, "", $termlink );

	}
}

class Custom_Post_Type_Permalinks_Admin {

	public function  __construct () {
		add_action('init', array(&$this,'load_textdomain'));
		add_action('admin_init', array(&$this,'settings_api_init'),30);
	}

	public function load_textdomain() {
		load_plugin_textdomain('cptp',false,'custom-post-type-permalinks');
	}

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
						$structure = Custom_Post_Type_Permalinks::$default_structure;

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
			<?php _e("The tags you can use is WordPress Structure Tags and '%{custom_taxonomy_slug}%'.",'cptp');?><br />
			<?php _e("%{custom_taxonomy_slug}% is replaced the taxonomy's term.'.",'cptp');?></p>

			<p><?php _e("Presence of the trailing '/' is unified into a standard permalink structure setting.",'cptp');?><br />
			<?php _e("If you don't entered permalink structure, permalink is configured /%year%/%monthnum%/%day%/%post_id%/.",'cptp');?>
			</p>
		<?php
	}

	public function setting_structure_callback_function(  $option  ) {
		$post_type = str_replace('_structure',"" ,$option);
		$slug = get_post_type_object($post_type)->rewrite['slug'];
		if( !$slug )
			$slug = $post_type;

		echo '/'.$slug.' <input name="'.$option.'" id="'.$option.'" type="text" class="regular-text code" value="' . get_option($option) .'" />';
	}

	public function setting_no_tax_structure_callback_function(){
		echo '<input name="no_taxonomy_structure" id="no_taxonomy_structure" type="checkbox" value="1" class="code" ' . checked( false, get_option('no_taxonomy_structure'),false) . ' />　';
		_e("If you check,The custom taxonomy's permalinks is example.com/post_type/taxonomy/term.","cptp");
	}

}

new Custom_Post_Type_Permalinks;
new Custom_Post_Type_Permalinks_Admin;
