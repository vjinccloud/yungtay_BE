// resources/js/composables/video/useVideoUpload.js
import { ref, computed, reactive, nextTick, inject } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import Sortable from 'sortablejs'
import { useVideo } from './useVideo'

export function useVideoUpload(props, emit) {
    // 使用 useVideo composable 處理影片基礎邏輯
    const {
        videoList,
        currentSeason,
        isLoading,
        getVideoList,
        deleteVideo,
        getContentTypeConfig
    } = useVideo(props.contentType)

    // Refs
    const dataTable = ref(null)
    const videoFormModal = ref(null)
    const playerModal = ref(null)
    const sortableInstance = ref(null)
    const originalOrder = ref([])
    const currentPlayVideo = ref(null)
    const isSortMode = ref(false)

    // 計算屬性
    const availableSeasons = computed(() => {
        const seasons = []
        for (let i = 1; i <= props.maxSeasons; i++) {
            seasons.push(i)
        }
        return seasons
    })

    const currentSeasonVideos = computed(() => {
        if (!videoList.value.data) return []
        return videoList.value.data.filter(video => 
            video.season === parseInt(currentSeason.value)
        )
    })

    const nextEpisodeNumber = computed(() => {
        const videos = currentSeasonVideos.value
        return videos.length > 0 ? Math.max(...videos.map(v => v.seq)) + 1 : 1
    })

    // 排序相關方法
    const initSortable = async () => {
        await nextTick()
        const tbody = dataTable.value?.$el?.querySelector('tbody')
        
        if (tbody && !sortableInstance.value) {
            sortableInstance.value = Sortable.create(tbody, {
                animation: 150,
                handle: '.sort-handle',
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                dragClass: 'sortable-drag',
                onEnd: (evt) => {
                    console.log('排序結束', evt)
                }
            })
        }
    }

    const toggleSort = () => {
        isSortMode.value = !isSortMode.value
        if (isSortMode.value) {
            originalOrder.value = [...videoList.value.data]
            nextTick(initSortable)
        } else {
            cancelSort()
        }
    }

    const cancelSort = () => {
        isSortMode.value = false
        videoList.value.data = [...originalOrder.value]
        destroySortable()
    }

    const saveSort = async () => {
        const tbody = dataTable.value?.$el?.querySelector('tbody')
        if (!tbody) return

        const newOrder = Array.from(tbody.querySelectorAll('tr'))
            .map(tr => parseInt(tr.getAttribute('data-id')))
            .filter(id => !isNaN(id))

        const config = getContentTypeConfig()
        const sweetAlert = inject('$sweetAlert')
        
        sweetAlert?.confirm('確定更新集數排序嗎？', () => {
            // 開始 loading
            isLoading.value = true
            
            router.put(route(`admin.${config.routePrefix}-episodes.sort`), 
                { ids: newOrder }, 
                {
                    onSuccess: (finalRes) => {
                        try {
                            const res = finalRes.props.flash?.result || finalRes.props.result;
                            if (res && res.status) {
                                sweetAlert?.resultData(res);
                                isSortMode.value = false
                                destroySortable()
                                getVideoList(props.contentId, currentSeason.value)
                            } else {
                                sweetAlert?.error({ msg: '排序更新失敗，請重試！' });
                            }
                        } catch (error) {
                            console.error('處理排序回應時發生錯誤:', error);
                            sweetAlert?.error({ msg: '處理回應時發生錯誤' });
                        }
                    },
                    onError: (errors) => {
                        console.error('排序請求失敗:', errors);
                        sweetAlert?.error({ msg: '排序更新失敗，請重試！' });
                    },
                    onFinish: () => {
                        // 結束 loading
                        isLoading.value = false
                    }
                }
            )
        })
    }

    const destroySortable = () => {
        if (sortableInstance.value) {
            sortableInstance.value.destroy()
            sortableInstance.value = null
        }
    }

    // 影片操作方法
    const openAddModal = () => {
        if (videoFormModal.value) {
            videoFormModal.value.openModal()
        }
    }

    const editVideo = async (id) => {
        if (videoFormModal.value) {
            videoFormModal.value.openModal(id)
        }
    }

    const playVideo = (id) => {
        const video = videoList.value.data.find(v => v.id === id)
        if (video && playerModal.value) {
            currentPlayVideo.value = video
            playerModal.value.openModal(video)
        }
    }

    const confirmDeleteVideo = async (id) => {
        const sweetAlert = inject('$sweetAlert')
        sweetAlert?.deleteConfirm('確定要刪除這個影片嗎？', async () => {
            const result = await deleteVideo(id)
            if (result.status) {
                await getVideoList(props.contentId, currentSeason.value)
                emit('video-change', { 
                    action: 'delete', 
                    season: currentSeason.value 
                })
            }
        })
    }

    // 事件處理
    const onSeasonChange = () => {
        getVideoList(props.contentId, currentSeason.value)
    }

    const reloadData = () => {
        getVideoList(props.contentId, currentSeason.value)
    }

    const onVideoSaved = () => {
        reloadData()
        emit('video-change', { 
            action: 'save', 
            season: currentSeason.value 
        })
    }

    const onPlayerModalClose = () => {
        currentPlayVideo.value = null
    }

    // DataTable 初始化
    const initDataTable = async () => {
        await nextTick()
        if (dataTable.value && !dataTable.value.dt) {
            dataTable.value.dt = DataTableHelper.createDataTable(dataTable.value)
        }
    }

    return {
        // 從 useVideo 繼承
        videoList,
        currentSeason,
        isLoading,
        
        // Refs
        dataTable,
        videoFormModal,
        playerModal,
        sortableInstance,
        currentPlayVideo,
        isSortMode,
        
        // 計算屬性
        availableSeasons,
        currentSeasonVideos,
        nextEpisodeNumber,
        
        // 方法
        initSortable,
        toggleSort,
        cancelSort,
        saveSort,
        openAddModal,
        editVideo,
        playVideo,
        confirmDeleteVideo,
        onSeasonChange,
        reloadData,
        onVideoSaved,
        onPlayerModalClose,
        initDataTable,
        getVideoList
    }
}