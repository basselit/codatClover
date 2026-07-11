<?php

namespace Codatsoft\CodatClover\Models;

use App\Clover\Business\STJson;
use App\Clover\Types\CLEndpoints;
use App\Clover\Types\CLParameters;
use App\TeckPay\Models\TMerchant;
use Codatsoft\Codatbase\Base\TModelNetwork;

class CLObject extends TModelNetwork
{
    public string $merchCode;

    public function setMerchant(TMerchant $merchant): void
    {
        parent::create($merchant->getGatewayUrl(),$merchant->getGatewayToken());
        $this->merchCode = $merchant->gatewayMerchantCode;

    }

    public function getEmployees(): \stdClass
    {
        $this->setEndPoint(CLEndpoints::EMPLOYEES);
        $this->addParameter(CLParameters::MERCHANT_ID,$this->merchCode);
        $data = $this->runFilter();
        return $data->content;

    }




}
