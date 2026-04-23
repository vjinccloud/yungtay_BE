<!-- resources/js/InertiaPages/Admin/Shared/Content/Components/BaseVideoUpload.vue -->
<template>
    <div class="video-upload-manager p-4" :data-content-type="contentType">
        <!-- 說明區塊 -->
        <div class="alert alert-info mb-4">
            <h6 class="alert-heading">
                <i class="fa fa-info-circle me-1"></i>
                集數管理說明
            </h6>
            <ul class="mb-0 small">
                <li>目前可管理最多 {{ maxSeasons }} 季的集數內容，最大季數和基本設定的季數連動</li>
                <li>每季的集數獨立管理，下面功能可以切換季別</li>
                <li>點擊「新增集數」按鈕開始上傳影片</li>
                <li>表格支援排序，可使用「更新排序」功能調整集數播放順序</li>
            </ul>
        </div>

        <!-- 影音管理區塊 -->
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <div class="row w-100 align-items-center">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center">
                            <label class="form-label me-3 mb-0">選擇季數：</label>
                            <select
                                v-model="currentSeason"
                                class="form-select"
                                style="width: 150px;"
                                @change="onSeasonChange"
                            >
                                <option
                                    v-for="season in availableSeasons"
                                    :key="season"
                                    :value="season"
                                >
                                    第 {{ season }} 季
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <!-- 排序按鈕 -->
                        <button
                            class="btn btn-info me-2"
                            @click="toggleSort"
                            v-if="!isSortMode && currentSeasonVideos.length > 1"
                        >
                            <i class="fa fa-sort"></i>
                            更新排序
                        </button>
                        <!-- 新增影片按鈕 -->
                        <button
                            class="btn btn-primary"
                            @click="openAddModal"
                        >
                            <i class="fa-solid fa-plus me-1"></i>
                            新增集數
                        </button>
                    </div>
                </div>
            </div>

            <div class="block-content block-content-full">
                <!-- 排序模式提示 -->
                <div class="alert alert-info d-flex align-items-center" v-if="isSortMode">
                    <i class="fa fa-info-circle me-2"></i>
                    <div>
                        <strong>排序模式已啟用</strong> - 拖曳表格列來調整影片播放順序
                        <button class="btn btn-sm btn-success ms-3" @click.stop="saveSort">
                            <i class="fa fa-check"></i> 儲存排序
                        </button>
                        <button class="btn btn-sm btn-secondary ms-2" @click.stop="cancelSort">
                            <i class="fa fa-times"></i> 取消
                        </button>
                    </div>
                </div>

                <!-- 無影片覆蓋層 -->
                <div v-show="currentSeasonVideos.length === 0" class="text-center py-5">
                    <i class="fa fa-play-circle fa-3x text-muted mb-3"></i>
                    <div>
                        <p class="text-muted">第 {{ currentSeason }} 季尚無影片</p>
                        <button
                            type="button"
                            class="btn btn-primary"
                            @click="openAddModal"
                        >
                            <i class="fa fa-plus me-1"></i>
                            開始新增影片
                        </button>
                    </div>
                </div>

                <!-- DataTable -->
                <DataTable
                    v-show="currentSeasonVideos.length > 0"
                    class="table table-bordered table-striped table-vcenter js-dataTable-full"
                    :class="{ 'sortable-table': isSortMode }"
                    :columns="tableColumns"
                    :options="tableOptions"
                    ref="dataTable"
                />
            </div>
        </div>

        <!-- 引入 Modal 元件 -->
        <BaseVideoForm
            ref="videoFormModal"
            :content-type="contentType"
            :content-id="contentId"
            :current-season="currentSeason"
            :next-seq="nextEpisodeNumber"
            @reload="reloadData"
        >
            <!-- 可以透過 slot 傳入額外欄位 -->
            <template #extra-fields="{ form }">
                <slot name="extra-form-fields" :form="form"></slot>
            </template>
        </BaseVideoForm>

        <!-- 播放 Modal -->
        <BaseVideoPlayer
            ref="playerModal"
            :video="currentPlayVideo"
            @close="onPlayerModalClose"
        />
    </div>
</template>

<script setup>
import { ref, computed, reactive, nextTick, inject, onMounted, watch, onBeforeUnmount } from 'vue'
import { router } from '@inertiajs/vue3'
import DataTablesCore from "datatables.net-bs5"
import DataTable from "datatables.net-vue3"
import DataTableHelper from "@/utils/datatableHelper"
import { useVideo } from '@/composables/video'
import BaseVideoForm from './BaseVideoForm.vue'
import BaseVideoPlayer from './BaseVideoPlayer.vue'
import Sortable from 'sortablejs'
import axios from 'axios'

DataTable.use(DataTablesCore)

// Props
const props = defineProps({
    contentType: {
        type: String,
        required: true,
        validator: (value) => ['drama', 'program'].includes(value)
    },
    contentId: {
        type: [Number, null],
        default: null
    },
    maxSeasons: {
        type: Number,
        default: 10
    },
    videoData: {
        type: Array,
        default: () => []
    }
})

// Emits
const emit = defineEmits(['update:videoData', 'video-change'])

// 使用 useVideo composable
const {
    currentConfig,
    table,
    dt,
    rows,
    columns,
    isSortMode,
    currentSeason,
    isLoading,
    initializeDataTable,
    reloadTable,
    toggleSortMode,
    cancelSortMode,
    saveSortOrder,
    getNextEpisodeNumber,
    deleteVideo,
    getApiRoute
} = useVideo(props.contentType, props.contentId)

// Refs
const dataTable = ref(null)
const videoFormModal = ref(null)
const playerModal = ref(null)
const sortableInstance = ref(null)
const originalOrder = ref([])

// State
const currentPlayVideo = ref(null)
const availableSeasons = computed(() => {
    return Array.from({ length: props.maxSeasons }, (_, i) => i + 1)
})

// 當前季數的影片
const currentSeasonVideos = computed(() => {
    return rows.value.filter(video => 
        !video.season || video.season === currentSeason.value
    )
})

// 下一個集數編號
const nextEpisodeNumber = computed(() => {
    return getNextEpisodeNumber()
})

// 權限
const can = inject('can')
// SweetAlert
const sweetAlert = inject('$sweetAlert')

// DataTable 欄位配置（對齊原版欄位與按鈕 class）
const tableColumns = computed(() => {
    const permissionPrefix = props.contentType === 'drama' ? 'admin.dramas' : 'admin.programs'

    const formatTitleWithSeq = (title, seq) => {
        if (!title) return `第${seq}集`
        const cleanTitle = title.replace(/^第\d+集\s*/, '')
        return `第${seq}集 ${cleanTitle}`
    }

    return [
        {
            title: '#',
            data: null,
            className: 'text-center',
            orderable: false,
            width: '60px',
            render: (data, type, row, meta) => {
                if (isSortMode.value) {
                    return `
                        <div class="sort-handle" data-id="${row.id}" style="cursor: move; padding: 8px;">
                            <i class="fa fa-grip-vertical text-primary"></i>
                        </div>
                    `
                }
                return meta.settings._iDisplayStart + meta.row + 1
            },
        },
        {
            title: '標題',
            data: 'episode_title',
            width: '250px',
            render: (data, type, row, meta) => {
                if (isSortMode.value) {
                    const pos = meta.row + 1
                    return formatTitleWithSeq(data, pos)
                }
                const seq = row.seq || (meta.row + 1)
                return formatTitleWithSeq(data, seq)
            },
        },
        {
            title: '影片來源',
            data: 'video_source',
            className: 'text-center',
            width: '120px',
            render: (data, type, row) => {
                const badgeClass = row.video_type === 'youtube' ? 'bg-danger' : 'bg-info'
                return `<span class="badge ${badgeClass}">${data}</span>`
            },
        },
        {
            title: '時長',
            data: 'duration_text_zh',
            className: 'text-center',
            width: '100px',
        },
        {
            title: '簡介',
            data: 'description_zh',
            width: '200px',
            render: (data) => {
                if (!data) return '-'
                return data.length > 50 ? data.substring(0, 50) + '...' : data
            },
        },
        {
            title: '播放',
            data: null,
            className: 'text-center',
            orderable: false,
            width: '80px',
            render: (data, type, row) => {
                if (isSortMode.value) return '<span class="text-muted">---</span>'
                if (row.video_url || row.youtube_url) {
                    return `
                        <button type="button" class="btn btn-sm btn-success js-bs-tooltip-enabled play-btn"
                                data-bs-toggle="tooltip" aria-label="播放影片" data-bs-title="播放影片"
                                data-id="${row.id}">
                            <i class="fa fa-play"></i>
                        </button>
                    `
                }
                return '<span class="text-muted">無影片</span>'
            },
        },
        {
            title: '更新時間',
            data: 'updated_at',
            className: 'text-center',
            width: '160px',
        },
        {
            title: '功能',
            data: null,
            orderable: false,
            width: '120px',
            className: 'text-center',
            render: (data) => {
                if (isSortMode.value) return '<span class="text-muted">---</span>'
                let btns = ''
                if (can(`${permissionPrefix}.edit`)) {
                    btns += `
                    <button type="button" class="btn btn-sm btn-info js-bs-tooltip-enabled edit-btn me-2"
                            data-bs-toggle="tooltip" aria-label="編輯" data-bs-title="編輯"
                            data-id="${data.id}">
                        <i class="fa fa-edit"></i>
                    </button>`
                }
                if (can(`${permissionPrefix}.delete`)) {
                    btns += `
                    <button type="button" class="btn btn-sm btn-danger js-bs-tooltip-enabled delete-btn"
                            data-bs-toggle="tooltip" aria-label="刪除" data-bs-title="刪除"
                            data-id="${data.id}">
                        <i class="fa-solid fa-trash"></i>
                    </button>`
                }
                return btns
            },
        },
    ]
})

// DataTable 選項
const tableOptions = computed(() => ({
    ...DataTableHelper.getBaseOptions(),
    responsive: true,
    searching: false,
    pageLength: 10,
    // 與預設 Helper 一致，顯示 10 筆
    lengthMenu: [10, 25, 50, 100],
    order: [[0, 'asc']],
    // 純 AJAX 模式（不改網址），並由 helper 轉成 DataTables 所需格式
    ajax: (data, callback) => {
        const extraParams = {
            [currentConfig.value.contentField]: props.contentId ?? null,
            season: currentSeason.value
        }

        DataTableHelper.fetchTableData(
            getApiRoute('index'),
            data,
            callback,
            rows,
            "",              // responseKey 不需要（AJAX 模式）
            extraParams,
            (error) => {
                console.error('DataTable 載入失敗:', error)
            },
            true              // useAjax = true（純 AJAX）
        )
    },
    drawCallback: function() {
        DataTableHelper.defaultDrawCallback()
        DataTableHelper.bindTableButtonEvents({
            edit: editVideo,
            delete: confirmDeleteVideo,
            play: playVideo,
        })
        if (isSortMode.value) initSortable()
    }
}))

//（事件統一交由 DataTableHelper.bindTableButtonEvents）

// 初始化排序
const initSortable = async () => {
    await nextTick()
    
    const tbody = dataTable.value?.$el?.querySelector('tbody')
    if (!tbody) return
    
    if (sortableInstance.value) {
        sortableInstance.value.destroy()
    }
    
    sortableInstance.value = Sortable.create(tbody, {
        handle: '.sort-handle',
        animation: 150,
        onEnd: (evt) => {
            const newOrder = Array.from(tbody.querySelectorAll('tr')).map(tr => {
                const rowIdx = dt.value.row(tr).index()
                return rows.value[rowIdx]
            })
            rows.value = newOrder
        }
    })
}

// 方法
const openAddModal = () => {
    if (videoFormModal.value) {
        videoFormModal.value.openModal()
    }
}

const editVideo = (id) => {
    // 使用 axios 從後端取得影片資料
    axios.get(getApiRoute('show', id))
        .then((response) => {
            const rowData = response.data; // 後端回傳的 JSON 資料
            // 呼叫 VideoForm 的 editModal 方法
            if (videoFormModal.value) {
                videoFormModal.value.editModal(rowData);
            }
        })
        .catch((error) => {
            console.error('載入影片資料失敗:', error);
            sweetAlert?.error({
                msg: '載入影片資料失敗，請重試！'
            });
        });
}

const playVideo = (id) => {
    console.log('BaseVideoUpload playVideo 被調用，ID:', id, typeof id);
    console.log('rows IDs:', rows.value.map(row => ({id: row.id, type: typeof row.id})));
    
    // 使用 == 而不是 === 來處理型別差異
    const video = rows.value.find(row => row.id == id)
    console.log('找到的影片:', video);
    
    if (video && playerModal.value) {
        currentPlayVideo.value = video
        console.log('設定 currentPlayVideo.value:', currentPlayVideo.value);
        playerModal.value.openModal()
        console.log('呼叫 openModal 完成');
    } else {
        console.error('找不到影片或播放器 modal');
    }
}

const confirmDeleteVideo = (id) => {
    if (sweetAlert) {
        sweetAlert.deleteConfirm('確定要刪除此影片嗎？', () => {
            // 顯示 loading
            isLoading.value = true

            router.delete(getApiRoute('destroy', id), {
                onStart: () => {
                    // 開始請求時顯示 loading
                    isLoading.value = true
                },
                onSuccess: (response) => {
                    const result = response.props.flash?.result;
                    if (result?.status) {
                        sweetAlert.success(result);
                        // 刪除後重置分頁回第一頁
                        reloadData({ resetPaging: true, action: 'delete' })
                    }
                },
                onError: () => {
                    sweetAlert.error({ msg: '刪除失敗，請重試！' });
                },
                onFinish: () => {
                    // 完成後關閉 loading
                    isLoading.value = false
                }
            });
        })
    }
}

const onSeasonChange = () => {
    reloadData()
}

const reloadData = async (options = {}) => {
    const { resetPaging = false, action = 'reload' } = options
    const instance = dataTable.value?.dt || dt.value
    if (instance && typeof instance.ajax?.reload === 'function') {
        instance.ajax.reload(null, !!resetPaging)
    } else {
        await initDataTable()
    }
    // 攜帶動作與季數，避免上層收到 undefined
    emit('video-change', { action, season: currentSeason.value })
}


const onPlayerModalClose = () => {
    currentPlayVideo.value = null
}

const toggleSort = () => {
    toggleSortMode()
    reloadData()
}

const cancelSort = () => {
    cancelSortMode()
    reloadData()
}

const saveSort = async () => {
    try {
        const result = await saveSortOrder()
        // saveSortOrder 內部已經處理 sweetAlert 和重新載入
        // 不需要重複處理
    } catch (error) {
        // saveSortOrder 內部已經處理錯誤
        console.error('排序更新失敗:', error)
    }
}

// 初始化 DataTable
const initDataTable = async () => {
    if (!dataTable.value) return
    
    isLoading.value = true
    
    // 檢查並銷毀現有的 DataTable 實例
    if (dt.value) {
        try {
            // 檢查是否有 destroy 方法
            if (typeof dt.value.destroy === 'function') {
                dt.value.destroy()
            } else if (dataTable.value?.dt && typeof dataTable.value.dt.destroy === 'function') {
                // 嘗試從元素的 dt 屬性銷毀
                dataTable.value.dt.destroy()
            }
        } catch (error) {
            console.warn('銷毀 DataTable 時發生錯誤，忽略並繼續:', error)
        }
        dt.value = null
    }
    
    await nextTick()
    
    const created = await DataTableHelper.createDataTable(dataTable.value, () => {
        isLoading.value = false
    })
    // created 可能為 undefined/null，如果 DataTable 尚未就緒
    if (created) {
        dt.value = created
    } else if (dataTable.value?.dt) {
        dt.value = dataTable.value.dt
    }
    
    // 設定到 composable 的 table
    table.value = dataTable.value
}

// 生命週期
onMounted(() => {
    nextTick(() => {
        initDataTable()
    })
})

onBeforeUnmount(() => {
    // 銷毀 DataTable
    if (dt.value) {
        try {
            if (typeof dt.value.destroy === 'function') {
                dt.value.destroy()
            } else if (dataTable.value?.dt && typeof dataTable.value.dt.destroy === 'function') {
                dataTable.value.dt.destroy()
            }
        } catch (error) {
            console.warn('清理 DataTable 時發生錯誤:', error)
        }
    }
    
    // 銷毀 Sortable
    if (sortableInstance.value) {
        try {
            sortableInstance.value.destroy()
        } catch (error) {
            console.warn('清理 Sortable 時發生錯誤:', error)
        }
    }
})

// 暴露方法
defineExpose({
    reloadData,
    openAddModal
})
</script>

<style scoped>
.sortable-table tbody tr {
    cursor: move;
}

.sortable-table tbody tr:hover {
    background-color: #f8f9fa;
}

.drag-handle {
    cursor: grab;
}

.drag-handle:active {
    cursor: grabbing;
}
</style>