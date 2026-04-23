<!-- resources/js/InertiaPages/Admin/Radios/Components/RadioEpisodeManager.vue -->
<template>
    <div class="radio-episode-manager">
        <!-- 說明區塊 -->
        <div class="alert alert-info mb-4">
            <h6 class="alert-heading">
                <i class="fa fa-info-circle me-1"></i>
                集數管理說明
            </h6>
            <ul class="mb-0 small">
                <li>目前可管理最多 {{ maxSeasons }} 季的廣播內容，最大季數和基本設定的季數連動</li>
                <li>每季的集數獨立管理，下面功能可以切換季別</li>
                <li>點擊「新增集數」按鈕開始上傳音檔</li>
                <li>表格支援排序，可使用「更新排序」功能調整播放順序</li>
            </ul>
        </div>

        <!-- 集數管理區塊 -->
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
                            v-if="!isSortMode && episodes.length > 1"
                        >
                            <i class="fa fa-sort"></i>
                            更新排序
                        </button>
                        <!-- 新增集數按鈕 -->
                        <button
                            class="btn btn-primary"
                            @click="openAddModal"
                            v-if="can('admin.radios.edit')"
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
                        <strong>排序模式已啟用</strong> - 拖曳表格列來調整播放順序
                        <button class="btn btn-sm btn-success ms-3" @click.stop="saveSort">
                            <i class="fa fa-check"></i> 儲存排序
                        </button>
                        <button class="btn btn-sm btn-secondary ms-2" @click.stop="cancelSort">
                            <i class="fa fa-times"></i> 取消
                        </button>
                    </div>
                </div>

                <!-- 無集數覆蓋層 -->
                <div v-show="episodes.length === 0 && !isLoading" class="text-center py-5">
                    <i class="fa fa-music fa-3x text-muted mb-3"></i>
                    <div>
                        <p class="text-muted">第 {{ currentSeason }} 季尚無集數</p>
                        <button
                            type="button"
                            class="btn btn-primary"
                            @click="openAddModal"
                            v-if="can('admin.radios.edit')"
                        >
                            <i class="fa fa-plus me-1"></i>
                            開始新增集數
                        </button>
                    </div>
                </div>

                <!-- DataTable -->
                <DataTable
                    v-show="episodes.length > 0"
                    class="table table-bordered table-striped table-vcenter js-dataTable-full"
                    :class="{ 'sortable-table': isSortMode }"
                    :columns="tableColumns"
                    :options="tableOptions"
                    ref="dataTable"
                />
            </div>
        </div>

        <!-- 新增/編輯 Modal -->
        <div class="modal fade" id="episodeModal" ref="episodeModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ isEditMode ? '編輯集數' : '新增集數' }}</h5>
                        <button type="button" class="btn-close" @click="closeModal"></button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="saveEpisode">
                            <!-- 季數（唯讀） -->
                            <div class="mb-4">
                                <select class="form-select text-center" disabled>
                                    <option>第 {{ currentSeason }} 季</option>
                                </select>
                            </div>

                            <!-- 音檔上傳 -->
                            <div class="mb-4">
                                <!-- ⭐ 關鍵：用 v-if + :key 強制每次開啟 modal 都重新 mount AudioUploader -->
                                <!-- 這樣可以避免 jFiler 狀態殘留導致第二次上傳卡住 -->
                                <AudioUploader
                                    v-if="uploaderKey"
                                    :key="uploaderKey"
                                    ref="audioUploader"
                                    :upload-url="audioUploadUrl"
                                    :remove-url="audioRemoveUrl"
                                    :extensions="['mp3']"
                                    :max-size="1024"
                                    accept=".mp3,audio/mpeg"
                                    :initial-audio-url="currentAudioUrl"
                                    :initial-file-name="currentAudioFileName"
                                    :is-editing="isEditMode"
                                    @uploaded="onAudioUploaded"
                                    @removed="onAudioRemoved"
                                    @error="onAudioError"
                                />
                                <div v-if="episodeErrors.audio_file" class="text-danger small mt-1">
                                    {{ episodeErrors.audio_file }}
                                </div>
                            </div>

                            <!-- 單集時長 -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">單集時長 (中文)<span class="text-danger">*</span></label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        v-model="episodeForm.duration_text.zh_TW"
                                        placeholder="格式 ex. 66分鐘"
                                        :class="{ 'is-invalid': episodeErrors['duration_text.zh_TW'] }"
                                    />
                                    <div v-if="episodeErrors['duration_text.zh_TW']" class="invalid-feedback">
                                        {{ episodeErrors['duration_text.zh_TW'] }}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">單集時長 (English)<span class="text-danger">*</span></label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        v-model="episodeForm.duration_text.en"
                                        placeholder="e.g. 66 minutes"
                                        :class="{ 'is-invalid': episodeErrors['duration_text.en'] }"
                                    />
                                    <div v-if="episodeErrors['duration_text.en']" class="invalid-feedback">
                                        {{ episodeErrors['duration_text.en'] }}
                                    </div>
                                </div>
                            </div>

                            <!-- 單集簡介 -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">單集簡介 (中文)</label>
                                    <textarea
                                        class="form-control"
                                        rows="3"
                                        v-model="episodeForm.description.zh_TW"
                                        placeholder="選填"
                                        :class="{ 'is-invalid': episodeErrors['description.zh_TW'] }"
                                    ></textarea>
                                    <div v-if="episodeErrors['description.zh_TW']" class="invalid-feedback">
                                        {{ episodeErrors['description.zh_TW'] }}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">單集簡介 (English)</label>
                                    <textarea
                                        class="form-control"
                                        rows="3"
                                        v-model="episodeForm.description.en"
                                        placeholder="Optional"
                                        :class="{ 'is-invalid': episodeErrors['description.en'] }"
                                    ></textarea>
                                    <div v-if="episodeErrors['description.en']" class="invalid-feedback">
                                        {{ episodeErrors['description.en'] }}
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="closeModal">取消</button>
                        <button
                            type="button"
                            class="btn btn-primary"
                            @click="saveEpisode"
                            :disabled="isSaving"
                        >
                            <span v-if="isSaving">
                                <i class="fa fa-spinner fa-spin me-1"></i>
                                處理中...
                            </span>
                            <span v-else>
                                <i class="fa fa-save me-1"></i>
                                儲存
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 音訊播放器（隱藏） -->
        <audio ref="audioPlayer" class="d-none"></audio>
    </div>
</template>

<script setup>
import { ref, computed, inject, onMounted, onBeforeUnmount, nextTick } from 'vue'
import axios from 'axios'
import DataTablesCore from "datatables.net-bs5"
import DataTable from "datatables.net-vue3"
import DataTableHelper from "@/utils/datatableHelper"
import Sortable from 'sortablejs'
import { Modal } from 'bootstrap'
import AudioUploader from '@/Plugin/AudioUploader.vue'

DataTable.use(DataTablesCore)

// Props
const props = defineProps({
    radioId: {
        type: [Number, String, null],
        required: false,
        default: null
    },
    maxSeasons: {
        type: Number,
        default: 1
    }
})

// Emits
const emit = defineEmits(['episode-season-changed'])

// Inject
const sweetAlert = inject('$sweetAlert')
const can = inject('can')
const isLoading = inject('isLoading')

// Refs
const dataTable = ref(null)
const episodeModal = ref(null)
const audioUploader = ref(null)
const audioPlayer = ref(null)

// State
const currentSeason = ref(1)
const episodes = ref([])
const isSortMode = ref(false)
const isEditMode = ref(false)
const isSaving = ref(false)
const editingEpisodeId = ref(null)
// 原始音檔（從後端載入的，不會被刪除操作清除）
const originalAudioUrl = ref('')
const originalAudioFileName = ref('')
// 當前顯示的音檔（會隨上傳/刪除而變化）
const currentAudioUrl = ref('')
const currentAudioFileName = ref('')
// 新上傳的音檔路徑
const uploadedAudioPath = ref('')
// ⭐ 關鍵：用 key 強制 AudioUploader 重新 mount，避免 jFiler 狀態殘留
const uploaderKey = ref(null)
const sortableInstance = ref(null)
const modalInstance = ref(null)
const nextEpisodeNumber = ref(1)
const currentPlayingId = ref(null)

// 表單資料
const episodeForm = ref({
    episode_number: 1,
    duration_text: {
        zh_TW: '',
        en: ''
    },
    description: {
        zh_TW: '',
        en: ''
    },
    is_active: true
})

// 表單錯誤
const episodeErrors = ref({})

// Computed
const availableSeasons = computed(() => {
    return Array.from({ length: props.maxSeasons }, (_, i) => i + 1)
})

// 音訊上傳/移除 URL（使用統一的暫存區路由）
const audioUploadUrl = computed(() => {
    return route('admin.uploads.tmp.upload')
})

const audioRemoveUrl = computed(() => {
    return route('admin.uploads.tmp.remove')
})

// API 路由
const getApiRoute = (action, id = null) => {
    const base = `/admin/radios/${props.radioId}/episodes`
    switch (action) {
        case 'index':
            return base
        case 'store':
            return base
        case 'show':
            // 讀取單筆集數不需要 radio 參數，直接用 episode ID
            return `/admin/episodes/${id}`
        case 'update':
            // 更新集數不需要 radio 參數，直接用 episode ID
            return `/admin/episodes/${id}`
        case 'destroy':
            // 刪除路由不需要 radio 參數，直接用 episode ID
            return `/admin/episodes/${id}`
        case 'sort':
            // 排序路由不需要 radio 參數，從 request body 取得 radio_id 和 season
            return `/admin/episodes/sort`
        case 'next-episode-number':
            return `${base}/next-episode-number`
        default:
            return base
    }
}

// DataTable 欄位配置
const tableColumns = computed(() => {
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
            title: '集數',
            data: 'episode_number',
            className: 'text-center',
            width: '80px',
            render: (data) => `第 ${data} 集`
        },
        {
            title: '音檔',
            data: null,
            className: 'text-center',
            orderable: false,
            width: '80px',
            render: (data, type, row) => {
                if (isSortMode.value) return '<span class="text-muted">---</span>'
                if (row.audio_url) {
                    return `
                        <button type="button" class="btn btn-sm btn-success js-bs-tooltip-enabled play-btn"
                                data-bs-toggle="tooltip" aria-label="播放音檔" data-bs-title="播放音檔"
                                data-id="${row.id}" data-url="${row.audio_url}">
                            <i class="fa fa-play"></i>
                        </button>
                    `
                }
                return '<span class="text-muted">無音檔</span>'
            }
        },
        {
            title: '時長',
            data: 'duration_text_zh',
            className: 'text-center',
            orderable: false,
            width: '120px',
            render: (data) => {
                return data || '-'
            }
        },
        {
            title: '簡介',
            data: 'description_zh',
            orderable: false,
            width: '200px',
            render: (data) => {
                if (!data) return '-'
                // 截斷過長文字
                if (data.length > 50) {
                    return data.substring(0, 50) + '...'
                }
                return data
            }
        },
        {
            title: '更新時間',
            data: 'updated_at',
            className: 'text-center',
            orderable: true,
            width: '150px',
            render: (data) => data || '-'
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
                if (can('admin.radios.edit')) {
                    btns += `
                    <button type="button" class="btn btn-sm btn-info js-bs-tooltip-enabled edit-btn me-2"
                            data-bs-toggle="tooltip" aria-label="編輯" data-bs-title="編輯"
                            data-id="${data.id}">
                        <i class="fa fa-edit"></i>
                    </button>`
                }
                if (can('admin.radios.delete')) {
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
    lengthMenu: [10, 25, 50, 100],
    order: [[1, 'asc']], // 按集數排序
    ajax: (data, callback) => {
        const extraParams = {
            radio_id: props.radioId,
            season: currentSeason.value
        }

        DataTableHelper.fetchTableData(
            getApiRoute('index'),
            data,
            callback,
            episodes,
            "",
            extraParams,
            (error) => {
                console.error('DataTable 載入失敗:', error)
            },
            true // useAjax = true
        )
    },
    drawCallback: function() {
        DataTableHelper.defaultDrawCallback()
        bindButtonEvents()
        if (isSortMode.value) initSortable()
    }
}))

// 綁定按鈕事件
const bindButtonEvents = () => {
    // 編輯按鈕
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.getAttribute('data-id')
            editEpisode(id)
        })
    })

    // 刪除按鈕
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.getAttribute('data-id')
            confirmDelete(id)
        })
    })

    // 播放按鈕
    document.querySelectorAll('.play-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.getAttribute('data-id')
            const url = btn.getAttribute('data-url')
            playAudio(id, url)
        })
    })
}

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
            // 更新排序後的順序
            const newOrder = Array.from(tbody.querySelectorAll('tr')).map(tr => {
                const handle = tr.querySelector('.sort-handle')
                return handle?.getAttribute('data-id')
            }).filter(Boolean)

            // 重新排列 episodes 陣列
            const reordered = newOrder.map(id =>
                episodes.value.find(ep => ep.id == id)
            ).filter(Boolean)
            episodes.value = reordered
        }
    })
}

// 季數變更
const onSeasonChange = () => {
    reloadData()
    fetchNextEpisodeNumber()
}

// 重新載入資料
const reloadData = async (resetPaging = false) => {
    const instance = dataTable.value?.dt
    if (instance && typeof instance.ajax?.reload === 'function') {
        instance.ajax.reload(null, resetPaging)
    }
}

// 取得下一個集數編號
const fetchNextEpisodeNumber = async () => {
    try {
        const response = await axios.get(getApiRoute('next-episode-number'), {
            params: { season: currentSeason.value }
        })
        nextEpisodeNumber.value = response.data.next_episode_number || 1
    } catch (error) {
        console.error('取得下一個集數編號失敗:', error)
        nextEpisodeNumber.value = 1
    }
}

// 開啟新增 Modal
const openAddModal = async () => {
    await fetchNextEpisodeNumber()

    isEditMode.value = false
    editingEpisodeId.value = null

    // 清除原始音檔（新增模式不需要）
    originalAudioUrl.value = ''
    originalAudioFileName.value = ''

    // 清除當前音檔
    currentAudioUrl.value = ''
    currentAudioFileName.value = ''
    uploadedAudioPath.value = ''
    episodeErrors.value = {}

    episodeForm.value = {
        episode_number: nextEpisodeNumber.value,
        duration_text: {
            zh_TW: '',
            en: ''
        },
        description: {
            zh_TW: '',
            en: ''
        },
        is_active: true
    }

    // ⭐ 關鍵：用 uploaderKey 強制重新 mount AudioUploader（新增模式）
    uploaderKey.value = Date.now()
    openModal()
}

// 編輯集數
const editEpisode = async (id) => {
    try {
        isLoading.value = true
        const response = await axios.get(getApiRoute('show', id))
        // Laravel Resource 的回應格式是 { data: { ... } }
        const episode = response.data.data || response.data

        isEditMode.value = true
        editingEpisodeId.value = id

        // 原始音檔（從後端來的，不會被刪除操作清除）
        originalAudioUrl.value = episode.audio_url || ''
        originalAudioFileName.value = episode.audio_path ? episode.audio_path.split('/').pop() : ''

        // 當前綁給子元件的值（初始就是原始）
        currentAudioUrl.value = originalAudioUrl.value
        currentAudioFileName.value = originalAudioFileName.value

        uploadedAudioPath.value = ''
        episodeErrors.value = {}

        episodeForm.value = {
            episode_number: episode.episode_number,
            duration_text: {
                zh_TW: episode.duration_text?.zh_TW || '',
                en: episode.duration_text?.en || ''
            },
            description: {
                zh_TW: episode.description?.zh_TW || '',
                en: episode.description?.en || ''
            },
            is_active: episode.is_active ?? true
        }

        // ⭐ 關鍵：用 uploaderKey 強制重新 mount AudioUploader（編輯模式）
        uploaderKey.value = Date.now()
        openModal()
    } catch (error) {
        console.error('載入集數資料失敗:', error)
        sweetAlert?.error({ msg: '載入集數資料失敗，請重試！' })
    } finally {
        isLoading.value = false
    }
}

// 開啟 Modal
const openModal = () => {
    if (!modalInstance.value && episodeModal.value) {
        modalInstance.value = new Modal(episodeModal.value)
    }
    modalInstance.value?.show()
}

// 關閉 Modal
const closeModal = () => {
    return new Promise((resolve) => {
        if (modalInstance.value) {
            const handleHidden = () => {
                // ⭐ 關鍵：在 modal 關閉後卸載 uploader，不初始化、不 reset
                // 透過 v-if="uploaderKey" 的機制，設為 null 會自動卸載組件
                uploaderKey.value = null

                episodeModal.value?.removeEventListener('hidden.bs.modal', handleHidden)
                resolve()
            }

            episodeModal.value?.addEventListener('hidden.bs.modal', handleHidden)
            modalInstance.value.hide()
        } else {
            resolve()
        }
    })
}

// AudioUploader 事件處理
const onAudioUploaded = (data) => {
    // 儲存上傳後的路徑（TempUploadController 回傳 video_file_path）
    uploadedAudioPath.value = data.video_file_path || data.path || data.file || ''
    episodeErrors.value.audio_file = ''
}

const onAudioRemoved = () => {
    // 清除新上傳的音檔路徑
    uploadedAudioPath.value = ''

    if (isEditMode.value && originalAudioUrl.value) {
        // 編輯模式且有原始音檔：回到「只用原始音檔」的狀態
        currentAudioUrl.value = originalAudioUrl.value
        currentAudioFileName.value = originalAudioFileName.value
    } else {
        // 新增模式 或 編輯模式但本來就沒有音檔
        currentAudioUrl.value = ''
        currentAudioFileName.value = ''
    }
}

const onAudioError = () => {
    sweetAlert?.error({ msg: '音訊上傳失敗，請重試！' })
}

// 驗證表單
const validateForm = () => {
    episodeErrors.value = {}
    let isValid = true

    if (!episodeForm.value.episode_number || episodeForm.value.episode_number < 1) {
        episodeErrors.value.episode_number = '請輸入有效的集數編號'
        isValid = false
    }

    // 驗證時長（必填）
    if (!episodeForm.value.duration_text.zh_TW?.trim()) {
        episodeErrors.value['duration_text.zh_TW'] = '請輸入中文時長'
        isValid = false
    }

    if (!episodeForm.value.duration_text.en?.trim()) {
        episodeErrors.value['duration_text.en'] = '請輸入英文時長'
        isValid = false
    }

    // 簡介為選填，不進行驗證

    // 音檔驗證：無論新增或編輯，都必須有音檔
    // 新增模式：必須有上傳的音檔
    // 編輯模式：必須有原始音檔（後端載入）或 上傳新的音檔
    const hasAudio = isEditMode.value
        ? (Boolean(originalAudioUrl.value) || Boolean(uploadedAudioPath.value))
        : Boolean(uploadedAudioPath.value)

    if (!hasAudio) {
        episodeErrors.value.audio_file = '請上傳 MP3 音檔'
        isValid = false
    }

    return isValid
}

// 儲存集數
const saveEpisode = async () => {
    if (!validateForm()) {
        return
    }

    // 使用 SweetAlert2 確認對話框
    sweetAlert?.confirm('確定要儲存嗎？', async () => {
        isSaving.value = true
        isLoading.value = true

        try {
            const data = {
                radio_id: props.radioId,  // 重要：傳送 radio_id，讓編輯時可以更新
                episode_number: episodeForm.value.episode_number,
                duration_text: {
                    zh_TW: episodeForm.value.duration_text.zh_TW,
                    en: episodeForm.value.duration_text.en
                },
                description: {
                    zh_TW: episodeForm.value.description.zh_TW,
                    en: episodeForm.value.description.en
                },
                is_active: episodeForm.value.is_active ? 1 : 0,
                season: currentSeason.value
            }

            // 如果有上傳新音檔，加入路徑
            if (uploadedAudioPath.value) {
                data.audio_path = uploadedAudioPath.value
            }

            let response
            if (isEditMode.value) {
                response = await axios.put(getApiRoute('update', editingEpisodeId.value), data)
            } else {
                response = await axios.post(getApiRoute('store'), data)
            }

            const result = response.data
          
            // 使用 resultData 統一處理成功/失敗訊息
            sweetAlert?.resultData(result, null, () => {
                if (result.status) {
                    closeModal();
                    reloadData(true)
                    fetchNextEpisodeNumber()

                    // 觸發操作紀錄更新事件
                    window.dispatchEvent(new CustomEvent('operationLogUpdated'))

                    // 通知父組件季數變更，讓基本設定同步更新
                    emit('episode-season-changed', currentSeason.value)
                }
            })
        } catch (error) {
            if (error.response?.data?.errors) {
                // 處理驗證錯誤
                const errors = error.response.data.errors
                for (const key in errors) {
                    episodeErrors.value[key] = errors[key][0]
                }
            } else {
                sweetAlert?.error({ msg: error.response?.data?.message || '儲存失敗，請重試！' })
            }
        } finally {
            isSaving.value = false
            isLoading.value = false
        }
    })
}

// 確認刪除
const confirmDelete = (id) => {
    sweetAlert?.deleteConfirm('確定要刪除此集數嗎？', async () => {
        try {
            isLoading.value = true
            const response = await axios.delete(getApiRoute('destroy', id))
            const result = response.data

            if (result.status || result.success) {
                sweetAlert?.success({ msg: result.msg || result.message || '刪除成功' })
                reloadData(true)
                fetchNextEpisodeNumber()

                // 觸發操作紀錄更新事件
                window.dispatchEvent(new CustomEvent('operationLogUpdated'))

                // 通知父組件季數變更（刪除後可能影響季數限制）
                emit('episode-season-changed', currentSeason.value)
            } else {
                sweetAlert?.error({ msg: result.msg || result.message || '刪除失敗' })
            }
        } catch (error) {
            console.error('刪除集數失敗:', error)
            sweetAlert?.error({ msg: error.response?.data?.message || '刪除失敗，請重試！' })
        } finally {
            isLoading.value = false
        }
    })
}

// 播放音檔
const playAudio = (id, url) => {
    if (!audioPlayer.value || !url) return

    // 如果正在播放相同的，則暫停
    if (currentPlayingId.value === id && !audioPlayer.value.paused) {
        audioPlayer.value.pause()
        currentPlayingId.value = null
        updatePlayButton(id, false)
        return
    }

    // 重置之前的播放按鈕
    if (currentPlayingId.value) {
        updatePlayButton(currentPlayingId.value, false)
    }

    // 播放新的
    audioPlayer.value.src = url
    audioPlayer.value.play()
    currentPlayingId.value = id
    updatePlayButton(id, true)
}

// 更新播放按鈕狀態
const updatePlayButton = (id, isPlaying) => {
    const btn = document.querySelector(`.play-btn[data-id="${id}"]`)
    if (btn) {
        const icon = btn.querySelector('i')
        if (icon) {
            icon.className = isPlaying ? 'fa fa-pause' : 'fa fa-play'
        }
    }
}

// 切換排序模式
const toggleSort = () => {
    isSortMode.value = true
    reloadData()
}

// 取消排序
const cancelSort = () => {
    isSortMode.value = false
    reloadData()
}

// 儲存排序
const saveSort = async () => {
    try {
        isLoading.value = true

        const sortData = episodes.value.map((ep, index) => ({
            id: ep.id,
            order: index + 1
        }))

        const response = await axios.post(getApiRoute('sort'), {
            radio_id: props.radioId,  // 可能是 null（暫存集數）
            season: currentSeason.value,
            sort_data: sortData
        })
        const result = response.data

        if (result.status || result.success) {
            sweetAlert?.success({ msg: result.msg || result.message || '排序更新成功' })
            isSortMode.value = false
            reloadData()

            // 觸發操作紀錄更新事件
            window.dispatchEvent(new CustomEvent('operationLogUpdated'))
        } else {
            sweetAlert?.error({ msg: result.msg || result.message || '排序更新失敗' })
        }
    } catch (error) {
        sweetAlert?.error({ msg: error.response?.data?.message || '排序更新失敗，請重試！' })
    } finally {
        isLoading.value = false
    }
}

// 監聽音訊結束事件
const onAudioEnded = () => {
    if (currentPlayingId.value) {
        updatePlayButton(currentPlayingId.value, false)
        currentPlayingId.value = null
    }
}

// 生命週期
onMounted(async () => {
    await nextTick()

    // 初始化 DataTable
    if (dataTable.value) {
        const created = await DataTableHelper.createDataTable(dataTable.value, () => {
            isLoading.value = false
        })
    }

    // 取得下一個集數編號
    fetchNextEpisodeNumber()

    // 綁定音訊結束事件
    if (audioPlayer.value) {
        audioPlayer.value.addEventListener('ended', onAudioEnded)
    }
})

onBeforeUnmount(() => {
    // 銷毀 DataTable
    if (dataTable.value?.dt) {
        try {
            dataTable.value.dt.destroy()
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

    // 銷毀 Modal
    if (modalInstance.value) {
        try {
            modalInstance.value.dispose()
        } catch (error) {
            console.warn('清理 Modal 時發生��誤:', error)
        }
    }

    // 停止音訊播放
    if (audioPlayer.value) {
        audioPlayer.value.pause()
        audioPlayer.value.removeEventListener('ended', onAudioEnded)
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

.sort-handle {
    cursor: grab;
}

.sort-handle:active {
    cursor: grabbing;
}
</style>
