<?php

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;
use App\Exceptions\CustomPermissionException;
class RoleRequest extends FormRequest
{
    public function authorize()
    {
        return true; // 根据需要调整为 false 或权限逻辑
    }

    public function rules()
    {
        return [
            'name' => 'required',
            'description' => 'nullable',
            'selectedIds' => 'array', // 验证 selectedIds 必须是数组
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $selectedIds = $this->input('selectedIds', []);
            if (is_array($selectedIds) && count($selectedIds) === 0) {
                throw new CustomPermissionException('請選擇要新增的權限','Admin/AdministrationSetting/Form');
            }
        });
     
    }
}

