<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
	
class Template {
	var $theme = '';
	var $directory = 'public/';
	var $path = array(
		'STYLE' => 'styles/',
		'SCRIPT' => 'scripts/'
	);
	var $filename = 'index';
	var $main_title = '';
	var $alt_title = '';
	var $fragment = array(
		'title' => '',
		'head' => '',
		'navigation' => '',
		'header' => '',
		'sidebar' => '',
		'content' => '', 
		'footer' => ''
	);
	var $response = '';
	var $module = array();
	var $ci = NULL;
	var $type = 'html';
	var $enabled = TRUE;
	var $allowed = array(
		'head', 
		'navigation', 
		'header', 
		'sidebar', 
		'content',
		'footer'
	);
	
	function Template() 
	{
		$this->ci =& get_instance();
		
		$this->ci->config->load('application', TRUE);
		$this->site_name = $this->ci->config->item('site_name', 'application');
		$this->site_tagline = $this->ci->config->item('site_tagline', 'application');
		$config = $this->ci->config->item('template', 'application');
		
		$this->theme = $config['theme'] . '/';
		$this->filename = $config['filename'];
		
		$this->ci->ui = $this;
		$this->ci->template = $this;
	}
	
	function enable()
	{
		$this->enabled = TRUE;
	}
	
	function disable()
	{
		$this->enabled = FALSE;
	}
	
	function set_output($type = 'html') 
	{
		$allowed = array('json', 'text', 'html');
		
		if (in_array($type, $allowed)) 
		{
			$this->type = $type;
		}
	}
	
	function set_title($title = '')
	{
		$this->main_title = $title;
	}
	
	function replace_title($title = '')
	{
		$this->alt_title = $title;
	}
	
	function set_template($dir = '') 
	{
		if (is_dir($this->directory . $this->path['STYLE'] . $dir)) 
		{
			$this->theme = $dir;
		}
	}
	
	function set_module($attr, $html = '')
	{
		if (is_array($attr)) 
		{
			foreach ($attr as $key => $value)
			{
				$this->module[$key] = $value;
			}
		}
		else {
			$this->module[$attr] = $html;
		}
	}
	
	function set_file($file = '') 
	{
		if (is_file($this->directory . $this->path['STYLE'] . $this->theme . $file)) 
		{
			$this->filename = $file;
		}
	}
	
	function add_script($file = '')
	{
		if (trim($file) !== '')
		{
			$this->fragment['head'] .= '<script type="text/javascript" src="' . $file . '"></script>';
		}
	}
	
	function no_cache()
	{
		$this->output->set_header("Cache-Control: no-cache, must-revalidate");
		$this->output->set_header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
	}
	
	function view($file, $data = array(), $part = 'content') 
	{
		$part = (($part == NULL || $part == '') ? 'content' : $part);
		
		if (in_array($part, $this->allowed)) 
		{
			$this->fragment[$part] .= $this->ci->load->view($file, $data, TRUE);
		}
	}
	
	function parse($file, $data = array(), $part = 'content') 
	{
		$part = (($part == NULL || $part == '') ? 'content' : $part);
		
		if (in_array($part, $this->allowed)) 
		{
			$this->fragment[$part] .= $this->ci->parser->parse($file, $data, TRUE);
		}
	}
	
	function clear($part = '') 
	{
		$part = (($part == NULL || $part == '') ? 'content' : $part);
		
		if (in_array($part, $this->allowed)) 
		{
			$this->fragment[$part] = '';
		}
	}
	
	public function append($content = '', $part = 'content') 
	{
		$part = (($part == NULL || $part == '') ? 'content' : $part);
		
		if (in_array($part, $this->allowed)) 
		{
			$this->fragment[$part] .= $content;
		}
	}
	
	function prepend($content = '', $part = 'content') 
	{
		$part = (($part == NULL || $part == '') ? 'content' : $part);
		
		if (in_array($part, $this->allowed)) 
		{
			$this->fragment[$part] = $content . $this->fragment[$part];
		}
	}
	
	function data($data)
	{
		$this->response = $data;
	}
	
	function render() 
	{
		if ( !! $this->enabled) 
		{
			if ($this->type == 'json') 
			{
				$response = json_encode($this->response);
				$this->ci->output->set_output($response);
			}
			elseif ($this->type == 'text') 
			{
				$response = $this->response;
				$this->ci->output->set_output($response);
			}
			else 
			{
				$data = file_get_contents(dirname( FCPATH ) . '/' . $this->directory . $this->path['STYLE'] . $this->theme . $this->filename, FALSE);
				
				$search = array(
					'{{HEAD}}',
					'{{NAVIGATION}}',
					'{{HEADER}}',
					'{{SIDEBAR}}',
					'{{CONTENT}}',
					'{{FOOTER}}'
				);
				
				$replace = array(
					$this->fragment['head'],
					$this->fragment['navigation'],
					$this->fragment['header'],
					$this->fragment['sidebar'],
					$this->fragment['content'],
					$this->fragment['footer']
				);
				
				$data = str_replace($search, $replace, $data);
				$data = $this->_standard($data);
				
				// Run it through CI default output, so it can be cache
				$this->ci->output->set_output($data);
			}
		}
	}
	
	function _standard($data) 
	{
		$title = $this->site_name;
		if (trim($this->site_tagline) != '')
		{
			$title .= ' | ' . $this->site_tagline;
		}
		
		if (trim($this->main_title) != '') 
		{
			$title = $this->main_title . ' &raquo; ' . $title;
		}
		
		if (trim($this->alt_title) != '') 
		{
			$title = $this->alt_title;
		}
		
		
		$search = array(
			'{{PAGE-NAME}}',
			'{{PAGE-TITLE}}',
			'{{TITLE}}',
			'{{TAGLINE}}',
			'{{URI}}',
			'{{BASE-URI}}',
			'{{INDEX-URI}}',
			'{{STYLE-URI}}',
			'{{SCRIPT-URI}}'
		);
		
		$replace = array(
			$this->main_title,
			$title,
			$this->site_name,
			$this->site_tagline,
			current_url(),
			base_url(),
			site_url(),
			$this->ci->config->config['base_url'] . $this->directory . $this->path['STYLE'] . $this->theme,
			$this->ci->config->config['base_url'] . $this->directory . $this->path['SCRIPT'],
		);
		
		if (count($this->module) > 0) 
		{
			foreach ($this->module as $key => $value) 
			{
				array_push($search, '{{MODULE-' . strtoupper( $key ) . '}}');
				array_push($replace, $value);
			}
		}
		
		return @str_replace($search, $replace, $data);
	}
}