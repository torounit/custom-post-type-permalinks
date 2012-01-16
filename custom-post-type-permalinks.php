<?php
/*
Plugin Name: Custom Post Type Permalinks
Plugin URI: http://www.torounit.com
Description:  Add post archives of custom post type and customizable permalinks.
Author: Toro-Unit
Author URI: http://www.torounit.com/plugins/custom-post-type-permalinks/
Version: 0.7.4
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

	function add_hooks() {
		add_action('wp_loaded',array(&$this,'set_archive_rewrite'),99);
		add_action('wp_loaded', array(&$this,'set_rewrite'),100);
		add_filter('post_type_link', array(&$this,'set_permalink'),10,3);
		add_filter('getarchives_where', array(&$this,'get_archives_where'), 10, 2);
		add_filter('get_archives_link', array(&$this,'get_archives_link'));
		add_action('wp_loaded', array(&$this,'add_tax_rewrite'));
		add_filter('term_link', array(&$this,'set_term_link'),10,3);
	}

	static function uninstall_hook() {
		if ( function_exists('register_uninstall_hook') ) {
			register_uninstall_hook(__FILE__, array(&$this,'uninstall_hook_custom_permalink'));
		}
	}

	function get_taxonomy_parents( $id, $taxonomy = 'category', $link = false, $separator = '/', $nicename = false, $visited = array() ) {
		$chain = '';
		$parent = &get_term( $id, $taxonomy, OBJECT, 'raw');
		if ( is_wp_error( $parent ) )
			return $parent;

		if ( $nicename )
			$name = $parent->slug;
		else
			$name = $parent->name;

		if ( $parent->parent && ( $parent->parent != $parent->term_id ) && !in_array( $parent->parent, $visited ) ) {
			$visited[] = $parent->parent;
			$chain .= $this->get_taxonomy_parents( $parent->parent, $taxonomy, $link, $separator, $nicename, $visited );
		}

		if ( $link )
			$chain .= '<a href="' . get_term_link( $parent->term_id, $taxonomy ) . '" title="' . esc_attr( sprintf( __( "View all posts in %s" ), $parent->name ) ) . '">'.$name.'</a>' . $separator;
		else
			$chain .= $name.$separator;
		return $chain;
	}



	function set_archive_rewrite() {
		$post_types = get_post_types( array('_builtin'=>false, 'publicly_queryable'=>true,'show_ui' => true) );

		foreach ( $post_types as $post_type ):
			if( !$post_type ) continue;
			$permalink = get_option( $post_type.'_structure' );
			$slug = get_post_type_object($post_type)->rewrite['slug'];

			if( $slug ){
				add_rewrite_rule($slug.'/date/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/feed/(feed|rdf|rss|rss2|atom)/?$','index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&feed=$matches[4]&post_type='.$post_type,'top');
				add_rewrite_rule($slug.'/date/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/(feed|rdf|rss|rss2|atom)/?$','index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&feed=$matches[4]&post_type='.$post_type,'top');
				add_rewrite_rule($slug.'/date/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/page/?([0-9]{1,})/?$','index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&paged=$matches[4]&post_type='.$post_type,'top');
				add_rewrite_rule($slug.'/date/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/?$','index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&post_type='.$post_type,'top');
				add_rewrite_rule($slug.'/date/([0-9]{4})/([0-9]{1,2})/feed/(feed|rdf|rss|rss2|atom)/?$','index.php?year=$matches[1]&monthnum=$matches[2]&feed=$matches[3]&post_type='.$post_type,'top');
				add_rewrite_rule($slug.'/date/([0-9]{4})/([0-9]{1,2})/(feed|rdf|rss|rss2|atom)/?$','index.php?year=$matches[1]&monthnum=$matches[2]&feed=$matches[3]&post_type='.$post_type,'top');
				add_rewrite_rule($slug.'/date/([0-9]{4})/([0-9]{1,2})/page/?([0-9]{1,})/?$','index.php?year=$matches[1]&monthnum=$matches[2]&paged=$matches[3]&post_type='.$post_type,'top');
				add_rewrite_rule($slug.'/date/([0-9]{4})/([0-9]{1,2})/?$','index.php?year=$matches[1]&monthnum=$matches[2]&post_type='.$post_type,'top');
				add_rewrite_rule($slug.'/date/([0-9]{4})/feed/(feed|rdf|rss|rss2|atom)/?$','index.php?year=$matches[1]&feed=$matches[2]&post_type='.$post_type,'top');
				add_rewrite_rule($slug.'/date/([0-9]{4})/(feed|rdf|rss|rss2|atom)/?$','index.php?year=$matches[1]&feed=$matches[2]&post_type='.$post_type,'top');
				add_rewrite_rule($slug.'/date/([0-9]{4})/page/?([0-9]{1,})/?$','index.php?year=$matches[1]&paged=$matches[2]&post_type='.$post_type,'top');
				add_rewrite_rule($slug.'/date/([0-9]{4})/?$','index.php?year=$matches[1]&post_type='.$post_type,'top');
				add_rewrite_rule($slug.'/author/([^/]+)/?$','index.php?author=$matches[1]&post_type='.$post_type,'top');
				add_rewrite_rule($slug.'/?$','index.php?post_type='.$post_type,'top');
			}

			add_rewrite_rule($post_type.'/date/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/feed/(feed|rdf|rss|rss2|atom)/?$','index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&feed=$matches[4]&post_type='.$post_type,'top');
			add_rewrite_rule($post_type.'/date/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/(feed|rdf|rss|rss2|atom)/?$','index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&feed=$matches[4]&post_type='.$post_type,'top');
			add_rewrite_rule($post_type.'/date/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/page/?([0-9]{1,})/?$','index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&paged=$matches[4]&post_type='.$post_type,'top');
			add_rewrite_rule($post_type.'/date/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/?$','index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&post_type='.$post_type,'top');
			add_rewrite_rule($post_type.'/date/([0-9]{4})/([0-9]{1,2})/feed/(feed|rdf|rss|rss2|atom)/?$','index.php?year=$matches[1]&monthnum=$matches[2]&feed=$matches[3]&post_type='.$post_type,'top');
			add_rewrite_rule($post_type.'/date/([0-9]{4})/([0-9]{1,2})/(feed|rdf|rss|rss2|atom)/?$','index.php?year=$matches[1]&monthnum=$matches[2]&feed=$matches[3]&post_type='.$post_type,'top');
			add_rewrite_rule($post_type.'/date/([0-9]{4})/([0-9]{1,2})/page/?([0-9]{1,})/?$','index.php?year=$matches[1]&monthnum=$matches[2]&paged=$matches[3]&post_type='.$post_type,'top');
			add_rewrite_rule($post_type.'/date/([0-9]{4})/([0-9]{1,2})/?$','index.php?year=$matches[1]&monthnum=$matches[2]&post_type='.$post_type,'top');
			add_rewrite_rule($post_type.'/date/([0-9]{4})/feed/(feed|rdf|rss|rss2|atom)/?$','index.php?year=$matches[1]&feed=$matches[2]&post_type='.$post_type,'top');
			add_rewrite_rule($post_type.'/date/([0-9]{4})/(feed|rdf|rss|rss2|atom)/?$','index.php?year=$matches[1]&feed=$matches[2]&post_type='.$post_type,'top');
			add_rewrite_rule($post_type.'/date/([0-9]{4})/page/?([0-9]{1,})/?$','index.php?year=$matches[1]&paged=$matches[2]&post_type='.$post_type,'top');
			add_rewrite_rule($post_type.'/date/([0-9]{4})/?$','index.php?year=$matches[1]&post_type='.$post_type,'top');
			add_rewrite_rule($post_type.'/date/([0-9]{4})/?$','index.php?author=$matches[1]&post_type='.$post_type,'top');
			add_rewrite_rule($post_type.'/?$','index.php?post_type='.$post_type,'top');
		endforeach;
	}
	//rewrite_tagの追加
	function set_rewrite() {
		global $wp_rewrite;

		$post_types = get_post_types( array('_builtin'=>false, 'publicly_queryable'=>true,'show_ui' => true) );

		foreach ( $post_types as $post_type ):

			$permalink = get_option($post_type.'_structure');

			if( !$permalink ){
				$permalink = '/%year%/%monthnum%/%day%/%post_id%/';
			}

			$permalink = str_replace('%postname%','%'.$post_type.'%',$permalink);
			$permalink = str_replace('%post_id%','%'.$post_type.'_id%',$permalink);

			$slug = get_post_type_object($post_type)->rewrite['slug'];
			if( !$slug ) {
				$slug = $post_type;
			}

			$permalink = '/'.$slug.'/'.$permalink;
			$permalink = $permalink.'/%'.$post_type.'_page%';
			$permalink = str_replace('//','/',$permalink);

			$wp_rewrite->add_rewrite_tag('%post_type%', '([^/]+)','post_type=');
			$wp_rewrite->add_rewrite_tag('%'.$post_type.'_id%', '([0-9]{1,})','post_type='.$post_type.'&p=');
			$wp_rewrite->add_rewrite_tag('%'.$post_type.'_page%', '/?([0-9]{1,}?)/?',"page=");
			$wp_rewrite->add_permastruct($post_type,$permalink, false);

		endforeach;

		$taxonomies = get_taxonomies(array("show_ui" => true, "_builtin" => false),'objects');
		foreach ( $taxonomies as $taxonomy => $objects ):
			$wp_rewrite->add_rewrite_tag("%$taxonomy%", '(.+?)',"$taxonomy=");
		endforeach;

		$wp_rewrite->use_verbose_page_rules = true;
	}

	function set_permalink( $post_link, $post,$leavename ) {
		global $wp_rewrite;
		$draft_or_pending = isset( $post->post_status ) && in_array( $post->post_status, array( 'draft', 'pending', 'auto-draft' ) );
		if( $draft_or_pending and !$leavename )
			return $post_link;

		$post_type = $post->post_type;
		$permalink = $wp_rewrite->get_extra_permastruct($post_type);

		$permalink = str_replace('%post_type%', $post->post_type, $permalink);
		$permalink = str_replace('%'.$post_type.'_id%', $post->ID, $permalink);
		$permalink = str_replace('%'.$post_type.'_page%', "", $permalink);

		if( !$leavename ){
			$permalink = str_replace('%'.$post_type.'%', $post->post_name, $permalink);
		}

		$taxonomies = get_taxonomies( array('show_ui' => true),'objects');
		foreach ( $taxonomies as $taxonomy => $objects ) {
			if ( strpos($permalink, "%$taxonomy%") !== false ) {
				$terms = get_the_terms($post->ID,$taxonomy);

				if ( $terms ) {
					usort($terms, '_usort_terms_by_ID'); // order by ID
					$term = $terms[0]->slug;

					if ( $parent = $terms[0]->parent ) {
						$term = $this->get_taxonomy_parents($parent,$taxonomy, false, '/', true) . $term;
					}
				}
				$permalink = str_replace("%$taxonomy%", $term, $permalink);
			}
		}

		$user = get_userdata($post->post_author);
		$permalink = str_replace("%author%", $user->user_nicename, $permalink);

		$post_date = strtotime($post->post_date);
		$permalink = str_replace("%year%",date("Y",$post_date), $permalink);
		$permalink = str_replace("%monthnum%",date("m",$post_date), $permalink);
		$permalink = str_replace("%day%",date("d",$post_date), $permalink);
		$permalink = str_replace("%hour%",date("H",$post_date), $permalink);
		$permalink = str_replace("%minute%",date("i",$post_date), $permalink);
		$permalink = str_replace("%second%",date("s",$post_date), $permalink);

		$permalink = str_replace('//',"/",$permalink);
		$permalink = home_url(user_trailingslashit($permalink));
		return $permalink;
	}

	/**
	 *
	 *wp_get_archives fix for custom post
	 *
	 *How To Use:
	 *
	 *	$arg = 'type=monthly';
	 *	if(is_post_type_archive()) {
	 *		$arg .= '&post_type='.get_query_var( 'post_type' );
	 *	}
	 *	wp_get_archives($arg);
	 *
	 *
	 *
	 */

	public $get_archives_where_r;
	function get_archives_where( $where, $r ) {
		$this->get_archives_where_r = $r;
		if ( isset($r['post_type']) ) {
			$where = str_replace( '\'post\'', '\'' . $r['post_type'] . '\'', $where );
		}
		return $where;
	}

	function get_archives_link( $link_html ) {
		if (isset($this->get_archives_where_r['post_type'])  and  $this->get_archives_where_r['type'] != 'postbypost'){
			$blog_url = get_bloginfo("url");
			$blog_url = rtrim($blog_url,"/");
			$link_html = str_replace($blog_url,$blog_url.'/'.$this->get_archives_where_r['post_type'].'/date',$link_html);
		}

		return $link_html;
	}

	/**
	 *
	 *fix permalink custom taxonomy
	 *
	 *Ex:
	 *	example.org/posttype/taxonomy/term/
	 *
	 */

	function add_tax_rewrite() {
		global $wp_rewrite;
		$taxonomies = get_taxonomies(array( '_builtin' => false));
		if(empty($taxonomies)){
			return ;
		}

		foreach ($taxonomies as $taxonomy) :
			$post_types = get_taxonomy($taxonomy)->object_type;
			foreach ($post_types as $post_type){
				$slug = get_post_type_object($post_type)->rewrite["slug"];
				//add taxonomy slug
				add_rewrite_rule($slug.'/'.$taxonomy.'/(.+?)/?$','index.php?taxonomy='.$taxonomy.'&term=$matches[1]','top');
				add_rewrite_rule($post_type.'/'.$taxonomy.'/(.+?)/?$','index.php?taxonomy='.$taxonomy.'&term=$matches[1]','top');
			}
		endforeach;
	}

	function set_term_link( $termlink, $term, $taxonomy ) {
		$taxonomy = get_taxonomy($taxonomy);
		if( $taxonomy->_builtin ) {
			return $termlink;
		}

		if(empty($taxonomy)){
			return $termlink;
		}
		$wp_home = get_option('home');
		$wp_home = rtrim($wp_home,'/');

		$post_type = $taxonomy->object_type[0];
		$slug = get_post_type_object($post_type)->rewrite['slug'];
		return str_replace($wp_home,$wp_home.'/'.$slug,$termlink);
	}

	static function uninstall_hook_custom_permalink () {
		$post_types = get_post_types( array('_builtin'=>false, 'publicly_queryable'=>true, 'show_ui' => true) );
		foreach ( $post_types as $post_type ):
			delete_option( $post_type.'_structure' );
		endforeach;
	}

}

class Custom_Post_Type_Permalinks_Admin {

	function add_hooks() {
		add_action('init', array(&$this,'load_textdomain'));
		add_action('admin_init', array(&$this,'settings_api_init'),10);
		add_action('admin_init', array(&$this,'set_rules'),50);
	}

 	function load_textdomain() {
		load_plugin_textdomain('cptp',false,'custom-post-type-permalinks');
	}

	function settings_api_init() {
	 	add_settings_section('setting_section',
			__("Permalink Setting for custom post type",'cptp'),
			array(&$this,'setting_section_callback_function'),
			'permalink');

		$post_types = get_post_types( array('_builtin'=>false, 'publicly_queryable'=>true, 'show_ui' => true) );
		foreach ($post_types as $post_type):
			if(isset($_POST['submit'])){
				if( strpos($_POST['_wp_http_referer'],'options-permalink.php') !== FALSE ) {

					$structure = trim(esc_attr($_POST[$post_type.'_structure']));#get setting

					#default permalink structure
					if( !$structure ) {
						$structure = '/%year%/%monthnum%/%day%/%post_id%/';
					}
					$structure = str_replace('//','/','/'.$structure);# first "/"

					#last "/"
					$lastString = substr(trim(esc_attr($_POST['permalink_structure'])),-1);
					$structure = rtrim($structure,'/');
					if ( $lastString == '/') {
						$structure = $structure.'/';
					}

					update_option($post_type.'_structure', $structure );
				}

			}

	 		add_settings_field($post_type.'_structure',
				$post_type,
				array(&$this,'setting_callback_function'),
				'permalink',
				'setting_section',
				$post_type.'_structure');

		 	register_setting('permalink',$post_type.'_structure');
		endforeach;
	}

	function set_rules() {
		global $wp_rewrite;
		$wp_rewrite->flush_rules();
	}

	function setting_section_callback_function() {
		?>
			<p><?php _e("Setting permalinks of custom post type.",'cptp');?><br />
			<?php _e("The tags you can use is WordPress Structure Tags and '%{custom_taxonomy_slug}%'.",'cptp');?><br />
			<?php _e("%{custom_taxonomy_slug}% is replaced the taxonomy's term.'.",'cptp');?></p>

			<p><?php _e("Presence of the trailing '/' is unified into a standard permalink structure setting.",'cptp');?><br />
			<?php _e("If you don't entered permalink structure, permalink is configured /%year%/%monthnum%/%day%/%post_id%/.",'cptp');?>
			</p>
		<?php
	}

	function setting_callback_function(  $option  ) {
		$post_type = str_replace('_structure',"" ,$option);
		$slug = get_post_type_object($post_type)->rewrite['slug'];
		if( !$slug ) {
			$slug = $post_type;
		}
		echo '/'.$slug.' <input name="'.$option.'" id="'.$option.'" type="text" class="regular-text code" value="' . get_option($option) .'" />';
	}
}


$custom_post_type_permalinks = new Custom_Post_Type_Permalinks;
if(get_option("permalink_structure") != ""){ $custom_post_type_permalinks->add_hooks(); }
$custom_post_type_permalinks->uninstall_hook();
$custom_post_type_permalinks_admin = new Custom_Post_Type_Permalinks_Admin;
$custom_post_type_permalinks_admin->add_hooks();

