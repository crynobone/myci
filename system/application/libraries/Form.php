<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Form {
	var $CI = NULL;
	var $fields = array ();
	var $output = array ();
	var $validate = TRUE;
	var $data = array ();
	var $full_data = array ();
	var $result = array ();
	var $success = TRUE;
	
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
	
	function Form()
	{
		$this->CI =& get_instance();
		$this->CI->load->library(array(
			'form_validation'
		));
		$this->CI->load->helper('form');
		
		$this->CI->form = $this;
		
		log_message('debug', "Form Class Initialized");
	}
	
	function validation($valid = TRUE)
	{
		if (is_bool($valid))
		{
			$this->validate = $valid;
		}	
	}
	function vars($fields = array(), $id = 'default')
	{
		if ( ! isset($this->fields[$id])) 
		{
			$this->fields[$id] = array ();
		}
		$this->fields[$id] = array_merge($this->fields[$id], $fields);
	}
	
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
	
	function generate($options = array(), $id = 'default', $alt = array())
	{
		$this->vars($options, $id);
		
		$validate = $this->validate;
		$template = $this->template;
		
		$fields = $this->fields[$id];
		$this->post($fields, $id);
		$pre = $id . '_';
		
		$hidden = array ();
		$final_html = sprintf('<%s class="%s">', $template['fieldset'], $template['fieldset_class']);
		
		foreach ($fields as $field) 
		{
			$field = $this->_prepare_field($field);
			$name = $pre . $field['id'];
			
			$rule = str_replace('matches[', 'matches['.$pre, $field['rule']);
			$this->CI->form_validation->set_rules($name, $field['name'], $rule);
		}
		
		$run = $this->CI->form_validation->run();
		
		foreach ($fields as $field) 
		{
			$html = '';
			
			$field = $this->_prepare_field($field);
			$type = strtolower($field['type']);
			
			$name = $pre . $field['id'];
			
			if ($type === 'dropdown')
			{
				$type = 'select';
			}
			
			
			if ($type !== 'hidden')
			{
				
				$html .= sprintf('<%s id="tr_%s" class="%s">', $template['group'], $name, $template['group_class']);
				$html .= sprintf('<%s class="%s">%s</%s>', $template['label'], $template['label_class'], $field['name'], $template['label']);
				$html .= sprintf('<%s class="%s">', $template['field'], $template['field_class']);
			}
			
			$value = $this->_pick_standard($name, $field, $alt);
			
			switch ($type) {
				case 'hidden' :
					$hidden[$name] = $value;
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
						
						if ($i > 0)
						{
							$output .= '<br />';
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
			
			$html .= sprintf('</%s></%s>', $template['field'], $template['group']);
			
			$this->output[$name] = $html;
			$final_html .= $html;
		}
		
		$final_html .= sprintf('</%s>', $template['fieldset']);
		
		if ($run == FALSE)
		{
			$this->success = FALSE;
		}
		
		$this->result[$id] = $run;
		
		return form_hidden($hidden) . $final_html;
	}
	
	function _prepare_field($field = array())
	{
		$default = array(
			"name" => "",
			"desc" => "",
			"id" => "",
			"standard" => "",
			"type" => "text",
			"class" => "",
			"html" => "",
			"maxlength" => "",
			"rule" => "",
			"rows" => "4",
			"xss" => FALSE
		);
		
		return $result = array_merge($default, $field);
	}
	
	function _pick_standard($name, $field, $alt)
	{
		if ( !! isset($alt[$field['id']]) && trim($alt[$field['id']])) 
		{
			$field['value'] = $alt[$field['id']];
		}
		
		if ( ! isset($field['value']) || $field['value'] === NULL) 
		{
			$field['value'] = $field['standard'];
		} 
		
		return set_value($name, $field['value']);
	}
	
	function set_template($data) {
		if (is_array($data)) 
		{
			$this->template = array_merge($this->template, $data);
		}
	}
	
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