<?php
namespace App\Models;

use GuzzleHttp\Client;
use App\Services\ApiService;
use Nette;

final class ApiFacade
{
    private $apiService;
    private $httpClient;


    public function __construct(ApiService $apiService, Client $httpClient)
    {
        $this->httpClient = $httpClient;
        $this->apiService = $apiService;

    }

    public function getTunnelsDataAndFilter($userId)
    {
        $data = $this->apiService->getTunnelsData();
        return $this->apiService->filterTunnelsData($data, $userId);
    }

    public function getTunnelsData()
    {
        $data = $this->apiService->getTunnelsData();
        return $data;
    }

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

        $statusCode = $response->getStatusCode();
        if ($statusCode === 200) {
            echo 'Request was successful.';
        } else {
            echo 'Request failed with status code: ' . $statusCode;
        }

    }

    public function getFpgaInfo()
    {
        $data = $this->apiService->getFpgas();
        return $data;
    }

    public function getAllFpgaCount()
    {
        $data = $this->apiService->getAllFpga();

        $count = 0;
        foreach ($data as $item)
        {
            if($item['state'] !== 'UNAVAILABLE')
            {
                $count++;
            }
        }

        return $count;
    }
}
