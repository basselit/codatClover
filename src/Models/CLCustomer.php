<?php


namespace Codatsoft\CodatClover\Models;


class CLCustomer
{
    public ?string $id = null;
    public ?string $firstName = null;
    public ?string $lastName = null;
    public ?string $fullName = null;

    public function loadFromJSON(\stdClass $cust): void
    {
        $this->id = $cust->id;
        if (property_exists($cust,'firstName'))
        {
            $this->firstName = $cust->firstName;
            $this->lastName = $cust->lastName;
            $this->fullName = $cust->firstName . ' ' . $cust->lastName;
        }

    }
}
