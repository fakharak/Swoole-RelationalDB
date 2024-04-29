<?php

namespace Small\SwooleDb\Core\IdGenerator;

use Small\SwooleDb\Core\Contract\IdGeneratorInterface;

class SnowflakeId implements IdGeneratorInterface
{

    private const EPOCH = 1620000000000; // Custom epoch for June 1, 2021 (in milliseconds)
    private const WORKER_ID_BITS = 10;
    private const SEQUENCE_BITS = 12;

    protected static int $sequence = 0;
    protected static int $lastTimestamp = -1;

    protected int $workerId;

    public function __construct() {

        $workerId = getmygid();

        if ($workerId === false) {
            throw new \LogicException('SnowflakeId cannot be generated.');
        }

        if ($workerId < 0 || $workerId >= (1 << self::WORKER_ID_BITS)) {
            throw new \LogicException("Worker ID must be between 0 and " . ((1 << self::WORKER_ID_BITS) - 1));
        }

        $this->workerId = $workerId;

    }

    public function generate(): string {

        $timestamp = $this->getTimestamp();

        if ($timestamp < self::$lastTimestamp) {
            throw new \LogicException("Clock moved backwards. Refusing to generate Snowflake ID.");
        }

        if ($timestamp === self::$lastTimestamp) {
            self::$sequence = (self::$sequence + 1) & ((1 << self::SEQUENCE_BITS) - 1);
            if (self::$sequence === 0) {
                usleep(1000); // Wait 1 millisecond to generate the next ID
                $timestamp = $this->getTimestamp();
            }
        } else {
            self::$sequence = 0;
        }

        self::$lastTimestamp = $timestamp;

        $snowflake = ($timestamp << (self::WORKER_ID_BITS + self::SEQUENCE_BITS)) | ($this->workerId << self::SEQUENCE_BITS) | $this->sequence;

        return (string)$snowflake;

    }

    private function getTimestamp(): int
    {

        return (int)(microtime(true) * 1000) - self::EPOCH;

    }

}