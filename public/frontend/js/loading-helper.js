/**
 * Loading Helper - 獨立的 Loading 控制器
 * 可在 Vue 初始化前後都使用
 */
window.LoadingHelper = (function() {
    let loadingElement = null;
    let loadingCount = 0;
    let isInitialized = false;
    
    // 創建 Loading HTML
    function createLoadingElement() {
        const div = document.createElement('div');
        div.className = 'loading';
        div.id = 'global-loading';
        div.style.display = 'none';
        div.innerHTML = `
            <div class="dots">
                <span></span>
                <span></span>
                <span></span>
            </div>
        `;
        document.body.appendChild(div);
        return div;
    }
    
    // 初始化
    function init() {
        if (isInitialized) return;
        
        loadingElement = document.getElementById('global-loading');
        if (!loadingElement) {
            loadingElement = createLoadingElement();
        }
        
        isInitialized = true;
        
        // 注意：已改為手動控制，不再自動攔截 AJAX 或 Fetch 請求
        // 如需顯示 loading，請使用 window.showLoading() 和 window.hideLoading()
    }
    
    // 顯示 Loading
    function show(text) {
        if (!isInitialized) init();
        
        loadingCount++;
        
        if (loadingElement) {
            loadingElement.style.display = 'flex';
            
            // 如果有提供文字，可以顯示
            if (text && loadingElement.querySelector('.loading-text')) {
                loadingElement.querySelector('.loading-text').textContent = text;
            }
        }
        
        // 如果 Vue Loading 存在，也觸發它
        if (window.$loading && window.$loading.showLoading) {
            window.$loading.showLoading(text);
        }
        
    }
    
    // 隱藏 Loading
    function hide() {
        if (!isInitialized) return;
        
        loadingCount = Math.max(0, loadingCount - 1);
        
        if (loadingCount === 0 && loadingElement) {
            // 使用淡出效果
            if (typeof $ !== 'undefined') {
                $(loadingElement).fadeOut(300);
            } else {
                setTimeout(() => {
                    if (loadingElement) {
                        loadingElement.style.display = 'none';
                    }
                }, 300);
            }
        }
        
        // 如果 Vue Loading 存在，也觸發它
        if (window.$loading && window.$loading.hideLoading) {
            window.$loading.hideLoading();
        }
        
    }
    
    // 強制隱藏
    function forceHide() {
        loadingCount = 0;
        if (loadingElement) {
            loadingElement.style.display = 'none';
        }
        
        if (window.$loading && window.$loading.forceHideLoading) {
            window.$loading.forceHideLoading();
        }
    }
    
    // 自動初始化
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    // 返回公開 API
    return {
        init: init,
        show: show,
        hide: hide,
        forceHide: forceHide
    };
})();

// 為了方便使用，創建全域快捷方式
window.showLoading = function(text) {
    window.LoadingHelper.show(text);
};

window.hideLoading = function() {
    window.LoadingHelper.hide();
};