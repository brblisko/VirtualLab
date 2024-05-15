<?php

namespace App\Presenters;

use Nette;
use App\Models\FileManager;
use Nette\Application\UI\Form;
use Nette\Application\Responses\FileResponse;

class FileManagerPresenter extends DefaultPresenter
{
    private $fileManager;

    // Constructor to initialize the FileManager
    public function __construct(FileManager $fileManager)
    {
        $this->fileManager = $fileManager;
    }

    // Method to render the list of files and directories
    public function renderFiles()
    {
        // Get the list of files and directories in the user's directory
        $contents = $this->fileManager->listFiles($this->getUser()->getId());

        // Pass the list of files and directories to the template
        $this->template->contents = $contents;
    }

    // Method to delete a directory
    public function actionDeleteDir($dir)
    {
        if ($this->fileManager->deleteDirectory($this->getUser()->getId(), $dir)) {
            $this->flashMessage('Directory: "' . $dir .  '" deleted successfully.', 'success');
        } else {
            $this->flashMessage("Failed to delete directory", 'error');
        }
        $this->redirect('files');
    }

    // Method to create the upload form component
    public function createComponentUploadForm()
    {
        $form = new Form();
        $form->addUpload('uploadedFile', 'Upload File:')
            ->setRequired('Please select a file to upload.');

        $form->addSubmit('submit', 'Upload');

        $form->onSuccess[] = function (Form $form, $values) {
            $this->actionUpload($values->uploadedFile);
        };

        return $form;
    }

    // Method to handle file upload
    public function actionUpload($uploadedFile)
    {
        try {
            // Upload the file
            $this->fileManager->uploadFile($this->getUser()->getId(), $uploadedFile);
            $this->flashMessage('File uploaded successfully.', 'success');
        } catch (\Exception $e) {
            $message = 'Failed to upload file: ' . (string) $e->getMessage();
            $this->flashMessage($message, 'error');
        }

        // Redirect back to the files action
        $this->redirect('files');
    }

    // Method to handle directory download
    public function actionDownloadDir($path)
    {
        $tempZip = $this->fileManager->downloadDir($this->getUser()->getId(), $path);

        if ($tempZip && file_exists($tempZip)) {
            $response = new FileResponse($tempZip, "archive.zip", 'application/zip', true);
            $this->sendResponse($response);
        } else if (!file_exists($tempZip)) {
            $this->flashMessage('Could not create an archive from an empty directory.', 'error');
            $this->redirect('files');
        } else {
            $this->flashMessage("Could not create zip archive.", 'error');
            $this->redirect('files');
        }
    }

    // Method to handle file download
    public function actionDownload($fileName)
    {
        $filePath = $this->fileManager->getPath($this->getUser()->getId(), $fileName);

        if ($filePath !== null) {
            $this->sendResponse(new FileResponse($filePath));
        } else {
            $this->flashMessage('Failed to download file: The file is missing', 'error');
        }
    }

    // Method to handle file deletion
    public function actionDelete($fileName)
    {
        try {
            // Delete the file
            $this->fileManager->deleteFile($this->getUser()->getId(), $fileName);
            $this->flashMessage('File: "' . $fileName .  '" deleted successfully.', 'success');
        } catch (\Exception $e) {
            $message = 'Failed to delete file: ' . $e->getMessage();
            $this->flashMessage($message, 'error');
        }

        // Redirect back to the files action
        $this->redirect('files');
    }
}
