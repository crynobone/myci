<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Simple_form {
	var $CI = NULL;
	var $fields = array ();
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
	
	function Simple_form()
	{
		$this->CI =& get_instance();
		$this->CI->load->library(array(
			'form_validation'
		));
		$this->CI->load->helper('form');
		
		$this->CI->simple_form = $this;
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
			$field = $this->_tune_field($field);
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
	
	function generate($options = array(), $id = 'default')
	{
		$this->vars($options, $id);
		
		$validate = $this->validate;
		$template = $this->template;
		
		$fields = $this->fields[$id];
		$this->post($fields, $id);
		$pre = $id . '_';
		
		$hidden = array ();
		$output = '<'. $template['fieldset'] . ' class="' . $template['fieldset_class'] . '">';
		
		foreach ($fields as $field) 
		{
			$field = $this->_tune_field($field);
			$name = $pre . $field['id'];
			$this->CI->form_validation->set_rules($name, $field['name'], $field['rule']);
		}
		
		$run = $this->CI->form_validation->run();
		
		foreach ($fields as $field) 
		{
			$field = $this->_tune_field($field);
			$type = strtolower($field['type']);
			
			$name = $pre . $field['id'];
			
			
			if ($type !== 'hidden')
			{
				
				$output .= '<' . $template['group']. ' id="tr_' . $name . '" class="'. $template['group_class'] . '">';
				$output .= '<'. $template['label'] . ' class="'. $template['label_class'] . '">' . $field['name'] . '</'. $template['label'] . '>';
				$output .= '<' . $template['field']. ' class="'. $template['field_class'] . '">';
			}
			
			$value = $this->_pick_standard($name, $field);
			
			switch ($type) {
				case 'hidden' :
					$hidden[$name] = $value;
					break;
				case 'password' :
					$output .= form_password(array (
						'name' => $name,
						'id' => $name,
						'value' => form_prep($value),
						'class' => $field['class'],
						'maxlength' => $field['maxlength']
					));
					break;
				case 'upload' :
					$output .= form_upload(array (
						'name' => $name,
						'id' => $name,
						'value' => form_prep($value),
						'class' => $field['class']
					));
					break;
				case 'textarea' :
					$output .= form_textarea(array (
						'name' => $name,
						'id' => $name,
						'value' => form_prep($value),
						'rows' => $field['rows'],
						'class' => $field['class'],
						'maxlength' => $field['maxlength']
					));
					break;
				case 'select' :
					$output .= form_dropdown(
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
						
						$output .= form_radio(array (
							'id' => $radio_name,
							'name' => $name,
							'value' => form_prep($key),
							'checked' => ($value == $key ? TRUE : FALSE),
							'class' => $field['class']
						));
						$output .= form_label($val, $radio_name);
						
						$i++;
					}
					
					break;
					
				case 'custom' :
					$output .= $field['html'];
					break;
				case 'checkbox' :
					
					$checked = FALSE;
					
					if ($value == TRUE) 
					{
						$checked = TRUE;
					}
					
					$output .= form_checkbox(array (
						'id' => $name,
						'name' => $name,
						'value' => form_prep($value),
						'checked' => $checked,
						'class' => $field['class']
					));
					$output .= form_label($field['desc'], $name);
					break;
				default :
					$output .= form_input(array (
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
					$output .= $field['html'];
				}
				
				if ( ! in_array($type, array('checkbox', 'radio'))) 
				{
					$output .= '<em>' . $field['desc'] . '</em>';
					$output .= form_error($name, '<' . $this->template['error'] . ' class="' . $this->template['error_class'] . '">', '</' . $this->template['error'] .'>');
				}
				
				
			}
			
			$output .= '</' . $template['field']. '></' . $template['group']. '>';
		}
		
		$output .= '</' . $template['fieldset'] . '>';
		
		if ($run == FALSE)
		{
			$this->success = FALSE;
		}
		
		$this->result[$id] = $run;
		
		return form_hidden($hidden) . $output;
	}
	
	function _tune_field($field = array())
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
	
	function _pick_standard($name, $field)
	{
		
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