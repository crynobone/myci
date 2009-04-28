<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
	
	class Template {
		var $theme = 'default/';
		var $directory = 'public/';
		var $path = array(
			'STYLE' => 'styles/',
			'SCRIPT' => 'scripts/'
		);
		var $filename = 'index';
		var $main_title = '';
		var $title = '', $header = '', $navigation = '', $content = '', $sidebar = '', $footer = '';
		var $response = '';
		var $module = array();
		var $ci = NULL;
		var $type = 'html';
		var $enabled = TRUE;
		var $allowed = array(
			'header', 
			'navigation', 
			'content', 
			'sidebar', 
			'footer'
		);
		function Template() 
		{
			$this->config->load( 'application', TRUE );
			$this->ci =& get_instance();
			$this->ci->ui = $this;
			$this->main_title = $this->ci->config->item( 'site_name', 'application' );
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
		function set_output( $type = 'html' ) 
		{
			$allowed = array( 'json', 'text', 'html' );
			
			if ( in_array( $type, $allowed ) ) :
				$this->type = $type;
			endif;
		}
		function set_template( $dir = '' ) 
		{
			if ( is_dir( $this->directory . $this->path['STYLE'] . $dir ) ) :
				$this->theme = $dir;
			endif;
		}
		function set_file( $file = '' ) 
		{
			if ( is_dir( $this->directory . $this->path['STYLE'] . $this->theme . $file . '.html' ) ) :
				$this->filename = $file . '.html';
			endif;
		}
		function view( $file, $data = array(), $part = 'content' ) 
		{
			$part = ( ( $part == NULL or $part == '' ) ? 'content' : $part );
			
			if ( in_array( $part, $this->allowed  ) ) :
				$this->$part .= $this->ci->load->view( $file, $data, TRUE );
			endif;
		}
		function parse( $file, $data = array(), $part = 'content' ) 
		{
			$part = ( ( $part == NULL or $part == '' ) ? 'content' : $part );
			
			if ( in_array( $part, $this->allowed ) ) :
				$this->$part .= $this->ci->parser->parse( $file, $data, TRUE );
			endif;
		}
		function clear( $part = '' ) 
		{
			$part = ( ( $part == NULL or $part == '' ) ? 'content' : $part );
			
			if ( in_array( $part, $this->allowed ) ) :
				$this->$part = '';
			endif;
		}
		public function append( $content = '', $part = 'content' ) 
		{
			$part = ( ( $part == NULL or $part == '' ) ? 'content' : $part );
			
			if ( in_array( $part, $this->allowed ) ) :
				$this->$part .= $content;
			endif;
		}
		function prepend( $content = '', $part = 'content' ) 
		{
			$part = ( ( $part == NULL or $part == '') ? 'content' : $part);
			
			if ( in_array($part, $this->allowed ) ) :
				$this->$part = $content . $this->$part;
			endif;
		}
		function render() 
		{
			$this->publish();
		}
		function data( $data )
		{
			$this->response = $data;
		}
		function publish() 
		{
			if ( !!$this->enabled ) :
				if ( $this->type == 'json' ) :
					$response = json_encode($this->response);
					print $response;
					die();
				elseif ( $this->type == 'text' ) :
					$response = $this->response;
					print $response;
					die();
				else :
					$data =  file_get_contents( $this->directory . $this->path['STYLE'] . $this->theme . $this->filename . '.html', FALSE );
					
					$search = array(
						'{{HEADER}}',
						'{{NAVIGATION}}',
						'{{SIDEBAR}}',
						'{{CONTENT}}',
						'{{FOOTER}}'
					);
					
					$replace = array(
						$this->header,
						$this->navigation,
						$this->sidebar,
						$this->content,
						$this->footer
					);
					
					$data = str_replace( $search, $replace, $data );
					$data = $this->_standard( $data );
					
					// Run it through CI default output, so it can be cache
					$this->ci->output->set_output( $data );
				endif;
			endif;
		}
		function _standard( $data ) 
		{
			$title = $this->site_name;
				
			if ( trim( $this->title ) != '') :
				$title = $this->title.' &raquo; ' . $title;
			endif;
			
			$search = array(
				'{{PAGE-NAME}}',
				'{{PAGE-TITLE}}',
				'{{TITLE}}',
				'{{URI}}',
				'{{BASE-URI}}',
				'{{INDEX-URI}}',
				'{{STYLE-URI}}',
				'{{SCRIPT-URI}}'
			);
			
			$replace = array(
				$this->title,
				$title,
				$this->site_name,
				current_url(),
				base_url(),
				site_url(),
				$this->ci->config->config['base_url'] . $this->directory . $this->path['STYLE'] . $this->theme,
				$this->ci->config->config['base_url'] . $this->directory . $this->path['SCRIPT'],
			);
			
			if ( count( $this->module ) > 0 ) :
				foreach ( $this->module as $key => $value ) :
					array_push( $search, '{{MODULE-' . strtoupper( $key ) . '}}' );
					array_push( $replace, $value );
				endforeach;
			endif;
			
			return @str_replace( $search, $replace, $data );
		}
	}
?>