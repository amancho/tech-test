<?php

namespace Tests\Unit;

use App\Services\LiftSimulatorService;
use PHPUnit\Framework\TestCase;

class ServiceSimulatorTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_execute_incorrect_params_fails()
    {
        $liftSimulator = new LiftSimulatorService();
        $liftSimulator->execute(0, 0, '1', '2');
        $log = $liftSimulator->getLog();

        $this->assertTrue(!empty($log));
        $this->assertStringContainsString('ERROR', $log[0]);
        $this->assertStringContainsString('floor', $log[0]);
    }

    public function test_execute_incorrect_lifts_fails()
    {
        $liftSimulator = new LiftSimulatorService();
        $liftSimulator->execute(rand(1, 100), rand(900, 999), '1', '2');
        $log = $liftSimulator->getLog();

        $this->assertTrue(!empty($log));
        $this->assertStringContainsString('ERROR', $log[0]);
        $this->assertStringContainsString('lift', $log[0]);
    }
}
