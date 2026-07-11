<?php

namespace Codatsoft\CodatClover\Business;

use Codatsoft\Codatbase\Base\TModelNetwork;
use Codatsoft\CodatClover\Models\CLDevices;
use Codatsoft\CodatClover\Models\CLEmployee;
use Codatsoft\CodatClover\Models\CLEmployees;
use Codatsoft\CodatClover\Models\CLMerchant;
use Codatsoft\CodatClover\Types\CLEndpoints;
use Codatsoft\CodatClover\Types\CLParameters;
use Illuminate\Database\QueryException;
use stdClass;

class STClover
{
    public CLMerchant $curMerch;
    public TModelNetwork $model;

    public function __construct(CLMerchant $theMerch)
    {
        $this->curMerch = $theMerch;
    }

    private function common(): void
    {
        $this->model = new TModelNetwork();
        $this->model->create($this->curMerch->gatewayUrl, $this->curMerch->gatewayPasswordToken);
        $this->model->addParameter(CLParameters::MERCHANT_ID,$this->curMerch->gatewayMerchantCode);
    }

    public function loadEmployees(): CLEmployees
    {
        $this->common();
        $this->model->setEndPoint(CLEndpoints::EMPLOYEES);
        $network = $this->model->runFilter();
        $emps = STJson::parseEmployees($network->content, $this->curMerch);
        return $emps;

    }

    public function loadEmployee(string $mobileNumber): CLEmployee
    {
        $all = $this->loadEmployees();
        $find = $all->findByMobileNumber($mobileNumber);
        return $find;
    }

    public function addEmployee(CLEmployee $employee): CLEmployee
    {
        $this->common();
        $this->model->setEndPoint(CLEndpoints::EMPLOYEE_ADD);
        $postEmployee = new stdClass();
        $postEmployee->name = $employee->name;
        if (!str_contains($employee->phoneNumber, '+1'))
        {
            $postEmployee->phoneNumber = '+1' . $employee->phoneNumber;
        } else
        {
            $postEmployee->phoneNumber = $employee->phoneNumber;
        }
        $postEmployee->customId = $employee->customId;
        $postEmployee->role = $employee->role;
        $postEmployee->pin = $employee->pin;
        $postEmployee->nickname = $employee->nickname;
        $this->model->setPost($postEmployee);
        $network = $this->model->runFilter();
        return STJson::parseEmployee($network->content);
    }

    public function checkDeviceOnline(string $deviceId): bool
    {
        $this->common();
        $this->model->setEndPoint(CLEndpoints::DEVICE);
        $this->model->addParameter(CLParameters::DEVICE_ID,$deviceId);
        $device = $this->model->runFilter();

        return $device->content->isDeviceOnline;

    }

    public function loadDevices(): CLDevices
    {
        $this->common();
        $this->model->setEndPoint(CLEndpoints::DEVICES);
        $network = $this->model->runFilter();
        $devs = STJson::parseDevices($network->content, $this->curMerch->id);
        return $devs;
    }

    public function updateDevices(CLDevices $devs): void
    {
        foreach ($devs as $device)
        {
            try {
                $newDev = new Device();
                $newDev->device_id = $device->id;
                $newDev->device_serial = $device->serial;
                $newDev->device_model = $device->model;
                $newDev->merchant_id = $device->merchantId;
                $newDev->save();
            } catch (QueryException $exception)
            {
                $code = $exception->getCode();
                if ($code != 23000)
                {
                    \Sentry\captureException($exception);
                    return;

                }

            } catch (\Exception $exception)
            {
                \Sentry\captureException($exception);
            }
        }

    }

    public function updateDevicesOnline(CLDevices $devs): void
    {

        foreach ($devs as $device)
        {
            $isOnline = $this->checkDeviceOnline($device->id);
            $findDev = Device::where('device_id',$device->id)->first();
            $findDev->is_device_online = $isOnline;
            $findDev->save();
        }

    }




}
