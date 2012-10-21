<?php
/*
Plugin Name: WP Worker Catalog
Plugin URI: http://claustrophobiccoder.wordpress.com/category/wp-worker-catalog/
Description: This plugin is created for manpower placement agencies to have an online catalog of their people, so that employers can browse and see if their skill sets fit their needs. Note: Needs Wordpress 3.1 or higher to work.
Author: Ardee Aram
Version: 0.3
Author URI: http://claustrophobiccoder.wordpress.com/
License: GPL 3.0
*/

/**
  *  Copyright 2011 Ardee Aram
  *  This file is part of WP Worker Catalog.
  *
  *  WP Worker Catalog is free software: you can redistribute it and/or modify
  *  it under the terms of the GNU General Public License as published by
  *  the Free Software Foundation, either version 3 of the License, or
  *  at your option) any later version.
  *
  * WP Worker Catalog is distributed in the hope that it will be useful,
  * but WITHOUT ANY WARRANTY; without even the implied warranty of
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  * GNU General Public License for more details.
  *
  * You should have received a copy of the GNU General Public License
  * along with WP Worker Catalog.  If not, see <http://www.gnu.org/licenses/>.
 */

/** Requires **/
require_once('manpower_header.inc.php');
require_once('Worker.class.php');
require_once('WorkerController.class.php');
require_once('manpower_search_widget.inc.php');

/** Actions **/
add_action('init', array('ManpowerPlugin', 'register'));
add_action('init', array('ManpowerPlugin', 'enqueue_scripts'));
add_action('save_post', array('ManpowerPlugin', 'save'));
add_action('widgets_init', array('ManpowerPlugin', 'register_search_widget'));
add_action('admin_menu', array('ManpowerPlugin', 'options_page'));				//using the Settings API
add_action('admin_init', array('ManpowerPlugin', 'options_settings'));
add_action('wp_ajax_interested', 'manpower_interested');
add_action('wp_ajax_nopriv_interested', 'manpower_interested');

/** Filters **/
add_filter('wp_mail_content_type','manpower_content_type');
add_filter('the_content', 'manpower_the_content');
//add_filter( 'map_meta_cap', array('ManpowerPlugin', 'map_meta_cap'), 10, 4 );

register_activation_hook(__FILE__, array('ManpowerPlugin', 'install'));

/*
 * All methods below are methods that connect to WordPress via hooks and filters.
 */

//TODO: Move all methods inside here to allow more freedom in giving names
//this also gives us the freedom to give simpler names
/**
 * @author ardee
 *
 */
class ManpowerPlugin
{
	public static function register()
	{
		$capability_type = get_option('mp_capability_type') ? get_option('mp_capability_type') : "post";
		
		
		$post_type_settings = array(
				"label" => __("Workers", MANPOWER_LANG),
				"labels" => array(
						"singular_name" => __("Worker", MANPOWER_LANG),
						"add_new_item" => __("Add New Worker", MANPOWER_LANG),
						"edit_item" => __("Update Worker", MANPOWER_LANG),
						"view_item" => __("View Worker Details", MANPOWER_LANG),
						"search_items" => __("Search Worker", MANPOWER_LANG),
						"not_found" => __("No Workers Found", MANPOWER_LANG),
				),
					
				"description" => "Catalog of available workers of a manpower agency",
				"public" => true,
				"supports" => array('title'),
				"rewrite" => array("slug" => get_option('mp_worker_slug') ?  get_option('mp_worker_slug') : MANPOWER_DEFAULT_WORKER_SLUG ),
				"register_meta_box_cb" => "_register_manpower_meta_box_cb"

		);
		
		//Allow Capabilities
		if ($capability_type != "post")
		{
			$post_type_settings["capability_type"] = "$capability_type";
			$post_type_settings['map_meta_cap'] = true;
		}
		
		register_post_type(MANPOWER, $post_type_settings);
		
		//var_dump($GLOBALS['wp_post_types']['manpower']->cap);
		//wp_die("");
				
	}
	
	public static function install()
	{
		//upon installation, install the default catalgo slug.
		manpower_create_page_with_slug(MANPOWER_DEFAULT_CATALOG_SLUG);
		update_option('mp_catalog_slug', MANPOWER_DEFAULT_CATALOG_SLUG);
		update_option('mp_worker_slug', MANPOWER_DEFAULT_WORKER_SLUG);		
	}
	
	public static function enqueue_scripts()
	{
		$manpower_css_url_path = plugins_url('css/manpower.css', __FILE__);
		wp_enqueue_style('manpower', $manpower_css_url_path);
		
		$manpower_js_url_path = plugins_url('js/manpower.js', __FILE__);
		wp_enqueue_script('manpower', $manpower_js_url_path, array("jquery"));
		wp_localize_script('manpower', 'manpower', array
				(
						"manpower_post_type" => MANPOWER
				));		
	}
	
	public static function save($post_id)
	{
		$post = get_post($post_id);
		
		/** this function should not touch other post_types **/
		if ($post->post_type !== MANPOWER) return;
		
		/** auto-draft status is used for newly created posts **/
		if ($post->post_status == "auto-draft") return;
		
		/*
		 * do not do anything on AJAX requests (i.e., autosave and quick-edit
		 		* Why? AJAX requests on saving do NOT include custom post types.
		 		* This causes the custom post types to go blank.
		 		*/
		if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) return;
		
		
		$worker = new Worker($post_id);
		$worker->get_data($_POST, $_FILES);
		$worker->save();
	}
	
	public static function register_search_widget()
	{
		return register_widget('Manpower_Search_Widget');		
	}
	
	public static function options_page()
	{
		add_options_page(MANPOWER_TITLE, MANPOWER_TITLE, 'manage_options', MANPOWER, 'manpower_options_page_callback' );
	}
	
	public static function options_settings()
	{
		register_setting(MANPOWER, 'mp_catalog_slug', 'manpower_create_page_with_slug');
		register_setting(MANPOWER, 'mp_worker_slug');
		register_setting(MANPOWER, 'mp_email_to_notify');
		register_setting(MANPOWER, 'mp_notify_workers');
		register_setting(MANPOWER, 'mp_capability_type');		
	}
	
	/**
	 * Props to http://justintadlock.com/archives/2010/07/10/meta-capabilities-for-custom-post-types
	 * @param unknown_type $caps
	 * @param unknown_type $cap
	 * @param unknown_type $user_id
	 * @param unknown_type $args
	 * @return multitype:string NULL 
	 */
	public static function map_meta_cap($caps, $cap, $user_id, $args)
	{
		//wp_die(json_encode($args));
		

		if($cap == 'edit_post')
		{
			//echo "<h1>Edit</h1>";
			//wp_die($user_id);
			
		}
		
		if ($user_id == 2 && strpos($cap, "manpower") !== false)
		{
			//wp_die(json_encode($cap));
			//$caps[] = "edit_posts";
			$caps = array();
		}
		
		//return $caps;
		
		
		
		/* If editing, deleting, or reading a movie, get the post and post type object. */
		//if ( 'edit_manpower' == $cap || 'delete_manpower' == $cap || 'read_manpower' == $cap )
		//{
		//	$post = get_post( $args[0] );
		//	$post_type = get_post_type_object( $post->post_type );
		
			/* Set an empty array for the caps. */
			//$caps = array();
	//	/}
		
		//brute force
		//$caps = array();
		//$caps[] = $post_type->cap->edit_posts;
		//$caps[] = $post_type->cap->delete_posts;
		//$caps[] = 'read';
		
		//wp_die(json_encode($caps));
		
		//return $caps;
		//end brute force

		/* If editing a movie, assign the required capability. */
		if ( 'edit_post' == $cap )
		{
				$post = get_post( $args[0] );
				$post_type = get_post_type_object( $post->post_type );
							
			if ( $user_id == $post->post_author )
				$caps[] = $post_type->cap->edit_posts;
			else
				$caps[] = $post_type->cap->edit_others_posts;
		}

		if ( 'read' == $cap && $user_id == 2)
		{
			//wp_die('read');
			
			//$post = get_post( $args[0] );
			//$post_type = get_post_type_object( $post->post_type );
				
			if ( $user_id == $post->post_author )
				$caps[] = $post_type->cap->read;
			else
				$caps[] = $post_type->cap->manage_options;
		}		
		
		/* If editing a movie, assign the required capability. */
		if ( 'edit_manpower' == $cap )
		{
			if ( $user_id == $post->post_author )
				$caps[] = $post_type->cap->edit_posts;
			else
				$caps[] = $post_type->cap->edit_others_posts;
		}
		
		/* If deleting a movie, assign the required capability. */
		elseif ( 'delete_manpower' == $cap ) 
		{
			if ( $user_id == $post->post_author )
				$caps[] = $post_type->cap->delete_posts;
			else
				$caps[] = $post_type->cap->delete_others_posts;
		}
		
		/* If reading a private movie, assign the required capability. */
		elseif ( 'read_manpower' == $cap ) 
		{
		
			if ( 'private' != $post->post_status )
				$caps[] = 'read';
			elseif ( $user_id == $post->post_author )
			$caps[] = 'read';
			else
				$caps[] = $post_type->cap->read_private_posts;
		}
		
		/* Return the capabilities required by the user. */
		//wp_die(json_encode($caps));
		return $caps;
				
	}
}




function _register_manpower_meta_box_cb()
{
	//TODO: invoke admin_view($post->ID) here instead of in the controller.
	add_meta_box("admin_manpower", "Worker Information", "WorkerController::admin_view", MANPOWER);
}




function manpower_the_content($content)
{
	global $post;
	$post_object = get_post($post);
	
	if(get_post_type() == MANPOWER)
	{
		ob_start();
		WorkerController::view(get_the_ID());
		return ob_get_clean();
	}
	elseif(get_post_type() == 'page' && $post_object->post_name == WorkerController::catalog_slug())
	{
		//override the page having that slug, if it exists. 
		ob_start();
		if ($_GET)
		{
			WorkerController::search($_GET);
		}
		else
		{
			WorkerController::catalog();
		}
		return ob_get_clean();
	}
	else
	{
		//do not modify if it is not one of our pages.
		return $content;
	}
}


function manpower_interested()
{
	$id = $_POST['id'];
	$email = $_POST['email'];
	$comment = $_POST['comment'];
	$have_errors = 0;
	
	//validation here.
	if(!$_POST['id']) wp_redirect(get_bloginfo('url'));
	
	if(!$_POST['email'])
	{	
		$error['email'] = 1;			//set the flag via OR
		$have_errors = 1;
	}
	
	if(!$_POST['comment'])
	{
		$error['comment'] = 1;		//set the flag via OR
		$have_errors = 1;
	}
	
	if ($have_errors === 0)
	{
		//go ahead, validation ok.
		WorkerController::interested($id, $email, $comment);
		wp_redirect(get_permalink($id) . "?have_errors=0#mp_interested");
	}
	else
	{	
		wp_redirect(get_permalink($id) . "?have_errors=$have_errors&email=$email&$comment=$comment&error_email={$error['email']}&error_comment={$error['comment']}#mp_interested");
	}
	die();
}

function manpower_content_type()
{
	return "text/html";
}


function manpower_options_page_callback()
{
	require_once("views/admin_options.phtml");
}


function manpower_create_page_with_slug($input)
{

	//check if the slug exists in the page
	global $wpdb;	//I tried using WP_Query, but damn it fails me this time.
	$query = $wpdb->prepare("SELECT COUNT(*) FROM $wpdb->posts WHERE post_type='page' AND post_status='publish' AND post_name = '%s'", $input);
	$count = $wpdb->get_var($query);
		
	
	if ($count == 0)
	{		
		//page does not exist yet, let us create one!
		$page_id = wp_insert_post(array(
			'post_title' => ucfirst($input),
			'post_status' => 'publish', 
			'post_type' => 'page',
			'post_name' => $input,
			'comment_status' => 'closed',
			'ping_status' => 'closed'
			
		));
		
		if(!$page_id) throw new Error("Page has NOT been created. Please contact the developer of this plugin.");

	}

	wp_reset_query();
	return $input;
}

function manpower_timthumb($src, $width, $height, $zc=1)
{

	return plugins_url('/lib/timthumb.php', __FILE__) . "?src=$src&w=$width&h=$height=zc=$zc";
}