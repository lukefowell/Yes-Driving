<?php
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
require_once('manpower_header.inc.php');
class WorkerController
{
	public static function admin_view()
	{
		/*
		 * Declare view variables here
		 */
		 
		//TODO: this should be invoked as admin_view($id)
		 
		
		global $post;
		$worker = Worker::load($post->ID);
		require_once("views/admin_view.phtml");
	}
	
	public static function catalog()
	{
		//TODO: add code so that it will search 
		//for a template in the templates directory
		global $post;
		$this_url = get_permalink($post->ID);
		
		ob_start();
		
		/** Calculations for pagination **/
		$page = get_query_var('paged') ? get_query_var('paged') : 1;
		$posts_per_page = get_query_var('posts_per_page');
		$total_workers =  wp_count_posts(MANPOWER)->publish;	

		if ($total_workers % $posts_per_page == 0)
		{
			//if divisible, just divide
			$total_pages = ($total_workers / $posts_per_page);
		}
		else
		{
			//if not divisible, add 1 for the remainder
			//note: there is no integral division in PHP. int / int = float.
			$total_pages = (int)($total_workers / $posts_per_page) + 1;
		}
		$workers = Worker::load_all($page);
		require_once("views/mp_catalog.phtml");
	}
	
	public static function view($id)
	{
		//TODO: add code so that it will search 
		//for a template in the templates directory

		$worker = Worker::load($id);
		require_once("views/mp_view.phtml");
		require_once("views/mp_interested.phtml");
	}
	
	public static function interested($id, $email, $comment = '')
	{
		if(get_option('mp_email_to_notify'))
		{
			$admin_email = get_option('mp_email_to_notify');
		}
		else
		{
			$admin_email = get_bloginfo('admin_email');
		}
		$worker = Worker::load($id);
		
		//get the e-mail template.
		ob_start();
		require_once("views/mp_email.phtml");
		$message = ob_get_clean();
		$mail_subject = "An employer is interested with {$worker->firstname} {$worker->lastname}";
		$mail_headers = array("Content-type" => "text/html");
		
		//Step #1: Send email to administrator	
		wp_mail($admin_email, $mail_subject, $message, $mail_headers);
		
		//Step #2: Send email to worker, if option is enabled AND worker has provided an email address.
		if(get_option('mp_notify_workers') == 'on' && $worker->get_email())
		{
			//wp_die("HERE!");
			wp_mail($worker->get_email(), $mail_subject, $message, $mail_headers);
		}
	}
	
	public static function search($search_fields)
	{
		global $post;
		$this_url = get_permalink($post->ID);
	
	
		$page = get_query_var('paged') ? get_query_var('paged') : 1;
		$posts_per_page = get_query_var('posts_per_page');
		
		$workers = Worker::search($search_fields, $page, $total_workers);

		if ($total_workers % $posts_per_page == 0)
		{
			//if divisible, just divide
			$total_pages = ($total_workers / $posts_per_page);
		}
		else
		{
			//if not divisible, add 1 for the remainder
			//note: there is no integral division in PHP. int / int = float.
			$total_pages = (int)($total_workers / $posts_per_page) + 1;
		}

		//TODO:build search params for pagination link
		$search_params = "";
		foreach($search_fields as $key=>$value)
		{
			$search_params = "$key=$value&";
		}
		$search_params = substr($search_params, 0, -1);	//remove trailing &
				
		require_once("views/mp_catalog.phtml");
	}
	
	//returns the endpoint URL to submit
	public static function action_endpoint()
	{
		return get_bloginfo('wpurl') . '/wp-admin/admin-ajax.php';
	}
	
	public static function catalog_url()
	{
		return get_bloginfo('url') . '/' . self::catalog_slug();
	}
	
	public static function catalog_slug()
	{
		return get_option('mp_catalog_slug') ? get_option('mp_catalog_slug') : 'catalog';
	}
	
	//TODO: Deprecate interested_action(). Use action_endpoint() instead.
	//returns the endpoint URL to submit
	public static function interested_action()
	{
		return get_bloginfo('wpurl') . '/wp-admin/admin-ajax.php';
	}
	
	public static function interested_settings($id)
	//include inside your form for the interested form
	{
		ob_start();
?>
		<input type="hidden" name="id" value="<?php echo $id?>" />
		<input type="hidden" name="action" value="interested" />
<?php
		return ob_get_clean();
	}
	

}