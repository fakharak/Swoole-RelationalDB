<?php

namespace Small\SwooleDb\Core\Index;

use Small\SwooleDb\Core\Enum\Operator;

class Index implements \JsonSerializable
{

    public IndexNode|null $root = null;

    public function __construct() {}

    /**
     * Search value
     * @param mixed $value
     * @return string[]
     */
    public function searchEqual(mixed $value): array
    {

        return $this->root?->searchEqual($value) ?? [];

    }

    /**
     * Insert a value
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function insert(string $key, mixed $value): self
    {

        if ($this->root === null) {
            $this->root = new IndexNode($this);
        }

        $this->root->insert($key, $value);

        return $this;

    }

    /**
     * Insert a value
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function remove(string $key, mixed $value): self
    {

        if ($this->root === null) {
            $this->root = new IndexNode($this);
        }

        $this->root->removeKey($key, $value);

        return $this;

    }

    /**
     * @param Operator $operator
     * @param mixed $value
     * @return string[]
     */
    public function getKeys(Operator $operator, mixed $value): array
    {

        return $this->root?->getKeys($operator, $value) ?? [];

    }


    public function jsonSerialize(): mixed
    {

        return $this->root?->jsonSerialize();

    }

}