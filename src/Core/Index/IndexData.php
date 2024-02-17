<?php

namespace Small\SwooleDb\Core\Index;

class IndexData
{

    /** @var string[] */
    protected array $keys = [];

    public function __construct(
        protected mixed $value,
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

    public function getValue(): mixed
    {

        return $this->value;

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
     * @param mixed $value
     * @return bool
     */
    public function inferior(mixed $value): bool
    {

        if (
            (is_array($value) && !is_array($this->value)) ||
            (!is_array($value) && is_array($this->value))
        ) {

            throw new \LogicException('Can\t compare array with scalar');

        }

        if (is_array($value)) {

            foreach ($value as $key => $item) {

                if (!is_array($this->value)) {
                    throw new \LogicException('Index value is not array');
                }

                if ($this->value[$key] > $item) {
                    return false;
                } else if ($this->value[$key] < $item){
                    return true;
                }

            }

            return false;

        } else {

            return $this->value < $value;

        }

    }

    /**
     * Return true if this value inferior or equal of parameter value
     * @param mixed $value
     * @return bool
     */
    public function equal(mixed $value): bool
    {

        if (
            (is_array($value) && !is_array($this->value)) ||
            (!is_array($value) && is_array($this->value))
        ) {

            throw new \LogicException('Can\t compare array with scalar');

        }

        if (is_array($value)) {

            foreach ($value as $key => $item) {

                if (!is_array($this->value)) {
                    throw new \LogicException('Index value is not array');
                }

                if ($this->value[$key] != $item) {
                    return false;
                }

            }

            return true;

        } else {

            return $this->value == $value;

        }

    }

    /**
     * Return true if this value inferior or equal of parameter value
     * @param mixed $value
     * @return bool
     */
    public function inferiorOrEqual(mixed $value): bool
    {

        if (
            (is_array($value) && !is_array($this->value)) ||
            (!is_array($value) && is_array($this->value))
        ) {

            throw new \LogicException('Can\t compare array with scalar');

        }

        if (is_array($value)) {

            foreach ($value as $key => $item) {

                if (!is_array($this->value)) {
                    throw new \LogicException('Index value is not array');
                }

                if ($this->value[$key] > $item) {
                    return false;
                } else if ($this->value[$key] < $item) {
                    return true;
                }

            }

            return true;

        } else {

            return $this->value <= $value;

        }

    }

    /**
     * Return true if this value superior of parameter value
     * @param mixed $value
     * @return bool
     */
    public function superior(mixed $value): bool
    {

        if (
            (is_array($value) && !is_array($this->value)) ||
            (!is_array($value) && is_array($this->value))
        ) {

            throw new \LogicException('Can\t compare array with scalar');

        }

        if (is_array($value)) {

            foreach ($value as $key => $item) {

                if (!is_array($this->value)) {
                    throw new \LogicException('Index value is not array');
                }

                if ($this->value[$key] < $item) {
                    return false;
                } else if ($this->value[$key] > $item){
                    return true;
                }

            }

            return false;

        } else {

            return $this->value > $value;

        }

    }

    /**
     * Return true if this value superior or equal of parameter value
     * @param mixed $value
     * @return bool
     */
    public function superiorOrEqual(mixed $value): bool
    {

        if (
            (is_array($value) && !is_array($this->value)) ||
            (!is_array($value) && is_array($this->value))
        ) {

            throw new \LogicException('Can\t compare array with scalar');

        }

        if (is_array($value)) {

            foreach ($value as $key => $item) {

                if (!is_array($this->value)) {
                    throw new \LogicException('Index value is not array');
                }

                if ($this->value[$key] < $item) {
                    return false;
                } else if ($this->value[$key] > $item) {
                    return true;
                }

            }

            return true;

        } else {

            return $this->value >= $value;

        }

    }

}