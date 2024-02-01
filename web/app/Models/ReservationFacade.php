<?php
namespace App\Models;

use Nette;

final class ReservationFacade 
{

    use Nette\SmartObject;
    private $database;

    public function __construct(Nette\Database\Explorer $database)
    {
        $this->database = $database;
    }

    public function getAllReservations()
    {
        return $this->database->table('Reservation');
    }

}