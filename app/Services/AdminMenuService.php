<?php
namespace App\Services;

use App\Repositories\AdminMenuRepository;
use Illuminate\Support\Facades\Cache;
class AdminMenuService extends BaseService
{
    public function __construct(private AdminMenuRepository $adminMenu) {

    }

    public function getMenu(){
        $role = auth('admin')->user()->roles->first();

        $adminRole = Cache::remember('role'.$role->id , 600, fn() => $role->getPermissionNames());

        $menus = $this->adminMenu->getMenu(0,$adminRole);
        return $menus ?? null;
    }

    /**
     * 取得使用者有權限的第一個可導向的選單路由
     * 遞迴搜尋選單樹，找到第一個有 url_name 的選單項目
     *
     * @return string|null 路由名稱或 null
     */
    public function getFirstAvailableRoute()
    {
        $menus = $this->getMenu();
        
        if (empty($menus)) {
            return null;
        }

        return $this->findFirstRouteInMenuTree($menus);
    }

    /**
     * 遞迴搜尋選單樹找到第一個有效的路由
     * 只返回以 'admin.' 開頭的真正路由名稱
     *
     * @param array $menus 選單陣列
     * @return string|null
     */
    private function findFirstRouteInMenuTree(array $menus): ?string
    {
        foreach ($menus as $menu) {
            // 如果這個選單項目有 url_name 且是有效的 Laravel 路由（以 admin. 開頭）
            if (!empty($menu['url_name']) && str_starts_with($menu['url_name'], 'admin.')) {
                // 確認路由確實存在
                if (\Route::has($menu['url_name'])) {
                    return $menu['url_name'];
                }
            }
            
            // 如果有子選單，遞迴搜尋
            if (!empty($menu['children'])) {
                $childRoute = $this->findFirstRouteInMenuTree($menu['children']);
                if ($childRoute) {
                    return $childRoute;
                }
            }
        }

        return null;
    }


    public function getCurrentRouteMenu(){
        return $this->adminMenu->getCurrentRouteMenu() ?? null;
    }

    public function getLoadBreadcrumbs() {
        return $this->adminMenu->loadBreadcrumbs();
    }
    public function getBuildAdminMenuTree(){
        //Cache::forget('admin_menu1'.auth('admin')->user()->id);
        return $this->adminMenu->buildAdminMenuTree($this->adminMenu->getMenu(1));
    }

}
