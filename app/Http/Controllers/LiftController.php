<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use LiftSimulator\Services\LiftSimulatorService;

class LiftController extends Controller
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    private $lifSimulatorService;

    public function simulator()
    {
        $requests = $this->getRequests();

        $this->lifSimulatorService = new LiftSimulatorService;
        $this->lifSimulatorService->execute(
            LiftSimulatorService::DEFAULT_FLOORS,
            LiftSimulatorService::DEFAULT_LIFTS,
            '09:00',
        '20:00',
            $requests
        );
        $data['logs'] = $this->lifSimulatorService->getLog();

        return view('simulator', $data);
    }

    private function getRequests()
    {
        $requests[] = $this->setRequest('09:00', '10:00', 5, 0, 2);
        $requests[] = $this->setRequest('09:00', '10:00', 10, 1, 0);

        $requests[] = $this->setRequest('11:00', '18:20', 20, 0, 1);
        $requests[] = $this->setRequest('11:00', '18:20', 20, 0, 2);
        $requests[] = $this->setRequest('11:00', '18:20', 20, 0, 3);

        $requests[] = $this->setRequest('14:00', '15:00', 4, 1, 0);
        $requests[] = $this->setRequest('14:00', '15:00', 4, 2, 0);
        $requests[] = $this->setRequest('14:00', '15:00', 4, 3, 0);

        return $requests;
    }

    private function setRequest(
        string $startTime,
        string $endTime,
        int $frequency,
        int $origin,
        int $destination)
    {
        return ['startTime' => $startTime, 'endTime' => $endTime, 'frequency' => $frequency, 'origin' => $origin, 'destination' => $destination];
    }

}
