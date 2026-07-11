<?php


namespace Codatsoft\CodatClover\Models;

use Codatsoft\Codatbase\Base\TModelNetwork;
use Codatsoft\Codatbase\Network\TNetwork;
use Codatsoft\Codatbase\STDates;
use Codatsoft\CodatClover\Types\CLEndpoints;
use Codatsoft\CodatClover\Types\CLParameters;
use Codatsoft\CodatClover\Types\CLParseStatus;
use stdClass;

class CLOrder
{

    public string $id;
    public int $total;
    public string $paymentState;
    public string $state;
    public int $createdTime;
    public int $clientCreatedTime;
    public int $modifiedTime;
    public ?string $deviceId;
    public ?string $employeeId;
    public ?string $employeeName;
    public ?string $employeeNickname;

    public ?string $cabNo;
    public ?string $airportTerminal;
    public string $appVersion;

    public CLPayments $payments;
    public CLLineItems  $lineItems;
    public CLCustomers $customers;
    public CLVoids $voids;

    public bool $hasPayments;
    public bool $hasLineItems;
    public bool $hasCustomers;
    public bool $hasVoids;

    public bool $noEmployeeOrDevice;
    public bool $noPaymentOrVoids;

    public bool $parseSuccess;
    public int $parseErrorCode;

    //teckpay data
    public int $accountId;
    public int $merchantId;
    public string $merchantCode;

    //processing

    public bool $isDateMismatch;

    public static function loadNetwork(CLMerchant $merch, string $orderId): TNetwork
    {

        $cl = new TModelNetwork();

        $cl->create($merch->gatewayUrl, $merch->gatewayPasswordToken);
        $cl->setEndPoint(CLEndpoints::ORDER_BY_ID);
        $cl->addParameter(CLParameters::MERCHANT_ID, $merch->gatewayMerchantCode);
        $cl->addParameter(CLParameters::ORDER_ID, $orderId);
        $net = $cl->runFilter();

        return $net;
    }

    public function __constructold(stdClass $jo)
    {
        //retire this one
        $this->id = $jo->id;
        //removed from here because there are events when total is null, in this case take it from payment or void
//        $this->total = $jo->total;
//        $this->total = 0;
        $this->paymentState = $jo->paymentState;
        if (property_exists($jo,'state'))
        {
            $this->state = $jo->state;
        } else
        {
            $this->state = 'Not Set';
        }

        $this->createdTime = $jo->createdTime;
        $this->clientCreatedTime = $jo->clientCreatedTime;
        $this->modifiedTime = $jo->modifiedTime;

        if (property_exists($jo,'note'))
        {
            $refId = $jo->note;
            if ($refId)
            {
                if (str_contains($refId,'cab'))
                {
                    $this->setReferenceId($refId);
                } else
                {
                    \Sentry\captureMessage("an order has a note saved but not by us:" . $this->id);
                }

            } else
            {
                $this->cabNo = null;
                $this->airportTerminal = null;
                $this->appVersion = '1.1';
            }
        } else
        {
            $this->cabNo = null;
            $this->airportTerminal = null;
        }

        $this->payments = new CLPayments();
        $this->payments->loadFromJSON($jo);
        $this->hasPayments = $this->payments->hasPayments;
        $this->parseSuccess = $this->payments->parseSuccess;
        $this->parseErrorCode = $this->payments->parseErrorCode;
        if (!$this->parseSuccess)
        {
            return;
        }

        $this->noEmployeeOrDevice = false;
        //this and device id must be called after payments are parsed
        $this->employeeId = $this->getEmployeeId($jo);

        if ($this->employeeId == null)
        {
            //todo mo employees attached to we set values for no payments for now
            $this->noEmployeeOrDevice = true;
            //$this->parseSuccess = false;
            //$this->parseErrorCode = CLParseStatus::NO_EMPLOYEE;
           // return;
        }

        $this->employeeName = $this->getEmployeeName($jo);
        $this->employeeNickname = $this->getEmployeeNickName($jo);

        $this->deviceId = $this->getDeviceId($jo);
        if ($this->deviceId == null)
        {
            $this->noEmployeeOrDevice = true;
            //$this->parseSuccess = false;
            //$this->parseErrorCode = CLParseStatus::NO_DEVICE;
            //return;
        }

        $this->lineItems = new CLLineItems();
        $this->lineItems->loadFromJSON($jo);
        $this->hasLineItems = $this->lineItems->hasLineItems;


        $this->customers = new CLCustomers();
        $this->customers->loadFromJSON($jo);
        $this->hasCustomers = $this->customers->hasCustomers;


        if ($this->hasCustomers)
        {
            $isFirstName = false;
            if (property_exists($this->customers->firstCustomer,'firstName'))
            {
                if ($this->customers->firstCustomer->firstName != null && $this->customers->firstCustomer->firstName != '')
                {
                    $isFirstName = true;
                }
            }

            if (!$isFirstName)
            {
                if ($this->hasPayments)
                {
                    if (property_exists($this->payments->firstPayment->cardTransaction,'cardholderName'))
                    {
                        $this->customers->firstCustomer->fullName = $this->payments->firstPayment->cardTransaction->cardholderName;
                    }
                }
            }

        } else
        {
            if ($this->hasPayments)
            {
                if (property_exists($this->payments->firstPayment->cardTransaction,'cardholderName'))
                {
                    if ($this->payments->firstPayment->cardTransaction->cardholderName != null)
                    {
                        $this->customers->firstCustomer = new CLCustomer();
                        $this->hasCustomers = true;
                        $this->customers->firstCustomer->fullName = $this->payments->firstPayment->cardTransaction->cardholderName;

                    }
                }
            }
        }

        if (!property_exists($jo,'voids'))
        {
            $this->hasVoids = false;
        } else {
            $this->hasVoids = true;
            $this->voids = new CLVoids();
            $this->voids->loadFromJSON($jo);
        }

        if (!$this->hasPayments && !$this->hasVoids)
        {
            $this->noPaymentOrVoids = true;
        } else {
            $this->noPaymentOrVoids = false;
        }


        if (property_exists($jo,'total'))
        {
            $this->total = $jo->total;
            return;
        }

        if ($this->hasPayments)
        {
            $this->total = $this->payments->firstPayment->amount;
        } else
        {
            if ($this->hasVoids)
            {
                $this->total = $this->voids->firstVoid->amount;
            } else
            {
                $this->total = 0;
            }
        }

    }






    public function setStatus()
    {
        if ($this->hasPayments && !$this->hasVoids)
        {

        }
        if (!$this->hasPayments && !$this->hasVoids)
        {

        }
    }

    public function checkValidStatus()
    {
        if ($this->hasPayments && !$this->$this->hasVoids)
        {

        }
    }

    public function checkDateMismatch(): void
    {
        $modTime = 0;
        if (property_exists($this,'modifiedTime'))
        {
            if ($this->modifiedTime != null)
            {
                if ($this->modifiedTime != 0)
                {
                    $modTime = $this->modifiedTime;
                }
            }
        }

        if ($modTime == 0)
        {
            $modTime = $this->createdTime;
        }

        $createdTime = $this->createdTime;
        $clientTime = $this->clientCreatedTime;

        $time1 = STDates::getSqlDT($createdTime);
        $time2 = STDates::getSqlDT($clientTime);
        $time3 = STDates::getSqlDT($modTime);

        $t1 = $time1->format('j');
        $t2 = $time2->format('j');
        $t3 = $time3->format('j');

        if ($t1 == $t2 && $t1 == $t3 && $t2 == $t3)
        {
            $this->isDateMismatch = false;
        } else
        {
            $this->isDateMismatch = true;
        }

    }

    public function getErrorFromCode(): string
    {
        return CLParseStatus::getParseDesc($this->parseErrorCode);

    }


}
