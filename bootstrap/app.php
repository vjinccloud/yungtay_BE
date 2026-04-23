<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\LocalizationMiddleware;
use App\Http\Middleware\HtmlHeadCleaner;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',  // 加入 API 路由
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        // 模組命令
        \Modules\EcpayPayment\Console\Commands\IssueInvoicesCommand::class,
        \Modules\FrontMenuSetting\Database\SeedAdminMenuCommand::class,
        \Modules\FrontMenuSetting\Database\GrantPermissionsCommand::class,
        \Modules\ProductSpecSetting\Database\SeedAdminMenuCommand::class,
        \Modules\ProductSpecSetting\Database\GrantPermissionsCommand::class,
        \Modules\ProductListing\Database\SeedAdminMenuCommand::class,
        \Modules\ProductListing\Database\GrantPermissionsCommand::class,
        \Modules\OrderManagement\Database\SeedAdminMenuCommand::class,
        \Modules\OrderManagement\Database\GrantPermissionsCommand::class,
    ])
    ->withMiddleware(function (Middleware $middleware) {        

        // Web middleware
        $middleware->web(append: [
            LocalizationMiddleware::class,
            HandleInertiaRequests::class,
            HtmlHeadCleaner::class,
        ]);
        
        // API middleware - 加入 CORS 支援
        $middleware->api(append: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);
        
        // Middleware 別名
        $middleware->alias([
            'guest' => RedirectIfAuthenticated::class,
            'auth' => Authenticate::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'cors' => \Illuminate\Http\Middleware\HandleCors::class,  // 加入 CORS 別名
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // 自定義錯誤處理
        $exceptions->render(function (Throwable $e, $request) {
            // API 錯誤處理 - 只檢查路徑，不依賴 Accept header
            if ($request->is('api/*')) {
                // ValidationException - 回傳 422 JSON
                if ($e instanceof \Illuminate\Validation\ValidationException) {
                    return response()->json([
                        'success' => false,
                        'message' => $e->getMessage(),
                        'errors' => $e->errors(),
                    ], 422);
                }

                // TooManyRequestsHttpException 節流限制（回傳 429）
                if ($e instanceof \Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException) {
                    return response()->json([
                        'success' => false,
                        'message' => __('frontend.email_verify.resend_limit'),
                    ], 429);
                }

                // HttpException (包含 404, 403, 405 等)
                if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                    return response()->json([
                        'success' => false,
                        'message' => $e->getMessage() ?: 'HTTP Error',
                        'status_code' => $e->getStatusCode(),
                    ], $e->getStatusCode());
                }

                // 其他異常的自訂處理
                // 開發環境顯示詳細錯誤
                if (config('app.debug')) {
                    return response()->json([
                        'success' => false,
                        'message' => $e->getMessage(),
                        'exception' => get_class($e),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => collect($e->getTrace())->take(5)->toArray(),
                    ], 500);
                }

                // 生產環境返回通用錯誤訊息
                return response()->json([
                    'success' => false,
                    'message' => '系統發生錯誤，請稍後再試',
                ], 500);
            }
            
            // 網頁錯誤處理 - 區分前後台
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                $statusCode = $e->getStatusCode();
                
                // 判斷是否為後台
                if ($request->is('admin/*')) {
                    // 後台錯誤頁面
                    if (view()->exists("admin.error.{$statusCode}")) {
                        return response()->view("admin.error.{$statusCode}", [], $statusCode);
                    }
                    // 後台預設錯誤頁面
                    if (view()->exists("admin.error.default")) {
                        return response()->view("admin.error.default", ['code' => $statusCode], $statusCode);
                    }
                } else {
                    // 前台錯誤頁面
                    if (view()->exists("frontend.error.{$statusCode}")) {
                        return response()->view("frontend.error.{$statusCode}", [], $statusCode);
                    }
                    // 前台預設錯誤頁面
                    if (view()->exists("frontend.error.default")) {
                        return response()->view("frontend.error.default", ['code' => $statusCode], $statusCode);
                    }
                }
                
                // 如果都沒有，使用 Laravel 預設錯誤頁面
                if (view()->exists("errors.{$statusCode}")) {
                    return response()->view("errors.{$statusCode}", [], $statusCode);
                }
            }
        });
    })->create();