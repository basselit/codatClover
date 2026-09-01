<?php


namespace Codatsoft\CodatClover\Models;


use App\TeckPay\DBModels\MerchantUser;
use App\Models\MerchantUserPending;
use App\Models\User;
use App\TeckPay\Models\DBMapped\TMerchant;
use App\TeckPay\Models\DBMapped\TMerchantPendingUsers;
use App\TeckPay\Models\DBMapped\TMerchants;
use App\TeckPay\Models\DBMapped\TUsers;
use Countable;
use Iterator;

class CLEmployees implements Iterator, Countable
{
    public array $elements;

    public bool $success;
    public string $errorMessage;
    private int $position;

    public function __construct()
    {
        $this->elements = [];
        $this->position = 0;
    }

    public function get(int $index): CLEmployee
    {
        return $this->elements[$index];
    }

    public function findByMerchUserId(string $merchUserId) :?CLEmployee
    {
        foreach ($this->elements as $key => $value)
        {
            $act = $this->get($key);
            if ($act->id == $merchUserId)
            {
                return $act;
            }
        }
        return null;
    }

    private function isPenStation($one): bool
    {
        if (!property_exists($one,'nickname'))
        {
            return false;
        }

        if (str_contains($one->nickname,'--') && str_contains(strtolower($one->nickname),'pen'))
        {
            return true;
        }

        return false;

    }

    public function addEmployee(CLEmployee $one)
    {
        $this->elements[] = $one;
    }

    public function findByMobileNumber(string $mobileNumber) :?CLEmployee
    {
        foreach ($this as $one)
        {
            if ($one->customId == $mobileNumber)
            {
                return $one;
            }
        }
        return null;
    }

    public function finalizeMissingEmployeeOne($one, TMerchant $merch)
    {
        if ($one->matched_user_id !== 0)
        {
            //we have the main user match now check if assigned to merchant
            $found = MerchantUser::where(['merchant_id' => $one->merchant_id,'user_id' => $one->matched_user_id])->first();
            if ($found == null)
            {
                $newMerchUser = new MerchantUser();
                $newMerchUser->account_id = $merch->accountId;
                $newMerchUser->merchant_id = $merch->id;
                $newMerchUser->user_id = $one->matched_user_id;
                $newMerchUser->is_owner = 0;
                $newMerchUser->merchant_user_id = $one->merchant_user_id;
                $newMerchUser->merchant_user_name = $one->merchant_user_name;
                $newMerchUser->merchant_user_phone = $one->phone_number;
                $newMerchUser->merchant_user_role = 3;
                $newMerchUser->merchant_user_pin = $one->merchant_user_pin;
                if ($one->exist_in_merchant == 0)
                {
                    $newMerchUser->merchant_active = 0;
                } else
                {
                    $newMerchUser->merchant_active = 1;
                }

                $newMerchUser->save();
                MerchantUserPending::where('id',$one->id)->delete();

            } else
            {
                $updated = false;
                if ($found->merchant_user_id !== $one->merchant_user_id)
                {
                    if ($found->merchant_user_old_id1 == null)
                    {
                        $found->merchant_user_old_id1 = $one->merchant_user_id;
                        $updated = true;
                    } else
                    {
                        if ($found->merchant_user_old_id2 == null)
                        {
                            $found->merchant_user_old_id2 = $one->merchant_user_id;
                            $updated = true;
                        }
                    }
                }

                if ($updated)
                {
                    if ($one->exist_in_merchant == 0)
                    {
                        $found->merchant_active = 0;
                    } else
                    {
                        $found->merchant_active = 1;
                    }

                    $found->save();
                    MerchantUserPending::where('id',$one->id)->delete();

                }

            }
        } else
        {
            $newUser = new User();
            $newUser->account_id = $merch->accountId;
            $newUser->active_merchant_id = $merch->id;
            $newUser->active_gateway_merchant_code = $merch->gatewayMerchantCode;
            $newUser->name = $one->merchant_user_name;
            $newUser->password = bcrypt($one->password);
            $newUser->phone = $one->phone_number;
            $newUser->user_role = 2;
            $newUser->phone_verified_at = now();
            if ($one->exist_in_merchant == 0)
            {
                $newUser->active = 0;
            } else
            {
                $newUser->active = 1;
            }
            $newUser->save();
            $addMerchUser = new MerchantUser();
            $addMerchUser->account_id = $merch->accountId;
            $addMerchUser->merchant_id = $merch->id;
            $addMerchUser->user_id = $newUser->id;
            $addMerchUser->is_owner = 0;
            $addMerchUser->merchant_user_id = $one->merchant_user_id;
            $addMerchUser->merchant_user_name = $one->merchant_user_name;
            $addMerchUser->merchant_user_phone = $one->phone_number;
            $addMerchUser->merchant_user_role = 3;
            $addMerchUser->merchant_user_pin = $one->merchant_user_pin;

            if ($one->exist_in_merchant == 0)
            {
                $addMerchUser->merchant_active = 0;
            } else
            {
                $addMerchUser->merchant_active = 1;
            }

            $addMerchUser->save();
            MerchantUserPending::where('id',$one->id)->delete();

        }

    }

    public function finalizeMissingEmployees(TMerchants $merchs)
    {
        foreach ($merchs->elements as $key => $value)
        {
            $oneMerch = $merchs->get($key);
            $all = DBRepo::readTableFilter('merchant_users_pending','merchant_id',$oneMerch->id);
            if (!$all->success)
            {
                $this->success = false;
                $this->errorMessage = $all->errorMessage;
                return;
            }

            foreach ($all->data as $one)
            {
                //it means that we did two processed of checking existance of user in merchant and in out database for matches
                if ($one->exist_in_merchant !== null && $one->matched_user_id !== null)
                {
                    $this->finalizeMissingEmployeeOne($one,$oneMerch);
                }
            }

        }

    }

    public function checkMissingEmployeeExistInTeckPay(int $accountId)
    {
        //in this step we set the matched employee by user input to our database users list and set two fields the id and phone
        //if not matched then we provide full info of user to be added like phone number password and all
        //in either case we also have to provide pin if user exist and has merchant user get it from there or provide it by user

        $users = new TUsers();
        $users->loadFromDB($accountId);

        $pending = new TMerchantPendingUsers();
        $pending->loadFromDBAccount($accountId);
        foreach ($pending->elements as $key => $value)
        {
            $pend = $pending->get($key);
            if ($pend->customId != null)
            {
                $found = $users->findByPhone($pend->customId);
                if ($found)
                {
                    DBRepo::updateTableValue('merchant_users_pending','id',$pend->id,'matched_user_id',$found->id);
                    DBRepo::updateTableValue('merchant_users_pending','id',$pend->id,'phone_number',$found->mobile);
                } else
                {
                    DBRepo::updateTableValue('merchant_users_pending','id',$pend->id,'matched_user_id',0);
                    DBRepo::updateTableValue('merchant_users_pending','id',$pend->id,'phone_number',$pend->customId);
                    DBRepo::updateTableValue('merchant_users_pending','id',$pend->id,'password',$pend->merchantUserPin . '00');
                }

            }
        }

    }


    public function current(): CLEmployee
    {
        return $this->elements[$this->position];
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function key(): int
    {
        return $this->position;
    }

    public function valid(): bool
    {
        return isset($this->elements[$this->position]);
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function count(): int
    {
        return count($this->elements);
    }
}
