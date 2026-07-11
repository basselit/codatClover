<?php


namespace Codatsoft\CodatClover\Models;


use stdClass;

class CLPayments
{
    public array $elements;
    public bool $hasPayments;
    public bool $splitPayments;
    public CLPayment $firstPayment;

    public bool $parseSuccess = true;
    public int $parseErrorCode = 0;

    public function __construct()
    {
        $this->elements = [];
    }

    public function loadFromJSON(stdClass $jo): void
    {
        $this->splitPayments = false;
        if (!property_exists($jo,'payments'))
        {
            $this->hasPayments = false;
            return;
        }

        if (!property_exists($jo->payments,'elements'))
        {
            $this->hasPayments = false;
            return;
        }

        if (count($jo->payments->elements) == 0)
        {
            $this->hasPayments = false;
            return;
        }

        if (count($jo->payments->elements) == 1)
        {
            $pay = $jo->payments->elements[0];

            $this->hasPayments = $this->checkValidPayment($pay);

            if ($this->hasPayments)
            {
                $this->firstPayment = new CLPayment();
                $this->firstPayment->loadFromJSON($pay);
                $this->parseSuccess = $this->firstPayment->parseSuccess;
                $this->parseErrorCode = $this->firstPayment->parseErrorCode;
            }

            return;

        }

        foreach ($jo->payments->elements as $onePay)
        {
            $newPay = new CLPayment();
            $newPay->loadFromJSON($onePay);
            array_push($this->elements,$newPay);
            $check = $this->checkValidPayment($newPay);
            if ($check)
            {
                $this->hasPayments = $check;
                $this->firstPayment = $newPay;
            }
        }

        foreach ($this->elements as $key => $value)
        {
            $ind = (int) $key;

            if ($ind > 0)
            {
                $one = $this->get($key);
                $this->splitPayments = $this->checkValidPayment($one);
                if ($this->splitPayments)
                {
                    return;
                }

            } else {
                $one = $this->get($key);
                $this->hasPayments = $this->checkValidPayment($one);
            }
        }

    }

    public function get(int $index): CLPayment
    {
        return $this->elements[$index];
    }



    public function checkValidPayment($onePay) : bool
    {
        if (!property_exists($onePay,'cardTransaction'))
        {
            if (property_exists($onePay,'cashTendered'))
            {
                return false;
            }

            return false;
//            $senMess = json_encode($pay);
//            \Sentry\captureMessage($senMess);
        }
        //end of todo check

        $card = $onePay->cardTransaction;
        if (!property_exists($card,'authCode'))
        {
            return false;
        }

        if ($card->authCode == null)
        {
            return false;
        }

        return true;

    }

}
