<?php

namespace Codatsoft\CodatClover\Models;

use stdClass;

class CLItems
{
    public array $elements;

    public bool $empty = false;

    public function __construct(StdClass $jo)
    {

        if (count($jo->elements) == 0)
        {
            $this->empty = true;
            $this->elements = [];
            return;
        }

        $this->elements = [];

        array_walk($jo->elements, function(StdClass $oneItem) {
            $newItem = new CLItem();
            $newItem->loadFromJSON($oneItem);
            array_push($this->elements,$newItem);

        });
    }

    public function get(int $index): CLItem
    {
        return $this->elements[$index];
    }

    public function findByCodeName(string $code, string $name): ?CLItem
    {
        foreach ($this->elements as $key => $value)
        {
            $one = $this->get($key);
            if ($one->code == $code && $one->name == $name)
            {
                return $one;
            }
        }

        return null;

    }


}
