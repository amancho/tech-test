<?php

namespace LiftSimulator\Domain;

final class Lift
{
    private $id;
    private $floor;
    private $available;
    private $trips;

    /**
     * @param LiftId $id Unique indentificator
     * @param int $floor Current floor number
     * @param bool $available Flag
     * @param int $trips Number of the trips
     */
    public function __construct(LiftId $id, int $floor, bool $available, int $trips)
    {
        $this->id = $id;
        $this->floor = $floor;
        $this->available = $available;
        $this->trips = $trips;
    }

    public function id(): LiftId
    {
        return $this->id;
    }

    public function floor(): int
    {
        return $this->floor;
    }

    public function setFloor(int $floor)
    {
        $this->floor = $floor;
    }

    public function trips(): int
    {
        return $this->trips;
    }

    public function setTrips(int $destination)
    {
        $trips =  abs($this->floor - $destination);
        $this->trips =+ $trips;
    }

    /**
     * Check lift is available
     *
     */
    public function isAvailable(): bool
    {
        return !empty($this->available);
    }

    /**
     * Check current floor and destination floor are different
     * @param int $destination
     * @return bool
     */
    public function isOnDestination(int $destination): bool
    {
        return ($this->floor == $destination);
    }

    /**
     * Take lift
     */
    public function take()
    {
        $this->available = false;
    }

    /**
     * Leave lift
     */
    public function leave()
    {
        $this->available = true;
    }
}