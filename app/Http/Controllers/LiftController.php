<?php

namespace App\Http\Controllers;

use App\Services\LiftSimulatorService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;

class LiftController extends Controller
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    private $lifSimulatorService;

    public function simulator()
    {
        $this->lifSimulatorService = new LiftSimulatorService;
        $this->lifSimulatorService->execute();
        $data['logs'] = $this->lifSimulatorService->getLog();

        return view('simulator', $data);
    }
}
