<?php
//The controller used for all the pages so far
class Controller
{
	private $model;
	//Your basic initialisation
	public function __construct($model)
	{
		$this->model = $model;

		//If it's a search, we will want to redirect to the results page
		if($this->model->gettype() == "search")
		{
			$newparams = implode("/", $this->model->getquery());
			header("Location: ../$newparams");
			exit;
		}
	}

	//Passes the page parameters into the Model
	public function params($params)
	{
		$this->model->setparams($params);
	}
}
?>
