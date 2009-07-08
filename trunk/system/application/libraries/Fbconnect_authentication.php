<?php if(!defined('BASEPATH')) exit('No direct script access allowed');

class Fbconnect_authentication
{
    var $ci 		= NULL;
	var $test 		= array ( 'id', 'name', 'pass', 'role' );
	var $optional 	= array ();
    var $member 	= array (
	    'id' => 0,
	    'name' => 'guest',
	    'fullname' => '',
	    'email' => '',
	    'role' => 0,
	    'status' => 0
    );
	var $config 	= array();
	
    function Fbconnect_authentication()
    {
        $this->ci =& get_instance();
		$this->ci->load->library('Facebook_connect');
        
		$this->ci->config->load( 'application', TRUE );
		$this->config = $this->ci->config->item( 'auth', 'application' );
		
		$this->_generate();	
	}
	function _generate()
	{
		$has_session = FALSE;
		
		$fb = $this->ci->fb_connect->initiate();
		
        if ( $this->ci->input->cookie( 'auth' ) AND $this->config['enable'] === TRUE AND $fb !== FALSE )
		{
			if ( is_array( $fb ) AND isset( $fb['value'] ) ) :
				$cookies = $fb['value'];
			else :
				$cookies = html_entity_decode( $this->ci->input->cookie( 'auth', TRUE ) );
			endif;
            $cookie = explode( "|", $cookies );
			
            if ( $cookie[2] > 0 )
			{
				$query = $this->_generate_query( $cookie );
				
				if ( $query->num_rows() > 0 ) 
				{
					$row = $query->row_array();
					
					$secret = $row[ $this->config['column']['name'] ] . $row[ $this->config['column']['pass'] ];
					
					if ( $cookie[1] == md5( $secret ) )
					{
						foreach ( $this->test as $value )
						{
							if ( trim( $row[$this->config['column'][$value]] ) !== '' ) 
							{
								$this->member[$value] = $row[$this->config['column'][$value]];
							}
						}
						
						foreach ( $this->optional as $value )
						{
							if ( trim( $row[$this->config['column'][$value]] ) !== '' ) 
							{
								$this->member[$value] = $row[$this->config['column'][$value]];
							}
						}
						
						$has_session = TRUE;
					}
				}
				else 
				{
					log_message( 'debug', 'No user authentication found' );
				}
            }
        }

        if ( $has_session === FALSE )
		{
			$this->_create();
        }

        $this->ci->auth = $this->member;
        $this->ci->authentication = $this;
    }
	function _generate_query()
	{
		
		$invalid = FALSE;
		$query = "";
		$select = "";
		$join = "";
		
		foreach ( $this->test as $value )
		{
			if ( trim( $this->config['column'][ $value ] ) === '' ) 
			{
				$invalid = TRUE;
			}
			else 
			{
				$this->ci->db->select( $this->config['column'][$value] );	
			}
		}
		
		foreach ( $this->optional as $value )
		{
			if ( trim( $this->config['column'][ $value ] ) !== '' ) 
			{
				$this->ci->db->select( $this->config['column'][$value] );
			}
		}
		
		if ( $invalid === FALSE ) 
		{
			if ( trim( $this->config['table_meta'] ) !== '' AND trim( $this->config['column']['key'] ) !== '' )
			{
				$this->ci->db->join( $this->config['table_meta'], $this->config['column']['key'] . '=' . $this->config['column']['id'], 'left' );
			}
			$this->ci->db->where( $this->config['column']['id'], $cookie[0] );
			$this->ci->db->where( $this->config['column']['role'], $cookie[2] );
			$this->ci->db->limit(1);
			$this->ci->db->from( $this->config['table'] );
			
			return $this->ci->db->get();
		}
		else {
			return NULL;
		}
	}
    function _create()
    {
    	$user = $this->ci->fb_user;
		
		$value = "0|" . md5( 'guest' ) . "|0";
		
		if ( !!$user ) :
	        $value = $user['uid'] . "|" . md5( $user['name'] . $user['profile_url'] ) . "|" . $user['role'] ;
		endif;
		
		$cookie = array (
	        'name' => 'auth',
	        'value' => $value,
	        'expire' => $this->_cookie_timeout()
        );
        set_cookie( $cookie );
    }
    function register( $id = 0, $name = 'guest', $password = '', $role = 0 )
    {
        $cookie = array (
	        'name' => 'auth',
	        'value' => $id . "|" . md5( $name . $password ) . "|" . $role,
	        'expire' => $this->_cookie_timeout()
        );

        set_cookie( $cookie );
    }
    function remove()
    {
        delete_cookie( 'auth' );
    }
	function _cookie_timeout()
	{
		if ( $this->config['expire'] > 0 )
		{
			return (int)$this->config['expire'] + time();
		}
		else 
		{
			return 0;
		}
	}
}