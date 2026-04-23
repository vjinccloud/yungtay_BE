/**
 * 內容管理通用 Composable
 * 用於影音、節目等內容的基本資料管理
 */

import { ref, computed, watch, inject } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { FormValidator, useSubmitForm, getSlimValue, destroySlim } from '@/utils'

export function useContentManagement(contentType, props) {
    // 配置
    const config = {
        drama: {
            updateRoute: 'admin.dramas.update',
            storeRoute: 'admin.dramas.store',
            contentName: '影音'
        },
        program: {
            updateRoute: 'admin.programs.update',
            storeRoute: 'admin.programs.store',
            contentName: '節目'
        }
    }

    const currentConfig = config[contentType] || config.drama

    // 注入服務
    const sweetAlert = inject('$sweetAlert')
    const { submitForm: performSubmit } = useSubmitForm()

    // 語言配置
    const langs = {
        zh_TW: { placeholder: '中文標題' },
        en: { placeholder: 'English Title' }
    }

    // 圖片配置
    const imageConfigs = [
        {
            key: 'poster_desktop',
            label: '桌機版圖片（建議尺寸：860px × 485px）',
            slimLabel: '圖片拖移至此，建議尺寸 寬860px × 高485px',
            width: 860,
            height: 485,
            containerWidth: '70%',
            clearKey: 'slimClearedDesktop'
        },
        {
            key: 'poster_mobile',
            label: '手機版圖片（建議尺寸：200px × 240px）',
            slimLabel: '圖片拖移至此，建議尺寸 寬200px × 高240px',
            width: 200,
            height: 240,
            containerWidth: '40%',
            clearKey: 'slimClearedMobile'
        },
        {
            key: 'banner_desktop',
            label: '影片Banner桌機版圖片（建議尺寸：1915px × 798px）',
            slimLabel: '圖片拖移至此，建議尺寸 寬1915px × 高798px',
            width: 1915,
            height: 798,
            containerWidth: '70%',
            clearKey: 'slimClearedBannerDesktop'
        },
        {
            key: 'banner_mobile',
            label: '影片Banner手機版圖片（建議尺寸：430px × 240px）',
            slimLabel: '圖片拖移至此，建議尺寸 寬430px × 高240px',
            width: 430,
            height: 240,
            containerWidth: '40%',
            clearKey: 'slimClearedBannerMobile'
        }
    ]


    // 標籤欄位配置
    const tagFields = [
        {
            key: 'tags_zh',
            lang: 'zh_TW',
            label: '標籤（中文）',
            placeholder: '輸入標籤後按 Enter',
            emptyText: '點擊添加中文標籤',
            suggestions: contentType === 'drama' 
                ? ['影音', '愛情', '懸疑', '喜劇', '動作', '科幻', '歷史', '古裝']
                : ['綜藝', '娛樂', '音樂', '訪談', '實境', '競賽', '教育', '旅遊']
        },
        {
            key: 'tags_en',
            lang: 'en',
            label: '標籤（英文）',
            placeholder: 'Enter tags and press Enter',
            emptyText: 'Click to add English tags',
            suggestions: contentType === 'drama'
                ? ['Drama', 'Romance', 'Thriller', 'Comedy', 'Action', 'Sci-Fi', 'Historical', 'Period']
                : ['Variety', 'Entertainment', 'Music', 'Talk Show', 'Reality', 'Competition', 'Education', 'Travel']
        }
    ]

    // 圖片參考存儲
    const imageRefs = ref({})

    // 表單資料初始化
    const form = useForm({
        // 多語系欄位
        title: {
            zh_TW: props.initialData?.title?.zh_TW || '',
            en: props.initialData?.title?.en || ''
        },
        description: {
            zh_TW: props.initialData?.description?.zh_TW || '',
            en: props.initialData?.description?.en || ''
        },
        crew: {
            zh_TW: props.initialData?.crew?.zh_TW || '',
            en: props.initialData?.crew?.en || ''
        },
        tags: {
            zh_TW: props.initialData?.tags?.zh_TW || '',
            en: props.initialData?.tags?.en || ''
        },
        other_info: {
            zh_TW: props.initialData?.other_info?.zh_TW || '',
            en: props.initialData?.other_info?.en || ''
        },

        // 基本欄位
        category_id: props.initialData?.category_id || '',
        subcategory_id: props.initialData?.subcategory_id || '',
        season_number: props.initialData?.season_number || 1,
        release_year: props.initialData?.release_year || new Date().getFullYear(),
        published_date: props.initialData?.published_date || new Date().toISOString().split('T')[0],
        is_active: props.initialData?.is_active ?? false,

        // 圖片欄位
        poster_desktop: null,
        poster_mobile: null,
        banner_desktop: null,
        banner_mobile: null,
        slimClearedMobile: false,
        slimClearedDesktop: false,
        slimClearedBannerMobile: false,
        slimClearedBannerDesktop: false,
    })

    // 驗證規則
    const getRules = () => ({
        'title.zh_TW': ['required', 'string', ['max', 255]],
        'title.en': ['required', 'string', ['max', 255]],
        'category_id': ['required'],
        'subcategory_id': ['required'],
        'season_number': ['required'],
        'release_year': [
            'required',
            'integer',
            ['minValue', 1900, '發行年份不能低於1900年'],
            ['maxValue', new Date().getFullYear() + 5, '發行年份不能超過未來5年']
        ],
        'published_date': ['required'],
        'description.zh_TW': ['required', 'string'],
        'description.en': ['required', 'string'],
        'crew.zh_TW': ['required', 'string'],
        'crew.en': ['required', 'string'],
        'tags.zh_TW': ['required', 'string'],
        'tags.en': ['required', 'string'],
        'other_info.zh_TW': ['required', 'string'],
        'other_info.en': ['required', 'string'],
        'poster_desktop': props.isEditing ? [] : ['required'],
        'poster_mobile': props.isEditing ? [] : ['required'],
        'banner_desktop': props.isEditing ? [] : ['required'],
        'banner_mobile': props.isEditing ? [] : ['required'],
    })

    // 建立驗證器
    const validator = new FormValidator(form, getRules)

    // 計算屬性
    const filteredSubcategories = computed(() => {
        if (!form.category_id || !props.subcategories) return []
        return props.subcategories.filter(sub => sub.parent_id === form.category_id)
    })

    const maxVideoSeason = computed(() => {
        if (!props.videoSeasons.length) return 1
        return Math.max(...props.videoSeasons)
    })

    const minAllowedSeason = computed(() => {
        return Math.max(1, maxVideoSeason.value)
    })

    const hasVideoData = computed(() => {
        return props.videoSeasons.length > 0
    })

    const availableSeasons = computed(() => {
        const options = []
        if (hasVideoData.value) {
            const min = minAllowedSeason.value
            for (let i = min; i <= min + 6; i++) {
                options.push(i)
            }
        } else {
            for (let i = 1; i <= 8; i++) {
                options.push(i)
            }
        }
        return options
    })

    // 監聽季數變化
    watch(minAllowedSeason, (newMinSeason) => {
        if (!props.isEditing && form.season_number < newMinSeason) {
            form.season_number = newMinSeason
        }
    }, { immediate: true })

    // 方法
    const validateField = async (fieldName) => {
        try {
            await validator.singleField(fieldName)
        } catch (error) {
            console.warn('驗證欄位時發生錯誤:', fieldName, error)
        }
    }

    const onCategoryChange = () => {
        form.subcategory_id = ''
        validateField('category_id')
    }

    const onSubcategoryChange = () => {
        validateField('subcategory_id')
    }

    const onSeasonChange = (emit) => {
        validateField('season_number')
        if (emit) {
            emit('season-change', form.season_number)
        }
    }

    const handleSubmit = async () => {
        try {
            form.clearErrors()

            // 處理所有圖片
            imageConfigs.forEach(config => {
                form[config.key] = getSlimValue(imageRefs.value[config.key])
            })

            form.confirm = false
            const url = props.isEditing
                ? route(currentConfig.updateRoute, props.initialData?.id)
                : route(currentConfig.storeRoute)
            const method = props.isEditing ? 'put' : 'post'

            const hasErrors = await validator.hasErrors()

            if (!hasErrors) {
                performSubmit({ form, url, method })
            } else {
                sweetAlert.error({
                    msg: '提交失敗，請檢查是否有欄位錯誤！'
                })
            }
        } catch (error) {
            console.error('提交表單時發生錯誤:', error)
            sweetAlert.error({
                msg: '系統錯誤，請稍後再試！'
            })
        }
    }

    const getImageData = () => {
        const data = {}
        imageConfigs.forEach(config => {
            data[config.key] = getSlimValue(imageRefs.value[config.key])
        })
        return data
    }

    const cleanupImages = async () => {
        for (const config of imageConfigs) {
            await destroySlim(imageRefs.value[config.key])
        }
    }

    return {
        // 配置
        currentConfig,
        langs,
        imageConfigs,
        tagFields,
        
        // 表單相關
        form,
        imageRefs,
        validator,
        
        // 計算屬性
        filteredSubcategories,
        minAllowedSeason,
        hasVideoData,
        availableSeasons,
        
        // 方法
        validateField,
        onCategoryChange,
        onSubcategoryChange,
        onSeasonChange,
        handleSubmit,
        getImageData,
        cleanupImages,
        
        // 工具
        sweetAlert
    }
}