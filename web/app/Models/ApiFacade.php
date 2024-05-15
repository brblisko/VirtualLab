<?php
/**
 * Application    VirtualLab
 * Author         Boris Vesely
 */

namespace App\Models;

use GuzzleHttp\Client;
use App\Services\ApiService;
use Nette;

final class ApiFacade
{
    private $apiService;
    private $httpClient;

    // Constructor to initialize the API service and HTTP client
    public function __construct(ApiService $apiService, Client $httpClient)
    {
        $this->httpClient = $httpClient;
        $this->apiService = $apiService;
    }

    // Method to get and filter tunnel data by user ID
    public function getTunnelsDataAndFilter($userId)
    {
        $data = $this->apiService->getTunnelsData();
        return $this->apiService->filterTunnelsData($data, $userId);
    }

    // Method to get tunnel data
    public function getTunnelsData()
    {
        return $this->apiService->getTunnelsData();
    }

    // Method to send an instruction to the FPGA
    public function sendInstruction(string $fpgaIp, string $clientIp, string $userId, string $type)
    {
        $payload = [
            'json' => [
                'type' => $type,
                'fpgaip' => $fpgaIp,
                'clientip' => $clientIp,
                'user' => $userId
            ]
        ];

        $response = $this->httpClient->post('http://localhost:20000/Instruction', $payload);

        return $response->getStatusCode() === 201;
    }

    // Method to set the state of an FPGA
    public function setState(string $fpgaIp, string $state)
    {
        $payload = [
            'json' => [
                'fpgaip' => $fpgaIp,
                'state' => $state
            ]
        ];

        $response = $this->httpClient->post('http://localhost:20000/State', $payload);

        return $response->getStatusCode() === 200;
    }

    // Method to get FPGA information
    public function getFpgaInfo()
    {
        return $this->apiService->getFpgas();
    }

    // Method to get the count of all FPGAs that are not disabled
    public function getAllFpgaCount()
    {
        $data = $this->apiService->getFpgas();

        $count = 0;
        foreach ($data as $item) {
            if ($item['state'] !== 'DISABLED') {
                $count++;
            }
        }

        return $count;
    }
}
