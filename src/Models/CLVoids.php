<?php


namespace Codatsoft\CodatClover\Models;

class CLVoids
{
    public array $elements;
    public CLVoid $firstVoid;

    public function loadFromJSON(\stdClass $jo): void
    {
        $this->elements = [];
        foreach ($jo->voids->elements as $oneVoid) {
            $newVoid = new CLVoid();
            $newVoid->loadFromJSON($oneVoid);
            array_push($this->elements,$newVoid);
        }

        $this->firstVoid = $this->elements[0];
    }

}
