<?php


namespace Codatsoft\CodatClover\Models;

class CLEmployee
{
    public string $id;
    public string $name;
    public string $nickname;
    public ?string $customId = null;
    public string $role;
    public string $pin;
    public int $merchantId;

    public string $merchantName;
    public bool $existInTeckPay;
    public string $existByName;
    public string $matchByNumber;
    public ?string $phoneNumber = null;


}
