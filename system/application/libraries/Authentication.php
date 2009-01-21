<?php if(!defined('BASEPATH')) exit('No direct script access allowed');

class Authentication
{
    var $ci = NULL;
    var $member = array (
	    'id' => 0,
	    'name' => 'guest',
	    'fullname' => '',
	    'email' => '',
	    'role' => 0,
	    'status' => 0
    );
    function Authentication()
    {
        $this->ci = & get_instance();
        $in = false;

        if ($this->ci->input->cookie('auth'))
		{
            $cookies = html_entity_decode($this->ci->input->cookie('auth', TRUE));
            $cookie = explode("|", $cookies);

            if ($cookie[2] > 0)
			{
                $query = "SELECT * FROM user_table WHERE user_id=? AND user_role=?";
                // SELECT * FROM uhs_user WHERE user_id=? AND user_role=?
                /*
                 $row = $this->ci->adodb->getRow($query, array(
                 $cookie[0],
                 $cookie[2]
                 ));
                 
                 if(!!$row) :
                 if($cookie[1] == md5($row['user_name'].$row['user_pass'])) :
                 /*
                 * Sample template for user data;
                 *
                 * $this->member['id'] = $row['user_id'];
                 * $this->member['name'] = $row['user_name'];
                 * $this->member['fullname'] = $row['user_fullname'];
                 * $this->member['email'] = $row['user_email'];
                 * $this->member['role'] = $row['user_role'];
                 * $this->member['status'] = $row['user_status'];
                 
                 
                 $in = true;
                 endif;
                 endif;
                 */
            }
        }

        if ($in === false)
		{
			$this->_create();
        }

        $this->ci->auth = $this->member;
        $this->ci->authentication = $this;
    }
    function _create()
    {

        $cookie = array (
	        'name' => 'auth',
	        'value' => "0|".md5('guest')."|0",
	        'expire' => 0,
	        'domain' => $this->ci->config->config['cookie_domain'],
	        'path' => $this->ci->config->config['cookie_path']
        );

        set_cookie($cookie);
    }
    function register($id = 0, $name = 'guest', $password = '', $role = 0)
    {
        $cookie = array (
	        'name' => 'auth',
	        'value' => $id."|".md5($name.$password)."|".$role,
	        'expire' => 0,
	        'domain' => $this->ci->config->config['cookie_domain'],
	        'path' => $this->ci->config->config['cookie_path']
        );

        set_cookie($cookie);
    }
    function remove()
    {
        delete_cookie('auth');
    }
}