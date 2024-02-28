<?php
namespace App\Models;

use Nette;
use Nette\Utils\Json;

final class ReservationFacade 
{

    use Nette\SmartObject;
    private $database;

    public function __construct(Nette\Database\Explorer $database)
    {
        $this->database = $database;
    }

    public function getAllReservations($user)
    {
        return $this->database->table('Reservation')
                              ->where('user_id', $user)
                              ->fetchAll();
    }

    public function getAllReservationsTimestamp()
    {
        $query = $this->database->table('Reservation')
                              ->select('time, COUNT(*) AS count')
                              ->group('time');

        $data = [];
        foreach ($query as $row){
            $data[(string) $row->time] = $row->count;
        }

        return $data;
    }

    public function getAllReservationsUserTimestamp($user)
    {
        return $this->database->table('Reservation')
                              ->where('user_id', $user)
                              ->fetchAssoc('time');
    }

    public function getLiveReservation($user)
    {
        return $this->database->table('Reservation')
                              ->where('time BETWEEN DATE_SUB(NOW(), INTERVAL 15 MINUTE) AND NOW()')
                              ->where('user_id', $user)
                              ->fetchAll();
    }

    public function deleteReservation($user, $timestamp) {
        return $this->database->table('Reservation')->where('user_id', $user)->where('time', $timestamp)->delete();
    }

    public function insertReservation($user, $timestamp)
    {
        return $this->database->table('Reservation')->insert([
            'user_id' => $user,
            'time' => $timestamp
        ]);
    }

}