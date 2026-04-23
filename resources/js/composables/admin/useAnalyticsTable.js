import { ref, reactive, inject, watch } from 'vue';
import DataTableHelper from '@/utils/datatableHelper';

/**
 * Analytics 報表通用 DataTable Composable
 *
 * 功能：
 * - 統一管理 DataTable 的初始化邏輯
 * - 提供可重用的表格配置
 * - 支援自訂欄位定義
 * - 統一數字格式化
 * - 支援跨 Tab 共享分頁設定
 *
 * @param {Object} options - 配置選項
 * @param {Array} options.columns - DataTable 欄位定義
 * @param {Object} options.props - 父組件的 props (包含 categories, dateRange)
 * @param {string} options.routeName - API 路由名稱（若需要 AJAX 模式）
 * @param {Object} options.tableConfig - 額外的 DataTable 配置
 * @returns {Object} - 回傳 table refs 和初始化方法
 */
export function useAnalyticsTable(options = {}) {
    const {
        columns = [],
        props = {},
        routeName = null,
        tableConfig = {},
        defaultOrder = [[2, 'desc']],  // 預設排序：可由各 Tab 自訂
    } = options;

    // 注入共享的分頁設定
    const sharedPageLength = inject('sharedPageLength', ref(10));

    // DataTable refs
    const table = ref(null);
    const dt = ref(null);
    const rows = ref([]);

    // 防止重複初始化的標記
    let hasInitialized = false;

    /**
     * 格式化數字：加入千分位逗號
     * @param {number} num - 要格式化的數字
     * @return {string} - 格式化後的字串
     */
    const formatNumber = (num) => {
        if (!num && num !== 0) return '0';
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    };

    /**
     * 建立基礎的 DataTable 配置
     */
    const createTableOptions = () => {
        const baseOptions = DataTableHelper.getBaseOptions();

        return reactive({
            ...baseOptions,
            // 如果有提供 routeName，使用 AJAX 模式
            ...(routeName ? {
                ajax: (data, callback) => {
                    const extraParams = {
                        start_date: props.dateRange?.start,
                        end_date: props.dateRange?.end,
                    };

                    // ✅ 如果有 parent_category_id 篩選，加入到 search_params
                    const urlParams = new URLSearchParams(window.location.search);
                    const parentCategoryId = urlParams.get('search_params[parent_category_id]');

                    if (parentCategoryId) {
                        extraParams.search_params = {
                            parent_category_id: parseInt(parentCategoryId),
                        };
                    }

                    DataTableHelper.fetchTableData(
                        route(routeName),
                        data,
                        callback,
                        rows,
                        "categories",
                        extraParams
                    );
                },
            } : {}),
            // Server-side 模式：啟用排序功能
            ordering: true,
            serverSide: true,
            // ✅ 關閉 stateSave：統計報表每次查詢條件不同，不應保存狀態
            stateSave: false,
            // 預設排序：由各 Tab 自訂
            order: defaultOrder,
            // 分頁設定（使用共享狀態）
            pageLength: sharedPageLength.value,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            // 關閉搜尋功能
            searching: false,
            // drawCallback 處理
            drawCallback: function () {
                try {
                    // ✅ 只在 nTableWrapper 存在時才執行基礎 drawCallback
                    if (this?.nTableWrapper && baseOptions.drawCallback) {
                        baseOptions.drawCallback.call(this);
                    }
                } catch (error) {
                    console.warn('drawCallback error:', error);
                }

                // 當用戶改變分頁數量時，同步到共享狀態
                try {
                    if (this.api && sharedPageLength.value !== this.api().page.len()) {
                        sharedPageLength.value = this.api().page.len();
                    }
                } catch (error) {
                    console.warn('page length sync error:', error);
                }
            },
            // 合併自訂配置
            ...tableConfig,
        });
    };

    /**
     * 初始化 DataTable
     */
    const initializeDataTable = () => {
        if (!table.value) {
            console.warn('Table ref is not available');
            return;
        }

        // ✅ 防止重複初始化
        if (hasInitialized) {
            console.warn('DataTable already initialized');
            return;
        }
        hasInitialized = true;

        // ✅ 清除舊的 DataTable 狀態（避免 stateSave 殘留）
        const tableId = table.value.$el?.id || 'DataTables';
        Object.keys(localStorage).forEach(key => {
            if (key.includes(tableId) || key.includes('DataTables_analytics')) {
                localStorage.removeItem(key);
            }
        });

        DataTableHelper.createDataTable(table.value, (dataTable) => {
            dt.value = dataTable;

            // 監聽共享分頁設定的變化，同步更新 DataTable
            watch(sharedPageLength, (newLength) => {
                if (dt.value && dt.value.page.len() !== newLength) {
                    dt.value.page.len(newLength).draw(false);
                }
            });

            // ✅ 監聽日期區間和 URL 參數變化，手動觸發 DataTable reload（顯示 loading 動畫）
            // 注意：不跳過第一次 watch，因為 Inertia 模式下首次載入也需要正確的參數
            watch(
                () => ({
                    start: props.dateRange?.start,
                    end: props.dateRange?.end,
                    parentCategoryId: new URLSearchParams(window.location.search).get('search_params[parent_category_id]')
                }),
                (newValues, oldValues) => {
                    // ✅ 移除「跳過第一次」的邏輯
                    // 因為 Inertia 會在參數變化時重新掛載組件
                    // 此時 DataTable 已經完成首次初始化，只需要 reload 資料

                    // 如果 DataTable 還沒初始化完成，則跳過
                    if (!dt.value) {
                        return;
                    }

                    // 如果有舊值，檢查是否真的改變了
                    if (oldValues) {
                        const hasChanged =
                            newValues.start !== oldValues.start ||
                            newValues.end !== oldValues.end ||
                            newValues.parentCategoryId !== oldValues.parentCategoryId;

                        if (!hasChanged) {
                            return;
                        }
                    }

                    // 執行 reload（顯示 loading 動畫）
                    dt.value.processing(true);
                    dt.value.ajax.reload(() => {
                        dt.value.processing(false);
                    }, false);  // false = 保持當前頁碼
                },
                { deep: true, flush: 'post' }  // flush: 'post' 確保在 DOM 更新後執行
            );
        });
    };

    /**
     * 建立標準欄位定義（序號 + 分類名稱）
     */
    const createBaseColumns = () => {
        return [
            {
                title: "#",
                data: null,
                className: "text-center",
                orderable: false,
                width: "50px",
                render: (_data, _type, _row, meta) =>
                    meta.settings._iDisplayStart + meta.row + 1,
            },
            {
                title: "分類名稱",
                data: "category_name",
                width: "180px",
                orderable: true,  // 可排序（實際會使用 seq 排序）
                render: (data) => data || "未知分類",
            },
        ];
    };

    /**
     * 建立數字欄位定義
     * @param {string} title - 欄位標題
     * @param {string} dataKey - 資料鍵值
     * @param {string} width - 欄位寬度
     */
    const createNumberColumn = (title, dataKey, width = "120px") => {
        return {
            title,
            data: dataKey,
            width,
            className: "text-end",
            orderable: true,  // 所有數字欄位都可排序
            render: (data) => formatNumber(data),
        };
    };

    // 建立表格配置
    const tableOptions = createTableOptions();

    return {
        // Refs
        table,
        dt,
        rows,
        // 表格配置
        tableOptions,
        // 工具方法
        formatNumber,
        initializeDataTable,
        createBaseColumns,
        createNumberColumn,
    };
}
