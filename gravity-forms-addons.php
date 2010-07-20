<?php
/*
Plugin Name: Gravity Forms Addons
Plugin URI: http://www.seodenver.com/gravity-forms-addons/
Description: Add functionality and usability to the great Gravity Forms plugin.
Author: Katz Web Services, Inc.
Version: 1.3
Author URI: http://www.katzwebservices.com

Copyright 2010 Katz Web Services, Inc.  (email: info@katzwebservices.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

*/

/*

Versions:
= 1.3 = 
* Added an admin notice if Gravity Forms is not installed properly. This will help the confusion: an installed version of Gravity Forms is required for this plugin to do anything!
* Added function to get field values from a lead: `get_gf_field_value($leadid, $fieldid)` will work for all field lengths (`get_gf_field_value_long()` worked for just long values)
* Added `IDs` link to Edit Forms page, allowing you to find out what the field IDs are for each field in a form. This allows you to use the functions in this plugin much more easily.
* Removed `$Forms->escape_text` reference, since it has been replaced by esc_html(). This would have caused a fatal error.

= 1.2.1.1 = 
* Updated with GPL information. Did you know Gravity Forms is also GPL? Any WordPress plugin is.

= 1.2.1 = 
* Fixed whitespace issue if site is gzip'ed. No need to upgrade if you aren't getting the `Warning: Cannot modify header information - headers already sent by...` PHP error.

= 1.2 = 
* Compatibility with Gravity Forms 1.3

= 1.1 =
* Added Edit link to Entries page to directly edit an entry
* Added a bunch of functions to use in directly accessing form and entry data from outside the plugin

= 1.0 =
* Launched plugin

*/
		
		
// Get Gravity Forms over here!
@include_once(WP_PLUGIN_DIR . "/gravityforms/gravityforms.php");
@include_once(WP_PLUGIN_DIR . "/gravityforms/forms_model.php");
@include_once(WP_PLUGIN_DIR . "/gravityforms/common.php"); // 1.3
@include_once(WP_PLUGIN_DIR . "/gravityforms/form_display.php"); // 1.3

// If Gravity Forms is installed and exists
if(class_exists('RGForms') && class_exists('RGFormsModel')) { 
	
	$Forms = new RGForms();  $RG = new RGFormsModel(); 
	if(class_exists('GFCommon')) { $Common = new GFCommon(); } else { $Common = false; }
	if(class_exists('GFFormDisplay')) { $FormDisplay = new GFFormDisplay(); } else { $FormDisplay = false; }
	
	function kws_gf_css() {
		echo '<style type="text/css">';
			kws_gf_expand_boxes_css(); 	
		echo '</style>';
	}
	
	function kws_gf_expand_boxes_css() {
		echo '.gforms_edit_form ul.menu li ul { display:block!important; } ';
	}
	
	function kws_gf_js() {
		global $Common;
		echo '<script src="'.$Common->get_base_url().'/js/jquery.simplemodal-1.3.min.js"></script>'; // Added for the new IDs popup
		echo '<script type="text/javascript">
			jQuery(document).ready(function($) {';
		
			kws_gf_expand_boxes_js();
			kws_gf_add_edit_js();
			
		echo '});
		</script>';
	}
	
	function kws_gf_expand_boxes_js() { ?>
		$("ul.menu li ul").each(function() {
			// Prevent the slideUp/slideDown functions from working
			$(this).css('min-height', $(this).height()).css('height', $(this).height()) ;
		});
	<?php }
	
	
	function kws_gf_show_field_ids($form) {
		if(isset($_REQUEST['show_field_ids'])) {
		echo <<<EOD
		<style type="text/css">
		#input_ids th, #input_ids td { border-bottom:1px solid #999; padding:.25em 15px; }
		#input_ids th { border-bottom-color: #333; font-size:.9em; background-color: #464646; color:white; padding:.5em 15px; font-weight:bold;  } 
		#input_ids { background:#ccc; margin:0 auto; font-size:1.2em; line-height:1.4; width:100%; border-collapse:collapse;  }
		#input_ids strong { font-weight:bold; }
		#preview_hdr { display:none;}
		#input_ids caption { color:white!important;}
		</style>
EOD;
		
		if(!empty($form)) { echo '<table id="input_ids"><caption id="input_id_caption">Fields for <strong>Form ID '.$form['id'].'</strong></caption><thead><tr><th>Field Name</th><th>Field ID</th></thead><tbody>'; }
		foreach($form['fields'] as $field) {
			// If there are multiple inputs for a field; ie: address has street, city, zip, country, etc.
			if(is_array($field['inputs'])) {
				foreach($field['inputs'] as $input) {
					echo "<tr><td width='50%'><strong>{$input['label']}</strong></td><td>{$input['id']}</td></tr>";
				}
			}
			// Otherwise, it's just the one input.
			else {
				echo "<tr><td width='50%'><strong>{$field['label']}</strong></td><td>{$field['id']}</td></tr>";
			}
		}
		if(!empty($form)) { echo '</tbody></table><div style="clear:both;"></div></body></html>'; exit(); }
		} else {
			return $form;
		}
	}
	add_filter('gform_pre_render','kws_gf_show_field_ids');
	
	function kws_gf_add_edit_js() {
	
			if(isset($_REQUEST['page']) && $_REQUEST['page'] == 'gf_entries') {
				?>
				$(".row-actions span.edit:contains('Delete')").each(function() { 
					var editLink = $(this).parents('tr').find('.column-title a').attr('href');
					editLink = editLink + '&screen_mode=edit';
					//alert();
					$(this).after('<span class="edit">| <a title="<?php _e("Edit this entry", "gravityforms"); ?>" href="'+editLink+'"><?php _e("Edit", "gravityforms"); ?></a></span>');
				});
				<?php 
			}
			
			else if(isset($_REQUEST['page']) && $_REQUEST['page'] == 'gf_edit_forms') {
				?>
				$(".row-actions span.edit:contains('Delete')").each(function() { 
					var editLink = $(this).parents('tr').find('.column-title a:contains("Preview")').attr('href');
					editLink = editLink + '&amp;show_field_ids=true';
					$(this).after('<span class="edit">| <a title="<?php _e("View form field IDs", "gravityforms"); ?>" href="'+editLink+'" class="form_ids"><?php _e("IDs", "gravityforms"); ?></a></span>');
				});
					$("a.form_ids").live('click', function(e) {
			           e.preventDefault(); 
			           $.get($(this).attr('href'),function(data) {
				           $.modal(data, {
					           	overlayClose:true, 
				           		containerCss:{
									backgroundColor:"transparent",
									borderColor: 'transparent',
									borderWidth: 0,
									minHeight: 400,
									padding:10,
									escClose: true,
									width:'auto',
									autoPosition: true
								}
							});
			           });
			         });

				<?php 
			}	
	}
	// Allows for edit links to work with a link instead of a form (GET instead of POST)
	if(isset($_GET["screen_mode"])) { $_POST["screen_mode"] = $_GET["screen_mode"]; }

	function kws_gf_head() {
		if(is_admin()) {
			kws_gf_css();
			kws_gf_js();
		}
	}
	
	add_action('admin_head', 'kws_gf_head',1);
	

	// To retrieve textarea inputs from a lead 
	// Example: get_gf_field_value_long(22, '14');
	function get_gf_field_value_long($leadid, $fieldid) {
		global $Forms, $RG, $Common;
		return $RG->get_field_value_long($leadid, $fieldid);
	}
	
	// To retrieve textarea inputs from a lead 
	// Example: get_gf_field_value_long(22, '14');
	function get_gf_field_value($leadid, $fieldid) {
		global $Forms, $RG, $Common;
		$lead = $RG->get_lead($leadid);
#		echo '<pre>'.print_r($lead,true).'</pre>'.floatval($fieldid);
		$fieldid = floatval($fieldid);
		if(is_numeric($fieldid)) {
			$result = $lead["$fieldid"];
		}
		
		$max_length = GFORMS_MAX_FIELD_LENGTH;
		
		if(strlen($result) >= ($max_length - 50)) {
			$result = get_gf_field_value_long($lead["id"], $fieldid);
        }
        $result = trim($result);
        
        if(!empty($result)) { return $result; }
		return false;
	}
	
	function gf_field_value_long($leadid, $fieldid) {
		echo get_gf_field_value_long($leadid, $fieldid);
	}
	
	// Gives you the label for a form input (such as First Name). Enter in the form and the field ID to access the label.
	// Example: echo get_gf_field_label(1,1.3);
	// Gives you the label for a form input (such as First Name). Enter in the form and the field ID to access the label.
	// Example: echo get_gf_field_label(1,1.3);
	function get_gf_field_label($form_id, $field_id) {
		global $Forms,$RG,$Common;
		$form = $RG->get_form_meta($form_id);
		foreach($form["fields"] as $field){
			if($field['id'] == $field_id) {
				# $output = $Forms->escape_text($field['label']); // No longer used
				$output = esc_html($field['label']); // Using esc_html(), a WP function
			}elseif(is_array($field['inputs'])) {
				foreach($field["inputs"] as $input){
					if($input['id'] == $field_id) {
						if($Common) {
							$output = esc_html($Common->get_label($field,$field_id));
						} else {
							#$output = $Forms->escape_text($Forms->get_label($field,$field_id));  // No longer used
							$output = esc_html($Forms->get_label($field,$field_id));  // No longer used
						}
					}
				}
			}
		}
		return $output;
	}
	function gf_field_label($form_id, $field_id) {
		echo get_gf_field_label($form_id, $field_id);
	}	
	
	// Returns a form using php instead of shortcode
	function get_gf_form($id, $display_title=true, $display_description=true, $force_display=false, $field_values=null){
		global $Forms,$RG,$Common,$FormDisplay;
		if($FormDisplay) {	
			return $FormDisplay->get_form($id, $display_title=true, $display_description=true, $force_display=false, $field_values=null);
		} else {
			return $RG->get_form($id, $display_title, $display_description);
		}
	}
	function gf_form($id, $display_title=true, $display_description=true, $force_display=false, $field_values=null){
		echo get_gf_form($id, $display_title, $display_description, $force_display, $field_values);
	}
	
	// Returns array of leads for a specific form
	function get_gf_leads($form_id, $sort_field_number=0, $sort_direction='DESC', $search='', $offset=0, $page_size=3000, $star=null, $read=null, $is_numeric_sort = false, $start_date=null, $end_date=null) {
		global $Forms, $RG,$Common;
		
		return $RG->get_leads($form_id,$sort_field_number, $sort_direction, $search, $offset, $page_size, $star, $read, $is_numeric_sort, $start_date, $end_date);
	}
	
	function gf_leads($form_id, $sort_field_number=0, $sort_direction='DESC', $search='', $offset=0, $page_size=3000, $star=null, $read=null, $is_numeric_sort = false, $start_date=null, $end_date=null) {
		echo get_gf_leads($form_id,$sort_field_number, $sort_direction, $search, $offset, $page_size, $star, $read, $is_numeric_sort, $start_date, $end_date);
	}
	

} else {
	// If the classes don't exist, the plugin won't do anything useful.
	function kws_gf_warning() {
			echo "<div id='kws-gf-warning' class='updated fade'><p><strong>".sprintf(__('Gravity Forms Addons has detected a problem: required Gravity Forms files cannot be found'), "http://sn.im/gravityforms")."</strong></p><p>".sprintf(__('The <a href="%1$s">Gravity Forms plugin</a> must be installed and activated for the Gravity Forms Addons plugin to work.</p><p>If you haven\'t installed the plugin, you can <a href="%1$s">purchase the plugin here</a>. If you have, and you believe this notice is in error, <a href="%2$s">start a topic on the plugin support forum</a>.'), "http://sn.im/gravityforms", "http://wordpress.org/tags/gravity-forms-addons?forum_id=10#postform")."</p></div>";
	}
	
	add_action('admin_notices', 'kws_gf_warning');
}
?>