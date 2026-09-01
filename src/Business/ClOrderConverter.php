<?php

namespace Codatsoft\CodatClover\Business;

use Codatsoft\CodatClover\Models\CLCustomer;
use Codatsoft\CodatClover\Models\CLCustomers;
use Codatsoft\CodatClover\Models\CLLineItems;
use Codatsoft\CodatClover\Models\CLNoteReference;
use Codatsoft\CodatClover\Models\CLOrder;
use Codatsoft\CodatClover\Models\CLPayments;
use Codatsoft\CodatClover\Models\CLVoids;
use stdClass;

class ClOrderConverter
{
    public static function convert(stdClass $jo): ?CLOrder
    {
        $cl = new CLOrder();
        $cl->id = $jo->id;
        //removed from here because there are events when total is null, in this case take it from payment or void
//        $this->total = $jo->total;
//        $this->total = 0;
        $cl->paymentState = $jo->paymentState;
        if (property_exists($jo,'state'))
        {
            $cl->state = $jo->state;
        } else
        {
            $cl->state = 'Not Set';
        }

        $cl->createdTime = $jo->createdTime;
        $cl->clientCreatedTime = $jo->clientCreatedTime;
        $cl->modifiedTime = $jo->modifiedTime;

        if (property_exists($jo,'note'))
        {
            $refId = $jo->note;
            if ($refId)
            {
                if (str_contains($refId,'cab'))
                {
                    $noteRef = self::getNoteRef($refId);
                    $cl->cabNo = $noteRef->cabNo;
                    $cl->airportTerminal = $noteRef->airportTerminal;
                    $cl->appVersion = $noteRef->appVersion;
                } else
                {
                    \Sentry\captureMessage("an order has a note saved but not by us:" . $cl->id);
                }

            } else
            {
                $cl->cabNo = null;
                $cl->airportTerminal = null;
                $cl->appVersion = '1.1';
            }
        } else
        {
            $cl->cabNo = null;
            $cl->airportTerminal = null;
        }

        $cl->payments = new CLPayments();
        $cl->payments->loadFromJSON($jo);
        $cl->hasPayments = $cl->payments->hasPayments;
        $cl->parseSuccess = $cl->payments->parseSuccess;
        $cl->parseErrorCode = $cl->payments->parseErrorCode;
        if (!$cl->parseSuccess)
        {
            return null;
        }

        $cl->noEmployeeOrDevice = false;
        //this and device id must be called after payments are parsed
        $cl->employeeId = self::getEmployeeId($jo, $cl->hasPayments);

        if ($cl->employeeId == null)
        {
            //todo mo employees attached to we set values for no payments for now
            $cl->noEmployeeOrDevice = true;
            //$this->parseSuccess = false;
            //$this->parseErrorCode = CLParseStatus::NO_EMPLOYEE;
            // return;
        }

        $cl->employeeName = self::getEmployeeName($jo, $cl->hasPayments);
        $cl->employeeNickname = self::getEmployeeNickName($jo, $cl->hasPayments);

        $cl->deviceId = self::getDeviceId($jo, $cl->hasPayments);
        if ($cl->deviceId == null)
        {
            $cl->noEmployeeOrDevice = true;
            //$this->parseSuccess = false;
            //$this->parseErrorCode = CLParseStatus::NO_DEVICE;
            //return;
        }

        $cl->lineItems = new CLLineItems();
        $cl->lineItems->loadFromJSON($jo);
        $cl->hasLineItems = $cl->lineItems->hasLineItems;


        $cl->customers = new CLCustomers();
        $cl->customers->loadFromJSON($jo);
        $cl->hasCustomers = $cl->customers->hasCustomers;


        if ($cl->hasCustomers)
        {
            $isFirstName = false;
            if (property_exists($cl->customers->firstCustomer,'firstName'))
            {
                if ($cl->customers->firstCustomer->firstName != null && $cl->customers->firstCustomer->firstName != '')
                {
                    $isFirstName = true;
                }
            }

            if (!$isFirstName)
            {
                if ($cl->hasPayments)
                {
                    if (property_exists($cl->payments->firstPayment->cardTransaction,'cardholderName'))
                    {
                        $cl->customers->firstCustomer->fullName = $cl->payments->firstPayment->cardTransaction->cardholderName;
                    }
                }
            }

        } else
        {
            if ($cl->hasPayments)
            {
                if (property_exists($cl->payments->firstPayment->cardTransaction,'cardholderName'))
                {
                    if ($cl->payments->firstPayment->cardTransaction->cardholderName != null)
                    {
                        $cl->customers->firstCustomer = new CLCustomer();
                        $cl->hasCustomers = true;
                        $cl->customers->firstCustomer->fullName = $cl->payments->firstPayment->cardTransaction->cardholderName;

                    }
                }
            }
        }

        if (!property_exists($jo,'voids'))
        {
            $cl->hasVoids = false;
        } else {
            $cl->hasVoids = true;
            $cl->voids = new CLVoids();
            $cl->voids->loadFromJSON($jo);
        }

        if (!$cl->hasPayments && !$cl->hasVoids)
        {
            $cl->noPaymentOrVoids = true;
        } else {
            $cl->noPaymentOrVoids = false;
        }


        if (property_exists($jo,'total'))
        {
            $cl->total = $jo->total;
            return $cl;

        }

        if ($cl->hasPayments)
        {
            $cl->total = $cl->payments->firstPayment->amount;
        } else
        {
            if ($cl->hasVoids)
            {
                $cl->total = $cl->voids->firstVoid->amount;
            } else
            {
                $cl->total = 0;
            }
        }

        return $cl;

    }

    private static function getNoteRef($value): ?CLNoteReference
    {
        //was setReferenceId
        $note = new CLNoteReference();
        try {
            $check = explode('-',$value);
            $note->cabNo = $check[1];

            if (strlen($note->cabNo) > 5)
            {
                $note->cabNo = substr($note->cabNo,0,5);
            }

            $note->airportTerminal = $check[3];
            if (count($check) > 4)
            {
                $note->appVersion = self::getAppVerFromReference($check[5]);
            } else {
                $note->appVersion = '1.1';
            }

        } catch (\Throwable $exception) {
            \Sentry\captureException($exception);
            return null;
        }

        return $note;
    }

    private static function getAppVerFromReference(string $ref): string
    {
        $fix1 = substr($ref,0,2);
        $fix2 = (int) $fix1;
        if ($fix2 == 1)
        {
            return '5.9';
        } else if ($fix2 == 2)
        {
            return '6.0';
        } else if ($fix2 == 4)
        {
            return '19.4';

        } else if ($fix2 == 5)
        {
            return '19.5';
        } else if ($fix2 == 6)
        {
            return '19.6';
        }
        else if ($fix2 == 7)
        {
            return '6.1';
        }
        else if ($fix2 == 8)
        {
            return '19.7';
        }
        else if ($fix2 == 9)
        {
            return '6.2';
        }
        else if ($fix2 == 10)
        {
            return '19.8';
        }
        else if ($fix2 == 11)
        {
            return '6.3';
        }
        else if ($fix2 == 12)
        {
            return '19.9';
        }
        else if ($fix2 == 13)
        {
            return '6.4';
        }
        else if ($fix2 == 14)
        {
            return '20.0';
        }
        else if ($fix2 == 15)
        {
            return '6.5';
        }
        else if ($fix2 == 16)
        {
            return '20.1';
        }
        else if ($fix2 == 17)
        {
            return '6.6';
        }
        else if ($fix2 == 18)
        {
            return '20.2';
        }
        else if ($fix2 == 19)
        {
            return '6.7';
        }
        else if ($fix2 == 20)
        {
            return '20.3';
        }
        else if ($fix2 == 21)
        {
            return '6.8';
        }
        else if ($fix2 == 22)
        {
            return '20.4';
        }


        else
        {
            return '9.9';
        }
    }

    private static function getEmployeeName($one, bool $hasPayments)
    {
        if (property_exists($one,'employee'))
        {
            return $one->employee->name;
        }

        if (!$hasPayments)
        {
            return null;
        }

        $onePay = $one->payments->elements[0];
        if (!property_exists($onePay,'employee'))
        {
            return null;
        }

        return $onePay->employee->name;

    }

    private static function getEmployeeNickName($one, bool $hasPayments)
    {
        if (property_exists($one,'employee'))
        {
            if (property_exists($one->employee,'nickname'))
            {
                return $one->employee->nickname;
            }
        }

        if (!$hasPayments)
        {
            return null;
        }

        $onePay = $one->payments->elements[0];
        if (!property_exists($onePay,'employee'))
        {
            return null;
        }

        if (property_exists($onePay->employee,'nickname'))
        {
            return $onePay->employee->nickname;
        }

        return null;

    }

    private static function getDeviceId($one, bool $hasPayments)
    {
        if (property_exists($one,'device'))
        {
            return $one->device->id;
        }

        if (!$hasPayments)
        {
            return null;
        }

        $onePay = $one->payments->elements[0];
        if (!property_exists($onePay,'device'))
        {
            return null;
        }

        return $onePay->device->id;
    }

    private static function getEmployeeId($one, bool $hasPayments)
    {
        if (property_exists($one,'employee'))
        {
            return $one->employee->id;
        }

        if (!$hasPayments)
        {
            return null;
        }

        $onePay = $one->payments->elements[0];
        if (!property_exists($onePay,'employee'))
        {
            return null;
        }

        return $onePay->employee->id;

    }



}
