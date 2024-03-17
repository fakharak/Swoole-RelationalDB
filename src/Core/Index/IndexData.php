<?php

namespace Small\SwooleDb\Core\Index;

class IndexData
{

    /** @var string[] */
    protected array $keys = [];

    /**
     * @param (int|float|string|null)[] $values
     */
    public function __construct(
        protected array $values,
    ) {}

    public function addKey(string $key): self
    {

        $this->keys[$key] = $key;

        return $this;

    }

    public function removeKey(string $key): self
    {

        unset($this->keys[$key]);

        return $this;

    }

    /**
     * @return (int|float|string|null)[]
     */
    public function getValues(): array
    {

        return $this->values;

    }

    /**
     * @return string[]
     */
    public function getKeys(): array
    {

        return array_values($this->keys);

    }

    /**
     * Return true if this value inferior of parameter value
     * @param (int|float|string|null)[] $values
     * @return bool
     */
    public function inferior(array $values): bool
    {

       foreach ($values as $key => $item) {

            if ($this->values[$key] > $item) {
                return false;
            } else if ($this->values[$key] < $item){
                return true;
            }

        }

        return false;

    }

    /**
     * Return true if this value inferior or equal of parameter value
     * @param (int|float|string|null)[] $values
     * @return bool
     */
    public function equal(array $values): bool
    {

        foreach ($values as $key => $item) {

            if ($this->values[$key] != $item) {
                return false;
            }

        }

        return true;

    }

    /**
     * Return true if this value inferior or equal of parameter value
     * @param (int|float|string|null)[] $values
     * @return bool
     */
    public function inferiorOrEqual(array $values): bool
    {

        foreach ($values as $key => $item) {

            if ($this->values[$key] > $item) {
                return false;
            } else if ($this->values[$key] < $item) {
                return true;
            }

        }

        return true;

    }

    /**
     * Return true if this value superior of parameter value
     * @param (int|float|string|null)[] $values
     * @return bool
     */
    public function superior(array $values): bool
    {

        foreach ($values as $key => $item) {

            if ($this->values[$key] < $item) {
                return false;
            } else if ($this->values[$key] > $item) {
                return true;
            }

        }

        return false;

    }

    /**
     * Return true if this value superior or equal of parameter value
     * @param (int|float|string|null)[] $values
     * @return bool
     */
    public function superiorOrEqual(array $values): bool
    {

        foreach ($values as $key => $item) {

            if ($this->values[$key] < $item) {
                return false;
            } else if ($this->values[$key] > $item) {
                return true;
            }

        }

        return true;

    }

}