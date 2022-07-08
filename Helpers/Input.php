<?php

class Joe_Input {
	
	private static $username_bad = array('.', "\$", '!', '*');
	private static $username_good = array('__dot__', '__dollar__', '__bang__', '__star__');
	
	static public function create_field($field, $set_value = null, $show_label = true) {
		//Must have ID
		if(! array_key_exists('id', $field) || ! $field['id']) {
			$field['id'] = substr(md5(rand(0,999999)), 0, 5);
		}

		//Use ID for Name (if absent)
		if(! array_key_exists('name', $field)) {
			$field['name'] = $field['id'];
		}

		//Default type
		if(! array_key_exists('type', $field)) {
			$field['type'] = 'text';
		}

		//Boolean
		if($field['type'] == 'boolean') {
			if(array_key_exists('class', $field)) {
				$field['class'] .= ' ';
			} else {
				$field['class'] = '';		
			}
			$field['class'] .= Joe_Helper::css_prefix() . 'short-input';
					
			if(! array_key_exists('options', $field) || ! is_array($field['options'])) {
				$field['options'] = array(
					'1' => esc_attr__('Yes', Joe_Config::get_item('plugin_text_domain')),
					'0' => esc_attr__('No', Joe_Config::get_item('plugin_text_domain'))
				);
			}
		}		
		
		//Start output
		$out = "\n" . '<!-- START ' . $field['id'] . ' Input -->' . "\n";
		
		//Add class?
		$add_class = (array_key_exists('class', $field)) ? ' ' . $field['class'] : '';
		$add_class .= ' ' . $field['id'] . '-container';

		//Container
		$out .= '<div class="' . Joe_Helper::css_prefix() . 'control-group ' . Joe_Helper::css_prefix() . 'control-type-' . $field['type'] . $add_class . '">' . "\n";
	
		//Label
		if($show_label && isset($field['title'])) {
			$out .= '	<label class="' . Joe_Helper::css_prefix() . 'control-label" for="' . $field['name'] . '">' . $field['title'] .  '</label>' . "\n";		
		}
		$out .= '	<div class="' . Joe_Helper::css_prefix() . 'controls">' . "\n";				

		//Prepend?		
		if(array_key_exists('prepend', $field)) {
			$out .= $field['prepend'];
		}
	
		//Create input
		$out .= static::create_input($field, $set_value);

		//Append?		
		if(array_key_exists('append', $field)) {
			$out .= $field['append'];
		}
	
		//Tip
		if(array_key_exists('tip', $field)) {
			//Add missing periods
			$last_char = $field['tip'][strlen($field['tip'])-1];
			if(! in_array($last_char, ['.', '?', '!'])) {
				$field['tip'] .= '.';
			}
			
			$out .= ' <a data-title="' . $field['tip'] . '';
			if(array_key_exists('tip_link', $field)) {
				$out .= ' ' . esc_attr__('Click here for more details.', Joe_Config::get_item('plugin_text_domain')) . '" href="' . $field['tip_link'] . '" target="_blank" class="' . Joe_Helper::css_prefix() . 'tooltip ' . Joe_Helper::css_prefix() . 'link"';					
			} else {
				$out .= '" href="#" onclick="return false;" class="' . Joe_Helper::css_prefix() . 'tooltip"';
			}
			$out .= '>?</a>';
		}
		
		$out .= '	</div>' . "\n";								
		$out .= '</div>' . "\n";
		$out .= '<!-- END ' . $field['id'] . ' Input -->' . "\n";
				
		return $out;
	}			


	/**
	 * ====================================
	 * ============== PRIVATE =============
	 * ====================================
	 */			
	static public function create_input($field, $set_value = null) {
		$out = '';

		if(! array_key_exists('type', $field)) {
			$field['type'] = 'text';
		}
		
		//Default
		if(array_key_exists('default', $field)) {
			if(is_array($field['default'])) {
				$field['default'] = implode(Joe_Config::get_item('multi_value_seperator'), static::process_output($field, $field['default']));				
			} else {
				$field['default'] = static::process_output($field, $field['default']);				
			}
		}
		
		//Process set value?
		if($set_value !== null) {
			if(is_array($set_value)) {
				$set_value = implode(Joe_Config::get_item('multi_value_seperator'), static::process_output($field, $set_value));				
			} else {
				$set_value = static::process_output($field, $set_value);			
			}
		}
		
		switch($field['type']) {
			case 'boolean' :
			case 'select' :
				//Do we have a value for this post?
				if($set_value === null && array_key_exists('default', $field)) {
					$set_value = $field['default'];
				}
							
				$out .= '		<select data-multi-value="' . $set_value . '" class="' . Joe_Helper::css_prefix() . 'input ' . Joe_Helper::css_prefix() . 'input-' . $field['id'] . '" name="' . $field['name'] . '" data-id="' . $field['id'] . '">' . "\n";
				if(isset($field['options'])) {
					foreach($field['options'] as $value => $description) {
						//Always use strings
						$value = (string)$value;
					
						$out .= '			<option value="' . $value . '"';
						//Has this value already been set
						if($set_value === $value) {
							$out .= ' selected="selected"';
						//Do we have a default?
						}	elseif($set_value == null && (array_key_exists('default', $field) && $field['default'] == $value)) {
							$out .= ' selected="selected"';				
						}		
						$out .= '>' . $description . '</option>' . "\n";
					}
				}
				$out .= '		</select>' . "\n";
				
				break;			
			case 'select_multi' :
				//Decode if JSON
				$set_value = (json_decode($set_value)) ? json_decode($set_value) : $set_value;
				//Use default if no set value
				if(! $set_value && isset($field['default'])) {
					$set_value = $field['default'];
				}
				//Is multi?
				if(is_string($set_value) && strpos($set_value, ',')) {
					$set_value = explode(',', $field['default']);
				} elseif(is_string($set_value) && strpos($set_value, Joe_Config::get_item('multi_value_seperator'))) {
					$set_value = explode(Joe_Config::get_item('multi_value_seperator'), $field['default']);
				}
				
				$out .= '		<select multiple="multiple" class="' . Joe_Helper::css_prefix() . 'input ' . Joe_Helper::css_prefix() . 'input-' . $field['id'] . '" name="' . $field['name'] . '[]" data-id="' . $field['id'] . '">' . "\n";
				
				//If we have options
				if(isset($field['options'])) {
					foreach($field['options'] as $value => $description) {
						//Always use strings
						$value = (string)$value;

						//Joe_Helper::debug($set_value);
					
						$out .= '			<option value="' . $value . '"';
									
						//Has this value already been set
						if(is_array($set_value) && in_array($value, $set_value)) {
							$out .= ' selected="selected"';
						} elseif($value == $set_value) {
							$out .= ' selected="selected"';
						}						

						$out .= '>' . $description . '</option>' . "\n";
					}
				}
				$out .= '		</select>' . "\n";

				break;					
			case 'textarea' :
				$out .= '		<textarea class="' . Joe_Helper::css_prefix() . 'input ' . Joe_Helper::css_prefix() . 'input-' . $field['id'] . '" name="' . $field['name'] . '" data-id="' . $field['id'] . '">';
				//Do we have a value for this post?
				if($value = htmlspecialchars($set_value)) {
					$out .= $value;
				//Do we have a default?
				}	elseif(array_key_exists('default', $field)) {
					$out .= $field['default'];
				}
				$out .= '</textarea>' . "\n";
				
				break;
			case 'textarea_rich' :
				//Content
				$content = $set_value;
				if(! $content && array_key_exists('default', $field)) {
					$content = $field['default'];
				}
				$content = htmlspecialchars_decode($content);
				
				
				//Markup
				//$out .= '		<textarea class="' . Joe_Helper::css_prefix() . 'input ' . Joe_Helper::css_prefix() . 'input-' . $field['id'] . '" name="' . $field['name'] . '" data-id="' . $field['id'] . '"></textarea>' . "\n";
				
				//Setup rich editor			
				ob_start();	
				wp_editor($content, $field['id'], array(
					'media_buttons' => false,
					'drag_drop_upload' => false,
					'textarea_name' => $field['name'],
					'textarea_rows' => 5,
					'teeny' => false,
					'quicktags' => false
				));
				$out .= ob_get_clean();
				
				break;				
			case 'submit' :
				$value = explode(' ', $field['title'])[0];
				$out .= '		<input type="submit" name="' . $field['name'] . '" value="' . $value . '" data-id="' . $field['id'] . '" class="' . Joe_Helper::css_prefix() . 'input ' . Joe_Helper::css_prefix() . 'input-' . $field['id'] . ' button-secondary" />' . "\n";
				
				break;				
			case 'file' :
				$out .= '		<input class="' . Joe_Helper::css_prefix() . 'input ' . Joe_Helper::css_prefix() . 'input-' . $field['id'] . '" type="file" name="' . $field['name'] . '" data-id="' . $field['id'] . '" />' . "\n";
				
				break;
			case 'text' :
			default :
				$out .= '		<input class="' . Joe_Helper::css_prefix() . 'input ' . Joe_Helper::css_prefix() . 'input-' . $field['id'] . '" type="text" name="' . $field['name'] . '" data-id="' . $field['id'] . '"';
				//Do we have a value for this post?
				if($set_value !== null) {
					$out .= ' value="' . $set_value . '"';
//					$out .= ' value="' . htmlspecialchars($set_value) . '"';
				//Do we have a default?
				}	elseif(array_key_exists('default', $field)) {
					$value = $field['default'];
					
					$out .= ' value="' . $value . '"';			
				}
				$out .= ' />' . "\n";
				
				break;
		}	
		
		return $out;
	}	

	static function group_fields($fields, $groups) {		
		$fields_grouped = array();
		
		foreach($fields as $field_key => $field_data) {
			$group_id = '';
			if(array_key_exists('group', $field_data)) {
				$group_id = $field_data['group'];			
			}		
			$fields_grouped[$group_id][$field_key] = $field_data;					
		}
		
		return $fields_grouped;	
	}	

	static function create_repeatable_parameter_groups($name = 'repeatable', $fields, $groups = [], $repeatable_data = []) {
		$count = 0;

		//Populate
		$repeatable_parameter_groups = '';
		if(sizeof($repeatable_data)) {
			foreach($repeatable_data as $data) {
				$repeatable_parameter_groups .= static::create_parameter_groups($fields, $groups, $data, $name . '[' . $count . '][%s]');			
				
				$count++;
			}		
		}		

		$out = '<!-- START Repeatable Container -->' . "\n";
		$out .= '<div class="' . Joe_Helper::css_prefix() . 'repeatable-container" data-count="' . $count . '">' . "\n";
		
		$out .= $repeatable_parameter_groups;

		//Template
		$out .= static::create_parameter_groups($fields, $groups, [], $name . '[__count__][%s]', '', Joe_Helper::css_prefix() . 'repeatable-template');			

		$out .= '<button class="button ' . Joe_Helper::css_prefix() . 'repeatable-add" title="' . __('Add Query', Joe_Config::get_item('plugin_text_domain')) . '"><i class="ion ion-plus"></i></button>';

		$out .= '</div>' . "\n";
		$out .= '<!-- END Repeatable Container -->' . "\n";

		return $out;
	}

	
	static function create_parameter_groups($fields, $groups = array(), $data = array(), $input_name_format = null, $id = '', $class_append = '') {				
		//Group
		$fields = static::group_fields($fields, $groups);
		
// 		Joe_Helper::debug($fields);
		
		$out = '<!-- START Parameter Container -->' . "\n";
		
		$id = ($id) ? ' id="' . $id . '"' : '';
		$class_append = ($class_append) ? ' ' . $class_append : '';		
		
		$out .= '<div' . $id . ' class="' . Joe_Helper::css_prefix() . 'parameters-container ' . Joe_Helper::css_prefix() . 'accordion-container' . $class_append . '">' . "\n";

		//Are we doing groups?
		$by_group = false;		
		if(is_array($groups) && sizeof($groups)) {	
			$by_group = true;
			
			$fields_reorder = [];
			//No group
			if(isset($fields[''])) {
				$fields_reorder[''] = $fields[''];		
			}
			foreach($groups as $group_id => $group) {
// 				Joe_Helper::debug($fields);

				if(isset($fields[$group_id])) {
					$fields_reorder[$group_id] = $fields[$group_id];					
				}
			}
			$fields = $fields_reorder;
		}		
					
		$current_group = false;
		foreach($fields as $group_id => $fields) {
			//Output group?
			if($by_group && ($current_group != $group_id)) {
				if(array_key_exists($group_id, $groups) && $group_id != false) {
					$group = $groups[$group_id];			
				} else {
					$group = array();
				}
			
				//Close previous fieldset?
				if($current_group !== false) {			
					$out .= '	</div>' . "\n";
					$out .= '</div>' . "\n";					
					$out .= '<!-- END Parameter Group -->' . "\n";										
				}
				$out .= '<!-- START Parameter Group -->' . "\n";										
				$out .= '	<div class="' . Joe_Helper::css_prefix() . 'parameter-group ' . Joe_Helper::css_prefix() . 'accordion-group ' . Joe_Helper::css_prefix() . 'parameter-group-' . $group_id . '" id="' . Joe_Helper::css_prefix() . 'parameter-group-' . $group_id . '">' . "\n";					
				$out .= '		<legend title="Click to expand">' . $group['group_title'] . '</legend>' . "\n";
				$out .= '		<div class="' . Joe_Helper::css_prefix() . 'accordion-group-content">' . "\n";
				if(array_key_exists('group_description', $group)) {			
					$out .= '			<p class="' . Joe_Helper::css_prefix() . 'parameter-group-description">' . $group['group_description'] . '</p>' . "\n";
				}
				$current_group = $group_id;
			}
			
			foreach($fields as $field) {
				//Use ID for Name
				if(array_key_exists('id', $field) && (! array_key_exists('name', $field))) {
					$field['name'] = $field['id'];
				}

				//Set value?
				if(array_key_exists($field['name'], $data)) {
					$set_value = $data[$field['name']];
				} else {
					$set_value = null;
				}	
				
				//Name format (widgets)
				if($input_name_format) {
					$field['name'] = sprintf($input_name_format, $field['name']);
				}					

				//Create input
				$out .= static::create_field($field, $set_value);			
			}
		}
		
		//As long as some groups were output
		if($by_group && ($current_group !== false)) {
			$out .= '		</div>' . "\n";
			$out .= '	</div>' . "\n";			
			$out .= '<!-- END Parameter Group -->' . "\n";													
		}

		$out .= '</div>' . "\n";
		$out .= '<!-- END Parameter Container -->' . "\n";
		
		return $out;		
	}
	
	static function process_input($param_def, $param_value) {
		//Do processing
		if(array_key_exists('input_processing', $param_def)) {
			$param_value = static::eval_processes_on_param_value($param_def['input_processing'], $param_value);
		}		
		
		return $param_value;
	}

	static function process_output($param_def, $param_value) {
		//Do processing
		if(array_key_exists('output_processing', $param_def)) {
			$param_value = static::eval_processes_on_param_value($param_def['output_processing'], $param_value);
		}
					
		return $param_value;
	}
	
	static function eval_processes_on_param_value($processes, $param_value) {
		if(is_array($processes)) {
			foreach($processes as $process) {
				//Values stored in array
				if(is_array($param_value)) {
					//Joe_Helper::debug($param_value, false);

					//Single Value
					if(sizeof($param_value) == 1) {
						//Make string
						$param_value = array_pop($param_value);
			
						//Process
						$param_value = trim($param_value);			
						eval("\$param_value = $process;");				
			
						//Back into array
						$param_value = array($param_value);											
					//Multiple values
					} else {
						//Each
						$param_value_out = array();
						$param_values = $param_value;
						foreach($param_values as $param_value) {
							//Process each value
							$param_value = trim($param_value);									
							eval("\$param_value = $process;");	
			
							$param_value_out[] = $param_value;
						}
									
						//Make string
						$param_value = $param_value_out;
					}					
				//Single value stored in string
				} else {	
					$param_value = trim($param_value);			
					eval("\$param_value = $process;");						
				}	
			}
		}		
		
		return $param_value;
	}
	
	//Thanks to: https://code.tutsplus.com/articles/attaching-files-to-your-posts-using-wordpress-custom-meta-boxes-part-1--wp-22291
// 	static function upload_file($file) {
// 		//Get the file type of the upload
// 		$filetype = wp_check_filetype(basename($file['name']));
// 		 
// 		//File type is supported
// 		if(array_key_exists('type', $filetype) && $filetype['type']) {
// 	    // Use the WordPress API to upload the file
// 	    $upload = wp_upload_bits($file['name'], null, file_get_contents($file['tmp_name']));
// 	
// 	    if(isset($upload['error']) && $upload['error'] != 0) {
// 	    	//!!! - Better error handling
// 	    	wp_die(esc_html__('File upload error.', Joe_Config::get_item('plugin_text_domain')) . ' (' . $upload['error'] . ')');
// 	    } else {
//         return $upload;     
// 	    }
// 		} else {
// 			//!!! - Better error handling
// 			wp_die(esc_html__('The file type uploaded is not supported.', Joe_Config::get_item('plugin_text_domain')));
// 		}
// 	}

	static function get_file_contents($file) {
		$response = [];

		//Ensure file is upload
		if(is_uploaded_file($file['tmp_name'])) {	
			//Get extension
			$file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);

			//Get *actual* MIME type
			$file_mime = mime_content_type($file['tmp_name']);
			
			//Is allowed file
			if(self::allowable_file($file_ext, $file_mime)) {
				$response = array_merge($response, array(
					'file_type' => $file_ext,
					'file_mime' => $file_mime,
					'file_contents' => file_get_contents($file['tmp_name']),
					'file_info' => $file
				));		
			//Not allowable file
			} else {
				$response['error'] = esc_html__('The file extension uploaded is not supported.', Joe_Config::get_item('plugin_text_domain'));		
				$response['file_ext'] = $file_ext;
				$response['file_mime'] = $file_mime;					
			}						
		}
		
		return $response;
	}	

	public static function allowable_file($ext = '', $mime = '', $file_image = 'file') {
		$allowable_mimes = Joe_Config::get_item('mimes', $file_image);
		
		//Valid extension
		if(array_key_exists($ext, $allowable_mimes)) {
			if($mime === false) {
				return true;
			}
			
			//Check MIME
			//Single
			if(is_string($allowable_mimes[$ext])) {
				return $mime == $allowable_mimes[$ext];
			//Multiple
			} elseif(is_array($allowable_mimes[$ext])) {
				return in_array($mime, $allowable_mimes[$ext]);
			}
		}
		
		return false;
	}	
	

//		add_filter('wp_check_filetype_and_ext', array($this, 'wp_check_filetype_and_ext'), 10, 4);				
// 	function wp_check_filetype_and_ext( $check, $file, $filename, $mimes ) {
// 		//Check file type
// 		$filetype = wp_check_filetype($filename, Joe_Config::get_item('mimes', 'file'));
// 
// 		//File type we are interested in
// 		if(array_key_exists('type', $filetype) && $filetype['type']) {
// 			//Allow
// 			return array(
// 				'ext' => $filetype['ext'],
// 				'type' => $filetype['type'],
// 				'proper_filename' => $filename
// 			);
// 		}
// 	
// 		return $check;
// 	}	
}
