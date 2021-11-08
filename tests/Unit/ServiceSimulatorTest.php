<?php

namespace Tests\Unit;

use App\Services\LiftSimulatorService;
use PHPUnit\Framework\TestCase;

class ServiceSimulatorTest extends TestCase
{
    protected $requests;

    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_execute_incorrect_params_fails()
    {
        $this->getRequests();

        $liftSimulator = new LiftSimulatorService();
        $liftSimulator->execute(0, 0, '1', '2', $this->requests);
        $log = $liftSimulator->getLog();

        $this->assertTrue(!empty($log));
        $this->assertStringContainsString('ERROR', $log[0]);
        $this->assertStringContainsString('floor', $log[0]);
    }

    public function test_execute_incorrect_lifts_fails()
    {
        $this->getRequests();

        $liftSimulator = new LiftSimulatorService();
        $liftSimulator->execute(rand(1, 100), rand(900, 999), '1', '2', $this->requests);
        $log = $liftSimulator->getLog();

        $this->assertTrue(!empty($log));
        $this->assertStringContainsString('ERROR', $log[0]);
        $this->assertStringContainsString('lift', $log[0]);
    }

    private function getRequests()
    {
        if (empty($this->requests)) {
            $this->requests[] = $this->setRequest('09:00', '10:00', 5, 0, 2);
            $this->requests[] = $this->setRequest('09:00', '10:00', 10, 1, 0);

            $this->requests[] = $this->setRequest('11:00', '18:20', 20, 0, 1);
            $this->requests[] = $this->setRequest('11:00', '18:20', 20, 0, 2);
            $this->requests[] = $this->setRequest('11:00', '18:20', 20, 0, 3);

            $this->requests[] = $this->setRequest('14:00', '15:00', 4, 1, 0);
            $this->requests[] = $this->setRequest('14:00', '15:00', 4, 2, 0);
            $this->requests[] = $this->setRequest('14:00', '15:00', 4, 3, 0);
        }
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
