<?php

declare(strict_types=1);

namespace App\Presenters;

use App\Models\ApiFacade;
use App\Models\ReservationFacade;
use Nette\Http\Request;
use Nette;

//debugging
use Tracy\Debugger;
use Tracy\OutputDebugger;
use Nette\Application\Responses\TextResponse;

final class ConnectionPresenter extends DefaultPresenter
{


    private $facade;
    private $res_facade;
    private $httpRequest;

    public function __construct(ApiFacade $facade, ReservationFacade $res_facade)
    {
        $this->res_facade = $res_facade;
        $this->facade = $facade;
    }

    protected function startup() {
        parent::startup();

        // // Enable Tracy and set output mode to debug
        // Debugger::enable(Debugger::DETECT, '/home/boris/shared/web/log', 'debug');

        // $outputDebugger = new OutputDebugger();
        // $outputDebugger->start();
    }

    public function actionDebug() {
        $userId = $this->getUser()->getId();

        $tunnelData = $this->facade->getTunnelsDataAndFilter((string) $this->getUser()->getId());
        // $tunnelData = $this->facade->getTunnelsData();
        $data = $this->res_facade->getLiveReservation($userId);

        // Convert the items to a simple array of objects
        $responseData = [];
        foreach ($data as $item) {
            $responseData[] = [
                'res_id' => $item->res_id,
                'time' => $item->time
            ];
        }
        $responseData = $this->httpRequest->getRemoteAddress();

        
        $fpgas = $this->facade->getFpgaInfo();
        $output = var_export($tunnelData, true);
        $this->sendResponse(new TextResponse($output));
    }

    private function createTunnel()
    {
        $clientIp = $this->httpRequest->getRemoteAddress();
        $userId = $this->getUser()->getId();
        $fpgas = $this->facade->getFpgaInfo();

        $fpga = null;
        foreach($fpgas as $item)
        {
            if($item['state'] === 'DEFAULT')
            {
                $fpga = $item;
                break;
            }
        }

        if($fpga === null)
        {
            // throw new \Exception("no fpga");

            // TODO
            $tunnels = $this->facade->getTunnelsData();
            foreach($tunnels as $item)
            {
                $liveRes = $this->res_facade->getLiveReservation($item['user']);
                if(empty($liveRes))
                {
                    $resultDelete = $this->facade->sendInstruction($item['fpgaip'], $item['clientip'], $item['user'], "DELETE");
                
                    $resultCreate = $this->facade->sendInstruction($item['fpgaip'], $clientIp, (string) $userId, "CREATE");

                    if(!$resultDelete || !$resultCreate)
                    {
                        return false;
                    }
                    else 
                    {
                        return true;
                    }
                }
            }
        }
        else
        {
            $resultCreate = $this->facade->sendInstruction($fpga['ip'], $clientIp,(string) $userId, "CREATE");

            if(!$resultCreate)
            {
                return false;
            }
            else 
            {
                return true;
            }
        }
    }

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

        // no tunnel created and user has active resevation then create a tunnel
        if(empty($tunnelData) && !empty($reservation))
        {
            $result = $this->createTunnel();

            if(!$result)
            {
                $this->flashMessage('There was an error preparing PYNQ device', 'error');

                $this->redirect("LandingPage:welcome");
            }

        }
        // no active reservation and no active tunnel, deny access
        else if(empty($reservation) && empty($tunnelData))
        {
            $this->flashMessage('You have no active reservation, please reserve a timeslot', 'error');

            $this->redirect("LandingPage:welcome");
        }
        // if the user has a no active reservation but a tunnel do nothing (allow access)
    }


    public function injectHttpRequest(Request $httpRequest): void {
        $this->httpRequest = $httpRequest;
    }

    public function renderActive(): void 
    {
        $this->getHttpResponse()->setHeader('X-Frame-Options', "");

        $tunnel = $this->facade->getTunnelsDataAndFilter((string) $this->getUser()->getId());

        $this->template->port = $tunnel[0]['port'];
    }


}

