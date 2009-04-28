<?php

class Welcome extends Controller {
	function Welcome()
	{
		parent::Controller();
	}
	
	function index()
	{
		if($this->uri->smart("id") == 10) 
		{
			echo "Hahahaha";
		}
		
		$this->ui->set_title( "Welcome" );
		$this->ui->view( 'welcome', 
			array(
				'page' => anchor('welcome/index/offset/1000/id/10', 'Hello world')
			)
		);
	}
	
}

/* End of file welcome.php */
/* Location: ./system/application/controllers/welcome.php */