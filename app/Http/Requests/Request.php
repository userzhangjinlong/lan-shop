<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class Request extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *定义request基类开启验证
     * @return bool
     */
    public function authorize()
    {
        return true;
    }
}
