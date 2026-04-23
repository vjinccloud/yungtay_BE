/**
 * DataTable 搜尋功能 Composable
 *
 * 專門處理後台 DataTable 的搜尋相關功能：
 * - 搜尋參數管理（reactive）
 * - 搜尋表單展開/收合
 * - 搜尋執行與重置
 * - 搜尋相關的 DataTable 初始化
 *
 * 使用範例：
 * const { searchParams, filterExpanded, toggleFilter, searchData, resetSearch } = useDataTableSearch({
 *   search: '',
 *   city_id: '',
 *   is_active: ''
 * })
 */

import { ref, reactive, nextTick } from 'vue'
import DataTableHelper from '@/utils/datatableHelper'

export function useDataTableSearch(initialParams = {}, options = {}) {
    // 搜尋相關狀態
    const filterExpanded = ref(false)
    const searchParams = reactive({
        ...initialParams
    })

    // DataTable 相關
    const table = ref(null)
    const dt = ref(null)
    const rows = ref([])

    // 配置選項
    const {
        route: searchRoute,
        dataKey = 'data',
        defaultOrder = [[0, 'desc']]
    } = options

    /**
     * 切換搜尋區塊顯示/隱藏
     */
    const toggleFilter = () => {
        filterExpanded.value = !filterExpanded.value
    }

    /**
     * 執行搜尋
     */
    const searchData = () => {
        if (dt.value && dt.value.ajax) {
            dt.value.ajax.reload()
        }
    }

    /**
     * 重置搜尋條件
     */
    const resetSearch = () => {
        // 重置所有搜尋參數
        Object.keys(searchParams).forEach(key => {
            searchParams[key] = ''
        })

        // 重載 DataTable
        if (dt.value) {
            if (typeof dt.value.search === 'function') {
                dt.value.search('').draw()
            }
            if (dt.value.ajax) {
                dt.value.ajax.reload()
            }
        }
    }

    /**
     * 重載表格
     */
    const reloadTable = () => {
        try {
            if (dt.value && typeof dt.value.ajax?.reload === 'function') {
                dt.value.ajax.reload(null, false)
            } else if (table.value?.dt && typeof table.value.dt.ajax?.reload === 'function') {
                table.value.dt.ajax.reload(null, false)
            } else {
                console.warn('DataTable 實例不存在或沒有 reload 方法，重新初始化')
                initializeDataTable()
            }
        } catch (error) {
            console.error('重載表格時發生錯誤:', error)
        }
    }

    /**
     * 初始化 DataTable
     */
    const initializeDataTable = async (columns, additionalOptions = {}) => {
        try {
            // 等待 DOM 完全載入
            await nextTick()

            if (!table.value) {
                console.error('Table element not found')
                return
            }

            // 基本 DataTable 配置
            const baseOptions = {
                ...DataTableHelper.getBaseOptions(),
                searching: false, // 關閉原生搜尋
                columns,
                order: defaultOrder,
                ajax: (data, callback) => {
                    const searchData = {
                        ...data,
                        search_params: searchParams
                    }

                    if (searchRoute) {
                        DataTableHelper.fetchTableData(
                            searchRoute,
                            searchData,
                            callback,
                            rows,
                            dataKey,
                            searchParams
                        )
                    }
                },
                ...additionalOptions
            }

            // 直接使用 DataTableHelper.createDataTable 並傳入完整 options
            dt.value = await DataTableHelper.createDataTable(table.value, baseOptions)

            // 檢查並清除預設搜尋內容
            if (dt.value && typeof dt.value.search === 'function') {
                dt.value.search('').draw()
            } else {
                console.warn('DataTable search method not available')
            }
        } catch (error) {
            console.error('DataTable 初始化失敗:', error)
        }
    }

    /**
     * 建立 DataTable 配置選項
     */
    const createDataTableOptions = (columns, additionalOptions = {}) => {
        return reactive({
            ...DataTableHelper.getBaseOptions(),
            searching: false, // 關閉原生搜尋
            columns,
            order: defaultOrder,
            ajax: (data, callback) => {
                const searchData = {
                    ...data,
                    search_params: searchParams
                }

                if (searchRoute) {
                    DataTableHelper.fetchTableData(
                        searchRoute,
                        searchData,
                        callback,
                        rows,
                        dataKey,
                        searchParams
                    )
                }
            },
            ...additionalOptions
        })
    }

    return {
        // 狀態
        filterExpanded,
        searchParams,
        table,
        dt,
        rows,

        // 方法
        toggleFilter,
        searchData,
        resetSearch,
        reloadTable,
        initializeDataTable,
        createDataTableOptions
    }
}