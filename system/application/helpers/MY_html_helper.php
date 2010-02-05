<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Generates the list
 *
 * Generates an HTML ordered list from an single or multi-dimensional array.
 *
 * @access	private
 * @param	string
 * @param	mixed
 * @param	mixed
 * @param	intiger
 * @return	string
 */
if ( ! function_exists('_list'))
{
	function _list($type = 'ul', $list, $attributes = '', $depth = 0)
	{
		// If an array wasn't submitted there's nothing to do...
		if ( ! is_array($list))
		{
			return $list;
		}

		// Set the indentation based on the depth
		$out = str_repeat(" ", $depth);

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

		// Write the opening list tag
		$out .= "<".$type. " " .$attributes.">\n";

		// Cycle through the list elements.  If an array is
		// encountered we will recursively call _list()

		static $_last_list_item = '';
		foreach ($list as $key => $val)
		{
			$_last_list_item = $key;

			$out .= str_repeat(" ", $depth + 2);
			$out .= "<li>";

			if ( ! is_array($val))
			{
				$out .= $val;
			}
			else
			{
				$out .= $_last_list_item."\n";
				$out .= _list($type, $val, '', $depth + 4);
				$out .= str_repeat(" ", $depth + 2);
			}

			$out .= "</li>\n";
		}

		// Set the indentation for the closing tag
		$out .= str_repeat(" ", $depth);

		// Write the closing list tag
		$out .= "</".$type.">\n";

		return $out;
	}
}

/**
 * 
 * @return 
 * @param object $text
 * @param object $attributes
 */
if(!function_exists('dom'))
{
	function dom($text = '', $type = 'strong', $attributes = '')
	{
		$type = strtolower( $type );
		
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
		
		$output = '<' . $type . ' ' . $attributes . '>';
		$output .= $text;
		$output .= '</'. $type .'>';
		
		return $output;
	}
}