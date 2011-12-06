<?php
/*
Plugin Name: Custom Post Type Permalinks
Plugin URI: http://www.torounit.com
Description:  Add post archives of custom post type and customizable permalinks.
Author: Toro-Unit
Author URI: http://www.torounit.com/plugins/custom-post-type-permalinks/
Version: 0.7.x
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
		$chain .= get_taxonomy_parents( $parent->parent, $taxonomy, $link, $separator, $nicename, $visited );
	}

	if ( $link )
		$chain .= '<a href="' . get_term_link( $parent->term_id, $taxonomy ) . '" title="' . esc_attr( sprintf( __( "View all posts in %s" ), $parent->name ) ) . '">'.$name.'</a>' . $separator;
	else
		$chain .= $name.$separator;
	return $chain;
}





class Custom_Post_Type_Permalinks {

	function init_function(){
		add_action('wp_loaded',array(&$this,'set_archive_rewrite'),99);
		add_action('wp_loaded', array(&$this,'set_rewrite'),100);
		add_filter('post_type_link', array(&$this,'set_permalink'),10,3);

		add_filter('getarchives_where', array(&$this,'get_archives_where'), 10, 2);
		add_filter('get_archives_link', array(&$this,'get_archives_link'));

		add_action('generate_rewrite_rules', array(&$this,'add_tax_rewrite'));
		add_filter('term_link', array(&$this,'set_term_link'),10,3);


		if ( function_exists('register_uninstall_hook') ) {
			register_uninstall_hook(__FILE__, array(&$this,'uninstall_hook_custom_permalink'));
		}

	}
	
	




	//カスタム投稿タイプのアーカイブのリライトルールの追加
	function set_archive_rewrite() {
		$post_types = get_post_types(array("_builtin"=>false));

		foreach ($post_types as $post_type):
			if(!$post_type) continue;
			$permalink = get_option($post_type."_structure");
			$slug = get_post_type_object($post_type)->rewrite["slug"];

			if($slug){
			
				add_rewrite_rule($slug.'/date/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/feed/(feed|rdf|rss|rss2|atom)/?$','index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&feed=$matches[4]&post_type='.$post_type,"top");
				add_rewrite_rule($slug.'/date/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/(feed|rdf|rss|rss2|atom)/?$','index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&feed=$matches[4]&post_type='.$post_type,"top");
				add_rewrite_rule($slug.'/date/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/page/?([0-9]{1,})/?$','index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&paged=$matches[4]&post_type='.$post_type,"top");
				add_rewrite_rule($slug.'/date/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/?$','index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&post_type='.$post_type,"top");
				add_rewrite_rule($slug.'/date/([0-9]{4})/([0-9]{1,2})/feed/(feed|rdf|rss|rss2|atom)/?$','index.php?year=$matches[1]&monthnum=$matches[2]&feed=$matches[3]&post_type='.$post_type,"top");
				add_rewrite_rule($slug.'/date/([0-9]{4})/([0-9]{1,2})/(feed|rdf|rss|rss2|atom)/?$','index.php?year=$matches[1]&monthnum=$matches[2]&feed=$matches[3]&post_type='.$post_type,"top");
				add_rewrite_rule($slug.'/date/([0-9]{4})/([0-9]{1,2})/page/?([0-9]{1,})/?$','index.php?year=$matches[1]&monthnum=$matches[2]&paged=$matches[3]&post_type='.$post_type,"top");
				add_rewrite_rule($slug.'/date/([0-9]{4})/([0-9]{1,2})/?$','index.php?year=$matches[1]&monthnum=$matches[2]&post_type='.$post_type,"top");
				add_rewrite_rule($slug.'/date/([0-9]{4})/feed/(feed|rdf|rss|rss2|atom)/?$','index.php?year=$matches[1]&feed=$matches[2]&post_type='.$post_type,"top");
				add_rewrite_rule($slug.'/date/([0-9]{4})/(feed|rdf|rss|rss2|atom)/?$','index.php?year=$matches[1]&feed=$matches[2]&post_type='.$post_type,"top");
				add_rewrite_rule($slug.'/date/([0-9]{4})/page/?([0-9]{1,})/?$','index.php?year=$matches[1]&paged=$matches[2]&post_type='.$post_type,"top");
				add_rewrite_rule($slug.'/date/([0-9]{4})/?$','index.php?year=$matches[1]&post_type='.$post_type,"top");
				add_rewrite_rule($slug.'/([0-9]{1,})/?$','index.php?p=$matches[1]&post_type='.$post_type,"top");
				add_rewrite_rule($slug.'/?$','index.php?post_type='.$post_type,"top");
			}


			add_rewrite_rule($post_type.'/date/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/feed/(feed|rdf|rss|rss2|atom)/?$','index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&feed=$matches[4]&post_type='.$post_type,"top");
			add_rewrite_rule($post_type.'/date/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/(feed|rdf|rss|rss2|atom)/?$','index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&feed=$matches[4]&post_type='.$post_type,"top");
			add_rewrite_rule($post_type.'/date/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/page/?([0-9]{1,})/?$','index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&paged=$matches[4]&post_type='.$post_type,"top");
			add_rewrite_rule($post_type.'/date/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/?$','index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&post_type='.$post_type,"top");
			add_rewrite_rule($post_type.'/date/([0-9]{4})/([0-9]{1,2})/feed/(feed|rdf|rss|rss2|atom)/?$','index.php?year=$matches[1]&monthnum=$matches[2]&feed=$matches[3]&post_type='.$post_type,"top");
			add_rewrite_rule($post_type.'/date/([0-9]{4})/([0-9]{1,2})/(feed|rdf|rss|rss2|atom)/?$','index.php?year=$matches[1]&monthnum=$matches[2]&feed=$matches[3]&post_type='.$post_type,"top");
			add_rewrite_rule($post_type.'/date/([0-9]{4})/([0-9]{1,2})/page/?([0-9]{1,})/?$','index.php?year=$matches[1]&monthnum=$matches[2]&paged=$matches[3]&post_type='.$post_type,"top");
			add_rewrite_rule($post_type.'/date/([0-9]{4})/([0-9]{1,2})/?$','index.php?year=$matches[1]&monthnum=$matches[2]&post_type='.$post_type,"top");
			add_rewrite_rule($post_type.'/date/([0-9]{4})/feed/(feed|rdf|rss|rss2|atom)/?$','index.php?year=$matches[1]&feed=$matches[2]&post_type='.$post_type,"top");
			add_rewrite_rule($post_type.'/date/([0-9]{4})/(feed|rdf|rss|rss2|atom)/?$','index.php?year=$matches[1]&feed=$matches[2]&post_type='.$post_type,"top");
			add_rewrite_rule($post_type.'/date/([0-9]{4})/page/?([0-9]{1,})/?$','index.php?year=$matches[1]&paged=$matches[2]&post_type='.$post_type,"top");
			add_rewrite_rule($post_type.'/date/([0-9]{4})/?$','index.php?year=$matches[1]&post_type='.$post_type,"top");
			add_rewrite_rule($post_type.'/([0-9]{1,})/?$','index.php?p=$matches[1]&post_type='.$post_type,"top");
			add_rewrite_rule($post_type.'/?$','index.php?post_type='.$post_type,"top");
		endforeach;
	}




	//パーマリンク構造を登録
	function set_rewrite() {
		global $wp_rewrite;
		$post_types = get_post_types(array("_builtin"=>false));
		foreach ($post_types as $post_type):
	
			$permalink = get_option($post_type."_structure");			
			$slug = get_post_type_object($post_type)->rewrite["slug"];
			if(!$permalink){
				$permalink = '/%year%/%monthnum%/%day%/%post_id%/';	
			}
			$permalink = '/%post_type%/'.$permalink;
			
			$permalink = str_replace('%postname%',"%$post_type%",$permalink);
			$permalink = str_replace('//','/',$permalink);
			

			$wp_rewrite->add_rewrite_tag('%post_type%', '([^/]+)','post_type=');

			//タクソノミーの処理
			$taxonomies = get_taxonomies('','objects');
			foreach ( $taxonomies as $key => $taxonomy ) {
				$wp_rewrite->add_rewrite_tag('%'.$key.'%', '([^/]+)',$key.'=');
			}
					
			$wp_rewrite->add_permastruct($post_type,$permalink, false);

		endforeach;
	}
	
	function term_id_asc($a, $b){
	
		if($a->term_id < $b->term_id){
			return -1;
		}else if($a->term_id > $b->term_id){
			return 1;
		}else{
			return 0;
		}
	}


	//個別投稿の出力URLの変更
	function set_permalink($post_link, $post,$leavename) {
		global $wp_rewrite;

		$newlink = $wp_rewrite->get_extra_permastruct($post->post_type);
	
		$newlink = str_replace("%post_type%", $post->post_type, $newlink);
		$newlink = str_replace("%post_id%", $post->ID, $newlink);
		if(!$leavename){
			$newlink = str_replace("%$post->post_type%", $post->post_name, $newlink);
		}
		

		//タクソノミーの処理
		$taxonomies = get_taxonomies(array("show_ui" => true),'objects');
		foreach ( $taxonomies as $taxonomy => $objects ) {
			$terms = get_the_terms($post->ID,$taxonomy);

			if( !empty($terms) ) {
			
				usort($terms, array($this, "term_id_asc"));
				$first_term = array_shift($terms);
				$slug = get_taxonomy_parents( $first_term->term_id,$taxonomy,false,'/', true);
				$slug = substr($slug, 0, (strlen($slug)-1) );

				$newlink = str_replace('%'.$taxonomy.'%',$slug,$newlink);
			}
			$newlink = str_replace('//',"/",$newlink);

			
		}


		
		$user = get_userdata($post->post_author);
		$newlink = str_replace("%author%", $user->user_login, $newlink);

	

		$post_date = strtotime($post->post_date);

		$newlink = str_replace("%year%",date("Y",$post_date), $newlink);
		$newlink = str_replace("%monthnum%",date("m",$post_date), $newlink);
		$newlink = str_replace("%day%",date("d",$post_date), $newlink);
		$newlink = str_replace("%hour%",date("H",$post_date), $newlink);
		$newlink = str_replace("%minute%",date("i",$post_date), $newlink);
		$newlink = str_replace("%second%",date("s",$post_date), $newlink);

		$newlink = str_replace('//',"/",$newlink);
	
		$newlink = home_url(user_trailingslashit($newlink));
		return $newlink;
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

	public $post_type_archives;

	function get_archives_where($where, $r) {
		if ( isset($r['post_type']) ) {
			$this->post_type_archives = $r['post_type'];
			$where = str_replace( '\'post\'', '\'' . $r['post_type'] . '\'', $where );
		}else {
			$this->post_type_archives = '';
		}
		return $where;
	}

	function get_archives_link($link_html) {
		if ( '' != $this->post_type_archives )
			$blog_url = get_bloginfo("url");
		$link_html = str_replace($blog_url,$blog_url.$this->post_type_archives,$link_html);
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

	function add_tax_rewrite(){
		global $wp_rewrite;
		$taxonomies = get_taxonomies(array("_builtin"=>false));
		if(empty($taxonomies)){
			return ;
		}

		foreach ($taxonomies as $taxonomy) :

			$post_types = get_taxonomy($taxonomy)->object_type;
			foreach ($post_types as $post_type){
				$slug = get_post_type_object($post_type)->rewrite["slug"];
				//add taxonomy slug

				add_rewrite_rule($slug.'/'.$taxonomy.'/(.+?)/?$','index.php?taxonomy='.$taxonomy.'&term=$matches[1]');
				add_rewrite_rule($post_type.'/'.$taxonomy.'/(.+?)/?$','index.php?taxonomy='.$taxonomy.'&term=$matches[1]');
			}
		endforeach;
	}

	function set_term_link($termlink,$term,$taxonomy){
		$taxonomy = get_taxonomy($taxonomy);
		if(empty($taxonomy)){
			return $termlink;
		}
		$wp_home = get_option("home");
		$wp_home = $wp_home."/";
		$wp_home = str_replace("//","/",$wp_home);
		$post_type = $taxonomy->object_type[0];
		$slug = get_post_type_object($post_type)->rewrite["slug"];
		return str_replace($wp_home,$wp_home.$slug,$termlink);
	}



	//アンインストール時
	function uninstall_hook_custom_permalink () {
		$post_types = get_post_types(array("_builtin"=>false));
		foreach ($post_types as $post_type):
			delete_option($post_type."_structure");
		endforeach;
	}



}

if(get_option("permalink_structure") != ""){
	$custom_post_type_permalinks = new Custom_Post_Type_Permalinks;
	$custom_post_type_permalinks->init_function();
}




class Custom_Post_Type_Permalinks_Admin {

	function init_function(){
		add_action('init', array(&$this,'load_textdomain'));
		add_action('admin_menu', array(&$this,'admin_menu_custom_permalink'));
	
	}
 	function load_textdomain(){
		load_plugin_textdomain('cptp',false,'custom-post-type-permalinks');	
	}
 
	function admin_menu_custom_permalink () {
		// 設定メニュー下にサブメニューを追加:
		$menuName = __("Permalinks of Custom post type","cptp");
		add_options_page($menuName, $menuName, 'manage_options', __FILE__, array(&$this,'admin_menu_custom_permalink_callback'));
	}
 
	// プラグインページのコンテンツを表示
	function admin_menu_custom_permalink_callback () {
		global $wp_rewrite;
		$wp_rewrite->flush_rules();
	
		// 設定変更画面を表示する
	?>
	<div class="wrap">
		<div class="icon32" id="icon-options-general"></div>
		<h2><?php echo __("Permalinks of Custom post type","cptp"); //カスタム投稿タイプのパーマリンク?></h2>
		<form method="post" action="options.php">
			<?php wp_nonce_field('update-options'); ?>
			<p><?php _e("Setting permalinks of custom post type.","cptp");//カスタム投稿タイプごとのパーマリンク構造を設定できます。?><br />
			<?php _e("The tags you can use is '%year%','%monthnum%','%day%','%hour%','%minute%','%second%','%postname%','%post_id%','%author%','%category%','%tag%' and '%{custom_taxonomy_slug}%(Replace the taxomomy term)'.","cptp");?><br />
			<?php _e("If you don't entered permalink structure, permalink is configured /%year%/%monthnum%/%day%/%post_id%/.","cptp");?>
			</p>
			<table class="form-table">
			<?php
			$post_types = get_post_types(array("_builtin"=>false));
			$page_options = "";
			foreach ($post_types as $post_type):
			
			?>
				<tr valign="top"><th scope="row"><?php echo $post_type;?></th><td>/<?php echo get_post_type_object($post_type)->rewrite["slug"];?>&nbsp;<?php ?><input type="text" name="<?php echo $post_type."_structure";?>" value="<?php echo get_option($post_type."_structure"); ?>" class="regular-text code" />
</td></tr>
			<?php
			$page_options .= $post_type."_structure".",";
			endforeach;?>
			</table>
			<input type="hidden" name="action" value="update" />
			<?php $page_options = rtrim($page_options, ",");?>

			<input type="hidden" name="page_options" value="<?php echo $page_options;?>" />
			<p class="submit">
				<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
			</p>
		</form>
	</div>

<?php

	}

}
$custom_post_type_permalinks_admin = new Custom_Post_Type_Permalinks_Admin;
$custom_post_type_permalinks_admin->init_function();
