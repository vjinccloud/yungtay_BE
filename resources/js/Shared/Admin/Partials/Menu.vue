<template>
      <div class="content-side content-side-full">
            <ul class="nav-main">
                <template  v-for="menu in menuItems" :key="menu.id">
                    <li class="nav-main-item"    v-if="menu.url">
                        <Link  :class="['nav-main-link', isActive(menu.url) ? 'active' : '']"
                         :href="route(menu.url_name)">
                            <i :class="['nav-main-link-icon', menu.icon_image]"></i>
                            <span class="nav-main-link-name">{{ menu.title }}</span>
                        </Link>
                    </li>
                    <li v-else
                        class="nav-main-item"
                        :class="{
                            open: isMenuOpen(menu)
                        }">
                        <a
                            class="nav-main-link nav-main-link-submenu"
                            data-toggle="submenu"
                            aria-haspopup="true"
                            aria-expanded="true"
                            :href="'#'">
                            <i :class="['nav-main-link-icon', menu.icon_image]"></i>
                            <span class="nav-main-link-name">{{ menu.title }}</span>
                        </a>
                        <ul class="nav-main-submenu">
                            <template v-for="submenu in menu.children" :key="submenu.id">
                                <li
                                    v-if="!submenu.children || submenu.children.length === 0"
                                    class="nav-main-item">
                                    <Link
                                    :class="['nav-main-link', isActive(submenu.url) ? 'active' : '']"
                                    :href="isRouteDefined(submenu.url_name)?route(submenu.url_name):'#'">
                                        <span class="nav-main-link-name">{{ submenu.title }}</span>
                                    </Link>

                                </li>
                                <li
                                    v-else
                                    class="nav-main-item"
                                    :class="{
                                        open: isMenuOpen(submenu)
                                    }">
                                    <a class="nav-main-link nav-main-link-submenu"
                                    data-toggle="submenu"
                                    aria-haspopup="true"
                                    aria-expanded="true"
                                    href="#">
                                        <span class="nav-main-link-name">{{ submenu.title }}</span>
                                    </a>
                                    <ul class="nav-main-submenu">
                                        <template v-for="(child, index) in submenu.children" :key="child.id">
                                            <li class="nav-main-item">
                                                <Link
                                                    :class="['nav-main-link', isActive(child.url) ? 'active' : '']"
                                                    :href="isRouteDefined(child.url_name)?route(child.url_name):'#'"
                                                >
                                                    <span class="nav-main-link-name">{{ child.title }}</span>
                                                </Link>
                                            </li>
                                        </template>
                                    </ul>
                                </li>
                            </template>
                        </ul>
                    </li>
                </template>
            </ul>
          </div>
</template>

<script>
import { usePage } from '@inertiajs/vue3';


export default {

    setup() {

        const page = usePage();
        const menuItems = page.props.menuItems;
        const thisMenu = page.props.thisMenu;

        // 檢查路由是否存在
        const isRouteDefined = (routeName) => {

            try {
                return route().has(routeName);
            } catch {
                return false;
            }
        };

        const isActive = (urlName) => {
            if (!urlName) return false;

            const currentPath = new URL(page.url, window.location.origin).pathname.substring(1);

            // 精確匹配
            if (currentPath === urlName) {
                return true;
            }

            // 檢查是否為子頁面（重要！）
            if (currentPath.startsWith(urlName + '/')) {
                return true;
            }

            // 檢查是否包含關鍵路徑
            if (currentPath.includes(urlName)) {
                return true;
            }

            return false;
        };

        const isMenuOpen = (menu) => {
            // 首先檢查當前頁面是否在選單結構中
            if (!isCurrentPageInMenu()) {
                return false; // 如果不在選單中，關閉所有選單
            }

            // 如果在選單中，按原邏輯處理
            if (menu.children && menu.children.length > 0) {
                return menu.children.some((submenu) => {
                    // 檢查子選單本身是否匹配
                    if (submenu.url && isActive(submenu.url)) {
                        return true;
                    }
                    // 檢查第三層選單是否匹配
                    if (submenu.children && submenu.children.length > 0) {
                        return submenu.children.some(child =>
                            child.url && isActive(child.url)
                        );
                    }
                    return false;
                });
            }

            // 檢查選單本身是否匹配當前路由
            return menu.url && isActive(menu.url);
        };

         // 檢查當前頁面是否在選單結構中
         const isCurrentPageInMenu = () => {
            const currentPath = new URL(page.url, window.location.origin).pathname.substring(1);

            // 遞迴檢查所有選單項目
            const checkMenuItems = (items) => {
                for (const item of items) {
                    // 檢查當前項目
                    if (item.url && currentPath.includes(item.url)) {
                        return true;
                    }
                    // 檢查子選單
                    if (item.children && item.children.length > 0) {
                        if (checkMenuItems(item.children)) {
                            return true;
                        }
                    }
                }
                return false;
            };

            return checkMenuItems(menuItems);
        };

        return {
            menuItems,
            isActive,
            isMenuOpen,
            isRouteDefined
        };
    },
};
</script>
