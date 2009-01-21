<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Generate dropdown select with <optgroup/>
 * @alias form_dropdown_group
 *
 * @return string
 * @param string $name[required]
 * @param array $data[required]
 * @param string $selected[optional]
 * @param string $attribute[optional]
 * @param string $prefix[optional]
*/
if(!function_exists('form_dropdown_group'))
{
    function form_dropdown_group($name = '', $data = array(), $selected = '', $attributes = '', $prefix = '')
	{
		// Were any attributes submitted?  If so generate a string
		if (is_array($attributes))
		{
			$atts = '';
			foreach ($attributes as $key => $val)
			{
				$atts .= ' ' . $key . '="' . $val . '"';
			}
						
			$attributes = $atts;
		}
		
		$output = '<select name="' . $name . '" '.$attributes.'>';
		
		if(count($data) > 0)
		{
			foreach($data as $key => $value)
			{
				if(is_array($value))
				{
					if(isset($value[1]) and $value[1] == $prefix.'0')
					{
						$output .= '<optgroup label="'.$value[0].'">';
						
						foreach($data as $skey => $svalue)
						{
							if(isset($svalue[1]) and $prefix.$svalue[1] == $prefix.$key)
							{
								$output .= '<option value="'.$skey.'" '.($selected == $skey ? 'selected="selected"' : '').'>'.$svalue[0].'</option>';
							}
						}
						$output .= '</optgroup>';
					}
						
				}
				else
				{
					$output .= '<option value="' . $key . '" '.($selected == $key ? 'selected="selected"' : '').'>'.$value.'</option>';
				}
			}
		}
			
		$output .= '</select>';
		
		return $output;
	}
}

if(!function_exists('form_dropdown_parent'))
{
	function form_dropdown_parent($name = '', $data = array(), $selected = '', $attributes = '', $prefix = '')
	{
		// Were any attributes submitted?  If so generate a string
		if (is_array($attributes))
		{
			$atts = '';
			foreach ($attributes as $key => $val)
			{
				$atts .= ' ' . $key . '="' . $val . '"';
			}
			
			$attributes = $atts;
		}
		
		$output = '<select name="' . $name . '" '.$attributes.'>';
		
		if(count($data) > 0)
		{
			foreach($data as $key => $value)
			{
				if(is_array($value))
				{
					if(isset($value[1]) and $value[1] == $prefix.'0')
					{
						$output .= '<option value="'.$key.'" '.($selected == $key ? 'selected="selected"' : '').'>'.$value[0].'</option>';
						
						foreach($data as $skey => $svalue) 
						{
							if(isset($svalue[1]) and $prefix.$svalue[1] == $prefix.$key) 
							{
								$output .= '<option value="'.$skey.'" '.($selected == $skey ? 'selected="selected"' : '').'>&nbsp;&mdash; '.$svalue[0].'</option>';
							}
						}
					}
				}
				else 
				{
					$output .= '<option value="' . $key . '" '.($selected == $key ? 'selected="selected"' : '').'>'.$value.'</option>';
				}
			}
		}
		
		$output .= '</select>';
		
		return $output;
	}
}
?>