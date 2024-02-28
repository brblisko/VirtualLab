<?php

namespace App\Presenters;

use App\Models\ReservationFacade;
use Nette\Application\UI\Presenter;
use Nette\Application\Responses\JsonResponse;

class DefaultPresenter extends Presenter
{
    private $facade;

    public function __construct(ReservationFacade $facade)
    {
        $this->facade = $facade;
    }

    public function renderDefault()
    {
        // Render default template
    }

    public function actionGetLiveReservation($user)
    {
        $items = $this->facade->getLiveReservation($user);

         // Convert the items to a simple array of objects
         $responseData = [];
         foreach ($items as $item) {
             $responseData[] = [
                 'res_id' => $item->res_id, 
                 'time' => $item->time
             ];
         }
 
         $this->sendJson($responseData);
    }

    public function actionButtons()
    {
        // Get current time
        $currentTime = time();

        // Calculate the next 15-minute interval
        $next15Minutes = ceil($currentTime / (15 * 60)) * (15 * 60);

        // Calculate the end time (1 day from the current time)
        $endTime = strtotime('+1 day', strtotime(date('Y-m-d H:i:s', $currentTime)));

        // Query reservations for the user within the time range
        $userId =  "xvesel92";//$this->getUser()->getId();
        $reservations = $this->facade->getAllReservationsUserTimestamp($userId);

        // Construct the JSON response
        $buttons = [];
        for ($time = $next15Minutes; $time < $endTime; $time += 15 * 60) {
            $timestampKey = date('Y-m-d H:i:s', $time);
            $reservationExists = isset($reservations[$timestampKey]);
            $buttons[$timestampKey] = ['active_reservation' => $reservationExists];
        }

        // Return the JSON response
        $this->sendJson($buttons);
    }

    public function actionAllReservations(){
        // Get current time
        $currentTime = time();

        // Calculate the next 15-minute interval
        $next15Minutes = ceil($currentTime / (15 * 60)) * (15 * 60);

        // Calculate the end time (1 day from the current time)
        $endTime = strtotime('+1 day', strtotime(date('Y-m-d H:i:s', $currentTime)));

        $reservations = $this->facade->getAllReservationsTimestamp();

        // Construct the JSON response
        $buttons = [];
        for ($time = $next15Minutes; $time < $endTime; $time += 15 * 60) {
            $timestampKey = date('Y-m-d H:i:s', $time);
            $reservationsCount = (isset($reservations[$timestampKey])) ? $reservations[$timestampKey] : 0;
            $buttons[$timestampKey] = ['reservation_count' => $reservationsCount];
        }

        // Return the JSON response
        $this->sendJson($buttons);
    }

    public function actionReservation()
    {
        $user = "xvesel92";
        
        $requestData = json_decode($this->getHttpRequest()->getRawBody(), true);

        // Extract timestamp and action from the request data
        $timestamp = $requestData['timestamp'];
        $action = $requestData['action'];


        if ($action == "create_reservation") {
            $this->facade->insertReservation($user,$timestamp);
        }
        else if ($action == "cancel_reservation") {
            $this->facade->deleteReservation($user, $timestamp);
        }

        $response = new JsonResponse(['success' => true]);
        $this->sendResponse($response);

    }
}