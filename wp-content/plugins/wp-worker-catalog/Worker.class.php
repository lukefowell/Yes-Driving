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
class Worker 
{
	private $id = null;
	
	/*
	 * Contains the fields of this worker. The goal is for the fields
	 * to be modifiable on the fly (ala Magento's EAV).
	 */
	
	//TODO: these fields should be dynamically modifiable
	//in the administration to allow
	private static $fields = array
	(
		"firstname",	//required. Can be left blank, but can't be removed in the fields array.
		"middlename",	//required. Can be left blank, but can't be removed in the fields array.
		"lastname",		//required. Can be left blank, but can't be removed in the fields array.
		"email",		//required. Can be left blank, but can't be removed in the fields array.
		"telephone_number",
		"birthday",
		"description",
		"skills",
		"photo1",
		"position_desired",		
		"citizenship",
		"height",
		"weight",
		"color_of_hair",
		"color_of_eyes",
		"civil_status"
	);
	
	
	/*
	 * Contains the actual values of the fields
	 */
	private $values = array();
	
	/**
	 * Some fields are just indispensible.
	 * The following are created as methods because this class assures that
	 * these fields exist. 
	 */
	public function get_id()
	{
		return $this->id;
	}
	 
	
	public function get_email()
	{
		return $this->__get('email');
	}
	
	public function get_firstname()
	{
		return $this->__get('firstname');
	}
	
	public function get_middlename()
	{
		return $this->__get('middlename');
	}
	
	public function get_lastname()
	{
		return $this->__get('lastname');
	}
	
	/**
	 * Here is how you access other fields aside from the 
	 * constant fields above.
	 */	
	
	public function __get($name)
	{
		//allow users to get the id, but not to set it.
		if ($name == "id") return $this->id;

		if (!in_array($name, Worker::$fields)) throw new Exception("Field '$name' not found for Worker class.");
		
		return $this->values[$name];
	}
	
	public function __set($name, $value)
	{
		if (!in_array($name, Worker::$fields)) throw new Exception("Field '$name' not found for Worker class.");
		$this->values[$name] = $value;
	}
	
	public static function load($id)
	{
		$worker = new Worker($id);
		
		/*** prepopulating ***/
		foreach(Worker::$fields as $field)
		{
			$worker->$field = get_post_meta($id, $field, true);
		}		
		
		return $worker;
	}
	
	
	/*
	 *
	 * @returns an array of Workers
	 */	
	public static function load_all($page=1)
	{
		$workers = array();
	
		$wp_query = new WP_Query(array(
			"post_type" => MANPOWER,
			"order" => "ASC",
			"paged" => $page,
			"orderby" => 'title' //by default, arrange in alphabetical order
		));
		
		while($wp_query->have_posts())
		{
			$wp_query->the_post();
			$workers[] = Worker::load(get_the_ID());
		}
		wp_reset_query();
		return $workers;
	}
	
	/*
	 * Searches for a particular worker given an array of search fields
	 * @param $search_fields, a key-value array, with the key
	 */
	public static function search($search_fields, $page=1, &$count=null)
	{
		$meta_query = array();
		
		//traverse only on the allowable fields
		foreach(Worker::$fields as $field)
		{			
			if (isset($search_fields[$field]))
			{
				$meta_query[] = array("key"=>$field, "value"=>$search_fields[$field], "compare"=>"LIKE");
			}
		}
	
		$wp_query = new WP_Query(array
		(
			"post_type" => MANPOWER,
			"paged"	=> $page,
			"order"	=> "ASC",
			"orderby" => 'title',		
			"meta_query" => $meta_query
		));
		
		$count = $wp_query->found_posts;
		
		while($wp_query->have_posts())
		{
			$wp_query->the_post();
			$workers[] = Worker::load(get_the_ID());
		}
		wp_reset_query();
		return $workers;			
	}
	
	
	
	
	/*
	 * This function assumes that the image has been properly uploaded. It
	 * does no checking whatsoever if the image indeed exists.
	 */
	public function get_image_url($field)
	{
		$image = $this->__get($field);
		$upload_url = wp_upload_dir();
		$upload_url = $upload_url['baseurl'];
		return "$upload_url/$image";
	}
	
	public function get_data($post, $file = null)
	{
	
		/** Warning: $post is dirty! **/		 
		 foreach(Worker::$fields as $allowable_fields)
		 {
			//if a field is found in file, do not assign the post value of it.
			//this means that if the image is not changed in the admin form,
			//do not replace it. 
			//$file[$allowable_fields] is always set even though it has no value.
			if ($file && !$file[$allowable_fields])
			{
				$this->values[$allowable_fields] = $post[$allowable_fields];
			}
		 }
		 
		 if ($file != null)
		 {

			 //look for a value that accepts a file input
			 foreach(Worker::$fields as $allowable_field)
			 {
				if($file[$allowable_field]['name'])
				{
					$upload_dir = wp_upload_dir();
					$upload_dir = $upload_dir['basedir'];
					$filename = date('YmdHis') . $file[$allowable_field]['name'];
					if (!move_uploaded_file($file[$allowable_field]['tmp_name'], "$upload_dir/$filename"))
					{
						throw new Exception("Fatal Error: Cannot upload file. Please check your server configuration");
					}
					else
					{
						$this->values[$allowable_field] = $filename;
					}
					
					
				}
			 }		 
		 }
		 else
		 {
			//wp_die("can't escape, sorry");
		 
		 }
		
	}
	
	public function __construct($id=null)
	{
		if ($id != null)
		{
			$this->id = $id;
		}
		
	}
	
	public function save()
	{
		/** We trust that update_post_meta can handle SQL injection attacks? **/
	
		if ($this->id == null) throw new Exception("Cannot save. id is null");
		
		//store the data in the content column so that our worker will be searchable.
		$wp_content = "";
		
		foreach($this->values as $field=>$value)
		{
			update_post_meta($this->id, $field, $value);
			$wp_content .= "<div>$value</div>";
		}
		
		//wp_die($wp_content);

		//save the data entry in wp_posts
		global $wpdb;
		$wpdb->update(
			$wpdb->posts, 
			array(
				"post_content" => $wp_content,
				"post_title" => "{$this->values['lastname']}, {$this->values['firstname']} {$this->values['middlename']}" 
			),
			array("ID" => $this->id),
			array("%s"),
			array("%d")
		);
		
		
	}
}