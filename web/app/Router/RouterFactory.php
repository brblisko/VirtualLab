<?php

declare(strict_types=1);

namespace App\Router;

use Nette;
use Nette\Application\Routers\RouteList;
use Nette\Application\Routers\Route;



final class RouterFactory
{
	use Nette\StaticClass;

	public static function createRouter(): RouteList
	{
		$router = new RouteList;

		$router[] = new Route('filemanager/download/<fileName>', 'FileManager:download');


		$router->addRoute('<presenter>/<action>[/<id>]', 'Home:default');
		return $router;
	}
}
