<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserAddressRequest;
use App\Models\UserAddress;
use Illuminate\Http\Request;

class UserAddressController extends Controller
{
    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request){
        return view('user_address.index', ['addresses' => $request->user()->addresses]);
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create(){
        return view('user_address.create_and_edit', ['address' => new UserAddress()]);
    }

    /**
     * @param UserAddressRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(UserAddressRequest $request){
        /**
         * $request->user() 获取当前登录用户。
         * user()->addresses() 获取当前用户与地址的关联关系（注意：这里并不是获取当前用户的地址列表）
         */
        $request->user()->addresses()->create($request->only([
            'province',
            'city',
            'district',
            'address',
            'zip',
            'contact_name',
            'contact_phone',
        ]));
//        insert into `user_addresses` (`province`, `city`, `district`, `address`, `zip`, `user_id`, `updated_at`, `created_at`) values (四川省, 成都市, 青羊区, 环球中心, 618100, 1, 2019-07-17 18:18:06, 2019-07-17 18:18:06)
        return redirect()->route('user_addresses.index');
    }

    /**
     * @param UserAddress $user_address
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(UserAddress $user_address)
    {
        /**
         * 检测是否是当前自己用户对应权限
         */
        $this->authorize('own', $user_address);

        return view('user_address.create_and_edit', ['address' => $user_address]);
    }

    /**
     * @param UserAddress $user_address
     * @param UserAddressRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UserAddress $user_address, UserAddressRequest $request)
    {
        /**
         * 检测是否是当前自己用户对应权限
         */
        $this->authorize('own', $user_address);

        $user_address->update($request->only([
            'province',
            'city',
            'district',
            'address',
            'zip',
            'contact_name',
            'contact_phone',
        ]));

        return redirect()->route('user_addresses.index');
    }

    /**
     * @param UserAddress $user_address
     * @return array
     * @throws \Exception
     */
    public function destroy(UserAddress $user_address)
    {
        /**
         * 检测是否是当前自己用户对应权限
         */
        $this->authorize('own', $user_address);

        $user_address->delete();

        return [];
    }

}
