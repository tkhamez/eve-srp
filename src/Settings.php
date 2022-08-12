<?php

declare(strict_types=1);

namespace EveSrp;

final class Settings implements \ArrayAccess
{
    public function __construct(private array $settings)
    {
    }

    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->settings);
    }

    public function offsetGet($offset)
    {
        return $this->offsetExists($offset) ? $this->settings[$offset] : null;
    }

    /**
     * @throws \BadMethodCallException
     */
    public function offsetSet($offset, $value)
    {
        throw new \BadMethodCallException('Read only.');
    }

    /**
     * @throws \BadMethodCallException
     */
    public function offsetUnset($offset)
    {
        throw new \BadMethodCallException('Read only.');
    }
}
