<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class LoadAdminUserRelations
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
    {
        if (Auth::guard('admin')->check()) {
                $adminUser = Auth::guard('admin')->user();
            if ($adminUser) {
                $adminUser->load('roles.permissions', 'roles'); // 加載關聯數據
            }
        }
        

        return $next($request);
    }
}
