<?php

namespace App\Clover\Business;

use App\Clover\Models\CLEmployees;
use App\Clover\Models\CLObject;
use App\TeckPay\Models\Filters\TEmployeesFilter;
use App\TeckPay\Models\TMerchant;
use Codatsoft\Codatbase\Network\TNetwork;

class CLBus
{
    public static function getEmployeesdelete(TMerchant $merch): CLEmployees
    {
        $cl = new CLObject();
        $cl->setMerchant($merch);
        $emps = $cl->getEmployees();

       // $filter = new TEmployeesFilter($merch);
       // $network = new TNetwork($filter);

        $merchEmployees = STJson::parseEmployees($emps,$merch);
        return $merchEmployees;
    }

}
