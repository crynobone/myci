<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Simple_form {
	var $ci = NULL;
	var $fields = array();
	var $validate = TRUE;
	var $data = array();
	var $success = TRUE;
	var $template = array (
		'fieldset' => 'table',
		'fieldset_class' => 'sf_table',
		'group' => 'tr',
		'group_class' => 'sf_col',
		'label' => 'td',
		'label_class' => 'sf_row_desc',
		'field' => 'td',
		'field_class' => 'sf_row_field',
		'error' => 'div',
		'error_class' => 'errorbox'
	);
	
	function __construct()
	{
		$this->ci =& get_instance();
		$this->ci->load->library(array(
			'form_validation'
		));
		$this->ci->load->helper('form');
		
		$this->ci->simple_form = $this;
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
				$checkbox = strtolower($this->ci->input->post($id . '_' . $field['id'], $field['xss']));
				
				$data[$field['id']] = (( !! isset($checkbox) && $checkbox === 'true') ? TRUE : FALSE);
			}
			else 
			{
				$data[$field['id']] = $this->ci->input->post($id . '_' . $field['id'], $field['xss']);
			}
		}
		
		$this->data = array_merge($this->data, $data);
		
		return $data;
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
			$this->ci->form_validation->set_rules($name, $field['name'], $field['rule']);
		}
		
		$run = $this->ci->form_validation->run();
		
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
	function run()
	{
		return $this->success;
	}
}