<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 
 * @return 
 * @param object $text
 * @param object $attributes
 */
if(!function_exists('strong'))
{
	function strong($text, $attributes)
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
		
		$output .= '<strong '.$attributes.'>';
		$output .= $text;
		$output .= '</strong>';
	}
}
?>