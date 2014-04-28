<?php
//The View class used for all the pages (so far)
class View
{
	protected $controller, $model;
	//Normal initialisation function, nothing special here
	public function __construct($controller, $model)
	{
		$this->controller = $controller;
		$this->model = $model;
	}

	//Creates the page to be echoed by the main page
	public function output()
	{
		//Gets the necessary data and turns them into variables
		extract($this->model->getdata());

		//Creates an output buffer to prevent anything from being printed quite yet
		ob_start();

		//All pages have very similar headers and footers, and an associated main
		//content section
		include("stats/content/header.php");
		include("stats/content/" . $this->model->gettype() . "body.php");
		include("stats/content/footer.php");

		//Stores the buffer as a string and clears it
		$outstring = ob_get_contents();
		ob_end_clean();
		return $outstring;
	}
}
?>
