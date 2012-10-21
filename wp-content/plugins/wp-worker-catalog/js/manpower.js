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
 
jQuery(document).ready(function($)
{
	//modify WordPress admin to allow file upload
	//only do this on our post type to be unintrusive
	if($("#post_type").val() == manpower.manpower_post_type)
	{
		$("body.wp-admin form#post").attr("enctype", "multipart/form-data");
		$("body.wp-admin input#title").attr("disabled", "disabled");
	}
	
	/**
	 * Admin Option Javascript
	 */
	if($("#mp_email_to_notify_mode").length != 0)
	{
		//upon loading, check if mp_email_to_notify has value
		if($("#mp_email_to_notify").val() != '')
		{
			$("#mp_email_to_notify_mode").val('others');
			$("#mp_email_to_notify").css('display','inline');
		}
	
		//onchange email to notify mode
		$("#mp_email_to_notify_mode").change(function()
		{
			if($("#mp_email_to_notify_mode").val() == 'others')
			{
				$("#mp_email_to_notify").css('display','inline');
			}
			else
			{
				$("#mp_email_to_notify").val('');
				$("#mp_email_to_notify").css('display','none');				
			}
		});
	}
});