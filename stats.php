<?php
//URLs are of the form http://hdwhite.org/qb/stats/[PAGE]/[QUERY1]/[QUERY2]
$urlarray = explode("/", $_SERVER['REQUEST_URI']);
$page = htmlentities($urlarray[3]);
$params = array_map('htmlentities', array_slice($urlarray, 4));

//At the moment all the pages use the same View and Controller, though that might change
switch($page)
{
	case "team":
		$model = "TeamModel";
		$view = "View";
		$controller = "Controller";
		break;
	case "player":
		$model = "PlayerModel";
		$view = "View";
		$controller = "Controller";
		break;
	case "search":
		$model = "SearchModel";
		$view = "View";
		$controller = "Controller";
		break;
	default:
		$model = "IndexModel";
		$view = "View";
		$controller = "Controller";
}

//Get the associated classes
require_once("stats/model/Model.php");
require_once("stats/model/$model.php");
require_once("stats/view/$view.php");
require_once("stats/controller/$controller.php");

//Initialise the classes
$model = new $model();
$controller = new $controller($model);
$view = new $view($controller, $model);

//Pass the parameters to the controller
$controller->params($params);

//Output the page
echo($view->output());
?>
