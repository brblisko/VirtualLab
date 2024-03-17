<?php
namespace App\Models;

use App\Services\ApiService;
use Nette;

final class ApiFacade
{
    use Nette\SmartObject;
    private $apiService;

    public function __construct(ApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    public function getTunnelsDataAndFilter($apiUrl, $userId)
    {
        $data = $this->apiService->getTunnelsData($apiUrl);
        return $this->apiService->filterTunnelsData($data, $userId);
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
