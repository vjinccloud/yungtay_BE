
// src/utils/scriptLoader.js - ES6 模組版本的 ScriptLoader
const loadedResources = new Map();
const loadingPromises = new Map();

export class ScriptLoader {
    constructor(options = {}) {
        this.options = {
            timeout: 30000,
            retries: 2,
            retryDelay: 1000,
            ...options
        };
    }
    
    async loadJs(src, attributes = {}) {
        // 檢查快取
        if (loadedResources.has(src)) {
            return { 
                src, 
                cached: true,
                element: loadedResources.get(src) 
            };
        }
        
        // 檢查是否正在載入
        if (loadingPromises.has(src)) {
            return loadingPromises.get(src);
        }
        
        // 建立載入 Promise
        const loadPromise = this._loadResource('script', src, attributes);
        loadingPromises.set(src, loadPromise);
        
        try {
            const result = await loadPromise;
            return result;
        } finally {
            loadingPromises.delete(src);
        }
    }
    
    async loadCss(href, attributes = {}) {
        if (loadedResources.has(href)) {
            return { 
                href, 
                cached: true,
                element: loadedResources.get(href) 
            };
        }
        
        if (loadingPromises.has(href)) {
            return loadingPromises.get(href);
        }
        
        const loadPromise = this._loadResource('link', href, attributes);
        loadingPromises.set(href, loadPromise);
        
        try {
            const result = await loadPromise;
            return result;
        } finally {
            loadingPromises.delete(href);
        }
    }
    
    _loadResource(type, url, attributes = {}, retriesLeft = this.options.retries) {
        return new Promise((resolve, reject) => {
            let element;
            let timeoutId;
            
            // 建立元素
            if (type === 'script') {
                element = document.createElement('script');
                element.src = url;
                element.type = 'text/javascript';
            } else if (type === 'link') {
                element = document.createElement('link');
                element.href = url;
                element.rel = 'stylesheet';
                element.type = 'text/css';
            }
            
            // 設定屬性
            Object.entries(attributes).forEach(([key, value]) => {
                element.setAttribute(key, value);
            });
            
            // 超時處理
            timeoutId = setTimeout(() => {
                cleanup();
                reject(new Error(`載入超時: ${url}`));
            }, this.options.timeout);
            
            const cleanup = () => {
                clearTimeout(timeoutId);
                element.onload = null;
                element.onerror = null;
            };
            
            // 成功處理
            element.onload = () => {
                cleanup();
                loadedResources.set(url, element);
                resolve({ url, cached: false, element });
            };
            
            // 錯誤處理與重試邏輯
            element.onerror = () => {
                cleanup();
                
                if (retriesLeft > 0) {
                    console.warn(`載入失敗，重試中... (剩餘 ${retriesLeft} 次): ${url}`);
                    
                    setTimeout(() => {
                        element.remove();
                        this._loadResource(type, url, attributes, retriesLeft - 1)
                            .then(resolve)
                            .catch(reject);
                    }, this.options.retryDelay);
                } else {
                    element.remove();
                    reject(new Error(`載入失敗: ${url}`));
                }
            };
            
            // 加入 DOM
            document.head.appendChild(element);
        });
    }
}

// 建立預設實例
const defaultLoader = new ScriptLoader();

// 匯出便利函數
export const loadJs = (src, attrs) => defaultLoader.loadJs(src, attrs);
export const loadCss = (href, attrs) => defaultLoader.loadCss(href, attrs);

