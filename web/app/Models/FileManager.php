<?php
namespace App\Models;

use Nette;
use Nette\Utils\Strings;
use Nette\Http\FileResponse;
use Nette\Http\Response;

class FileManager
{
    private $userDirectory = "";

    public function uploadFile($userId, $uploadedFile)
    {
        $this->buildDir($userId);
        $uploadedFileName = $uploadedFile->getName();
        // Ensure the file name is sanitized properly
        $sanitizedName = Strings::webalize(pathinfo($uploadedFileName, PATHINFO_FILENAME));
        $extension = pathinfo($uploadedFileName, PATHINFO_EXTENSION);
        $destination = $this->userDirectory . $sanitizedName . '.' . $extension;

        // Check if the file already exists
        if (file_exists($destination)) {
            throw new \RuntimeException("File '{$sanitizedName}'.'{$extension}' already exists.");
        }

        // Move the uploaded file to the user directory
        $uploadedFile->move($destination);
    }

    public function downloadFile($userId, $fileName)
    {
        $this->buildDir($userId);
        $filePath = realpath($this->userDirectory . $fileName);


        // Check if the file exists
        if (!file_exists($filePath)) {
            throw new \RuntimeException("File '{$fileName}' does not exist.");
        }

        $response = new Response();
        $response->setContentType('application/octet-stream'); // Set content type as binary data
        $response->setHeader('Content-Disposition', 'attachment; filename="' . $filePath . '"');

        // Send the file contents as response
        return $response;
    }

    public function deleteFile($userId, $fileName)
    {
        $this->buildDir($userId);
        $filePath = $this->userDirectory . $fileName;

        // Check if the file exists
        if (!file_exists($filePath)) {
            throw new \RuntimeException("File '{$fileName}' does not exist.");
        }

        // Delete the file
        unlink($filePath);
    }

    public function listFiles($userId)
    {
        $this->buildDir($userId);
        // List files in user directory
        $files = scandir($this->userDirectory);
        
        // Remove '.' and '..' from the list
        $files = array_diff($files, array('.', '..'));

        return $files;
    }

    private function buildDir($userId)
    {
        $this->userDirectory = "../../UserData/" . (string) $userId . "/";
        if (!file_exists($this->userDirectory)) {
            mkdir($this->userDirectory, 0777, true);
        }
    }
}