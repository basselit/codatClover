<?php

namespace Codatsoft\CodatClover\Business;

use Codatsoft\CodatClover\Models\CLDevice;
use Codatsoft\CodatClover\Models\CLDevices;
use Codatsoft\CodatClover\Models\CLEmployee;
use Codatsoft\CodatClover\Models\CLEmployees;
use Codatsoft\CodatClover\Models\CLMerchant;
use Codatsoft\CodatClover\Models\CLOrder;
use Codatsoft\CodatClover\Models\CLOrders;
use Codatsoft\CodatClover\Types\CLMerchantFilterType;
use stdClass;

class STJson
{

    public static function parseOrder(stdClass $order, CLMerchant $curMerch): CLOrder
    {
        $newOrder = ClOrderConverter::convert($order);
        $newOrder->accountId = $curMerch->accountId;
        $newOrder->merchantId = $curMerch->id;
        $newOrder->merchantCode = $curMerch->gatewayMerchantCode;

        return $newOrder;

    }

    public static function parseOrders(stdClass $jo, CLMerchant $curMerch, array $filterForEmployees = []): CLOrders
    {

        $orders = new CLOrders();
        if (count($filterForEmployees) > 0)
        {
            $filter = true;
        } else
        {
            $filter = false;
        }

        try {
            foreach ($jo->elements as $one) {
                if ($filter)
                {
                    $empId = $one->employee->id;
                    if (in_array($empId, $filterForEmployees))
                    {
                        $newOrder = self::parseOrder($one,$curMerch);
                        $orders->add($newOrder);
                    }
                } else
                {
                    $newOrder = self::parseOrder($one,$curMerch);
                    $orders->add($newOrder);
                }
            }
            $orders->parseSuccess = true;
            return $orders;


        } catch (\Exception $exception)
        {
            $orders->errorMessage = $exception->getMessage();
            $orders->parseSuccess = false;
        }

        return $orders;

    }

    public static function findEmployee(stdClass $jo): ?stdClass
    {
        if (property_exists($jo,'employee'))
        {
            return $jo->employee;
        }

        if (property_exists($jo, 'payments') && property_exists($jo->payments,'elements'))
        {
            if (count($jo->payments->elements) != 0)
            {
                $pay = $jo->payments->elements[0];
                if (property_exists($pay, 'employee'))
                {
                    return $pay->employee;
                }
            }

        }

        return null;

    }

    public static function parseDevices(stdClass $jo, int $merchId): CLDevices
    {
        $devices = new CLDevices();
        foreach ($jo->elements as $one) {
            $new = self::parseDevice($one, $merchId);
            $devices->add($new);
        }

        return $devices;

    }

    public static function parseDevice(stdClass $jo, int $merchId): CLDevice
    {
        $device = new CLDevice();
        $device->id = $jo->id;
        $device->merchantId = $merchId;
        $device->model = $jo->model;
        $device->serial = $jo->serial;

        return $device;

    }

    public static function parseEmployees(stdClass $jo, CLMerchant $curMerch): CLEmployees
    {
        $emps = new CLEmployees();
        if (!property_exists($jo,"elements"))
        {
            return $emps;
        }

        foreach ($jo->elements as $one)
        {
            if (self::filterPassed($curMerch,$one))
            {
                $parOne = self::parseEmployee($one);
                $emps->addEmployee($parOne);
            }
        }

        return $emps;

    }

    private static function filterPassed(CLMerchant $curMerch, $emp): bool
    {
        if ($curMerch->filterField == null)
        {
            return true;
        }

        if (!property_exists($emp,$curMerch->filterField))
        {
            if ($curMerch->filterType == CLMerchantFilterType::EXCLUDE)
            {
                return true;
            } else
            {
                return false;
            }
        }

        $value1 = strtolower($emp->{$curMerch->filterField});
        $value = str_replace(' ', '', $value1);

        $contains = str_contains($value,$curMerch->filterValue);

        if ($curMerch->filterType == CLMerchantFilterType::INCLUDE)
        {
            return $contains;
        } else
        {
            return !$contains;
        }

    }

    public static function parseEmployee($one): CLEmployee
    {
        //whats the deal with pen station
        $newEmp = new CLEmployee();
        $newEmp->id = $one->id;
        $newEmp->name = $one->name;
        if (property_exists($one,'customId'))
        {
            if ($one->customId != '')
            {
                $newEmp->customId = $one->customId;
            }
        }
        if (property_exists($one,'nickname'))
        {
            $newEmp->nickname = $one->nickname;
        }

        if (property_exists($one,'phoneNumber'))
        {
            $newEmp->phoneNumber = $one->phoneNumber;
        }


        $newEmp->role = $one->role;
        if ($one->id == 'GAEJ6P67SQTY4')
        {
            $newEmp->pin = '000000';

        } else
        {
            $newEmp->pin = $one->pin;

        }


        return $newEmp;


    }

}
