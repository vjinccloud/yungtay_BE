<?php

namespace App\Repositories;

use App\Models\AdminMenu;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
class AdminMenuRepository
{
    public function buildTree($elements, $parentId = 0, $parentLevel = 0)
    {
        $branch = [];

        foreach ($elements as $element) {
            if ($element['parent_id'] == $parentId) {
                $element['level'] = $parentLevel + 1;
                $element['children'] = $this->buildTree($elements, $element['id'], $element['level']);
                $branch[] = $element;
            }
        }

        return $branch;
    }

    public function getMenu($type=0,$role = null)
    {
        return Cache::remember('admin_menu' . $type.auth('admin')->user()->id, 600, fn() => $this->fetchAndPrepareMenus($type, $role));
    }


    protected function fetchAndPrepareMenus($type,  $role)
    {
        $query = AdminMenu::with('parent');

        if ($type == 0) {
            $query = $query->where('type', '!=', 0);
        }
        $menus = $query->status()
                    ->orderBy('type')
                    ->orderBy('seq')
                    ->get()->map(fn ($menu) => [
                        'id' => $menu->id,
                        'title' => $menu->title,
                        'parent_id' => $menu->parent_id,
                        'type' => $menu->type,
                        'level' => $menu->level,
                        'url' => $menu->url,
                        'url_name' => $menu->url_name,
                        'icon_image' => $menu->icon_image,
                        'seq' => $menu->seq,
                    ]);
        if($role){
            $menus =  $menus->filter(
                fn($menu) => !$menu['url_name'] || $role->contains($menu['url_name'])
            );
        }
        $menus = $menus->toArray();
        return $this->buildTree($menus);
    }

    public function getCurrentRouteMenu()
    {
        $currentRouteName = Route::currentRouteName();
        return Cache::remember( $currentRouteName, 600, fn() => $this->fetchCurrentRouteMenu($currentRouteName));

    }

    public function fetchCurrentRouteMenu($currentRouteName)
    {
        $menu = AdminMenu::with('parent')->where('url_name', $currentRouteName)
            ->status()
            ->first();

        return $menu;
    }

    public function loadBreadcrumbs()
    {
        // 獲取當前路由的菜單項目
        $menuItem = $this->getCurrentRouteMenu();

        // 建立一個空的麵包屑陣列
        $breadcrumbs = [];

        // 遍歷每個父項目並加入到麵包屑陣列中
        while ($menuItem != null) {
            $breadcrumbs[] = $menuItem;

            $menuItem = $menuItem->parent;
        }

        // 反轉麵包屑陣列，因為我們是從最底層的項目開始的
        $breadcrumbs = array_reverse($breadcrumbs);

        return $breadcrumbs;
    }

    /**
     * 建立樹狀結構圖權限樹
     *
     * @param $menu menu Data
     * @return void
     */
    public function buildAdminMenuTree($menu){
        $result = [];
        foreach ($menu as $item) {
            $node = [
                'id' => $item["url_name"],
                "text" => $item["title"],
                'checked'=> true,
            ];

            if (!empty($item["children"])) {
                $node["children"] = $this->buildAdminMenuTree($item["children"]);
            }

            $result[] = $node;
        }

        return $result;
    }
}
