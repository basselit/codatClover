<?php


namespace Codatsoft\CodatClover\Models;


class CLPayment
{
    public string $id;
    public int $amount;
    public int $tipAmount;
    public int $createdTime;
    public int $clientCreatedTime;
    public int $modifiedTime;
    public bool $offline;
    public string $result;
    public CLCardTransaction $cardTransaction;
    public bool $parseSuccess;
    public int $parseErrorCode;

    public function loadFromJSON(\stdClass $onePay): void
    {
        $this->id = $onePay->id;
        $this->amount = $onePay->amount;
        if (!property_exists($onePay,'tipAmount'))
        {
            $this->tipAmount = 0;
        } else
        {
            $this->tipAmount = $onePay->tipAmount;
        }
        $this->createdTime = $onePay->createdTime;
        $this->clientCreatedTime = $onePay->clientCreatedTime;
        $this->modifiedTime = $onePay->modifiedTime;
        $this->offline = $onePay->offline;
        $this->result = $onePay->result;
        $card = $onePay->cardTransaction;
        $this->cardTransaction = new CLCardTransaction();
        $this->cardTransaction->loadFromJSON($card);
        $this->parseSuccess = $this->cardTransaction->parseSuccess;
        $this->parseErrorCode = $this->cardTransaction->parseErrorCode;

    }
}
