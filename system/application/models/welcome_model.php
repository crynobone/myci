<?php

class Welcome_model extends Model
{
    function __construct()
    {
    	parent::Model();
    }
	function sloppy() 
	{
		return array (
			'title' => 'Hello world',
			'desc' => 'Hahaha'
		);
	}
}

?>