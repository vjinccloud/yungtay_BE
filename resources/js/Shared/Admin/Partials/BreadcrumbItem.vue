<template>
    <div class="row">
        <div class="col-sm-12">
            <div class="d-flex align-items-center justify-content-between content-heading">
                <h3 class="page-title mb-0">{{ safeMenuTitle }}</h3>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <Link :href="route('admin.dashboard')" one-link-mark="yes">首頁</Link>
                    </li>
                    <li
                        v-for="(menu, index) in safeBreadcrumbs"
                        :key="index"
                        :class="[
                            'breadcrumb-item',
                            menu.url && menu.id !== safeThisMenu.id && isActive(menu.url) ? 'active' : ''
                        ]"
                    >
                        <Link
                            v-if="menu.url && menu.id !== safeThisMenu.id"
                            :href="route(menu.url_name)"
                            one-link-mark="yes"
                        >
                            {{ menu.title || '未命名' }}
                        </Link>
                        <span v-else>{{ menu.title || '未命名' }}</span>
                    </li>
                </ol>
            </div>
        </div>
    </div>
</template>

<script>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export default {
    setup() {
        const page = usePage();

        // 安全地獲取數據，提供默認值
        const safeThisMenu = computed(() => {
            try {
                return page.props.thisMenu || { id: null, title: '頁面', url: null };
            } catch (error) {
                console.warn('獲取 thisMenu 時發生錯誤:', error);
                return { id: null, title: '頁面', url: null };
            }
        });

        const safeBreadcrumbs = computed(() => {
            try {
                return page.props.breadcrumbs || [];
            } catch (error) {
                console.warn('獲取 breadcrumbs 時發生錯誤:', error);
                return [];
            }
        });

        const safeMenuTitle = computed(() => {
            try {
                return safeThisMenu.value?.title || '頁面';
            } catch (error) {
                console.warn('獲取 menu title 時發生錯誤:', error);
                return '頁面';
            }
        });

        const isActive = (urlName) => {
            try {
                if (!page.url || !urlName) return false;
                const currentPath = page.url.substring(1);
                return currentPath === urlName;
            } catch (error) {
                console.warn('檢查 URL 是否 active 時發生錯誤:', error);
                return false;
            }
        };

        return {
            safeThisMenu,
            safeMenuTitle,
            safeBreadcrumbs,
            isActive,
        };
    },
};
</script>
