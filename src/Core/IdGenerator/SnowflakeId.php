<?php

namespace Small\SwooleDb\Core\IdGenerator;

use Small\SwooleDb\Core\Contract\IdGeneratorInterface;

class SnowflakeId implements IdGeneratorInterface
{

    private const EPOCH = 1620000000000; // Custom epoch for June 1, 2021 (in milliseconds)
    private const WORKER_ID_BITS = 10;
    private const SEQUENCE_BITS = 12;

    private int $sequence = 0;
    private int $lastTimestamp = -1;

    public function __construct() {

        $workerId = getmygid();

        if ($workerId < 0 || $workerId >= (1 << self::WORKER_ID_BITS)) {
            throw new \LogicException("Worker ID must be between 0 and " . ((1 << self::WORKER_ID_BITS) - 1));
        }

        $this->workerId = $workerId;

    }

    public function generate(): string {

        $timestamp = $this->getTimestamp();

        if ($timestamp < $this->lastTimestamp) {
            throw new RuntimeException("Clock moved backwards. Refusing to generate Snowflake ID.");
        }

        if ($timestamp === $this->lastTimestamp) {
            $this->sequence = ($this->sequence + 1) & ((1 << self::SEQUENCE_BITS) - 1);
            if ($this->sequence === 0) {
                usleep(1000); // Wait 1 millisecond to generate the next ID
                $timestamp = $this->getTimestamp();
            }
        } else {
            $this->sequence = 0;
        }

        $this->lastTimestamp = $timestamp;

        $snowflake = ($timestamp << (self::WORKER_ID_BITS + self::SEQUENCE_BITS)) | ($this->workerId << self::SEQUENCE_BITS) | $this->sequence;

        return (string)$snowflake;

    }

    private function getTimestamp(): int
    {

        return (int)(microtime(true) * 1000) - self::EPOCH;

    }

}