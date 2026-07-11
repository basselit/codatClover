<?php


namespace Codatsoft\CodatClover\Models;


class CLVoid
{
    public string $id;
    public int $amount;
    public int $tipAmount;
    public string $voidReason;
    public string $result;
    public bool $offline;

    public function loadFromJSON(\stdClass $void): void
    {
        $this->id = $void->id;
        $this->amount = $void->amount;
        //todo check this possiblity later
        if (property_exists($void,'tipAmount'))
        {
            $this->tipAmount = $void->tipAmount;
        } else
        {
            $this->tipAmount = 0;
        }

        $this->voidReason = $void->voidReason;
        $this->result = $void->result;
        $this->offline = $void->offline;
    }

}
