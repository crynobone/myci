<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Base Site Name
|--------------------------------------------------------------------------
|
| Name of your site/application, e.g:
|
|	My First Website
|
*/
$config['site_name'] = 'My CodeIgniter';

/*
|--------------------------------------------------------------------------
| Template Option
|--------------------------------------------------------------------------
|
| Enable you to configure template option
| Default template: <your-site>/public/styles/<theme>/<filename>.html
*/

$config['template']['theme'] = 'default';
$config['template']['filename'] = 'index';

/*
|--------------------------------------------------------------------------
| Option Table Schema
|--------------------------------------------------------------------------
|
| Enable you to set/get option value from your database
|
*/
$config['option']['enable'] = FALSE;
$config['option']['table'] = '';
$config['option']['attribute'] = '';
$config['option']['value'] = '';

/*
|--------------------------------------------------------------------------
| User Session/Authentication
|--------------------------------------------------------------------------
|
| Enable you to validate logged-in user
|
*/
$config['auth']['enable'] = FALSE;
$config['auth']['table'] = '';							// Table: user main table
$config['auth']['table_meta'] = '';						// Table: user meta table (if you store meta data separately)
$config['auth']['column']['id'] = '';					// Column: user id (INT) PRIMARY KEY
$config['auth']['column']['key'] = '';					// Column: foreign key for meta user id
$config['auth']['column']['name'] = '';					// Column: user name (VARCHAR) UNIQUE
$config['auth']['column']['email'] = '';				// Column: user email (VARCHAR)
$config['auth']['column']['pass'] = '';					// Column: user pass (VARCHAR) Encrypted
$config['auth']['column']['fullname'] = '';				// Column: user fullname (VARCHAR)
$config['auth']['column']['role'] = '';					// Column: user role (INT)
$config['auth']['column']['status'] = '';				// Column: user status (INT)
$config['auth']['expire'] = 0;

/*
|--------------------------------------------------------------------------
| Default QueryString URI Segmentation
|--------------------------------------------------------------------------
|
| Enable you to assign a key for $_GET to parse URI Segmentation
| e.g: http://your-domain.com/?p=/controller/module
|
*/
$config['node_segment'] = 'p';
