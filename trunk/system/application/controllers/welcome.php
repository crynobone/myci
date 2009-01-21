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
		$this->ui->title = "Welcome";
		$this->ui->view('welcome_message', 
			array(
				'page' => smart_anchor(array (
					'welcome/index',
					'offset' => 1000,
					'id' => 10
				), 'Hello world')
			)
		);
	}
}

/* End of file welcome.php */
/* Location: ./system/application/controllers/welcome.php */