<?php

declare(strict_types=1);

namespace Gubee\Integration\Library;

use ArrayAccess;

class ItemList implements ArrayAccess
{
    protected array $itens;

    public function __construct(array $items)
    {
        $this->itens = $items;
    }

    /**
     * @return array
     */
    public function getItens(): array
    {
        return $this->itens;
    }

    /**
     * @param array $itens
     */
    public function setItens(array $itens): self
    {
        $this->itens = $itens;
        return $this;
    }

    /**
     * Whether an offset exists
     * Whether or not an offset exists.
     *
     * @param mixed $offset An offset to check for.
     * @return bool Returns `true` on success or `false` on failure.
     */
    public function offsetExists($offset): bool
    {
        return isset($this->itens[$offset]);
    }

    /**
     * Offset to retrieve
     * Returns the value at specified offset.
     *
     * @param mixed $offset The offset to retrieve.
     * @return TValue Can return all value types.
     */
    public function offsetGet($offset): TValue
    {
        return $this->itens[$offset];
    }

    /**
     * Assigns a value to the specified offset.
     *
     * @param TKey $offset The offset to assign the value to.
     * @param TValue $value The value to set.
     */
    public function offsetSet($offset, $value): void
    {
        $this->itens[$offset] = $value;
    }

    /**
     * Unsets an offset.
     *
     * @param TKey $offset The offset to unset.
     */
    public function offsetUnset($offset): void
    {
        unset($this->itens[$offset]);
    }
}
