<?php
namespace App\Models;

use Nette;
use Nette\Utils\Strings;
use Nette\Http\Response;

use GuzzleHttp\Client;
use Nette\Application\Responses\FileResponse;

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

    public function getPath($userId, $fileName){
        $this->buildDir($userId);
        $filePath = realpath($this->userDirectory . $fileName);

        if (file_exists($filePath)) {
            // Send the file as a response
            return $filePath;
        } else {
            // Handle file not found error
            return null;
        }
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

    public function listFiles($userId, $directory = '')
    {
        $this->buildDir($userId);
        $currentDirectory = $this->userDirectory . $directory; // Remove extra slash
        // List files and directories in user directory
        $contents = scandir($currentDirectory);
        
        // Remove '.' and '..' from the list
        $contents = array_diff($contents, array('.', '..'));

        // Prepare array to hold files and directories
        $files = [];
        foreach ($contents as $item) {
            $path = $currentDirectory . '/' . $item; // Add slash for files/directories
            $isDirectory = is_dir($path);
            $files[] = [
                'name' => $item,
                'isDirectory' => $isDirectory,
                'path' => $directory ? $directory . '/' . $item : $item, // Include directory path
            ];
            
            // If item is a directory, recursively list its contents
            if ($isDirectory) {
                $subContents = $this->listFiles($userId, $directory ? $directory . '/' . $item : $item);
                foreach ($subContents as $subItem) {
                    $files[] = $subItem;
                }
            }
        }

        return $files;
    }

    public function deleteDirectory($userId, $dir) {
        $this->buildDir($userId);

        $dir = $this->userDirectory . $dir;

        if (!is_dir($dir)) {
            return false; // Directory doesn't exist
        }
        
        // List the contents of the directory
        $contents = scandir($dir);
        
        // Remove '.' and '..' from the list
        $contents = array_diff($contents, array('.', '..'));
        
        // Recursively delete files and subdirectories
        foreach ($contents as $item) {
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->deleteDirectory($userId, $path); // Recursively delete subdirectory
            } else {
                unlink($path); // Delete file
            }
        }
        
        // Finally, delete the directory itself
        return rmdir($dir);
    }
    
    private function buildDir($userId)
    {
        $this->userDirectory = "../../UserData/" . (string) $userId . "/";
        if (!file_exists($this->userDirectory)) {
            mkdir($this->userDirectory, 0777, true);
        }
    }
}