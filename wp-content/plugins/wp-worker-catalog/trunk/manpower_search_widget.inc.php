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
/**
 * FooWidget Class
 */
require_once('manpower_header.inc.php');
 
class Manpower_Search_Widget extends WP_Widget 
{
    /** constructor */
    function Manpower_Search_Widget() 
	{
        parent::WP_Widget(false, __('Manpower Search Widget', MANPOWER), array(
			"description" => __("A search box for searching workers.", MANPOWER)
		));
    }

    /** @see WP_Widget::widget */
    function widget($args, $instance) 
	{
		$before_widget = $args['before_widget'];
		$after_widget = $args['after_widget'];
		$before_title = $args['before_title'];
		$after_title = $args['after_title'];		
        $title = apply_filters('widget_title', $instance['title']);		
		require("views/widget_view.phtml");
    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) 
	{
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		return $instance;
    }

    /** @see WP_Widget::form */
    function form($instance) 
	{
		$title = esc_attr($instance['title']);
		require('views/widget_admin.phtml');
    }

} // class FooWidget