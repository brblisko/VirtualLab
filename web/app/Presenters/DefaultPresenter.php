<?php

namespace App\Presenters;

use App\Models\ApiFacade;
use App\Models\ReservationFacade;
use Nette\Application\UI\Presenter;
use Nette\Application\Responses\JsonResponse;
use App\Models\Authenticator;
use Nette\Utils\DateTime;
use Nette\Bridges\ApplicationLatte\ILatteFactory;

class DefaultPresenter extends Presenter
{
    private $res_facade;
    private $api_facade;
    private $authenticator;

    /** @var ILatteFactory @inject */
    public $latteFactory;

    // Constructor to initialize the facades and authenticator
    public function __construct(ReservationFacade $res_facade, Authenticator $authenticator, ApiFacade $api_facade)
    {
        $this->res_facade = $res_facade;
        $this->authenticator = $authenticator;
        $this->api_facade = $api_facade;
    }

    // Method to render the default template
    public function renderDefault()
    {
        // Render default template
    }

    // Method to check if the user is logged in at startup
    protected function startup()
    {
        parent::startup();
        if (!$this->getUser()->isLoggedIn()) {
            $this->redirect('Sign:in');
        }
    }

    // Method to prepare data before rendering the template
    protected function beforeRender()
    {
        parent::beforeRender();

        // Get the currently logged-in user
        $user = $this->getUser()->getIdentity();

        // Pass the username to the layout template
        $this->template->username = $user ? $user->getData()['username'] : null;
    }

    // Method to get live reservation data
    public function actionGetLiveReservation()
    {
        $userId = $this->getUser()->getId();
        $items = $this->res_facade->getLiveReservation($userId);
        $tunnel = $this->api_facade->getTunnelsDataAndFilter((string) $userId);

        $responseData = ($items || $tunnel) ? [['data' => true]] : [];

        $this->sendJson($responseData);
    }

    // Method to generate reservation buttons
    public function actionButtons()
    {
        $currentTime = new DateTime();
        $endTime = (clone $currentTime)->modify('+1 days');

        // Calculate the nearest 15-minute interval for the next window
        $minutes = $currentTime->format('i');
        $roundedMinutes = (int) (ceil($minutes / 15) * 15);

        // If the next window is at 60 minutes, set it to 0 and add 1 hour
        if ($roundedMinutes == 60) {
            $roundedMinutes = 0;
            $currentTime->modify('+1 hour');
        }

        // Set the initial time slot to the next window
        $hours = (int) $currentTime->format('H');
        $currentTime->setTime($hours, $roundedMinutes);

        // Query reservations for the user within the time range
        $userId = $this->getUser()->getId();
        $reservations = $this->res_facade->getAllReservationsUserTimestamp($userId);
        $allReservations = $this->res_facade->getAllReservationsTimestamp();
        $maxFpga = $this->api_facade->getAllFpgaCount();
        $userResCounter = $this->res_facade->getReservationsCountUser($userId);

        // Construct the JSON response
        $buttons = [];
        while ($currentTime <= $endTime) {
            $timestampKey = $currentTime->format('Y-m-d H:i:s');
            $reservationExists = isset($reservations[$timestampKey]);

            $reservationsCount = (isset($allReservations[$timestampKey])) ? $allReservations[$timestampKey] : 0;
            
            $locked = ($reservationsCount === $maxFpga) || ($userResCounter === 5);
            $buttons[$timestampKey] = [
                'active_reservation' => $reservationExists,
                'locked' => $locked
            ];

            $currentTime->modify('+15 minutes');
        }

        $this->sendJson($buttons);
    }

    // Method to get all reservations
    public function actionAllReservations()
    {
        $currentTime = new DateTime();
        $endTime = (clone $currentTime)->modify('+1 days');

        // Calculate the nearest 15-minute interval for the next window
        $minutes = $currentTime->format('i');
        $roundedMinutes = (int) (ceil($minutes / 15) * 15);

        // If the next window is at 60 minutes, set it to 0 and add 1 hour
        if ($roundedMinutes == 60) {
            $roundedMinutes = 0;
            $currentTime->modify('+1 hour');
        }

        // Set the initial time slot to the next window
        $hours = (int) $currentTime->format('H');
        $currentTime->setTime($hours, $roundedMinutes);

        $reservations = $this->res_facade->getAllReservationsTimestamp();

        // Construct the JSON response
        $buttons = [];
        while ($currentTime <= $endTime) {
            $timestampKey = $currentTime->format('Y-m-d H:i:s');
            $reservationsCount = (isset($reservations[$timestampKey])) ? $reservations[$timestampKey] : 0;
            $buttons[$timestampKey] = ['reservation_count' => $reservationsCount];

            $currentTime->modify('+15 minutes');
        }

        $this->sendJson($buttons);
    }

    // Method to get FPGA count
    public function actionGetFpgaCount()
    {
        $this->payload->count = $this->api_facade->getAllFpgaCount();
        $this->sendPayload();
    }

    // Method to get user reservation count
    public function actionGetUserReservationCount()
    {
        $this->payload->count = $this->res_facade->getReservationsCountUser($this->getUser()->getId());
        $this->sendPayload();
    }

    // Method to create or cancel a reservation
    public function actionReservation()
    {
        $userId = $this->getUser()->getId();
        $maxFpga = $this->api_facade->getAllFpgaCount();
        $reservations = $this->res_facade->getAllReservationsTimestamp();

        $requestData = json_decode($this->getHttpRequest()->getRawBody(), true);

        // Extract timestamp and action from the request data
        $timestamp = $requestData['timestamp'];
        $action = $requestData['action'];

        $tmp = isset($reservations[$timestamp]) ? $reservations[$timestamp] : 0;

        if(($tmp == $maxFpga) && ($action !== "cancel_reservation"))
        {
            $response = new JsonResponse(['success' => false]);
            $this->sendResponse($response);
            return;
        }

        if ($action == "create_reservation") {
            $this->res_facade->insertReservation($userId, $timestamp);
        } elseif ($action == "cancel_reservation") {
            $this->res_facade->deleteReservation($userId, $timestamp);
        }

        $response = new JsonResponse(['success' => true]);
        $this->sendResponse($response);
    }
}
