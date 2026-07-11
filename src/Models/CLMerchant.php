<?php

namespace Codatsoft\CodatClover\Models;

use Codatsoft\CodatClover\Types\CLMerchantFilterType;
use Codatsoft\CodatClover\Types\CLMerchantType;

class CLMerchant
{
    public int $accountId;
    public int $id;
    public string $gatewayUrl;
    public string $gatewayPasswordToken;
    public string $gatewayMerchantCode;
    public ?string $filterField = null;
    public ?string $filterValue = null;
    public ?CLMerchantFilterType $filterType = null;

    public function __construct(CLMerchantType $merchType)
    {
        if ($merchType == CLMerchantType::PRODUCTION) {
            $this->gatewayUrl = 'https://api.clover.com';
        } else {
            $this->gatewayUrl = 'https://sandbox.dev.clover.com';
        }

    }

    public function setTeckPayValues(int $accountId, int $merchantId): void
    {
        $this->accountId = $accountId;
        $this->id = $merchantId;
    }

    public function setCredentials(string $merchantId, string $token): void
    {
        $this->gatewayMerchantCode = $merchantId;
        $this->gatewayPasswordToken = $token;
    }

    public function setFilter(string $filterField, string $filterValue, CLMerchantFilterType $filter): void
    {
        $this->filterField = $filterField;
        $this->filterValue = $filterValue;
        $this->filterType = $filter;
    }


}