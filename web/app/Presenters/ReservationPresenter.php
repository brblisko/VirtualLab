<?php

declare(strict_types=1);

namespace App\Presenters;


use Nette\Utils\DateTime;
use App\Models\ReservationFacade;
use Nette;

final class ReservationPresenter extends Nette\Application\UI\Presenter
{

    private $facade;
    
    public function __construct(ReservationFacade $facade)
    {
        $this->facade = $facade;
    }

    public function renderTable()
    {
        $timeSlots = $this->generateTimeSlots();
        $this->template->timeSlots = $timeSlots;
        
        // Calculate start and end indices for each column
        $totalTimeSlots = count($timeSlots);
        $columnSize = (int) ceil($totalTimeSlots / 3);

        $columnStartIndex = [];
        $columnEndIndex = [];
        for ($i = 0; $i < 3; $i++) {
            $columnStartIndex[$i] = $i * $columnSize;
            $columnEndIndex[$i] = min(($i + 1) * $columnSize - 1, $totalTimeSlots - 1);
        }

        $this->template->columnStartIndex = $columnStartIndex;
        $this->template->columnEndIndex = $columnEndIndex;
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
