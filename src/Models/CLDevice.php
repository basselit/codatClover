<?php


namespace Codatsoft\CodatClover\Models;


class CLDevice
{
    public string $serial;
    public string $id;
    public string $model;
    public int $merchantId;
    public ?string $productName = null;
    public string $merchantName;
    public ?bool $isDeviceOnline = null;

    public function loadFromJSON($one): void
    {
        $this->id = $one->id;
        $this->serial = $one->serial;
        $this->model = $one->model;
    }
}
