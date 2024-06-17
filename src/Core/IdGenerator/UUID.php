<?php
/*
 *  This file is a part of small-swoole-db
 *  Copyright 2024 - Sébastien Kus
 *  Under GNU GPL V3 licence
 */

namespace Small\SwooleDb\Core\IdGenerator;

use Small\SwooleDb\Core\Contract\IdGeneratorInterface;

class UUID implements IdGeneratorInterface
{

    public function generate(): string
    {

        // Generate a 16-byte binary string
        $data = random_bytes(16);

        // Set the version (4) and variant (2) bits
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // version 4
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // variant 2

        // Convert binary to hexadecimal
        $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));

        return $uuid;

    }

}