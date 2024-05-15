<?php
/**
 * Application    VirtualLab
 * Author         Boris Vesely
 */


declare(strict_types=1);

namespace App\Presenters;

use App\Models\ReservationFacade;
use Nette;

final class LandingPagePresenter extends DefaultPresenter
{
    private $facade;
    private $message = "Welcome to the virtual laboratory!";

    // Constructor to initialize the ReservationFacade
    public function __construct(ReservationFacade $facade)
    {
        $this->facade = $facade;
    }

    // Method to render the welcome message
    public function renderWelcome(): void
    {
        $this->template->message = $this->message;
    }
}
