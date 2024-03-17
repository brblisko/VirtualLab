<?php

namespace App\Presenters;

use App\Models\ApiFacade;
use App\Models\ReservationFacade;
use Nette\Application\UI\Presenter;
use Nette\Application\Responses\JsonResponse;
use App\Models\Authenticator;
use Nette\Bridges\ApplicationLatte\ILatteFactory;



class DefaultPresenter extends Presenter
{
    private $res_facade;
    private $api_facade;
    private $authenticator;

    /** @var ILatteFactory @inject */
    public $latteFactory;


    public function __construct(ReservationFacade $res_facade, Authenticator $authenticator, ApiFacade $api_facade)
    {
        $this->res_facade = $res_facade;
        $this->authenticator = $authenticator;
        $this->api_facade = $api_facade;
    }

    public function renderDefault()
    {
        // Render default template
    }

    protected function startup()
    {
        parent::startup();
        if (!$this->getUser()->isLoggedIn()) {
            $this->redirect('Sign:in');
        }
    }


    protected function beforeRender()
    {
        parent::beforeRender();

        // Get the currently logged-in user
        $user = $this->getUser()->getIdentity();

        // Pass the username to the layout template
        $this->template->username = $user ?  $user->getData()['username'] : null;
    }

    public function actionGetLiveReservation()
    {
        $userId = $this->getUser()->getId();
        $items = $this->res_facade->getLiveReservation($userId);

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
        $userId =  $this->getUser()->getId();
        $reservations = $this->res_facade->getAllReservationsUserTimestamp($userId);

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

        $reservations = $this->res_facade->getAllReservationsTimestamp();

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

    public function actionGetFpgaCount(){
        $this->payload->count = $this->api_facade->getAllFpgaCount();

        $this->sendPayload();
    }

    public function actionGetUserReservationCount()
    {
        $this->payload->count = $this->res_facade->getReservationsCountUser($this->getUser()->getId());
        
        $this->sendPayload();
    }

    public function actionReservation()
    {
        $userId = $this->getUser()->getId();
        
        $requestData = json_decode($this->getHttpRequest()->getRawBody(), true);

        // Extract timestamp and action from the request data
        $timestamp = $requestData['timestamp'];
        $action = $requestData['action'];


        if ($action == "create_reservation") {
            $this->res_facade->insertReservation($userId,$timestamp);
        }
        else if ($action == "cancel_reservation") {
            $this->res_facade->deleteReservation($userId, $timestamp);
        }

        $response = new JsonResponse(['success' => true]);
        $this->sendResponse($response);

    }
}