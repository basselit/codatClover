<?php


namespace Codatsoft\CodatClover\Models;


class CLCustomers
{
    public array $elements;
    public bool $hasCustomers;
    public CLCustomer $firstCustomer;

    public function loadFromJSON(\stdClass $jo): void
    {
        if (property_exists($jo,'customers'))
        {
            $this->hasCustomers = true;
            $this->firstCustomer = new CLCustomer();
            $cust = $jo->customers->elements[0];
            $this->firstCustomer->loadFromJSON($cust);
            return;
        }

        $this->hasCustomers = false;

    }

}
