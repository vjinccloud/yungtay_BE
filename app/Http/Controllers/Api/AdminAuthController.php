<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminAuthController extends Controller
{
    /**
     * 僅檢查帳密是否匹配，不建立登入狀態。
     */
    public function checkCredentials(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'username' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        $admin = AdminUser::query()->where('email', $payload['username'])->first();

        $isValid = (bool) $admin
            && (int) $admin->is_active === 1
            && Hash::check($payload['password'], $admin->password);

        return response()->json([
            'success' => true,
            'exists' => $isValid,
        ]);
    }
}
