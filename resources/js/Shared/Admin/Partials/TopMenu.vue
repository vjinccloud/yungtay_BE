<template>
    <div class="dropdown d-inline-block">
        <button type="button" class="btn btn-sm btn-alt-secondary" 
        @click="toggleDropdown"
        :class="{'show': dropdownVisible}"
          id="page-header-user-dropdown"
        >
            <i class="fa fa-user d-sm-none"></i>
            <span class="d-none d-sm-inline-block fw-semibold"><i class="fa fa-gear noti-icon"></i></span>
            <i class="fa fa-angle-down opacity-50 ms-1"></i>
        </button>
        <div class="dropdown-menu dropdown-menu-md dropdown-menu-end p-0" 
        :class="{'show': dropdownVisible}"
        style="position: absolute; inset: 0px 0px auto auto; margin: 0px; transform: translate(0px, 33px);"
        id="page-header-user-dropdown-menu"
        >
            <div class="px-2 py-3 bg-body-light rounded-top">
            <h5 class="h6 text-center mb-0">
                {{ auth.user.name }}
            </h5>
            </div>
            <div class="p-2">
            <a
            @click="handleClick"
            class="dropdown-item d-flex align-items-center justify-content-between space-x-1" href="javascript:void(0)">
                <span>基本資料設定</span>
                <i class="fa fa-fw fa-user opacity-25"></i>
            </a>

            <Link 
                v-if="can('admin.operation-logs')"
                class="dropdown-item d-flex align-items-center justify-content-between space-x-1" 
                :href="route('admin.operation-logs')" 
                @click="toggleDropdown"
                >
                <span>操作紀錄</span>
                <i class="fa fa-fw fa-history opacity-25"></i>
            </Link>
            <div class="dropdown-divider"></div>
            <Link  v-if="can('admin.admin-settings')"
            @click="toggleDropdown"
            class="dropdown-item d-flex align-items-center justify-content-between space-x-1" :href="route('admin.admin-settings')">
                <span>管理員管理</span>
                <i class="fa fa-fw fa-user-gear opacity-25"></i>
            </Link>
            <!-- Toggle Side Overlay -->
            <!-- Layout API, functionality initialized in Template._uiApiLayout() -->
            <Link 
            v-if="can('admin.administration-settings')"
            class="dropdown-item d-flex align-items-center justify-content-between space-x-1" :href="route('admin.administration-settings')" 
             @click="toggleDropdown"
            >
                <span>角色權限管理</span>
                <i class="fa fa-fw fa-wrench opacity-25"></i>
            </Link>
            <!-- END Side Overlay -->

            <div class="dropdown-divider"></div>
            <a class="dropdown-item d-flex align-items-center justify-content-between space-x-1" href="javascript:void(0)" @click="confirmLogout">
                <span>登出</span>
                <i class="fa fa-fw fa-sign-out-alt opacity-25"></i>
            </a>
            </div>
        </div>
    </div>
</template>

<script>
import { inject,ref,onMounted,onBeforeUnmount } from 'vue';
import Swal from 'sweetalert2';
export default {
    props: {
        auth: Object, // 父組件傳遞過來的 props
        openModal: Function // 父組件傳遞過來的函數
    },
    
    setup(props) {
        const can = inject('can');
        const sweetAlert = inject('$sweetAlert');
        const dropdownVisible = ref(false);
        const toggleDropdown = () => {
            dropdownVisible.value = !dropdownVisible.value;
        };
        const handleClickOutside = (event) => {
            const dropdownMenu = document.getElementById('page-header-user-dropdown-menu');
            const dropdownButton = document.getElementById('page-header-user-dropdown');
            
            // 確保點擊的是下拉菜單以外的區域
            if (dropdownMenu && !dropdownMenu.contains(event.target) && !dropdownButton.contains(event.target)) {
                dropdownVisible.value = false;
            }
        }
        const handleClick = () => {
            props.openModal(); 
            toggleDropdown();
        }
        const confirmLogout = () => {
            toggleDropdown();
            Swal.fire({
                icon: 'warning',
                title: '確認登出',
                text: '是否確認要登出系統？',
                showCancelButton: true,
                confirmButtonText: '確認登出',
                cancelButtonText: '取消',
                customClass: {
                    confirmButton: 'btn btn-danger m-1',
                    cancelButton: 'btn btn-secondary m-1',
                },
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '/admin/logout';
                }
            });
        }
        onMounted(() => {
            document.addEventListener('mousedown', handleClickOutside);
        });
        onBeforeUnmount(() => {
            // 組件卸載時，移除事件監聽器
            document.removeEventListener('mousedown', handleClickOutside);
        });
        return {
            can,
            dropdownVisible,
            toggleDropdown,
            handleClick,
            confirmLogout
        };
    },
};
</script>
