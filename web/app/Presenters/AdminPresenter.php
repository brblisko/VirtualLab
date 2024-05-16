<?php
/**
 * Application    VirtualLab
 * Author         Boris Vesely
 */


declare(strict_types=1);

namespace App\Presenters;

use Nette\Utils\DateTime;
use App\Models\ReservationFacade;
use Nette\Application\UI\Form;
use App\Models\ApiFacade;
use App\Models\Authenticator;
use Nette;

final class AdminPresenter extends DefaultPresenter
{
    private $facade;
    private $authenticator;
    private $api_facade;

    // Constructor to initialize the facades and authenticator
    public function __construct(ReservationFacade $facade, Authenticator $authenticator, ApiFacade $api_facade)
    {
        $this->facade = $facade;
        $this->authenticator = $authenticator;
        $this->api_facade = $api_facade;
    }

    // Method to run startup logic
    protected function startup()
    {
        parent::startup();

        // Check if the user has admin access
        if (!$this->getUser()->getRoles() || !in_array('admin', $this->getUser()->getRoles())) {
            $this->flashMessage('Access denied. This section is only available to administrators.', 'error');
            $this->redirect("LandingPage:welcome");
        }
    }

    // Method to get FPGA information and tunnel data
    public function actionFPGAs()
    {
        $FPGAs = $this->api_facade->getFpgaInfo();
        $tunnels = $this->api_facade->getTunnelsData();

        foreach ($FPGAs as &$FPGA) {
            $matchingFPGA = null;
            foreach ($tunnels as $tunnel) {
                if ($tunnel['fpgaip'] === $FPGA['ip']) {
                    $tunnel['username'] = $this->facade->getUsernameByUserId((int) $tunnel['user']);
                    $matchingFPGA = $tunnel;
                    break;
                }
            }
            
            if ($matchingFPGA !== null) {
                $FPGA['tunnel'] = $matchingFPGA; 
            } else {
                $FPGA['tunnel'] = []; 
            }
        }
        $this->sendJson($FPGAs);
    }

    // Method to create the upload form component
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

    // Method to handle file upload
    private function actionUpload($uFile)
    {
        // Define the path for the new CSV file
        $newFilePath = '/home/student/UserData/' . (string)$this->getUser()->getId() . '/UserPasswords.csv';

        // Open the uploaded file for reading
        if (($handle = fopen($uFile->getTemporaryFile(), 'r')) !== FALSE) {
            // Open the new file for writing
            $newFile = fopen($newFilePath, 'w');

            // Loop through each row in the uploaded CSV
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $username = $data[0];
                $password = bin2hex(random_bytes(8)); // Generate a random 16-character password

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

            $this->flashMessage('File with user passwords is available in your file manager.', 'success');
        } else {
            $this->flashMessage('Could not create a file with user passwords', 'error');
        }
    }

    // Method to render the default view
    public function renderDefault()
    {
        // Render default
    }

    // Method to handle timeslot actions
    public function actionTimeslots()
    {
        $timeSlots = $this->generateTimeSlots();
        $groupedReservations = $this->facade->getFutureReservationsGroupedByTimestamp();

        foreach ($timeSlots as $index => $dateTime) {
            $timestampStr = $dateTime->format('Y-m-d H:i:s');
            $userDetails = [];
        
        
            // Check if there are reservations for the current timeslot

            // Check if there are reservations for the current timeslot
            if (isset($groupedReservations[$timestampStr])) {
                foreach ($groupedReservations[$timestampStr] as $reservation) {
                    $userDetails[] = (object)[
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

    // Method to delete a reservation
    public function actionDeleteReservation()
    {
        $requestData = json_decode($this->getHttpRequest()->getRawBody(), true);
        $userId = $requestData['userId'];
        $timestamp = $requestData['timestamp'];

        if ($userId && $timestamp) {
            $result = $this->facade->deleteReservation($userId, $timestamp);

            if ($result) {
                $this->sendJson(['success' => true, 'message' => 'Reservation deleted successfully.']);
            } else {
                $this->sendJson(['success' => false, 'message' => 'Failed to delete reservation.']);
            }
        } else {
            $this->sendJson(['success' => false, 'message' => 'Missing userId or timestamp.']);
        }
    }

    // Method to disable an FPGA
    public function actionDisableFPGA()
    {
        $requestData = json_decode($this->getHttpRequest()->getRawBody(), true);
        $ip = $requestData['ip'];

        $logData = [
            'deleted_tunnels' => [],
            'deleted_reservations' => []
        ];

        $tunnels = $this->api_facade->getTunnelsData();

        foreach ($tunnels as $tunnel) {
            if ($tunnel['fpgaip'] === $ip) {
                $result = $this->api_facade->sendInstruction($tunnel['fpgaip'], $tunnel['clientip'], $tunnel['user'], "DELETE");

                if (!$result) {
                    $this->sendJson(['success' => false, 'message' => 'Failed to delete a tunnel. userId: ' . $tunnel['user'] . ' clientip: ' . $tunnel['clientip']]);
                    return;
                }
                $logData['deleted_tunnels'][] = $tunnel;
                break;
            }
        }

        $response = $this->api_facade->setState($ip, "DISABLED");

        $groupedReservations = $this->facade->getFutureReservationsGroupedByTimestamp();
        $maxPYNQs = $this->api_facade->getAllFpgaCount();

        foreach ($groupedReservations as $dateTime => $users) {
            $numberOfUsers = count($users);
            if ($numberOfUsers > $maxPYNQs) {
                $result = $this->facade->deleteReservation($users[0]['user_id'], $dateTime);

                if (!$result) {
                    $this->sendJson(['success' => false, 'message' => 'Failed to delete reservation. userId: ' . $users[0]['user_id'] . ' timestamp: ' . $dateTime]);
                    return;
                }

                $logData['deleted_reservations'][] = [
                    'username' => $users[0]['username'],
                    'user_id' => $users[0]['user_id'],
                    'timestamp' => $dateTime
                ];
            }
        }

        $this->logActivity($logData);

        if ($response) {
            $this->sendJson(['success' => true, 'message' => 'FPGA with IP: ' . $ip . ' disabled successfully. All deleted reservations and cancelled tunnels are available in the file manager.']);
        } else {
            $this->sendJson(['success' => false, 'message' => 'Error disabling FPGA: ' . $ip]);
        }
    }

    // Method to log activity
    private function logActivity($logData)
    {
        $timestamp = date('Y-m-d H:i:s');

        $jsonData = json_encode([
            'timeOfDeletion' => $timestamp,
            'data' => $logData
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($jsonData === false) {
            $jsonData = "JSON encode error: " . json_last_error_msg();
        }

        $jsonData .= "\n";

        $directoryPath = '/home/student/UserData/' . (string)$this->getUser()->getId();
        if (!is_dir($directoryPath)) {
            mkdir($directoryPath, 0777, true);
        }

        $filePath = $directoryPath . '/log.txt';

        file_put_contents($filePath, $jsonData, FILE_APPEND);
    }

    // Method to enable an FPGA
    public function actionEnableFPGA()
    {
        $requestData = json_decode($this->getHttpRequest()->getRawBody(), true);
        $ip = $requestData['ip'];

        $response = $this->api_facade->setState($ip, "DEFAULT");

        if ($response) {
            $this->sendJson(['success' => true, 'message' => 'FPGA with IP: ' . $ip . ' enabled successfully.']);
        } else {
            $this->sendJson(['success' => false, 'message' => 'Error enabling FPGA: ' . $ip]);
        }
    }

    // Method to generate timeslots
    private function generateTimeSlots()
    {
        $currentTime = new DateTime();
        $endDate = (clone $currentTime)->modify('+1 days');

        // Calculate the nearest 15-minute interval for the next window
        $minutes = $currentTime->format('i');
        $roundedMinutes = (int)(ceil($minutes / 15) * 15);

        // If the next window is at 60 minutes, set it to 0 and add 1 hour
        if ($roundedMinutes == 60) {
            $roundedMinutes = 0;
            $currentTime->modify('+1 hour');
        }

        // Set the initial time slot to the next window
        $hours = (int)$currentTime->format('H');
        $currentTime->setTime($hours, $roundedMinutes);

        $timeSlots = [];
        while ($currentTime <= $endDate) {
            $timeSlotDateTime = clone $currentTime;
            $timeSlots[] = $timeSlotDateTime;

            // Increment current time by 15 minutes
            $currentTime->modify('+15 minutes');
        }

        return $timeSlots;
    }
}
