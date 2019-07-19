<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserAddressPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * 授权策略类 检查是否是当前自己用户的操作地址权限
     * @param User $user
     * @param UserAddress $userAddress
     * @return bool
     */
    public function own(User $user, UserAddress $userAddress){
        return $userAddress->user_id == $user->id;
    }
}
