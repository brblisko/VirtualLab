<?php

declare(strict_types=1);

namespace App\Presenters;

use App\Models\ApiFacade;
use Nette;

final class ConnectionPresenter extends Nette\Application\UI\Presenter
{


    private $facade;

    public function __construct(ApiFacade $facade)
    {
        $this->facade = $facade;
    }

    public function renderTest(): void 
    {
       
        $this->getHttpResponse()->setHeader('X-Frame-Options', "");


        $tmp = $this->facade->getTunnelsDataAndFilter("http://localhost:20000/Tunnels","xvesel92")[0];
        $this->template->port = 30000;
    }


}

