<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Form Generator for CodeIgniter
 *
 * PHP version 5
 *
 * @category  CodeIgniter
 * @package   Form CI
 * @author    Mior Muhammad Zaki (hello@crynobone.com)
 * @version   0.1
 * Copyright (c) 2009 Mior Muhammad Zaki  (http://crynobone.com)
 * Licensed under the MIT.
*/

class Form {
	// CI singleton
	var $CI = NULL;
	
	// form output
	var $output = array ();
	
	// form data
	var $fields = array ();
	var $value = array ();
	var $data = array ();
	var $full_data = array ();
	
	// validation result
	var $validate = TRUE;
	var $result = array ();
	var $success = TRUE;
	
	// default template
	var $template = array (
		'fieldset' => 'fieldset',
		'fieldset_class' => '',
		'group' => 'div',
		'group_class' => 'fields',
		'label' => 'label',
		'label_class' => '',
		'field' => 'div',
		'field_class' => '',
		'error' => 'div',
		'error_class' => 'errorbox'
	);
	
	/**
	 * Constructor 
	 * 
	 * @access 		public
	 * @return 		void
	 */
	function Form()
	{
		// load CI object
		$this->CI =& get_instance();
		
		// load required libraries and helpers
		$this->CI->load->library(array(
			'form_validation'
		));
		$this->CI->load->helper('form');
		
		// add this class to CI object
		$this->CI->form = $this;
		
		log_message('debug', "Form Class Initialized");
	}
	
	/**
	 * Check whether the form is validated
	 * 
	 * @access public
	 * @param boolean $valid [optional]
	 * @return void
	 */
	function validation($valid = TRUE)
	{
		if (is_bool($valid))
		{
			$this->validate = $valid;
		}	
	}
	
	/**
	 * Load new form fields to stack
	 * 
	 * @access public
	 * @param array $fields [optional]
	 * @param string $id [optional]
	 * @return void
	 */
	function vars($fields = array(), $id = 'default')
	{
		if ( ! isset($this->fields[$id])) 
		{
			$this->fields[$id] = array ();
		}
		$this->fields[$id] = array_merge($this->fields[$id], $fields);
	}
	
	/**
	 * Extract form field values from $_POST or $this->CI->input->post()
	 * 
	 * @access public
	 * @param array $fields [optional]
	 * @param string $id [optional]
	 * @return array
	 */
	function post($fields = array(), $id = 'default')
	{
		$data = array ();
		
		foreach ($fields as $field)
		{
			$field = $this->_prepare_field($field);
			$type = strtolower($field['type']);
			
			if ($type === 'checkbox')
			{
				$checkbox = strtolower($this->CI->input->post($id . '_' . $field['id'], $field['xss']));
				
				$data[$field['id']] = (( !! isset($checkbox) && $checkbox === 'true') ? TRUE : FALSE);
			}
			else 
			{
				$data[$field['id']] = $this->CI->input->post($id . '_' . $field['id'], $field['xss']);
			}
		}
		
		$this->data = array_merge($this->data, $data);
		$this->full_data = array_merge($this->full_data, array("$id" => $data));
		
		return $data;
	}
	
	/**
	 * Return form result for all or based on prefix
	 *  
	 * @access public
	 * @param array $id [optional]
	 * @return array
	 */
	function result($id = '')
	{
		if (trim($id) !== '')
		{
			return $this->full_data[$id];
		}
		else 
		{
			return $this->data;
		}
	}
	
	/**
	 * Generate HTML (for form) from stack
	 * 
	 * @access public
	 * @param array $options [optional]
	 * @param string $id [optional]
	 * @param array $alt [optional]
	 * @return 
	 */
	function generate($options = array(), $id = 'default', $alt = array(), $is_form = TRUE)
	{
		$this->vars($options, $id);
		
		$validate = $this->validate;
		$template = $this->template;
		
		$fields = $this->fields[$id];
		$this->post($fields, $id);
		$pre = $id . '_';
		
		if ( ! isset($this->output[$id]))
		{
			$this->output[$id] = array ();
		}
		
		if ( ! isset($this->value[$id]))
		{
			$this->value[$id] = array ();
		}
		
		$hidden_html = '';
		$final_html = sprintf('<%s class="%s">', $template['fieldset'], $template['fieldset_class']);
		
		// configure form validation rules
		foreach ($fields as $field) 
		{
			$field = $this->_prepare_field($field);
			$name = $pre . $field['id'];
			
			if ( !! $is_form)
			{
				$rule = str_replace('matches[', 'matches['.$pre, $field['rule']);
				$this->CI->form_validation->set_rules($name, $field['name'], $rule);
			}
		}
		
		// run the form validation
		$run = $this->CI->form_validation->run();
		
		foreach ($fields as $field) 
		{
			$hidden = '';
			$html = '';
			
			// each field need to have minimum set of data to avoid PHP errors, in case you didn't add
			$field = $this->_prepare_field($field);
			// type need to be lowercase
			$type = strtolower($field['type']);
			
			// add a prefix to this form, to avoid conflict name
			$name = $pre . $field['id'];
			
			// dropdown is actually select
			if ($type === 'dropdown')
			{
				$type = 'select';
			}
			
			// load field label for all fieldtype except hidden
			if ($type !== 'hidden')
			{
				$html .= sprintf('<%s id="tr_%s" class="%s">', $template['group'], $name, $template['group_class']);
				$html .= sprintf('<%s class="%s">%s</%s>', $template['label'], $template['label_class'], $field['name'], $template['label']);
				$html .= sprintf('<%s class="%s">', $template['field'], $template['field_class']);
			}
			
			$value = $this->_pick_standard($name, $field, $alt);
			
			if ( ! $is_form) 
			{
				if ( ! in_array($type, array('hidden')))
				{
					$html .= $value;
				
					if (trim($field['html_view']) !== '')
					{
						$html .= $field['html_view'];
					}
				}
			}
			else
			{
				// check form field type
				switch ($type) {
					case 'hidden' :
						$hidden .= form_hidden($name, $value);
						break;
					case 'password' :
						$html .= form_password(array (
							'name' => $name,
							'id' => $name,
							'value' => form_prep($value),
							'class' => $field['class'],
							'maxlength' => $field['maxlength']
						));
						break;
					case 'upload' :
						$html .= form_upload(array (
							'name' => $name,
							'id' => $name,
							'value' => form_prep($value),
							'class' => $field['class']
						));
						break;
					case 'textarea' :
						$html .= form_textarea(array (
							'name' => $name,
							'id' => $name,
							'value' => form_prep($value),
							'rows' => $field['rows'],
							'class' => $field['class'],
							'maxlength' => $field['maxlength']
						));
						break;
					case 'select' :
						$html .= form_dropdown(
							$name,
							$field['options'],
							$value,
							'id="' . $name . '" class="' . $field['class'] . '"'
						);
						break;
					
					case 'radio' :
						$i = 0;
						foreach ($field['options'] as $key => $val)
						{
							$radio_name = $name . '_' . $key;
							
							// give each radio a breakline
							if ($i > 0)
							{
								$html .= '<br />';
							}
							
							$html .= form_radio(array (
								'id' => $radio_name,
								'name' => $name,
								'value' => form_prep($key),
								'checked' => ($value == $key ? TRUE : FALSE),
								'class' => $field['class']
							));
							$html .= form_label($val, $radio_name);
							
							$i++;
						}
						
						break;
						
					case 'custom' :
						$html .= $field['html'];
						break;
					case 'checkbox' :
						
						$checked = FALSE;
						
						if ($value == TRUE) 
						{
							$checked = TRUE;
						}
						
						$html .= form_checkbox(array (
							'id' => $name,
							'name' => $name,
							'value' => form_prep($value),
							'checked' => $checked,
							'class' => $field['class']
						));
						$html .= form_label($field['desc'], $name);
						break;
					default :
						$html .= form_input(array (
							'name' => $name,
							'id' => $name,
							'value' => form_prep($value),
							'class' => $field['class'],
							'maxlength' => $field['maxlength']
						));
						break;
				}
			
				if ( ! in_array($type, array('hidden')))
				{
					if ($type !== 'custom' && trim($field['html']) !== '')
					{
						$html .= $field['html'];
					}
					
					if ( ! in_array($type, array('checkbox', 'radio'))) 
					{
						$html .= sprintf('<em>%s</em>',  $field['desc']);
						$html .= form_error(
							$name, 
							sprintf('<%s class="%s">', $this->template['error'], $this->template['error_class']), 
							sprintf('</%s>', $this->template['error'])
						);
					}
				}
			}
			
			$html .= sprintf('</%s></%s>', $template['field'], $template['group']);
			
			if ( ! in_array($type, array('hidden')))
			{
				$this->output[$id][$field['id']] = $html;
				$final_html .= $html;
			}
			else 
			{
				$this->output[$id][$field['id']] = $hidden;
				$hidden_html .= $hidden;
			}
			
			$this->value[$id][$field['id']] = $value;
		}
		
		$final_html .= sprintf('</%s>', $template['fieldset']);
		
		if ($run == FALSE)
		{
			$this->success = FALSE;
		}
		
		$this->result[$id] = $run;
		
		return $hidden_html . $final_html;
	}
	
	/**
	 * Prepare all the fields for minimum requirement
	 * 
	 * @access private
	 * @param array $field [optional]
	 * @return array
	 */
	function _prepare_field($field = array())
	{
		$default = array(
			"name" => "",
			"desc" => "",
			"id" => "",
			"standard" => "",
			"type" => "text",
			"options" => array(),
			"class" => "",
			"html" => "",
			"html_view" => "", 
			"maxlength" => "",
			"rule" => "",
			"rows" => "4",
			"xss" => FALSE
		);
		
		return $result = array_merge($default, $field);
	}
	
	/**
	 * Set input value for each of the fields
	 * 
	 * @access private
	 * @param string $name
	 * @param array $field
	 * @param array $alt
	 * @return string
	 */
	function _pick_standard($name, $field, $alt)
	{
		if ( !! isset($alt[$field['id']]) && trim($alt[$field['id']]) !== '') 
		{
			$field['value'] = $alt[$field['id']];
		}
		
		if ( ! isset($field['value']) || $field['value'] === NULL) 
		{
			$field['value'] = $field['standard'];
		} 
		
		return set_value($name, $field['value']);
	}
	
	/**
	 * Set HTML template for Form
	 * 
	 * @access public
	 * @param array $data
	 * @return void
	 */
	function set_template($data) {
		if (is_array($data)) 
		{
			$this->template = array_merge($this->template, $data);
		}
	}
	
	/**
	 * Run the validation library
	 * 
	 * @access public
	 * @param string $id [optional]
	 * @return boolean
	 */
	function run($id = '')
	{
		if (trim($id) !== '')
		{
			return $this->success;
		}
		else {
			return $this->result[$id];
		}
		
	}
}