<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Form Generator for CodeIgniter
 *
 * PHP version 5
 *
 * @category  CodeIgniter
 * @package   Form CI
 * @author    Mior Muhammad Zaki (hello@crynobone.com)
 * @version   0.1.1
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
		'fieldset' => 'table',
		'fieldset_class' => 'formgrid',
		'group' => 'tr',
		'group_class' => 'fields',
		'heading_class' => 'heading',
		'label' => 'td',
		'label_class' => 'label_field',
		'field' => 'td',
		'field_class' => 'input_field',
		'radio' => 'div',
		'radio_class' => 'radio_field',
		'button_class' => 'buttons',
		'error' => 'div',
		'error_class' => 'error_box'
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
		
		$run = FALSE;
		$form_submit = 'Submit';
		$form_buttons = array ();
		
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
		$final_html = sprintf(
			'<%s class="%s">', 
			$template['fieldset'], 
			$template['fieldset_class']
		);
		
		if ( !! $is_form && $this->CI->input->post($pre . '_form_submit') != FALSE)
		{
			// configure form validation rules
			foreach ($fields as $field) 
			{
				$field = $this->_prepare_field($field);
				$name = $pre . $field['id'];
				
				// disable adding rule if it not a form or readonly
				if ( !! $is_form || $field['type'] != 'readonly')
				{
					$rule = str_replace('matches[', 'matches['.$pre, $field['rule']);
					$this->CI->form_validation->set_rules($name, $field['name'], $rule);
				}
			}
			
			// run the form validation
			$run = $this->CI->form_validation->run();
		}
		
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
			
			// load field label for all fieldtype except hidden, form_submit and heading
			if ($type == 'heading') 
			{
				$html .= sprintf(
					'<%s class="%s" %s>',
					$template['label'],
					$template['heading_class'],
					(in_array($template['label'], array('td', 'th'), FALSE) ? 'colspan="2"' : '')
				);
			}
			elseif ( ! in_array($type, array('hidden', 'form_submit')))
			{
				if ( ! ( ! $is_form && $type === 'password')) :
					$html .= sprintf(
						'<%s id="tr_%s" class="%s %s">', 
						$template['group'], 
						$name, 
						$template['group_class'], 
						$field['group_class']
					);
					$html .= sprintf(
						'<%s class="%s" %s>%s</%s>', 
						$template['label'], 
						$template['label_class'],
						($template['label'] == 'label' ? 'for="' . $name . '"' : ''), 
						($template['label'] == 'label' ? $field['name'] : form_label($field['name'], $name)), 
						$template['label']
					);
					$html .= sprintf('<%s class="%s">', $template['field'], $template['field_class']);
				endif;
			}
			
			$value = $this->_pick_standard($name, $field, $alt);
			
			if ( ! $is_form) 
			{
				if ( ! in_array($type, array('hidden', 'password')))
				{
					$display_value = $value;
					
					if (isset($field['refer_to']) && isset($alt[$field['refer_to']]))
					{
						$display_value = $alt[$field['refer_to']];
					}
					
					if (trim($field['sprintf']) == '')
					{
						$html .= $display_value;
					}
					else {
						$html .= sprintf($field['sprintf'], $display_value);
					}
					
					if ( ! in_array($type, array('checkbox', 'radio', 'readonly'))) 
					{
						$html .= sprintf('<em>%s</em>',  $field['desc']);
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
							'class' => trim($field['class']) . ' form_password',
							'maxlength' => $field['maxlength']
						));
						break;
					case 'upload' :
						$html .= form_upload(array (
							'name' => $name,
							'id' => $name,
							'value' => form_prep($value),
							'class' => trim($field['class']) . ' form_upload'
						));
						break;
					case 'textarea' :
						$html .= form_textarea(array (
							'name' => $name,
							'id' => $name,
							'value' => form_prep($value),
							'rows' => $field['rows'],
							'class' => trim($field['class']) . ' form_textarea',
							'maxlength' => $field['maxlength']
						));
						break;
					case 'select' :
						$html .= form_dropdown(
							$name,
							$field['options'],
							$value,
							'id="' . $name . '" class="' . trim($field['class']) . ' form_dropdown"'
						);
						break;
					
					case 'radio' :
						foreach ($field['options'] as $key => $val)
						{
							$radio_name = $name . '_' . $key;
							
							$html .= sprintf('<%s class="%s">', $template['radio'], $template['radio_class']);
							
							$html .= form_radio(array (
								'id' => $radio_name,
								'name' => $name,
								'value' => form_prep($key),
								'checked' => ($value == $key ? TRUE : FALSE),
								'class' => trim($field['class']) . ' form_radio'
							));
							$html .= form_label($val, $radio_name);
							
							$html .= sprintf('</%s>', $template['radio']);
						}
						
						break;
						
					case 'custom' :
						$html .= $field['html'];
						break;
					case 'readonly' :
						if (trim($field['sprintf']) == '')
						{
							$html .= $value;
						}
						else {
							$html .= sprintf($field['sprintf'], $value);
						}
						$hidden .= form_hidden($name, $value);
						break;
					case 'heading' :
						$html .= $field['name'];
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
							'value' => form_prep('true'),
							'checked' => $checked,
							'class' => trim($field['class']) . ' form_checkbox'
						));
						$html .= form_label($field['desc'], $name);
						break;
					case 'checkbox[]' :
						
						foreach ($field['options'] as $key => $val)
						{
							$check_name = $name . '_' . $key;
							
							$html .= sprintf('<%s class="%s">', $template['radio'], $template['radio_class']);
							
							$html .= form_checkbox(array (
								'id' => $check_name,
								'name' => $name . '[]',
								'value' => form_prep($key),
								'checked' => (in_array($key, $value) ? TRUE : FALSE),
								'class' => trim($field['class']) . ' form_checkbox'
							));
							$html .= form_label($val, $name);
							
							$html .= sprintf('</%s>', $template['radio']);
						}
						break;
					case 'form_submit' :
						$form_submit = $field['name'];
						break;
					default :
						$html .= form_input(array (
							'name' => $name,
							'id' => $name,
							'value' => form_prep($value),
							'class' => trim($field['class']) . ' form_input',
							'maxlength' => $field['maxlength']
						));
						break;
				}
			
				if ( ! in_array($type, array('hidden', 'heading', 'form_submit')))
				{
					if ($type !== 'custom' && trim($field['html']) !== '')
					{
						$html .= $field['html'];
					}
					
					if ( ! in_array($type, array('checkbox', 'radio', 'readonly'))) 
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
			
			if ( ! in_array($type, array('hidden', 'form_submit')))
			{
				if ( ! ( ! $is_form && $type !== 'password'))
				{
					$html .= sprintf(
						'</%s></%s>', 
						($type !== 'heading' ? $template['field'] : $template['heading']),
						$template['group']
					);
				}
			}
			
			
			if ( ! in_array($type, array('hidden', 'readonly')))
			{
				$this->output[$id][$field['id']] = $html;
				$final_html .= $html;
			}
			else 
			{
				$this->output[$id][$field['id']] = $hidden;
				$hidden_html .= $hidden;
				
				if ($type === 'readonly')
				{
					$final_html .= $html;
				}
			}
			
			$this->value[$id][$field['id']] = $value;
		}
		
		// add a submit button for form
		if ( !! $is_form)
		{
			$final_html .= sprintf(
				'<%s class="%s" %s>',
				$template['label'],
				$template['button_class'],
				(in_array($template['label'], array('td', 'th'), FALSE) ? 'colspan="2"' : '')
			);
			$final_html .= form_submit($pre . '_form_submit', $form_submit, 'class="form_submit"');
			$final_html .= sprintf('</%s></%s>', $template['label'], $template['group']);
			
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
			"display" => "", 
			"maxlength" => "",
			"rule" => "",
			"rows" => "4",
			"xss" => FALSE,
			"group_class" => "",
			"refer_to" => "",
			"sprintf" => "",
			"allow_cache" => TRUE
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
		$id = $field['id'];
		
		if ( !! isset($alt[$id]) && (is_array($alt[$id]) || trim($alt[$id]) !== '')) 
		{
			$field['value'] = $alt[$id];
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