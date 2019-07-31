<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Installment;
use Illuminate\Auth\Access\HandlesAuthorization;

class InstallmentPolicy
{
    use HandlesAuthorization;

    /**
     * 个人权限校验
     * @param User $user
     * @param Installment $installment
     * @return bool
     */
    public function own(User $user, Installment $installment)
    {
        return $installment->user_id == $user->id;
    }
}
