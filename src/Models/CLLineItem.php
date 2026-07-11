<?php


namespace Codatsoft\CodatClover\Models;


class CLLineItem
{
    public string $id;
    public string $name;
    public int $price;
    public string $itemCode;

    public function loadFromJSON(\stdClass $oneItem): void
    {
        $this->id = $oneItem->id;
        $this->name = $oneItem->name;
        $this->price = $oneItem->price;
        if (!property_exists($oneItem,'itemCode'))
        {
            $this->itemCode = '';
        } else
        {
           $this->itemCode = $oneItem->itemCode;
        }

    }


}
