<?php
namespace App\Services;

final class ApiService
{
    // Method to get tunnel data from the API
    public function getTunnelsData()
    {
        $response = file_get_contents("http://localhost:20000/Tunnels");

        // Check if the response is not false
        if ($response !== false) {
            $data = json_decode($response, true);

            // Check if JSON decoding was successful
            if ($data !== null) {
                return $data;
            } else {
                throw new \RuntimeException("Error decoding JSON response.");
            }
        } else {
            throw new \RuntimeException("Error making API call.");
        }
    }

    // Method to get FPGA data from the API
    public function getFpgas()
    {
        $response = file_get_contents("http://localhost:20000/FPGAs");

        // Check if the response is not false
        if ($response !== false) {
            $data = json_decode($response, true);

            // Check if JSON decoding was successful
            if ($data !== null) {
                return $data;
            } else {
                throw new \RuntimeException("Error decoding JSON response.");
            }
        } else {
            throw new \RuntimeException("Error making API call.");
        }
    }

    // Method to filter tunnel data by user ID
    public function filterTunnelsData($data, $userId)
    {
        return array_filter($data, function ($item) use ($userId) {
            return $item['user'] === $userId;
        });
    }
}
