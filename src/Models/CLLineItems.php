<?php


namespace Codatsoft\CodatClover\Models;


use stdClass;

class CLLineItems
{
    public array $elements;
    public bool $hasLineItems;

    public function loadFromJSON(\stdClass $jo): void
    {
        if (!property_exists($jo,'lineItems'))
        {
            $this->hasLineItems = false;
            return;
        }

        if (count($jo->lineItems->elements) == 0)
        {
            $this->hasLineItems = false;
            return;
        }

        $this->hasLineItems = true;
        $this->elements = [];

        array_walk($jo->lineItems->elements, function(StdClass $oneItem) {
            $newItem = new CLLineItem();
            $newItem->loadFromJSON($oneItem);
            array_push($this->elements,$newItem);


        });
    }

    public function get(int $index): CLLineItem
    {
        return $this->elements[$index];
    }

}
