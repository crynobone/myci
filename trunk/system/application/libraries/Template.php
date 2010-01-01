<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
	
class Template {
	private $CI_output = NULL;
	private $CI_config = NULL;
	private $CI_load = NULL;
	private $CI_parser = NULL;
	
	public $theme = '';
	public $directory = 'public/';
	public $path = array(
		'STYLE' => 'styles/',
		'SCRIPT' => 'scripts/'
	);
	public $filename = 'index';
	public $main_title = '';
	public $alt_title = '';
	
	public $response = '';
	public $module = array();
	
	private $_fragment = array(
		'title' => '',
		'head' => '',
		'navigation' => '',
		'header' => '',
		'sidebar' => '',
		'content' => '', 
		'footer' => ''
	);
	private $_type = 'html';
	private $_enabled = TRUE;
	private $_allowed = array(
		'head', 
		'navigation', 
		'header', 
		'sidebar', 
		'content',
		'footer'
	);
	
	public function Template() 
	{
		$CI =& get_instance();
		
		$CI->config->load('application', TRUE);
		$this->site_name = $CI->config->item('site_name', 'application');
		$this->site_tagline = $CI->config->item('site_tagline', 'application');
		$config = $CI->config->item('template', 'application');
		
		$this->theme = $config['theme'] . '/';
		$this->filename = $config['filename'];
		
		$CI->ui = $this;
		
		// Declare another object for backward compatiblity
		$CI->template = $this;
		
		$this->CI_config =& $CI->config;
		$this->CI_output =& $CI->output;
		$this->CI_load =& $CI->load;
		$this->CI_parser =& $CI->parser;
	}
	
	public function enable()
	{
		$this->_enabled = TRUE;
	}
	
	public function disable()
	{
		$this->_enabled = FALSE;
	}
	
	public function set_output($type = 'html') 
	{
		$allowed = array('json', 'text', 'html');
		
		if (in_array($type, $allowed)) {
			$this->_type = $type;
		}
	}
	
	public function set_title($title = '')
	{
		$this->main_title = $title;
	}
	
	public function replace_title($title = '')
	{
		$this->alt_title = $title;
	}
	
	public function set_template($dir = '') 
	{
		if (is_dir($this->directory . $this->path['STYLE'] . $dir)) {
			$this->theme = $dir;
		}
	}
	
	public function set_module($attr, $html = '')
	{
		if (is_array($attr)) {
			foreach ($attr as $key => $value) {
				$this->module[$key] = $value;
			}
		}
		else {
			$this->module[$attr] = $html;
		}
	}
	
	public function set_file($file = '') 
	{
		if (is_file($this->directory . $this->path['STYLE'] . $this->theme . $file)) {
			$this->filename = $file;
		}
	}
	
	public function add_script($file = '')
	{
		if (trim($file) !== '') {
			$this->_fragment['head'] .= '<script type="text/javascript" src="' . $file . '"></script>';
		}
	}
	
	public function no_cache() {
		$this->CI_output->set_header("Cache-Control: no-cache, must-revalidate");
		$this->CI_output->set_header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
	}
	
	public function view($file, $data = array(), $part = 'content') 
	{
		$part = (($part == NULL || $part == '') ? 'content' : $part);
		
		if (in_array($part, $this->_allowed)) {
			$this->_fragment[$part] .= $this->CI_load->view($file, $data, TRUE);
		}
	}
	
	public function parse($file, $data = array(), $part = 'content') 
	{
		$part = (($part == NULL || $part == '') ? 'content' : $part);
		
		if (in_array($part, $this->_allowed)) {
			$this->_fragment[$part] .= $this->CI_parser->parse($file, $data, TRUE);
		}
	}
	
	public function clear($part = '') 
	{
		$part = (($part == NULL || $part == '') ? 'content' : $part);
		
		if (in_array($part, $this->_allowed)) {
			$this->_fragment[$part] = '';
		}
	}
	
	public function append($content = '', $part = 'content') 
	{
		$part = (($part == NULL || $part == '') ? 'content' : $part);
		
		if (in_array($part, $this->_allowed)) {
			$this->_fragment[$part] .= $content;
		}
	}
	
	public function prepend($content = '', $part = 'content') 
	{
		$part = (($part == NULL || $part == '') ? 'content' : $part);
		
		if (in_array($part, $this->_allowed)) {
			$this->_fragment[$part] = $content . $this->_fragment[$part];
		}
	}
	
	public function data($data)
	{
		$this->response = $data;
	}
	
	public function render() 
	{
		if ( !! $this->_enabled) {
			switch ($this->_type) {
				case 'json' :
					$response = json_encode($this->response);
					$this->CI_output->set_output($response);
					break;
				case 'text' :
					$response = $this->response;
					$this->CI_output->set_output($response);
					break;
				default :
					$data = file_get_contents(
						dirname( FCPATH ) . '/' . $this->directory . $this->path['STYLE'] . $this->theme . $this->filename, 
						FALSE
					);
					
					$search = array(
						'{{HEAD}}',
						'{{NAVIGATION}}',
						'{{HEADER}}',
						'{{SIDEBAR}}',
						'{{CONTENT}}',
						'{{FOOTER}}'
					);
					
					$replace = array(
						$this->_fragment['head'],
						$this->_fragment['navigation'],
						$this->_fragment['header'],
						$this->_fragment['sidebar'],
						$this->_fragment['content'],
						$this->_fragment['footer']
					);
					
					$data = str_replace($search, $replace, $data);
					$data = $this->_standard($data);
					
					// Run it through CI default output, so it can be cache
					$this->CI_output->set_output($data);
					break;
			}
		}
	}
	
	private function _standard($data) 
	{
		$title = $this->site_name;
		
		if (trim($this->site_tagline) != '') {
			$title .= ' | ' . $this->site_tagline;
		}
		
		if (trim($this->main_title) != '') {
			$title = $this->main_title . ' &raquo; ' . $title;
		}
		
		if (trim($this->alt_title) != '') {
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
			$this->CI_config->config['base_url'] . $this->directory . $this->path['STYLE'] . $this->theme,
			$this->CI_config->config['base_url'] . $this->directory . $this->path['SCRIPT'],
		);
		
		if (count($this->module) > 0) {
			foreach ($this->module as $key => $value) {
				array_push($search, '{{MODULE-' . strtoupper( $key ) . '}}');
				array_push($replace, $value);
			}
		}
		
		return @str_replace($search, $replace, $data);
	}
}