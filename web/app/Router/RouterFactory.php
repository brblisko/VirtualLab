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

		$router->addRoute('/', 'LandingPage:welcome');
		$router->addRoute('<presenter>/<action>[/<id>]', 'Home:default');
		return $router;
	}
}
