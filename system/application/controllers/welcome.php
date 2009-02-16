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
	function sloppy()
	{
		$data = array ();
		$this->ui->disable();
		$this->load->model('Welcome_model', 'model');
		$this->load->view("sloppy", $data);
	}
	
}

/* End of file welcome.php */
/* Location: ./system/application/controllers/welcome.php */