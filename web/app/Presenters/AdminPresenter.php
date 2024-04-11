<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette\Utils\DateTime;
use App\Models\ReservationFacade;
use Nette\Application\UI\Form;
use Nette\Application\Responses\FileResponse;
use App\Models\Authenticator;
use Nette;


//debugging
use Tracy\Debugger;
use Tracy\OutputDebugger;
use Nette\Application\Responses\TextResponse;

final class AdminPresenter extends DefaultPresenter
{
    private $facade;
    private $authenticator;
    
    public function __construct(ReservationFacade $facade, Authenticator $authenticator)
    {
        $this->facade = $facade;
        $this->authenticator = $authenticator;
    }

    

    protected function startup()
    {
        parent::startup();
        
        
        
        // // Enable Tracy and set output mode to debug
            // Debugger::enable(Debugger::DETECT, '/home/boris/shared/web/log', 'debug');
    
            // $outputDebugger = new OutputDebugger();
            // $outputDebugger->start();
        





        if (!$this->getUser()->getRoles() || !in_array('admin', $this->getUser()->getRoles())) {
            $this->flashMessage('Access denied. This section is only available to administrators.', 'error');
            $this->redirect("Landingpage:welcome");
        }
    }


    public function createComponentUploadForm()
    {
    $form = new Form();
    $form->addUpload('uploadedFile', 'Upload File:')
        ->setRequired('Please select a file to upload.')
        ->addRule(function ($fileControl) {
            $file = $fileControl->getValue();
            if (!$file->isOk()) {
                return false;
            }
            $fileExtension = strtolower(pathinfo($file->getName(), PATHINFO_EXTENSION));
            return $fileExtension === 'csv';
        }, 'Only CSV files are allowed.');

    $form->addSubmit('submit', 'Upload');

    $form->onSuccess[] = function (Form $form, $values) {
        $this->actionUpload($values->uploadedFile);
    };

    return $form;
    }

    private function  actionUpload($uFile)
    {
        // Define the path for the new CSV file
        $newFilePath = '../../UserData/' . (string) $this->getUser()->getId() . '/UserPasswords.csv';
            
        // Open the uploaded file for reading
        if (($handle = fopen($uFile->getTemporaryFile(), 'r')) !== FALSE) {
            // Open the new file for writing
            $newFile = fopen($newFilePath, 'w');
            
            // Loop through each row in the uploaded CSV
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $username = $data[0];
                
                // Generate a random 16-character password
                $password = bin2hex(random_bytes(8)); // 8 bytes = 16 characters in hex
                
                $success = true;

                try {
                    $this->authenticator->createUser($username, $password);
                } catch (\PDOException $e) {
                    $success = false;
                }

                // Write the username and password pair to the new CSV file
                fputcsv($newFile, [$username, $password, $success], ',');
            }
            
            // Close the file handlers
            fclose($handle);
            fclose($newFile);
            
            $this->flashMessage('File with user passwords is avaliable in your filemanager.', 'success');

            // do you want dirrect download???
            // $this->sendResponse(new FileResponse($newFilePath));
        } else {
            $this->flashMessage('Could not create a file with user passwords', 'error');
        }
    }


    public function actionDebug() {
        
        $timeSlots = $this->generateTimeSlots();
        
        $groupedReservations = $this->facade->getFutureReservationsGroupedByTimestamp();


        foreach ($timeSlots as $index => $dateTime) {
            // Convert the DateTime object to a string that matches the keys in $groupedReservations
            $timestampStr = $dateTime->format('Y-m-d H:i:s');
            
            // Initialize an empty array to store user details
            $userDetails = [];
        
            // Check if there are reservations for the current timeslot
            if (isset($groupedReservations[$timestampStr])) {
                foreach ($groupedReservations[$timestampStr] as $reservation) {
                    $userDetails[] = (object) [
                        'user_id' => $reservation['user_id'],
                        'username' => $reservation['username'],
                    ];
                }
            }
        
            $timeSlots[$index] = [
                'timeslot' => $timestampStr,
                'userDetails' => $userDetails,
            ];
        }

        $output = var_export($timeSlots, true);
        $this->sendResponse(new TextResponse($output));
    }

    public function renderDefault()
    {
        $timeSlots = $this->generateTimeSlots();
        
        $groupedReservations = $this->facade->getFutureReservationsGroupedByTimestamp();
        
        foreach ($timeSlots as $index => $dateTime) {
            // Convert the DateTime object to a string that matches the keys in $groupedReservations
            $timestampStr = $dateTime->format('Y-m-d H:i:s');
            
            // Initialize an empty array to store user details
            $userDetails = [];
        
            // Check if there are reservations for the current timeslot
            if (isset($groupedReservations[$timestampStr])) {
                foreach ($groupedReservations[$timestampStr] as $reservation) {

                    $userDetails[] = (object) [
                        'user_id' => $reservation['user_id'],
                        'username' => $reservation['username'],
                    ];
                }
            }
        
            $timeSlots[$index] = [
                'timeslot' => $timestampStr,
                'userDetails' => $userDetails,
            ];
        }
        
        
        $this->template->timeSlots = $timeSlots;
    }


    public function actionTimeslots()
{
    $timeSlots = $this->generateTimeSlots();
        
        $groupedReservations = $this->facade->getFutureReservationsGroupedByTimestamp();
        
        foreach ($timeSlots as $index => $dateTime) {
            // Convert the DateTime object to a string that matches the keys in $groupedReservations
            $timestampStr = $dateTime->format('Y-m-d H:i:s');
            
            // Initialize an empty array to store user details
            $userDetails = [];
        
            // Check if there are reservations for the current timeslot
            if (isset($groupedReservations[$timestampStr])) {
                foreach ($groupedReservations[$timestampStr] as $reservation) {

                    $userDetails[] = (object) [
                        'user_id' => $reservation['user_id'],
                        'username' => $reservation['username'],
                    ];
                }
            }
        
            $timeSlots[$index] = [
                'timeslot' => $timestampStr,
                'userDetails' => $userDetails,
            ];
        }
    
    $this->sendJson($timeSlots);
}

    public function actionDeleteReservation()
    {
        $requestData = json_decode($this->getHttpRequest()->getRawBody(), true);
        $userId = $requestData['userId'];
        $timestamp = $requestData['timestamp'];


        if ($userId && $timestamp) {
            // Assuming you have a method to delete the reservation by userId and timestamp
            $result = $this->facade->deleteReservation($userId, $timestamp);

            if ($result) {
                $this->sendJson(['success' => true, 'message' => 'Reservation deleted successfully.']);
            } else {
                // Handle case where deletion was not successful, e.g., reservation not found
                $this->sendJson(['success' => false, 'message' => 'Failed to delete reservation.']);
            }
        } else {
            $this->sendJson(['success' => false, 'message' => 'Missing userId or timestamp.']);
        }

    }


    private function generateTimeSlots()
    {
        $currentTime = new DateTime();
        $endDate = (clone $currentTime)->modify('+1  days');

        // Calculate the nearest 15-minute interval for the next window
        $minutes = $currentTime->format('i');
        $roundedMinutes = (int) (ceil($minutes / 15) * 15);

        // If the next window is at 60 minutes, set it to 0 and add 1 hour
        if ($roundedMinutes == 60) {
            $roundedMinutes = 0;
            $currentTime->modify('+1 hour');
        }

        // Set the initial time slot to the next window
        $hours = (int) $currentTime->format('H');
        $currentTime->setTime($hours, $roundedMinutes);

        $timeSlots = [];
        while ($currentTime <= $endDate) {
            // Create DateTime object for current time slot
            $timeSlotDateTime = clone $currentTime;
            $timeSlots[] = $timeSlotDateTime;

            // Increment current time by 15 minutes
            $currentTime->modify('+15 minutes');
        }

        return $timeSlots;
    }
}