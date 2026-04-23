<template>
  <!-- 動態設定頁面 Head -->
  <Head>
    <title>{{ siteNameFull }} - 後台管理系統</title>
    <template v-if="websiteSettings?.favicon">
      <link rel="shortcut icon" :href="websiteSettings.favicon" head-key="favicon">
      <link rel="icon" type="image/x-icon" :href="websiteSettings.favicon" head-key="icon">
    </template>
    <template v-if="websiteSettings?.website_icon">
      <link rel="apple-touch-icon" :href="websiteSettings.website_icon" head-key="apple-icon">
      <meta property="og:image" :content="websiteSettings.website_icon" head-key="og-image">
    </template>
  </Head>

  <!-- 页面容器 -->
<!--
  以下为 #page-container 可用的类：

  **侧边栏和侧边覆盖层（Sidebar & Side Overlay）**

  - 'sidebar-r'                                 右侧侧边栏和左侧覆盖层（默认为左侧边栏和右侧覆盖层）
  - 'sidebar-mini'                              小型悬停式侧边栏（屏幕宽度 > 991px 时有效）
  - 'sidebar-o'                                 默认可见的侧边栏（屏幕宽度 > 991px 时）
  - 'sidebar-o-xs'                              默认可见的侧边栏（屏幕宽度 < 992px 时）
  - 'sidebar-dark'                              暗色主题侧边栏

  - 'side-overlay-hover'                        悬停显示的侧边覆盖层（屏幕宽度 > 991px 时）
  - 'side-overlay-o'                            默认可见的侧边覆盖层

  - 'enable-page-overlay'                       当侧边覆盖层打开时，启用一个可见且可点击的页面覆盖层（点击关闭覆盖层）

  - 'side-scroll'                               启用自定义滚动条（适用于侧边栏和覆盖层），替代原生滚动条（屏幕宽度 > 991px 时）

  **页头（Header）**

  - ''                                          静态页头（如果未添加任何类，则默认为静态）
  - 'page-header-fixed'                         固定页头

  **页头样式（Header Style）**

  - ''                                          经典页头样式（如果未添加任何类，则为经典样式）
  - 'page-header-modern'                        现代页头样式
  - 'page-header-dark'                          暗色主题页头（仅适用于经典样式）
  - 'page-header-glass'                         默认透明的浅色主题页头
                                               （绝对定位，适合放置在浅色背景图片上——如果页头被设置为固定，则在滚动时变为纯色背景）
  - 'page-header-glass page-header-dark'        默认透明的深色主题页头
                                               （绝对定位，适合放置在深色背景图片上——如果页头被设置为固定，则在滚动时变为深色背景）

  **主要内容布局（Main Content Layout）**

  - ''                                          如果未添加任何类，则主内容全宽显示
  - 'main-content-boxed'                        主内容全宽显示，但在特定最大宽度内（屏幕宽度 > 1200px 时）
  - 'main-content-narrow'                       主内容全宽显示，但按比例宽度（屏幕宽度 > 1200px 时）

  **暗模式（Dark Mode）**

  - 'sidebar-dark page-header-dark dark-mode'   启用暗模式（暗模式不支持浅色的侧边栏或页头）
-->

<div id="page-container" class="sidebar-o sidebar-dark enable-page-overlay side-scroll page-header-modern ">
    <!-- Side Overlay-->

    <aside id="side-overlay" v-if="can('admin.operation-logs')">
      <!-- Side Header -->
      <div class="content-header">
        <!-- User Avatar -->
        <a class="img-link me-2" href="javascript:void(0)">
          <img class="img-avatar img-avatar32" :src="auth.user.img"  alt="">
        </a>
        <!-- END User Avatar -->

        <!-- User Info -->
        <a class="link-fx text-body-color-dark fw-semibold fs-sm" href="javascript:void(0)">
          {{  auth.user.name  }}
        </a>
        <!-- END User Info -->

        <!-- Close Side Overlay -->
        <!-- Layout API, functionality initialized in Template._uiApiLayout() -->
        <button type="button" class="btn btn-sm btn-alt-danger ms-auto" data-toggle="layout" data-action="side_overlay_close">
          <i class="fa fa-fw fa-times"></i>
        </button>
        <!-- END Close Side Overlay -->
      </div>
      <!-- END Side Header -->

      <!-- Side Content -->
      <div class="content-side">
        <p>
          <OperationLog />
        </p>
      </div>
      <!-- END Side Content -->
    </aside>
    <!-- END Side Overlay -->

    <!-- Sidebar -->
    <!--
      Helper classes

      Adding .smini-hide to an element will make it invisible (opacity: 0) when the sidebar is in mini mode
      Adding .smini-show to an element will make it visible (opacity: 1) when the sidebar is in mini mode
        If you would like to disable the transition, just add the .no-transition along with one of the previous 2 classes

      Adding .smini-hidden to an element will hide it when the sidebar is in mini mode
      Adding .smini-visible to an element will show it only when the sidebar is in mini mode
      Adding 'smini-visible-block' to an element will show it (display: block) only when the sidebar is in mini mode
    -->
    <nav id="sidebar">
      <!-- Sidebar Content -->
      <div class="sidebar-content">
        <!-- Side Header -->
        <div class="content-header justify-content-lg-center">
          <!-- Logo -->
          <div>
            <span class="smini-visible fw-bold tracking-wide fs-lg">
              {{ siteNameShort.substring(0, 1) }}<span class="text-primary">{{ siteNameShort.substring(1, 2) }}</span>
            </span>
            <Link class="link-fx fw-bold tracking-wide mx-auto" :href="route('admin.dashboard')">
              <span class="smini-hidden">
                <span class="fs-sm text-dual">{{ siteNameFull }}</span>
              </span>
            </Link>
          </div>
          <!-- END Logo -->

          <!-- Options -->
          <div>
            <!-- Close Sidebar, Visible only on mobile screens -->
            <!-- Layout API, functionality initialized in Template._uiApiLayout() -->
            <button type="button" class="btn btn-sm btn-alt-danger d-lg-none" data-toggle="layout" data-action="sidebar_close">
              <i class="fa fa-fw fa-times"></i>
            </button>
            <!-- END Close Sidebar -->
          </div>
          <!-- END Options -->
        </div>
        <!-- END Side Header -->

        <!-- Sidebar Scrolling -->
        <div class="js-sidebar-scroll">
          <!-- Side User -->
          <div class="content-side content-side-user px-0 py-0">
            <!-- Visible only in mini mode -->
            <div class="smini-visible-block animated fadeIn px-3">
              <img class="img-avatar img-avatar32" :src="auth.user.img"  alt="">
            </div>
            <!-- END Visible only in mini mode -->

            <!-- Visible only in normal mode -->
            <div class="smini-hidden text-center mx-auto">
              <a class="img-link" href="javascript:void(0)">
                <img class="img-avatar" :src="auth.user.img" alt="">
              </a>
              <ul class="list-inline mt-3 mb-0">
                <li class="list-inline-item">
                  <a class="link-fx text-dual fs-sm fw-semibold text-uppercase" href="javascript:void(0)" @click="openModal">{{  auth.user.name  }}</a>
                </li>
                <li class="list-inline-item">
                  <!-- Layout API, functionality initialized in Template._uiApiLayout() -->
                  <a class="link-fx text-dual" data-toggle="layout" data-action="dark_mode_toggle" href="javascript:void(0)">
                    <i class="far fa-fw fa-moon" data-dark-mode-icon></i>
                  </a>
                </li>
                <li class="list-inline-item">
                  <a class="link-fx text-dual" href="javascript:void(0)" @click="confirmLogout">
                    <i class="fa fa-sign-out-alt"></i>
                  </a>
                </li>
              </ul>
            </div>
            <!-- END Visible only in normal mode -->
          </div>
          <!-- END Side User -->

          <!-- Side Navigation -->
            <Menu />
          <!-- END Side Navigation -->
        </div>
        <!-- END Sidebar Scrolling -->
      </div>
      <!-- Sidebar Content -->
    </nav>
    <!-- END Sidebar -->

    <!-- Header -->
    <header id="page-header">
      <!-- Header Content -->
      <div class="content-header">
        <!-- Left Section -->
        <div class="space-x-1">
          <!-- Toggle Sidebar -->
          <!-- Layout API, functionality initialized in Template._uiApiLayout() -->
          <button type="button" class="btn btn-sm btn-alt-secondary" data-toggle="layout" data-action="sidebar_toggle">
            <i class="fa fa-fw fa-bars"></i>
          </button>
          <!-- END Toggle Sidebar -->

          <!-- Open Search Section -->
          <!-- Layout API, functionality initialized in Template._uiApiLayout() -->
          <button style="display: none;" type="button" class="btn btn-sm btn-alt-secondary" data-toggle="layout" data-action="header_search_on">
            <i class="fa fa-fw fa-search"></i>
          </button>
          <!-- END Open Search Section -->

          <!-- Options -->
          <div class="dropdown d-inline-block" >
            <button type="button" style="display: none;" class="btn btn-sm btn-alt-secondary" id="page-header-themes-dropdown" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-haspopup="true" aria-expanded="false">
              <i class="fa fa-fw fa-brush"></i>
            </button>
            <div style="display: none;" class="dropdown-menu dropdown-menu-lg p-0" aria-labelledby="page-header-themes-dropdown">
              <div class="px-3 py-2 bg-body-light rounded-top">
                <h5 class="fs-sm text-center mb-0">
                  Dark Mode
                </h5>
              </div>
              <div class="px-2 py-3">
                <div class="row g-1 text-center">
                  <div class="col-4">
                    <button type="button" class="dropdown-item mb-0 d-flex align-items-center gap-2" data-toggle="layout" data-action="dark_mode_off" data-dark-mode="off">
                      <i class="far fa-sun fa-fw opacity-50"></i>
                      <span class="fs-sm fw-medium">Light</span>
                    </button>
                  </div>
                  <div class="col-4">
                    <button type="button" class="dropdown-item mb-0 d-flex align-items-center gap-2" data-toggle="layout" data-action="dark_mode_on" data-dark-mode="on">
                      <i class="fa fa-moon fa-fw opacity-50"></i>
                      <span class="fs-sm fw-medium">Dark</span>
                    </button>
                  </div>
                  <div class="col-4">
                    <button type="button" class="dropdown-item mb-0 d-flex align-items-center gap-2" data-toggle="layout" data-action="dark_mode_system" data-dark-mode="system">
                      <i class="fa fa-desktop fa-fw opacity-50"></i>
                      <span class="fs-sm fw-medium">System</span>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <!-- END Options -->
        </div>
        <!-- END Left Section -->

        <!-- Right Section -->
        <div class="space-x-1">
          <!-- User Dropdown -->
          <TopMenu :auth="auth" :openModal="openModal"/>
          <!-- END User Dropdown -->
        </div>
        <!-- END Right Section -->
      </div>
      <!-- END Header Content -->

      <!-- Header Search -->
      <div id="page-header-search" class="overlay-header bg-body-extra-light">
        <div class="content-header">
          <form class="w-100" action="/dashboard" method="POST">
            @csrf
            <div class="input-group">
              <!-- Close Search Section -->
              <!-- Layout API, functionality initialized in Template._uiApiLayout() -->
              <button type="button" class="btn btn-secondary" data-toggle="layout" data-action="header_search_off">
                <i class="fa fa-fw fa-times"></i>
              </button>
              <!-- END Close Search Section -->
              <input type="text" class="form-control" placeholder="Search or hit ESC.." id="page-header-search-input" name="page-header-search-input">
              <button type="submit" class="btn btn-secondary">
                <i class="fa fa-fw fa-search"></i>
              </button>
            </div>
          </form>
        </div>
      </div>
      <!-- END Header Search -->

      <!-- Header Loader -->
      <div id="page-header-loader" class="overlay-header bg-primary">
        <div class="content-header">
          <div class="w-100 text-center">
            <i class="far fa-sun fa-spin text-white"></i>
          </div>
        </div>
      </div>
      <!-- END Header Loader -->
    </header>
    <!-- END Header -->

    <!-- Main Container -->
    <main id="main-container">
        <slot />
    </main>
    <!-- END Main Container -->

    <!-- Footer -->
    <footer id="page-footer">
      <div class="content py-3">
        <div class="row fs-sm">
          <div class="col-sm-12 py-1 text-center">
            {{ websiteSettings?.copyright }}
          </div>
        </div>
      </div>
    </footer>
    <Loading v-if="isLoading" />
    <ProfileForm
      :auth="auth"
      ref="profileModel"
    />
    <MenuSettingModal ref="menuSettingModalRef" />
    <!-- END Footer -->
  </div>
  <!-- END Page Container -->
</template>

<script>
    import ProfileForm from  '@/InertiaPages/Admin/Auth/ProfileForm.vue';
    import Menu from  '@/Shared/Admin/Partials/Menu.vue';
    import TopMenu from  '@/Shared/Admin/Partials/TopMenu.vue';
    import Loading from  '@/Shared/Admin/Partials/Loading.vue';
    import OperationLog from '@/Shared/Admin/Partials/OperationLog.vue'
    import NotificationDropdown from '@/Shared/Admin/Partials/NotificationDropdown.vue'
    import MenuSettingModal from '@/Shared/Admin/Partials/MenuSettingModal.vue'
    import { provide, ref, nextTick, computed, inject, onMounted, onUnmounted } from 'vue';
    import { Head, Link } from '@inertiajs/vue3';
    import Swal from 'sweetalert2';
    export default {
        components: {
            Menu,
            TopMenu,
            Loading,
            OperationLog,
            NotificationDropdown,
            ProfileForm,
            MenuSettingModal,
            Head,
            Link,
        },
        props: {
            auth: Object,
            permissions:  Object,
            websiteSettings: Object,
            unreadNotificationCount: {
                type: Number,
                default: 0
            },
        },
        setup(props) {
            // 🔥 使用全域的 isLoading (由 app.js 提供並透過 Inertia router 事件控制)
            const  isLoading = ref(false);
            const notifications = ref([]);
            const showNotifications = ref(false);
            provide('isLoading', isLoading);
            const can = (permission) => {
                return props.permissions.includes(permission);
            };
            provide('can', can);
            // 🔥 不再 provide isLoading，因為已經由 app.js 提供
            const profileModel = ref(null);
            const openModal = () => {
                nextTick(() => {
                    if (profileModel.value) {
                      profileModel.value.openModal();
                    }
                });
            };

            // 計算網站標題的簡短版本（用於mini模式）
            const siteNameShort = computed(() => {
                const title = props.websiteSettings?.title || 'Team';
                return title.length >= 2 ? title.substring(0, 2) : title;
            });

            // 計算完整網站標題
            const siteNameFull = computed(() => {
                return props.websiteSettings?.title || 'Team';
            });

            // ========== Ctrl+F11 選單管理快捷鍵 ==========
            const menuSettingModalRef = ref(null);
            const handleKeydown = (e) => {
                if (e.ctrlKey && e.key === 'F11') {
                    e.preventDefault();
                    e.stopPropagation();
                    menuSettingModalRef.value?.openModal();
                }
            };
            onMounted(() => {
                document.addEventListener('keydown', handleKeydown);
            });
            onUnmounted(() => {
                document.removeEventListener('keydown', handleKeydown);
            });

            const confirmLogout = () => {
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
            };

            return {
              isLoading,
              profileModel,
              openModal,
              can,
              siteNameShort,
              siteNameFull,
              menuSettingModalRef,
              confirmLogout,
            }
        },
    };



</script>
