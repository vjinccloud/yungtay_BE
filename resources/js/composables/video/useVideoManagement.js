// resources/js/composables/video/useVideoManagement.js
// 這個 composable 實際上應該重新命名或重構
// 因為 BaseBasicInfo 使用的是 useContentManagement
// 這裡建立一個 alias 或轉接層

import { useContentManagement } from '../content/useContentManagement'

// 匯出為 useVideoManagement 以符合命名規範
export function useVideoManagement(contentType, props) {
    // 直接使用 useContentManagement 的所有功能
    return useContentManagement(contentType, props)
}

// 也可以擴展功能
export function useExtendedVideoManagement(contentType, props) {
    const contentManagement = useContentManagement(contentType, props)
    
    // 可以在這裡加入額外的集數管理相關邏輯
    const videoSpecificLogic = () => {
        // 影片特定的業務邏輯
    }
    
    return {
        ...contentManagement,
        videoSpecificLogic
    }
}