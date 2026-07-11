<?php


namespace Codatsoft\CodatClover\Types;


abstract class CLParseStatus
{
    const NO_CARD_TYPE = 1;
    const NO_EMPLOYEE = 2;
    const NO_DEVICE = 3;

    public static function getParseDesc(int $code): string
    {
        if ($code == 1)
        {
            return 'No Card Type provided';
        } else if ($code == 2)
        {
            return 'No Employee attached to order';
        }
        return 'unknown parse error';
    }

}
