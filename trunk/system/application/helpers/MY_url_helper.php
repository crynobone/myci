<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Show base url (added index_page information if available)
 * @alias index_url
 * @return string
 */
if (!function_exists('index_url'))
{
    function index_url()
    {
        return site_url();
    }
}

/**
 * 
 * @return 
 * @param object $filters[optional]
 */
if (!function_exists('segment_url'))
{
    function segment_url($filters = array ())
    {
        $CI = & get_instance();
        $CI->uri->auto_segment();

        $uri = "";

        $keys = array ();

        foreach ($CI->uri->auto_segments as $key => $value)
        {
            if (!is_int($key))
            {
                $filter = elements($key, $filters);

                if ($filter !== FALSE)
                {
                    array_push($keys, $key);
                    $uri .= $key."/".$filter."/";
                }
                else
                {
                    $uri .= $key."/".$value."/";
                }
            }
            else
            {
                $uri .= $value."/";
            }
        }

        foreach ($filters as $fkey=>$fvalue)
        {
            if (!in_array($fkey, $keys))
			{
				if(!is_int($fkey))
				{
					$uri .= $fkey."/".$fvalue."/";
				}
				else {
					$uri .= $fvalue."/";
				}
                
			}
		}

        return site_url($uri);
    }
}

if (!function_exists('smart_anchor'))
{
	function smart_anchor($filter = array (), $text = "", $attributes = '')
	{
		$site = segment_url($filter);
		
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
		
		return '<a href="' . $site . '" ' . $attributes . '>' . $text . '</a>';
	}
}

?>