/**
 * 統一的影音管理 Composable
 * 整合所有影音相關功能：管理、驗證、上傳、表格、播放
 */

import { ref, computed, reactive, nextTick, onBeforeUnmount, inject } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import { FormValidator, useSubmitForm } from '@/utils'
import DataTableHelper from '@/utils/datatableHelper'
import axios from 'axios'
import { Modal } from 'bootstrap'
import Sortable from 'sortablejs'

export function useVideo(contentType, contentId = null) {
    // ========================
    // 依賴注入
    // ========================
    const sweetAlert = inject('$sweetAlert')
    const isLoading = inject('isLoading')
    
    // ========================
    // 配置設定
    // ========================
    const config = {
        drama: {
            apiPrefix: 'dramas',
            contentField: 'drama_id',
            contentName: '影音',
            routePrefix: 'admin.dramas-episodes'
        },
        program: {
            apiPrefix: 'programs',
            contentField: 'program_id',
            contentName: '節目',
            routePrefix: 'admin.programs-episodes'
        }
    }

    const currentConfig = computed(() => config[contentType] || config.drama)

    // ========================
    // 表單管理
    // ========================
    const form = useForm({
        id: null,
        [currentConfig.value.contentField]: contentId,
        season: 1,
        seq: 1,
        video_type: '',
        youtube_url: '',
        video_file: '',
        video_file_path: '',
        original_filename: '',
        file_size: '',
        video_format: '',
        duration_text: {
            zh_TW: '',
            en: ''
        },
        description: {
            zh_TW: '',
            en: ''
        },
        confirm: false
    })

    const isEditing = ref(false)
    const uploadType = ref('youtube')

    // ========================
    // 上傳管理
    // ========================
    const uploadProgress = ref(0)
    const isUploading = ref(false)
    const uploadError = ref(null)
    const currentFile = ref(null)

    const uploadConfig = {
        maxSize: 5 * 1024 * 1024 * 1024, // 5GB
        allowedExtensions: ['mp4', 'mov', 'avi', 'wmv', 'flv'],
        chunkSize: 2 * 1024 * 1024 // 2MB chunks
    }

    // ========================
    // 表格管理
    // ========================
    const table = ref(null)
    const dt = ref(null)
    const rows = ref([])
    const isSortMode = ref(false)
    const sortableInstance = ref(null)
    const originalOrder = ref([])
    const currentSeason = ref(1)

    // ========================
    // 播放器管理
    // ========================
    const isPlaying = ref(false)
    const currentVideo = ref(null)
    const playerRef = ref(null)
    const playerModal = ref(null)

    // ========================
    // Modal 管理
    // ========================
    const modalForm = ref(null)
    const videoUploaderRef = ref(null)
    let modalInstance = null

    // ========================
    // API 路由方法
    // ========================
    const getApiRoute = (action, id = null) => {
        // 特殊處理 index 路由，因為它不存在，改用 admin 路由
        if (action === 'index') {
            // 使用 admin 路由來獲取資料
            const adminRoute = contentType === 'drama' 
                ? 'admin.dramas-episodes' 
                : 'admin.programs-episodes'
            return route(adminRoute)
        }
        
        // 特殊處理 destroy -> delete (因為路由實際名稱是 delete)
        if (action === 'destroy') {
            action = 'delete'
        }
        
        const routeName = `${currentConfig.value.routePrefix}.${action}`
        return id ? route(routeName, id) : route(routeName)
    }

    // ========================
    // 表單方法
    // ========================
    const resetForm = (season = 1) => {
        form.id = null
        form[currentConfig.value.contentField] = contentId
        form.season = season
        form.seq = 1
        form.video_type = ''
        form.video_file = ''
        form.youtube_url = ''
        form.video_file_path = ''
        form.original_filename = ''
        form.file_size = ''
        form.video_format = ''
        form.duration_text = { zh_TW: '', en: '' }
        form.description = { zh_TW: '', en: '' }
        form.confirm = false
        form.clearErrors()
        isEditing.value = false
        uploadType.value = 'youtube'
    }

    const setEditData = (data, season = 1) => {
        isEditing.value = true
        form.id = data.id
        form.season = data.season || season
        form.youtube_url = data.youtube_url || ''
        form.video_file_path = data.video_file_path || ''
        form.original_filename = data.original_filename || ''
        form.file_size = data.file_size || ''
        form.video_format = data.video_format || ''

        // ✅ 自動判定 video_type：如果資料庫為空，根據實際內容判斷
        if (data.video_type) {
            form.video_type = data.video_type
        } else {
            // 如果有 youtube_url 就是 youtube，否則是 upload
            form.video_type = data.youtube_url ? 'youtube' : 'upload'
        }

        form.duration_text = {
            zh_TW: data.duration_text?.zh_TW || '',
            en: data.duration_text?.en || ''
        }
        form.description = {
            zh_TW: data.description?.zh_TW || '',
            en: data.description?.en || ''
        }

        // 同步 uploadType
        uploadType.value = form.video_type
    }

    // ========================
    // 驗證方法
    // ========================
    const getValidationRules = () => ({
        youtube_url: [uploadType.value === 'youtube' ? 'required' : ''],
        video_file: [uploadType.value === 'upload' && !isEditing.value ? 'required' : ''],
        'duration_text.zh_TW': ['required', 'string', ['max', 50]],
        'duration_text.en': ['required', 'string', ['max', 50]],
        'description.zh_TW': ['required', 'string'],
        'description.en': ['required', 'string']
    })

    // ========================
    // 上傳方法
    // ========================
    const validateFile = (file) => {
        if (file.size > uploadConfig.maxSize) {
            return {
                valid: false,
                error: `檔案大小不能超過 ${uploadConfig.maxSize / (1024 * 1024 * 1024)}GB`
            }
        }

        const extension = file.name.split('.').pop().toLowerCase()
        if (!uploadConfig.allowedExtensions.includes(extension)) {
            return {
                valid: false,
                error: `只允許上傳 ${uploadConfig.allowedExtensions.join(', ')} 格式的檔案`
            }
        }

        return { valid: true }
    }

    const handleFileUploadSuccess = (data) => {
        form.video_file = data.filename
        form.video_file_path = data.video_file_path
        form.video_type = data.video_type
        form.original_filename = data.original_filename || ''
        form.file_size = data.file_size
        form.video_format = data.video_format
        form.clearErrors('video_file_path')
    }

    const handleFileRemove = () => {
        form.video_file = null
        form.video_file_path = null
        form.video_type = ''
        form.original_filename = ''
        form.file_size = ''
        form.video_format = ''
        form.clearErrors('video_file_path')
    }

    // ========================
    // CRUD 操作
    // ========================
    const { submitForm: performSubmit } = useSubmitForm()

    const submitForm = async (emit, closeModal) => {
        form.clearErrors()
        form.confirm = false

        const validator = new FormValidator(form, getValidationRules)
        const hasErrors = await validator.hasErrors()

        if (!hasErrors) {
            const method = isEditing.value ? 'put' : 'post'
            const url = isEditing.value
                ? getApiRoute('update', form.id)
                : getApiRoute('store')

            // ✅ 移除這裡的 emit('reload')，避免在用戶確認前就觸發表格更新
            // emit('reload') 已在 formUtils.js 的成功回調中處理（用戶點擊確認後）

            performSubmit({
                form,
                url,
                method,
                emit,
                closeModal
            })

            return true
        }

        return false
    }

    const deleteVideo = async (id) => {
        try {
            const response = await axios.delete(getApiRoute('destroy', id))
            return response.data
        } catch (error) {
            console.error('刪除影片失敗:', error)
            throw error
        }
    }

    const sortVideos = async (items, season = null) => {
        return new Promise((resolve, reject) => {
            sweetAlert?.confirm('確定更新集數排序嗎？', () => {
                // 開始 loading
                isLoading.value = true
                
                router.put(route(`${currentConfig.value.routePrefix}.sort`), 
                    { 
                        ids: items,
                        [currentConfig.value.contentField]: contentId,
                        season
                    }, 
                    {
                        onSuccess: (finalRes) => {
                            try {
                                const res = finalRes.props.flash?.result || finalRes.props.result;
                                if (res && res.status) {
                                    sweetAlert?.resultData(res);
                                    resolve(res)
                                } else {
                                    sweetAlert?.error({ msg: '排序更新失敗，請重試！' });
                                    reject(new Error('排序更新失敗'))
                                }
                            } catch (error) {
                                console.error('處理排序回應時發生錯誤:', error);
                                sweetAlert?.error({ msg: '處理回應時發生錯誤' });
                                reject(error)
                            }
                        },
                        onError: (errors) => {
                            console.error('排序請求失敗:', errors);
                            sweetAlert?.error({ msg: '排序更新失敗，請重試！' });
                            reject(new Error('排序請求失敗'))
                        },
                        onFinish: () => {
                            // 結束 loading
                            isLoading.value = false
                        }
                    }
                )
            })
        })
    }

    // ========================
    // 表格方法
    // ========================
    const columns = [
        {
            data: null,
            title: '排序',
            orderable: false,
            searchable: false,
            width: '60px',
            className: 'text-center drag-handle',
            render: (data, type, row) => {
                if (isSortMode.value) {
                    return `<i class="fa fa-grip-vertical text-muted"></i>`
                }
                return `<span class="text-muted">${row.seq}</span>`
            }
        },
        {
            data: 'thumbnail',
            title: '縮圖',
            orderable: false,
            searchable: false,
            width: '120px',
            render: (data) => {
                if (data) {
                    return `<img src="${data}" class="img-thumbnail" style="max-width: 100px; height: auto;" alt="縮圖">`
                }
                return '<div class="text-center text-muted">無縮圖</div>'
            }
        },
        {
            data: null,
            title: '影片資訊',
            orderable: false,
            render: (data, type, row) => {
                let html = `<div class="fw-semibold">第 ${row.seq} 集</div>`
                
                if (row.duration_text?.zh_TW) {
                    html += `<div class="text-muted small">時長: ${row.duration_text.zh_TW}</div>`
                }
                
                if (row.description?.zh_TW) {
                    const desc = row.description.zh_TW.length > 50
                        ? row.description.zh_TW.substring(0, 50) + '...'
                        : row.description.zh_TW
                    html += `<div class="text-muted small mt-1">${desc}</div>`
                }
                
                return html
            }
        },
        {
            data: 'video_type',
            title: '來源',
            width: '100px',
            className: 'text-center',
            render: (data) => {
                if (data === 'youtube') {
                    return '<span class="badge bg-danger"><i class="fab fa-youtube"></i> YouTube</span>'
                } else if (data === 'upload') {
                    return '<span class="badge bg-primary"><i class="fa fa-upload"></i> 上傳</span>'
                }
                return '<span class="badge bg-secondary">未知</span>'
            }
        },
        {
            data: null,
            title: '操作',
            orderable: false,
            searchable: false,
            width: '150px',
            className: 'text-center',
            render: (data, type, row) => {
                if (isSortMode.value) {
                    return ''
                }
                
                return `
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-info" onclick="window.playVideo(${row.id})" title="播放">
                            <i class="fa fa-play"></i>
                        </button>
                        <button class="btn btn-warning" onclick="window.editVideo(${row.id})" title="編輯">
                            <i class="fa fa-edit"></i>
                        </button>
                        <button class="btn btn-danger" onclick="window.deleteVideo(${row.id})" title="刪除">
                            <i class="fa fa-trash"></i>
                        </button>
                    </div>
                `
            }
        }
    ]

    const initializeDataTable = async () => {
        if (!table.value) return

        isLoading.value = true

        if (dt.value) {
            dt.value.destroy()
            dt.value = null
        }

        await nextTick()

        dt.value = DataTableHelper.createDataTable(table.value, (dtInstance) => {
            isLoading.value = false
        })
    }

    const reloadTable = () => {
        if (dt.value && typeof dt.value.ajax?.reload === 'function') {
            dt.value.ajax.reload(null, false)
        } else {
            initializeDataTable()
        }
    }

    const toggleSortMode = () => {
        isSortMode.value = !isSortMode.value
        
        if (isSortMode.value) {
            originalOrder.value = [...rows.value]
            reloadTable()
        } else {
            cancelSortMode()
        }
    }

    const cancelSortMode = () => {
        isSortMode.value = false
        rows.value = [...originalOrder.value]
        
        if (sortableInstance.value) {
            sortableInstance.value.destroy()
            sortableInstance.value = null
        }
        
        reloadTable()
    }

    const saveSortOrder = async () => {
        const items = rows.value.map(row => row.id)  // 只取 ID
        
        const result = await sortVideos(items, currentSeason.value)
        if (result.status) {  // 改為 status
            isSortMode.value = false
            reloadTable()
        }
        
        return result
    }

    // ========================
    // 播放器方法
    // ========================
    const getVideoUrl = (video) => {
        if (!video) return ''
        
        if (video.youtube_url) {
            return getYouTubeEmbedUrl(video.youtube_url)
        }
        
        if (video.video_file_path) {
            if (video.video_file_path.startsWith('http')) {
                return video.video_file_path
            }
            return `/storage/${video.video_file_path}`
        }
        
        return ''
    }

    const getYouTubeEmbedUrl = (url) => {
        if (!url) return ''
        
        const patterns = [
            /(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([^&\n?#]+)/,
            /youtube\.com\/watch\?.*v=([^&\n?#]+)/
        ]
        
        for (const pattern of patterns) {
            const match = url.match(pattern)
            if (match) {
                return `https://www.youtube.com/embed/${match[1]}`
            }
        }
        
        return url
    }

    const playVideo = (video) => {
        currentVideo.value = video
        isPlaying.value = true
        
        if (playerModal.value) {
            playerModal.value.show()
        }
    }

    const stopVideo = () => {
        isPlaying.value = false
        currentVideo.value = null
        
        if (playerModal.value) {
            playerModal.value.hide()
        }
        
        if (playerRef.value && playerRef.value.pause) {
            playerRef.value.pause()
        }
    }

    // ========================
    // Modal 方法
    // ========================
    const openModal = () => {
        resetForm(currentSeason.value)
        nextTick(() => {
            if (modalInstance) {
                modalInstance.show()
            }
        })
    }

    const closeModal = () => {
        resetForm()
        if (modalInstance) {
            modalInstance.hide()
        }
    }

    const editModal = (data) => {
        setEditData(data, currentSeason.value)
        if (modalInstance) {
            modalInstance.show()
        }
    }

    // ========================
    // 工具方法
    // ========================
    const clearTempFiles = async () => {
        try {
            await axios.post(route('admin.uploads.tmp.clear-all'))
        } catch (error) {
            console.warn('清理暫存檔案失敗:', error)
        }
    }

    const getNextEpisodeNumber = () => {
        const seasonVideos = rows.value.filter(v => 
            !v.season || v.season === currentSeason.value
        )
        if (seasonVideos.length === 0) {
            return 1
        }
        const maxSeq = Math.max(...seasonVideos.map(v => v.seq || 0))
        return maxSeq + 1
    }

    // ========================
    // 生命週期
    // ========================
    onBeforeUnmount(() => {
        clearTempFiles()
        if (dt.value) {
            try {
                // 檢查 destroy 是否為函數
                if (typeof dt.value.destroy === 'function') {
                    dt.value.destroy()
                } else if (dt.value.dt && typeof dt.value.dt.destroy === 'function') {
                    // 可能是 Vue ref 包裝的 DataTable
                    dt.value.dt.destroy()
                }
            } catch (error) {
                console.warn('銷毀 DataTable 時發生錯誤，忽略並繼續:', error)
            }
        }
        if (sortableInstance.value) {
            try {
                if (typeof sortableInstance.value.destroy === 'function') {
                    sortableInstance.value.destroy()
                }
            } catch (error) {
                console.warn('銷毀 Sortable 實例時發生錯誤，忽略並繼續:', error)
            }
        }
    })

    // ========================
    // 導出
    // ========================
    return {
        // 配置
        currentConfig,
        uploadConfig,
        
        // 表單相關
        form,
        isEditing,
        uploadType,
        resetForm,
        setEditData,
        submitForm,
        getValidationRules,
        
        // 上傳相關
        uploadProgress,
        isUploading,
        uploadError,
        currentFile,
        validateFile,
        handleFileUploadSuccess,
        handleFileRemove,
        
        // 表格相關
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
        
        // 播放器相關
        isPlaying,
        currentVideo,
        playerRef,
        playerModal,
        getVideoUrl,
        getYouTubeEmbedUrl,
        playVideo,
        stopVideo,
        
        // Modal 相關
        modalForm,
        modalInstance,
        videoUploaderRef,
        openModal,
        closeModal,
        editModal,
        
        // API 操作
        getApiRoute,
        deleteVideo,
        sortVideos,
        
        // 工具方法
        clearTempFiles
    }
}