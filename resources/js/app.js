import './bootstrap';
import { createApp, h, ref } from 'vue';
import { createInertiaApp, Link } from '@inertiajs/vue3';
import { ZiggyVue } from '../../vendor/tightenco/ziggy/dist/index.esm'
import { Ziggy } from './ziggy'
import "bootstrap/dist/js/bootstrap.bundle.min.js";
import SweetAlertPlugin from './utils/sweetalert2Plugin';
import PrimeVue from 'primevue/config';
import Aura from '@primeuix/themes/aura';
import '../sass/main.scss';

// 修正 Slim 的 touchmove passive 警告
if (typeof window !== 'undefined') {
    const originalAddEventListener = EventTarget.prototype.addEventListener;

    EventTarget.prototype.addEventListener = function(type, listener, options) {
        // 對於 touchmove 事件，預設使用 passive: true
        if (type === 'touchmove' && typeof options !== 'object') {
            options = { passive: true };
        } else if (type === 'touchmove' && typeof options === 'object' && !options.hasOwnProperty('passive')) {
            options = { ...options, passive: true };
        }

        return originalAddEventListener.call(this, type, listener, options);
    };

    // 添加 Slim 觸控優化 CSS
    document.addEventListener('DOMContentLoaded', () => {
        if (!document.querySelector('#slim-touch-fix')) {
            const style = document.createElement('style');
            style.id = 'slim-touch-fix';
            style.textContent = `
                .slim-area { touch-action: manipulation; }
                .slim-crop-area { touch-action: none; }
                .slim-image-area { touch-action: pan-x pan-y; }
            `;
            document.head.appendChild(style);
        }
    });
}

// loading 狀態
const isLoading = ref(false);

createInertiaApp({
  resolve: name => {
    // 先從 Modules 目錄尋找
    const modulePages = import.meta.glob('../../Modules/**/Vue/**/*.vue', { eager: true });
    
    // 將 Admin/HomeImageSetting/Form 轉換為對應的 Module 路徑
    // 例如: Admin/HomeImageSetting/Form => ../../Modules/HomeImageSetting/Vue/Form.vue
    const parts = name.split('/');
    if (parts[0] === 'Admin' && parts.length >= 2) {
      const moduleName = parts[1];
      const componentPath = parts.slice(2).join('/') || 'Form';
      const modulePath = `../../Modules/${moduleName}/Vue/${componentPath}.vue`;
      
      if (modulePages[modulePath]) {
        return modulePages[modulePath];
      }
    }
    
    // 如果不是模組或模組中找不到，則從 InertiaPages 目錄尋找
    const pages = import.meta.glob('./InertiaPages/**/*.vue', { eager: true })
    return pages[`./InertiaPages/${name}.vue`]
  },
  setup({ el, App, props, plugin }) {
    createApp({ render: () => h(App, props) })
      .use(plugin)
      .component('Link', Link)
      .use(ZiggyVue, Ziggy)
      .provide('$sweetAlert', SweetAlertPlugin)
      .provide('isLoading', isLoading)
      .use(PrimeVue, {
        theme: {
            preset: Aura,
        }
      })
      .mount(el);
  },
});

// Inertia loading 狀態管理
// 注意：不要全域監聽 router 事件，因為不是所有操作都需要 loading 動畫
// 需要 loading 時，在 formUtils.js 或各組件中手動控制 isLoading.value
