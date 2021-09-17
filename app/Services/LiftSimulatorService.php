<?php


namespace App\Services;

use Carbon\Carbon;
use Exception;
use function PHPUnit\Framework\throwException;

/**
 * Service
 *
 * Class LiftSimulatorService
 * @package App\Services
 */
class LiftSimulatorService
{
    const DEFAULT_FLOORS = 4;
    const DEFAULT_LIFTS = 3;

    const DEFAULT_START = '09:00';
    const DEFAULT_END = '20:00';

    private $floors;
    private $lifts;
    private $hourStart;
    private $hourEnd;

    private $liftsData = [];

    private $sequences;
    private $log = [];

    public function __construct()
    {

    }

    /**
     * Execute lift service simulation and log results
     *
     * @param int $floors Number of floors
     * @param int $lifts Number of lifts
     * @param string $hourStart DateTime start
     * @param string $hourEnd DateTime End
     * @return array
     */
    public function execute(
        int $floors = self::DEFAULT_FLOORS,
        int $lifts = self::DEFAULT_LIFTS,
        string $hourStart = self::DEFAULT_START,
        string $hourEnd = self::DEFAULT_END): array
    {
        try{
            $this->setParams($floors, $lifts, $hourStart, $hourEnd);
            $this->setLifts($this->lifts);
            $this->setSequences($this->hourStart, $this->hourEnd);
            $this->setRequests();

            $this->processSequences();
        }catch (\Exception $ex){
            $this->setLog('ERROR ' . $ex->getMessage());
        } finally {
            return $this->log;
        }
    }

    /**
     * Check and set params
     *
     * @param int $floors
     * @param int $lifts
     * @param string $hourStart
     * @param string $hourEnd
     * @throws Exception
     */
    private function setParams(
        int $floors = self::DEFAULT_FLOORS,
        int $lifts = self::DEFAULT_LIFTS,
        string $hourStart = self::DEFAULT_START,
        string $hourEnd = self::DEFAULT_END)
    {
        if($floors < 1 || $floors > 100 ){
            throw new Exception('Incorrect number of floors, between 1 and 100');
        }

        if($lifts < 1 || $lifts > 5 ){
            throw new Exception('Incorrect number of lifts, between 1 and 5');
        }

        $beginHour = Carbon::parse($hourStart);
        $endHour = Carbon::parse($hourEnd);
        if($beginHour->addMinute()->gt($endHour)){
            throw new Exception('Incorrect hours, hourEnd should be after than hourstart');
        }

        $this->floors = $floors;
        $this->lifts = $lifts;
        $this->hourStart = $hourStart;
        $this->hourEnd = $hourEnd;
    }

    /**
     * Set interval sequence
     * @param string $start
     * @param string $end
     */
    private function setSequences(string $start, string $end)
    {
        $startSecuence = $this->getDateTime($start);
        $endSecuence = $this->getDateTime($end);

        $interval = new \DateInterval('P0YT1M'); //1 minuto
        $this->sequences = new \DatePeriod($startSecuence, $interval, $endSecuence);
    }

    /**
     * Set interval requests
     */
    private function setRequests()
    {
        $this->requests[] = $this->generateRequest('09:00', '11:00', 5, 0, 2);
        $this->requests[] = $this->generateRequest('09:00', '10:00', 10, 1, 0);

        $this->requests[] = $this->generateRequest('11:00', '18:20', 20, 0, 1);
        $this->requests[] = $this->generateRequest('11:00', '18:20', 20, 0, 2);
        $this->requests[] = $this->generateRequest('11:00', '18:20', 20, 0, 3);

        $this->requests[] = $this->generateRequest('14:00', '15:00', 4, 1, 0);
        $this->requests[] = $this->generateRequest('14:00', '15:00', 4, 2, 0);
        $this->requests[] = $this->generateRequest('14:00', '15:00', 4, 3, 0);
    }

    /**
     * @param string $startTime Start hour HH:ii
     * @param string $endTime End hour HH:ii
     * @param int $frequency Frequency in minutes
     * @param int $origin Floor origin number
     * @param int $destination Floor origin destination
     * @return array
     */
    private function generateRequest(
        string $startTime,
        string $endTime,
        int $frequency,
        int $origin,
        int $destination)
    {
        return  [
            'startTime' => $this->getDateTime($startTime),
            'endTime' => $this->getDateTime($endTime),
            'frequency' => $frequency,
            'origin' => $origin,
            'destination' => $destination
        ];
    }

    private function getDateTime(string $time)
    {
        $dateTime = new \DateTime();
        $dateTime->setTime($this->getHour($time), $this->getMinute($time));

        return $dateTime;
    }

    private function getHour(string $time)
    {
        $match = $this->splitTime($time);
        return $match[1];
    }

    private function getMinute(string $time)
    {
        $match = $this->splitTime($time);
        return $match[2];
    }

    private function splitTime(string $time)
    {
        preg_match("/([0-9]{1,2}):([0-9]{1,2})/", $time, $match);
        return $match;
    }

    /**
     * Set initial lifts status
     *
     * @param int $lifts
     */
    private function setLifts(int $lifts)
    {
        for ($i=0; $i < $lifts; $i++) {
            $this->liftsData[$i] = [
                'id' => $i,
                'floor' => 0,
                'available' => true,
                'trips' => 0
            ];
        }
    }

    private function processSequences()
    {
        $this->setLog('START SIMULATION');

        foreach ($this->sequences as $currentSequence) {
            $this->setLog('SEQUENCE ' . $currentSequence->format('Y-m-d H:i'));


            $this->checkLiftsPosition();
        }
    }

    private function checkLiftsPosition()
    {
        foreach ($this->liftsData as $currentLift) {
            $this->setLog('LIFT '. $currentLift['id'] . ' FLOOR :: ' . $currentLift['floor'] . ' TRIPS :: ' . $currentLift['trips']);
        }
    }

    /**
     * Save log information
     */
    public function setLog(string $message)
    {
        $this->log[] = date('Y-m-d H:i:s') . ' ' . $message;
    }

    /**
     * Get execution log information
     *
     * @return array
     */
    public function getLog(): array
    {
        return $this->log;
    }



}
