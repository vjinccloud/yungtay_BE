import Swal from 'sweetalert2';

const sweetAlertMethods = {
  // 安全轉換為字串（完整防護）
  safeString(value) {
    try {
      if (typeof value === 'string') return value;
      if (typeof value === 'object' && value !== null) {
        const cache = new Set();
        return JSON.stringify(value, (key, val) => {
          if (typeof val === 'object' && val !== null) {
            if (cache.has(val)) return '[Circular]';
            cache.add(val);
          }
          return val;
        }, 2);
      }
      return String(value ?? '');
    } catch {
      return '[Unserializable Object]';
    }
  },

  isInModal() {
    return document.querySelector('.modal.show') !== null;
  },

  getTargetContainer() {
    const activeModal = document.querySelector('.modal.show');
    if (activeModal) return activeModal;
    const pageContainer = document.querySelector('#page-container');
    return pageContainer ? '#page-container' : 'body';
  },

  resultData(result, router, callback) {
    return result.status
      ? this.success(result, router, callback)
      : this.error(result, router, callback);
  },

  success(result, _router, callback) {
    if (typeof result === 'string') result = { msg: result };
    const config = {
      allowOutsideClick: false,
      buttonsStyling: false,
      target: this.getTargetContainer(),
      customClass: {
        confirmButton: 'btn btn-primary m-1',
        input: 'form-control',
      },
    };
    if (this.isInModal()) {
      config.heightAuto = false;
      config.backdrop = false;
      config.customClass.container = 'swal2-modal-container';
      delete config.target;
    }
    const toast = Swal.mixin(config);
    const fireOptions = result.text
      ? { title: this.safeString(result.msg), text: this.safeString(result.text), icon: 'success' }
      : { title: this.safeString(result.msg), icon: 'success' };

    return toast.fire(fireOptions).then(() => {
      if (typeof callback === 'function') callback();
      else if (result.redirect) {
        const isInertiaContext = window.$inertia || (window.history.state && window.history.state.component);
        if (isInertiaContext) {
          import('@inertiajs/vue3').then(({ router }) => router.get(result.redirect));
        } else location.href = result.redirect;
      }
    });
  },

  error(result, _router, callback) {
    if (typeof result === 'string') result = { status: false, msg: result };
    const config = {
      allowOutsideClick: false,
      buttonsStyling: false,
      target: this.getTargetContainer(),
      customClass: {
        confirmButton: 'btn btn-danger m-1',
        input: 'form-control',
      },
    };
    if (this.isInModal()) {
      config.heightAuto = false;
      config.backdrop = false;
      config.customClass.container = 'swal2-modal-container';
      delete config.target;
    }
    const toast = Swal.mixin(config);
    let error = result?.msg;
    if (typeof error === 'object') {
      try {
        error = Object.values(error).flat().join('\n');
      } catch {
        error = '[Unserializable Error Object]';
      }
    }
    const fireOptions = result?.text
      ? { title: this.safeString(error), text: this.safeString(result.text), icon: 'warning' }
      : { title: this.safeString(error), icon: 'warning' };

    return toast.fire(fireOptions).then(() => {
      if (typeof callback === 'function') callback();
      else if (result.redirect) {
        const isInertiaContext = window.$inertia || (window.history.state && window.history.state.component);
        if (isInertiaContext) {
          import('@inertiajs/vue3').then(({ router }) => router.get(result.redirect, {}, { preserveState: true }));
        } else location.href = result.redirect;
      }
    });
  },

  confirm(title, callback, message) {
    const currentLocale = document.documentElement.lang || 'zh_TW';
    const confirmText = currentLocale === 'en' ? 'Confirm' : '確定';
    const cancelText = currentLocale === 'en' ? 'Cancel' : '取消';
    const config = {
      icon: 'warning',
      title,
      text: message,
      showCancelButton: true,
      confirmButtonText: confirmText,
      cancelButtonText: cancelText,
      customClass: {
        confirmButton: 'btn btn-primary m-1',
        cancelButton: 'btn btn-secondary m-1',
      },
      allowOutsideClick: false,
    };
    if (this.isInModal()) {
      config.heightAuto = false;
      config.backdrop = false;
      config.customClass.container = 'swal2-modal-container';
      config.target = this.getTargetContainer();
    }
    return Swal.fire(config).then((r) => r.isConfirmed && callback());
  },

  deleteConfirm(title, callback) {
    const config = {
      icon: 'warning',
      title,
      text: '刪除後不可回復',
      showCancelButton: true,
      confirmButtonText: '確定刪除',
      cancelButtonText: '取消',
      customClass: {
        confirmButton: 'btn btn-danger m-1',
        cancelButton: 'btn btn-secondary m-1',
      },
    };
    if (this.isInModal()) {
      config.heightAuto = false;
      config.backdrop = false;
      config.customClass.container = 'swal2-modal-container';
      config.target = this.getTargetContainer();
    }
    return Swal.fire(config).then((r) => r.isConfirmed && callback());
  },

  showToast(message, type = 'success') {
    const config = {
      icon: type,
      title: message,
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 3000,
      timerProgressBar: true,
      showClass: { popup: 'swal2-show' },
      hideClass: { popup: 'swal2-hide' },
      background: '#fff',
    };
    if (this.isInModal()) config.target = this.getTargetContainer();
    return Swal.fire(config);
  },
};

export default sweetAlertMethods;
