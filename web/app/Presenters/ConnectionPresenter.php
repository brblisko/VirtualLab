<?php
/**
 * Application    VirtualLab
 * Author         Boris Vesely
 */


declare(strict_types=1);

namespace App\Presenters;

use App\Models\ApiFacade;
use App\Models\ReservationFacade;
use Nette\Http\Request;
use Nette;

final class ConnectionPresenter extends DefaultPresenter
{
    private $facade;
    private $res_facade;
    private $httpRequest;

    // Constructor to initialize the facades
    public function __construct(ApiFacade $facade, ReservationFacade $res_facade)
    {
        $this->res_facade = $res_facade;
        $this->facade = $facade;
    }

    // Method to create a tunnel
    private function createTunnel()
    {
        $clientIp = $this->httpRequest->getRemoteAddress();
        $userId = $this->getUser()->getId();
        $fpgas = $this->facade->getFpgaInfo();

        $fpga = null;
        foreach ($fpgas as $item) {
            if ($item['state'] === 'DEFAULT') {
                $fpga = $item;
                break;
            }
        }

        if ($fpga === null) {
            // If no available FPGA, check existing tunnels and create one if possible
            $tunnels = $this->facade->getTunnelsData();
            foreach ($tunnels as $item) {
                $liveRes = $this->res_facade->getLiveReservation($item['user']);
                if (empty($liveRes)) {
                    $resultDelete = $this->facade->sendInstruction($item['fpgaip'], $item['clientip'], $item['user'], "DELETE");
                    $resultCreate = $this->facade->sendInstruction($item['fpgaip'], $clientIp, (string) $userId, "CREATE");

                    if (!$resultDelete || !$resultCreate) {
                        return false;
                    } else {
                        return true;
                    }
                }
            }
        } else {
            $resultCreate = $this->facade->sendInstruction($fpga['ip'], $clientIp, (string) $userId, "CREATE");

            if (!$resultCreate) {
                return false;
            } else {
                return true;
            }
        }
    }

    // Method to prepare connection before rendering the template
    protected function beforeRender()
    {
        parent::beforeRender();

        $userId = $this->getUser()->getId();
        $tunnelData = $this->facade->getTunnelsDataAndFilter((string) $userId);
        $tmp = $this->res_facade->getLiveReservation($userId);

        $reservation = [];
        foreach ($tmp as $item) {
            $reservation[] = [
                'res_id' => $item->res_id,
                'time' => $item->time
            ];
        }

        // Create a tunnel if user has an active reservation but no tunnel
        if (empty($tunnelData) && !empty($reservation)) {
            $result = $this->createTunnel();

            if (!$result) {
                $this->flashMessage('There was an error preparing PYNQ device', 'error');
                $this->redirect("LandingPage:welcome");
            }

        } elseif (empty($reservation) && empty($tunnelData)) {
            // Deny access if no active reservation and no active tunnel
            $this->flashMessage('You have no active reservation, please reserve a timeslot', 'error');
            $this->redirect("LandingPage:welcome");
        }
        // Allow access if the user has no active reservation but a tunnel
    }

    // Method to inject HTTP request
    public function injectHttpRequest(Request $httpRequest): void
    {
        $this->httpRequest = $httpRequest;
    }

    // Method to render active tunnels
    public function renderActive(): void
    {
        $this->getHttpResponse()->setHeader('X-Frame-Options', "");

        $tunnel = $this->facade->getTunnelsDataAndFilter((string) $this->getUser()->getId());

        $this->template->port = $tunnel[0]['port'];
    }
}
