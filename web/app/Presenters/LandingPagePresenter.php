<?php

declare(strict_types=1);

namespace App\Presenters;

use App\Models\ReservationFacade;
use Nette;

final class LandingPagePresenter extends Nette\Application\UI\Presenter
{

    private $facade;

    private $message = "Vitajte vo virtualnom laboratoriu!";
    
    public function __construct(ReservationFacade $facade)
    {
        $this->facade = $facade;
    }

    public function renderWelcome(): void
    {
        $this->template->message = $this->message;
        $this->template->reservations = $this->facade->getAllReservations();
    }
}