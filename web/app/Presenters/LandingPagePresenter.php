<?php

declare(strict_types=1);

namespace App\Presenters;

use App\Models\ReservationFacade;
use Nette;

final class LandingPagePresenter extends DefaultPresenter
{

    private $facade;

    private $message = "Welcome to the virtual laboratory!";
    
    public function __construct(ReservationFacade $facade)
    {
        $this->facade = $facade;
    }

    public function renderWelcome(): void
    {
        $this->template->message = $this->message;
    }
}