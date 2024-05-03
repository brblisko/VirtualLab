<?php
namespace App\Services;



final class ApiService
{
    public function getTunnelsData()
    {
        $response = file_get_contents("http://localhost:20000/Tunnels");

        if ($response !== false) {
            $data = json_decode($response, true);

            if ($data !== null) {
                return $data;
            } else {
                throw new \RuntimeException("Error decoding JSON response.");
            }
        } else {
            throw new \RuntimeException("Error making API call.");
        }
    }

    public function getFpgas()
    {
        $response = file_get_contents("http://localhost:20000/FPGAs");

        if ($response !== false) {
            $data = json_decode($response, true);

            if ($data !== null) {
                return $data;
            } else {
                throw new \RuntimeException("Error decoding JSON response.");
            }
        } else {
            throw new \RuntimeException("Error making API call.");
        }
    }

    public function filterTunnelsData($data, $userId)
    {
        return array_filter($data, function ($item) use ($userId) {
            return $item['user'] === $userId;
        });
    }
}