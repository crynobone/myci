<?php if (!defined('BASEPATH')) exit ('No direct script access allowed');

class MY_URI extends CI_URI
{
    var $auto_segments = array ();
    var $filters = array (
	    'id' => 0,
	    'user' => '',
	    'offset' => 0,
	    'sort' => 'desc'
    );
	function MY_URI() 
	{
		parent::CI_URI();
	}
    function auto_segment($input = array ())
    {
        if (is_array($input) and count($input) > 0)
        {
            $this->filters = array_merge($input, $this->filters);
        }

        $data = array ();
        $not = array ();

        foreach ($this->segments as $key=>$value)
        {
            $is = elements(trim($value), $this->filters, FALSE);

            if ($is !== FALSE)
            {
                if ( isset ($this->segments[($key+1)]))
                {
                    $data[$value] = $this->segments[($key+1)];
                    array_push($not, ($key+1));
                }
                else
                {
                    $data[$value] = $this->filters[$value];
                }
            }
            elseif (!in_array($key, $not))
            {
                $data[$key] = $value;

            }
        }

        $this->auto_segments = $data;
    }
    function smart($key = 'id')
    {
        return ( isset ($this->auto_segments[$key]) ? $this->auto_segments[$key] : $this->filters[$key]);
    }
}
?>
