<?php

namespace Small\SwooleDb\Core\IdGenerator;

use Random\RandomException;
use Small\SwooleDb\Core\Contract\IdGeneratorInterface;

class ULID implements IdGeneratorInterface
{

    protected const ENCODING = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';
    protected const TIME_BITS = 48;
    protected const RANDOM_BITS = 80;

    /**
     * Generate a ULID.
     * @return string
     * @throws RandomException
     */
    public function generate(): string
    {

        $time = microtime(true) * 1000;
        $timeBits = str_pad(
            base_convert(
                (string)floor($time),
                10,
                32
            ), (int)floor(self::TIME_BITS / 5),
            '0',
            STR_PAD_LEFT
        );

        $randomBits = '';
        for ($i = 0; $i < self::RANDOM_BITS / 5; $i++) {
            $randomBits .= self::ENCODING[random_int(0, 31)];
        }

        return $timeBits . $randomBits;

    }

}