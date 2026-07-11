<?php


namespace Codatsoft\CodatClover\Models;

use Countable;
use Iterator;

class CLOrders implements Iterator, Countable
{
    public array $elements;
    public bool $isEmpty;
    public bool $parseSuccess = true;
    public string $errorMessage;
    public int $parseErrorCode;
    private int $position;

    public function __construct()
    {
        $this->elements = [];
        $this->position = 0;
    }

    public function get(int $index): CLOrder
    {
        return $this->elements[$index];

    }

    public function add(CLOrder $order): void
    {
        $this->elements[] = $order;
    }

    public function findByOrderId(string $orderId): ?CLOrder
    {
        foreach ($this->elements as $key => $value)
        {
            $act = $this->get($key);
            if ($act->id === $orderId)
            {
                return $act;
            }
        }

        return null;
    }

    public function getFirstEmployee($empId): ?CLOrder
    {
        foreach ($this->elements as $key => $value)
        {
            $act = $this->get($key);
            if ($act->employeeId === $empId)
            {
                return $act;
            }
        }

        return null;
    }

    public function getEmployeeIds(): array
    {
        $oneCol = array_column($this->elements,"employeeId");
        return array_unique($oneCol);
    }

    public function current(): CLOrder
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
