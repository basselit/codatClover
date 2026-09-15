<?php

namespace Codatsoft\CodatClover\Business;

use Codatsoft\Codatbase\Base\TModelNetwork;
use Codatsoft\Codatbase\Network\TFilterBase;
use Codatsoft\Codatbase\Network\TNetwork;
use Codatsoft\Codatbase\STDates;
use Codatsoft\CodatClover\Models\CLMerchant;
use Codatsoft\CodatClover\Models\CLOrder;
use Codatsoft\CodatClover\Models\CLOrders;
use Codatsoft\CodatClover\Types\CLEndpoints;
use Codatsoft\CodatClover\Types\CLParameters;
use Codatsoft\CodatClover\Types\CLParseStatus;
use stdClass;

class STOrders
{

    public bool $parseSuccess;
    public string $parseMessage;
    public string $parseOverload;


    public CLMerchant $curMerch;
    public TModelNetwork $model;
    public array $filterForEmployeeIds;

    public function __construct(CLMerchant $theMerch)
    {
        $this->parseSuccess = true;
        if ($theMerch->filterField != null)
        {
            $tmpClover = new STClover($theMerch);
            $emps = $tmpClover->loadEmployees();
            if (!$tmpClover->success)
            {
                $this->parseSuccess = false;
                $this->parseMessage = $tmpClover->message;
                return;
            }
            $this->filterForEmployeeIds = array_column($emps->elements, 'id');
        } else
        {
            $this->filterForEmployeeIds = [];
        }

        $this->curMerch = $theMerch;
        $this->common();
    }

    private function common(): void
    {
        $this->model = new TModelNetwork();
        $this->model->create($this->curMerch->gatewayUrl, $this->curMerch->gatewayPasswordToken);
        $this->model->addParameter(CLParameters::MERCHANT_ID,$this->curMerch->gatewayMerchantCode);
    }
    
    public function loadCustomers(int $offset)
    {
        $this->model->setEndPoint(CLEndpoints::CUSTOMERS);
        $this->model->addParameter(CLParameters::OFFSET,$offset);
        $network = $this->model->runFilter();

        if ($network->success)
        {
            return $network->content;

        }

        $this->parseSuccess = false;
        $this->parseMessage = $network->message;
        return null;

    }
    
    public function loadCustomerById(string $customerId)
    {
        $this->model->setEndPoint(CLEndpoints::CUSTOMER_BY_ID);
        $this->model->addParameter(CLParameters::CUSTOMER_BY_ID,$customerId);
        $network = $this->model->runFilter();

        if ($network->success)
        {
            return $network->content;

        }

        $this->parseSuccess = false;
        $this->parseMessage = $network->message;
        return null;


    }

    public function loadOrder(string $orderId): ?CLOrder
    {
        $this->model->setEndPoint(CLEndpoints::ORDER_BY_ID);
        $this->model->addParameter(CLParameters::ORDER_ID, $orderId);
        $network = $this->model->runFilter();

        if ($network->success)
        {
            $parOrder = ClOrderConverter::convert($network->content);
            if (!$parOrder->parseSuccess)
            {
                $this->parseSuccess = false;
                $this->parseMessage = $parOrder->getErrorFromCode();
                return null;
            }

            return $parOrder;
        }

        $this->parseSuccess = false;
        $this->parseMessage = $network->message;
        return null;

    }

    public static function findEmployeeFromOrder(stdClass $order): ?stdClass
    {
        if (property_exists($order,'employee'))
        {
            return $order->employee;
        }

        if (property_exists($order,'payments'))
        {
            if (count($order->payments->elements) > 0)
            {
                $pay = $order->payments->elements[0];
                if (property_exists($pay,'employee'))
                {
                    return $pay->employee;
                }
            }
        }

        return null;

    }

    public function loadPayments(int $totalParts = 1, int $curPart = 1): ?CLOrders
    {
        $month = 11;
        $year = 2025;
        $day = 21;

        $teckDate = $month . '-' . $day . '-' . $year;
        $dtRange = STDates::getOneDayDateRange($teckDate,$totalParts ,$curPart);

        $this->model->setEndPoint(CLEndpoints::PAYMENTS_BY_DATE);
        $this->model->addParameter(CLParameters::START_TIMESTAMP, $dtRange->unixStartTime);
        $this->model->addParameter(CLParameters::END_TIMESTAMP, $dtRange->unixEndTime);
        $network = $this->model->runFilter();

        if ($network->success)
        {
            $parOrder = STJson::parseOrders($network->content,$this->curMerch);
            if (!$parOrder->parseSuccess)
            {
                $this->parseSuccess = false;
                $this->parseMessage = CLParseStatus::getParseDesc($parOrder->parseErrorCode);
                return null;
            }
            $this->parseSuccess = true;


            return $parOrder;
        }

        $this->parseSuccess = false;
        $this->parseMessage = $network->message;
        return null;
    }

    public function loadOrders(string $orderIds): ?CLOrders
    {
        $this->model->setEndPoint(CLEndpoints::ORDERS_BY_IDS);
        $this->model->addParameter(CLParameters::ORDERS_IDS, $orderIds);
        $network = $this->model->runFilter();

        if ($network->success)
        {
            $parOrder = STJson::parseOrders($network->content,$this->curMerch);
            if (!$parOrder->parseSuccess)
            {
                $this->parseSuccess = false;
                $this->parseMessage = CLParseStatus::getParseDesc($parOrder->parseErrorCode);
                return null;
            }

            return $parOrder;
        }

        $this->parseSuccess = false;
        $this->parseMessage = $network->message;
        return null;

    }

    public function loadOrdersLast(): ?CLOrders
    {
        $this->model->setEndPoint(CLEndpoints::ORDERS_BY_LAST);
        $network = $this->model->runFilter();

        if ($network->success)
        {
            $parOrder = STJson::parseOrders($network->content,$this->curMerch, $this->filterForEmployeeIds);
            if (!$parOrder->parseSuccess)
            {
                $this->parseSuccess = false;
                $this->parseMessage = CLParseStatus::getParseDesc($parOrder->parseErrorCode);
                return null;
            }
            
            $this->parseSuccess = true;
            return $parOrder;
        }

        $this->parseSuccess = false;
        $this->parseMessage = $network->message;
        return null;

    }

    public function loadOrdersForCards(array $orderIdsArray): ?stdClass
    {
        $orderIds = "id in ('" . implode("','", $orderIdsArray) . "')";

        $this->model->setEndPoint(CLEndpoints::ORDERS_BY_IDS_CARDS);
        $this->model->addParameter(CLParameters::ORDERS_IDS, $orderIds);
        $network = $this->model->runFilter();

        if ($network->success)
        {
            return $network->content;
        }

        $this->parseSuccess = false;
        $this->parseMessage = $network->message;
        return null;

    }


    public function loadOrdersOneDayPure(int $month, int $day, int $year, int $totalParts = 1, int $curPart = 1): ?stdClass
    {
        $teckDate = $month . '-' . $day . '-' . $year;

        $dtRange = STDates::getOneDayDateRange($teckDate,$totalParts ,$curPart);

        $this->model->setEndPoint(CLEndpoints::ORDERS_BY_DATE);
        $this->model->addParameter(CLParameters::START_TIMESTAMP, $dtRange->unixStartTime);
        $this->model->addParameter(CLParameters::END_TIMESTAMP, $dtRange->unixEndTime);
        $network = $this->model->runFilter();

        if ($network->success)
        {
            $this->parseSuccess = true;
            return $network->content;
        }

        $this->parseSuccess = false;
        $this->parseMessage = $network->message;
        return null;
    }

    public function loadOrdersOneDay(int $month, int $day, int $year, int $totalParts = 1, int $curPart = 1): ?CLOrders
    {
        $teckDate = $month . '-' . $day . '-' . $year;
        $dtRange = STDates::getOneDayDateRange($teckDate,$totalParts ,$curPart);

        $this->model->setEndPoint(CLEndpoints::ORDERS_BY_DATE);
        $this->model->addParameter(CLParameters::START_TIMESTAMP, $dtRange->unixStartTime);
        $this->model->addParameter(CLParameters::END_TIMESTAMP, $dtRange->unixEndTime);
        $network = $this->model->runFilter();

        if ($network->success)
        {
            $parOrder = STJson::parseOrders($network->content,$this->curMerch, $this->filterForEmployeeIds);
            if (!$parOrder->parseSuccess)
            {
                $this->parseSuccess = false;
                $this->parseMessage = CLParseStatus::getParseDesc($parOrder->parseErrorCode);
                return null;
            }
            $this->parseSuccess = true;


            return $parOrder;
        }

        $this->parseSuccess = false;
        $this->parseMessage = $network->message;
        return null;
    }

    public function loadOrdersByEmployee(string $employeeId, int $ridesCount): ?CLOrders
    {
        $this->model->setEndPoint(CLEndpoints::ORDERS_BY_EMPLOYEE);
        $this->model->addParameter(CLParameters::EMPLOYEE_ID,$employeeId);
        $this->model->addParameter(CLParameters::ROWS_LIMIT,$ridesCount);
        $network = $this->model->runFilter();

        if ($network->success)
        {
            $parOrder = STJson::parseOrders($network->content,$this->curMerch);
            if (!$parOrder->parseSuccess)
            {
                $this->parseSuccess = false;
                $this->parseMessage = CLParseStatus::getParseDesc($parOrder->parseErrorCode);
                return null;
            }

            return $parOrder;
        }

        $this->parseSuccess = false;
        $this->parseMessage = $network->message;
        return null;

    }

    public function loadOrdersByDBRides($rides): ?CLOrders
    {
        $idFilter = $this->getOrderIdsFromDbOrders($rides);
        $filter = new TOrderByIds($this->curMerch,$idFilter);
        $network = new TNetwork($filter);

        return STJson::parseOrders($network->content,$this->curMerch);

    }

    public function loadOrdersByWebHooks($webHooks): ?CLOrders
    {
        $idFilter = $this->getOrderIdsFromWebHooks($webHooks);
        $filter = new TOrdersByIds($this->curMerch,$idFilter);
        return $this->runFilter($filter);
    }

    private function runFilter(TFilterBase $filter): ?CLOrders
    {
        $network = new TNetwork();
        $network->executeFilter($filter);

        if (!$network->success)
        {
            $this->parseSuccess = false;
            $this->parseMessage = CLParseStatus::getParseDesc($network->message);
            return null;
        }

        $clOrders = STJson::parseOrders($network->content, $this->curMerch);

        if (!$clOrders->parseSuccess)
        {
            $this->parseSuccess = false;
            $this->parseMessage = CLParseStatus::getParseDesc($clOrders->parseErrorCode);
            return null;
        }

        return $clOrders;

    }

    private function getOrderIdsFromDbOrders($rides): string
    {
        //id in ('id1','id2')
        $cloverFilter = "id in (";
        foreach ($rides as $oneRide)
        {
            $cloverFilter .= "'" . $oneRide->order_id . "',";
        }
        $cloverFilter = substr($cloverFilter, 0, -1);
        $cloverFilter .= ")";
        return $cloverFilter;

    }

    private function getOrderIdsFromWebHooks($hooks): string
    {
        //id in ('id1','id2')
        $cloverFilter = "id in (";
        foreach ($hooks as $hook)
        {
            if ($hook->merchant_id == $this->curMerch->gatewayMerchantCode)
            {
                $cloverFilter .= "'" . $hook->object_id . "',";
            }

        }
        $cloverFilter = substr($cloverFilter, 0, -1);
        $cloverFilter .= ")";
        return $cloverFilter;

    }



    public function loadOrdersClover(TOrdersDateRange $fil): ?CLOrders
    {
        //salamon

        $network = new TNetwork($fil);

        if (!$network->success)
        {
            return null;
        }

        $orders = new CLOrders();

        $contents = array();
        $contents[] = $network->content;

        foreach ($contents as $content)
        {
            $chucnkOrders = STJson::parseOrders($content, $this->curMerch);

            if (!$chucnkOrders->parseSuccess)
            {
                $this->parseSuccess = false;
                $this->parseMessage = CLParseStatus::getParseDesc($chucnkOrders->parseErrorCode);
                return null;
            }

            array_push($orders->elements, ...$chucnkOrders->elements);

        }

        $this->parseSuccess = true;
        return $orders;

    }


    public function loadOrdersByEmployeeold(string $empId, int $ordersCount): ?CLOrders
    {
        $filter = new TOdersByEmployee($this->curMerch,$empId,$ordersCount);
        $network = new TNetwork();
        $content = $network->executeFilter($filter);

        if (!$network->success)
        {
            $this->parseSuccess = false;
            $this->parseMessage = CLParseStatus::getParseDesc($network->message);
            return null;
        }

        $clOrders = STJson::parseOrders($content, $this->curMerch);

        if (!$clOrders->parseSuccess)
        {
            $this->parseSuccess = false;
            $this->parseMessage = CLParseStatus::getParseDesc($clOrders->parseErrorCode);
            return null;
        }

        return $clOrders;

    }

    public function loadOrdersByFilter(TFilterBase $filter): ?CLOrders
    {
        $network = new TNetwork();
        $orders = new CLOrders();

        $content = $network->executeFilter($filter);
        if (!$network->success)
        {
            $this->parseSuccess = false;
            $this->parseMessage = $network->message;
            return null;
        }

        $orders = STJson::parseOrders($content, $this->curMerch);
        $this->parseSuccess = true;
        return $orders;

    }


    public static function isDateMismatchClover($dates): bool
    {
        $modTime = 0;
        if (property_exists($dates,'modifiedTime'))
        {
            if ($dates->modifiedTime != null)
            {
                if ($dates->modifiedTime != 0)
                {
                    $modTime = $dates->modifiedTime;
                }
            }
        }

        if ($modTime == 0)
        {
            $modTime = $dates->createdTime;
        }

        $createdTime = $dates->createdTime;
        $clientTime = $dates->clientCreatedTime;

        $time1 = STDates::getSqlDT($createdTime);
        $time2 = STDates::getSqlDT($clientTime);
        $time3 = STDates::getSqlDT($modTime);

        $t1 = $time1->format('j');
        $t2 = $time2->format('j');
        $t3 = $time3->format('j');

        if ($t1 == $t2 && $t1 == $t3 && $t2 == $t3)
        {
            return false;
        }

        return true;

    }




}
