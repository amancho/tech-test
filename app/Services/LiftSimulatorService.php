<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\Traits\Creator;
use Exception;
use function PHPUnit\Framework\throwException;

/**
 * Service to simulate a several lift requests
 *
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
    private $requests;
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
     * @param array $requests Array of requests with frequency, floor origin, floor destination
     * @return array
     */
    public function execute(
        int $floors = self::DEFAULT_FLOORS,
        int $lifts = self::DEFAULT_LIFTS,
        string $hourStart = self::DEFAULT_START,
        string $hourEnd = self::DEFAULT_END,
        array $requests
    ): array
    {
        try{
            $this->setParams($floors, $lifts, $hourStart, $hourEnd);

            $this->setLifts($this->lifts);
            $this->setSequences($this->hourStart, $this->hourEnd);
            $this->setRequests($requests);

            $this->processSequences();
        }catch (\Exception $ex){
            $this->setLog('ERROR ' . $ex->getMessage());
        } finally {
            return $this->log;
        }
    }

    /**
     * Check values and set params
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
    private function setRequests(array $requests)
    {
        foreach($requests as $request){
            $this->requests[] = $this->generateRequest($request['startTime'], $request['endTime'], $request['frequency'], $request['origin'], $request['destination']);
        }
    }

    /**
     * @param string $startTifme Start hour HH:ii
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

            $this->checkRequests($currentSequence);
            $this->logLiftsPosition();
        }
    }

    /**
     * Check current sequence has a lift request
     * @param \DateTime $sequence
     * @return bool
     */
    private function checkRequests(\DateTime $sequence)
    {
        foreach ($this->requests as $request) {
            if ($this->hasRequest($sequence, $request)) {
                $this->requestLift($request);
                return true;
            }
        }

        return false;
    }

    /**
     * Check current sequence has a request
     * @param \DateTime $sequence current execution sequence
     * @param array $request scheduled execution request
     * @return bool
     */
    private function hasRequest(\DateTime $sequence, array $request): bool
    {
        $currentSequence = new Carbon($sequence->format('Y-m-d H:i:s.u'), $sequence->getTimezone());
        $startDate = new Carbon($request['startTime']->format('Y-m-d H:i:s.u'), $request['startTime']->getTimezone());
        $endDate = new Carbon($request['endTime']->format('Y-m-d H:i:s.u'), $request['endTime']->getTimezone());

        $isBetweenRequest = $currentSequence->between($startDate, $endDate);
        if (!$isBetweenRequest) {
            return false;
        }

        $frequency = (int) $request['frequency'];
        $currentMinute = (int) $currentSequence->format('i');
        if($currentMinute % $frequency != 0) {
            return false;
        }

        return true;
    }

    /**
     * Make a lift request
     * @param array $request
     */
    private function requestLift(array $request)
    {
        foreach ($this->liftsData as $lift) {
            if ($this->isLiftAvailable($lift['id'])) {
                $this->getLift($lift['id']);
                $this->setLiftPosition($lift['id'], $request['destination']);
            }
        }
    }
    /**
     * Check lift is available
     */
    private function isLiftAvailable(int $id): bool
    {
        return !empty($this->liftsData[$id]['available']);
    }

    /**
     * Set lift not available
     */
    private function getLift(int $id)
    {
        $this->liftsData[$id]['available'] = false;
    }

    /**
     * Set lift position and count trips
     * @param int $id
     * @param int $destination
     */
    private function setLiftPosition(int $id, int $destination)
    {
        if (!empty($this->liftsData[$id]) &&  !empty($this->liftsData[$id]['available'])
          && ($this->liftsData[$id]['floor'] != $destination)) {

            $trips =  abs($this->liftsData[$id]['floor'] - $destination);

            $this->liftsData[$id]['floor'] = $destination;
            $this->liftsData[$id]['trips'] =+ $trips;
            $this->liftsData[$id]['available'] = true;
        }
    }

    private function logLiftsPosition()
    {
        foreach ($this->liftsData as $lift) {
            $this->setLog('LIFT '. $lift['id'] . ' FLOOR :: ' . $lift['floor'] . ' TRIPS :: ' . $lift['trips']);
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
