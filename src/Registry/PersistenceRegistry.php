<?php
/*
 *  This file is a part of small-swoole-db
 *  Copyright 2024 - Sébastien Kus
 *  Under GNU GPL V3 licence
 */

namespace Small\SwooleDb\Registry;

use Small\SwooleDb\Core\Contract\PersistenceInterface;
use Small\SwooleDb\Core\Persistence\AsJsonFile;
use Small\SwooleDb\Exception\NotFoundException;
use Small\SwooleDb\Exception\SmallSwooleDbException;
use Small\SwooleDb\Registry\Enum\ParamType;
use Small\SwooleDb\Registry\Trait\RegistryTrait;

final class PersistenceRegistry
{

    use RegistryTrait;

    const DEFAULT = 'DEFAULT';

    /** @var PersistenceInterface[] */
    private array $persistenceChannels = [
    ];

    private function __construct() {
        $this->persistenceChannels[self::DEFAULT] = new AsJsonFile();
    }

    /**
     * Set default persistence channel
     * @param PersistenceInterface $persistence
     * @return $this
     */
    public function setDefaultChannel(PersistenceInterface $persistence): self
    {
        $this->persistenceChannels[self::DEFAULT] = $persistence;

        return $this;
    }

    /**
     * Set persistence channel $channelName
     * @param string $channelName
     * @param PersistenceInterface $persistence
     * @return $this
     */
    public function setChannel(string $channelName, PersistenceInterface $persistence): self
    {
        $this->persistenceChannels[$channelName] = $persistence;

        return $this;
    }

    /**
     * Get a channel by name
     * @param string $channelName
     * @return PersistenceInterface
     * @throws SmallSwooleDbException
     */
    public function getChannel(string $channelName): PersistenceInterface
    {

        if (!array_key_exists($channelName, $this->persistenceChannels)) {
            throw new NotFoundException('Persistence channel ' . $channelName . ' not defined');
        }

        return $this->persistenceChannels[$channelName];

    }

}