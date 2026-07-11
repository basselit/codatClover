<?php

namespace Codatsoft\CodatClover\Models;

class CLItem
{
    public string $id;
    public bool $hidden;
    public string $name;
    public int $price;
    public string $priceType;
    public string $code;

    public function loadFromJson(\stdClass $oneItem): void
    {
        $this->id = $oneItem->id;
        $this->name = $oneItem->name;
        $this->price = $oneItem->price;
        if (!property_exists($oneItem,'code'))
        {
            $this->code = '';
        } else
        {
            $this->code = $oneItem->code;
        }

        $this->hidden = $oneItem->hidden;
        $this->priceType = $oneItem->priceType;

    }

    public function createFare()
    {
        $this->name = 'Fare';
        $this->code = '1000';
        $this->price = 0;
        $this->hidden = true;
        $this->priceType = 'VARIABLE';
    }

    public function createTolls()
    {
        $this->name = 'Tolls';
        $this->code = '2000';
        $this->price = 0;
        $this->hidden = true;
        $this->priceType = 'VARIABLE';
    }

    public function createProcFee()
    {
        $this->name = 'Processing Fee';
        $this->code = '3000';
        $this->price = 0;
        $this->hidden = true;
        $this->priceType = 'VARIABLE';

    }


}
