<?php

namespace Codatsoft\CodatClover\Models;

use App\Clover\Types\CLParseStatus;

class CLCardTransaction
{
    public string $cardType;
    public string $last4;
    public string $first6;
    public string $authCode;
    public string $state;
    public string $authorizingNetworkName;
    public ?string $cardholderName = null;

    public bool $parseSuccess;
    public int $parseErrorCode = 0;


    public function loadFromJSON(\stdClass $card): void
    {
        if (!property_exists($card,'cardType'))
        {
            if ($card->extra->authorizingNetworkName == 'PAYPALVNMO')
            {
                $this->cardType = 'Venmo';
                $this->last4 = '';
            } else
            {
                $this->parseSuccess = false;
                $this->parseErrorCode = CLParseStatus::NO_CARD_TYPE;
                return;

            }
        } else
        {
            $this->cardType = $card->cardType;
            $this->last4 = $card->last4;
            $this->first6 = $card->first6;
        }



        $this->authCode = $card->authCode;
        $this->state = $card->state;
        $this->authorizingNetworkName = $card->extra->authorizingNetworkName;
        if (property_exists($card,'cardholderName'))
        {
            if ($card->cardholderName !== '')
            {
                $this->cardholderName = $card->cardholderName;
            }

        }
        $this->parseSuccess = true;
    }


}
