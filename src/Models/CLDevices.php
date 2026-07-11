<?php


namespace Codatsoft\CodatClover\Models;


use Countable;
use Iterator;

class CLDevices implements Iterator, Countable
{
    public array $elements;
    public int $position = 0;

    public function __construct()
    {
        $this->elements = [];
        $this->position = 0;
    }

    public function add(ClDevice $clDevice): void
    {
        $this->elements[] = $clDevice;
    }

    public function get(int $index): CLDevice
    {
        return $this->elements[$index];

    }

    public function current(): CLDevice
    {
        return $this->elements[$this->position];
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function key(): int
    {
        return $this->position;
    }

    public function valid(): bool
    {
        return isset($this->elements[$this->position]);
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function count(): int
    {
        return count($this->elements);
    }
}
