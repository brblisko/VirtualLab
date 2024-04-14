<?php
namespace App\Models;

use DateTime;
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

    public function getAllReservations($userId)
    {
        return $this->database->table('reservations')
                              ->where('user_id', $userId)
                              ->fetchAll();
    }

    public function getAllReservationsTimestamp()
    {
        $query = $this->database->table('reservations')
                              ->select('time, COUNT(*) AS count')
                              ->group('time');

        $data = [];
        foreach ($query as $row){
            $data[(string) $row->time] = $row->count;
        }

        return $data;
    }

    public function getReservationsCountUser($userId): int
    {
        $currentTime = new DateTime();
        return $this->database->table('reservations')
                              ->where('user_id', $userId)
                              ->where('time > ?', $currentTime)
                              ->count('*');
    }

    public function getAllReservationsUserTimestamp($userId)
    {
        return $this->database->table('reservations')
                              ->where('user_id', $userId)
                              ->fetchAssoc('time');
    }

    public function getLiveReservation($userId)
    {
        return $this->database->table('reservations')
                              ->where('time BETWEEN DATE_SUB(NOW(), INTERVAL 15 MINUTE) AND NOW()')
                              ->where('user_id', $userId)
                              ->fetchAll();
    }

    public function deleteReservation($userId, $timestamp) {
        return $this->database->table('reservations')->where('user_id', $userId)->where('time', $timestamp)->delete();
    }

    public function insertReservation($userId, $timestamp)
    {
        return $this->database->table('reservations')->insert([
            'user_id' => $userId,
            'time' => $timestamp
        ]);
    }


    public function getFutureReservationsGroupedByTimestamp(): array
    {
        $reservations = $this->database->table('reservations')
            ->where('time > ?', new \DateTime()) // Filter for future reservations
            ->order('time ASC') // Ensure they are sorted by timestamp
            ->fetchAll();

        $groupedReservations = [];
        foreach ($reservations as $reservation) {
            $timestamp = $reservation->time->format('Y-m-d H:i:s');
            $userId = $reservation->user_id;

            // Fetch username for each reservation
            $username = $this->getUsernameByUserId($userId);

            // Group by timestamp
            if (!isset($groupedReservations[$timestamp])) {
                $groupedReservations[$timestamp] = [];
            }

            $groupedReservations[$timestamp][] = [
                'user_id' => $userId,
                'username' => $username,
            ];
        }

        return $groupedReservations;
    }

    
    public function getUsernameByUserId(int $userId): ?string
    {
        $user = $this->database->table('users')
            ->select('username')
            ->where('id', $userId)
            ->fetch();

        return $user ? $user->username : null;
    }

}