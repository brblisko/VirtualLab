<?php

namespace App\Presenters;

use Nette;
use App\Models\FileManager;
use Nette\Application\UI\Form;

class FileManagerPresenter extends DefaultPresenter
{

    private $fileManager;

    public function __construct(FileManager $fileManager)
    {
        $this->fileManager = $fileManager;
    }


    public function renderFiles()
    {
        // Get the list of files in the user's directory
        $files = $this->fileManager->listFiles($this->getUser()->getId());
        
        // Pass the list of files to the template
        $this->template->files = $files;
    }


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


    public function actionUpload($uploadedFile)
    {
        try {
            // Upload the file
            $this->fileManager->uploadFile($this->getUser()->getId(), $uploadedFile);
            $this->flashMessage('File uploaded successfully.', 'success');
        } catch (\Exception $e) {
            $this->flashMessage('Failed to upload file: ' . $e->getMessage(), 'error');
        }

        // Redirect back to the default action
        $this->redirect('files');
    }

    public function actionDownload($fileName)
    {
        try {
            // Download the file
            $response = $this->fileManager->downloadFile($this->getUser()->getId(), $fileName);
            
            $this->sendPayload($response);
        } catch (\Exception $e) {
            $this->flashMessage('Failed to download file: ' . $e->getMessage(), 'error');
            // Redirect back to the default action
            $this->redirect('files');
        }
    }

    public function actionDelete($fileName)
    {
        try {
            // Delete the file
            $this->fileManager->deleteFile($this->getUser()->getId(), $fileName);
            $this->flashMessage('File deleted successfully.', 'success');
        } catch (\Exception $e) {
            $this->flashMessage('Failed to delete file: ' . $e->getMessage(), 'error');
        }

        // Redirect back to the default action
        $this->redirect('files');
    }
}