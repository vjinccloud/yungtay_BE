<!-- resources/js/InertiaPages/Admin/Shared/Themes/Form.vue -->
<!-- 共用主題表單：完整還原原本影音主題表單動線，並加入節目分支 -->
<template>
  <div class="content">
    <BreadcrumbItem />

    <div class="block block-rounded">
      <div class="block-header block-header-default">
        <h3 class="block-title">
          <button type="button" class="btn btn-sm btn-alt-secondary" @click="goBack">
            <i class="fa fa-arrow-left me-1"></i>
            返回列表
          </button>
        </h3>
      </div>

      <div class="block-content block-content-full">
        <!-- 上半：名稱 + 分類篩選 + 單選 Select2 -->
        <form @submit.prevent="submitForm">
          <div class="row g-3 mb-4">
            <!-- 名稱（中/英） -->
            <div class="col-md-6">
              <label class="form-label">主題名稱（中文）<span class="text-danger">*</span></label>
              <input
                type="text"
                class="form-control"
                :class="{'is-invalid': form.errors?.name?.zh_TW}"
                v-model="form.name.zh_TW"
                placeholder="請輸入中文名稱"
                @blur="() => validator.singleField('name.zh_TW')"
                required
              />
              <div v-if="form.errors?.name?.zh_TW" class="invalid-feedback">{{ form.errors?.name?.zh_TW }}</div>
            </div>
            <div class="col-md-6">
              <label class="form-label">主題名稱（英文）<span class="text-danger">*</span></label>
              <input
                type="text"
                class="form-control"
                :class="{'is-invalid': form.errors?.name?.en}"
                v-model="form.name.en"
                placeholder="請輸入英文名稱"
                @blur="() => validator.singleField('name.en')"
                required
              />
              <div v-if="form.errors?.name?.en" class="invalid-feedback">{{ form.errors?.name?.en }}</div>
            </div>

            <!-- 分類 + 單選內容 -->
            <div class="col-12">
              <div class="block block-rounded bg-body-light">
                <div class="block-header block-header-default">
                  <h3 class="block-title">
                    <i class="fa fa-search opacity-50 me-1"></i>
                    搜尋{{ contentLabel }}
                  </h3>
                </div>
                <div class="block-content">
                  <div class="alert alert-info d-flex align-items-center" role="alert">
                    <div class="flex-shrink-0"><i class="fa fa-fw fa-info-circle"></i></div>
                    <div class="flex-grow-1 ms-3">
                      <p class="mb-0">請先選擇分類或直接文字篩選{{ contentLabel }}，然後選擇要加入主題的{{ contentLabel }}，最後點擊「送出」按鈕完成新增。</p>
                    </div>
                  </div>

                  <div class="row g-3">
                    <div class="col-md-6">
                      <label class="form-label">{{ contentLabel }}主分類</label>
                      <select class="form-select" v-model="searchForm.main_category_id" @change="onMainCategoryChange">
                        <option value="">請選擇主分類</option>
                        <option v-for="category in categories.main" :key="category.id" :value="category.id">
                          {{ category.name_zh_tw }}
                        </option>
                      </select>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">{{ contentLabel }}次分類</label>
                      <select class="form-select" v-model="searchForm.sub_category_id" :disabled="!searchForm.main_category_id" @change="loadSelectOptions">
                        <option value="">請選擇次分類</option>
                        <option v-for="subcategory in filteredSubcategories" :key="subcategory.id" :value="subcategory.id">
                          {{ subcategory.name_zh_tw }}
                        </option>
                      </select>
                    </div>

                    <!-- 第三區：選擇影音 -->
                    <div class="col-12 mb-4">
                      <label class="form-label">選擇{{ contentLabel }} <span class="text-danger">*</span></label>
                      <Select2Input
                        v-model="selectedContentId"
                        ref="select2Ref"
                        :options="select2Options"
                        :placeholder="`請選擇要新增的${contentLabel}`"
                        :class="{'is-invalid': isRadio ? form.errors?.radio_id : (isProgram ? form.errors?.program_id : form.errors?.drama_id)}"
                        @change="validateSelected"
                        @blur="validateSelected"
                      />
                      <div v-if="isRadio ? form.errors?.radio_id : (isProgram ? form.errors?.program_id : form.errors?.drama_id)" class="invalid-feedback d-block">
                        {{ isRadio ? form.errors?.radio_id : (isProgram ? form.errors?.program_id : form.errors?.drama_id) }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- 按鈕區 -->
            <div class="col-12">
              <div class="d-flex gap-2">
                <button
                  type="button"
                  class="btn btn-primary"
                  :disabled="form.processing"
                  @click="submitForm"
                >
                  <span v-if="form.processing">
                    <i class="fa fa-spinner fa-spin me-1"></i>
                    處理中...
                  </span>
                  <span v-else>
                    <i class="fa fa-save me-1"></i>
                    送出
                  </span>
                </button>
                <button
                  type="button"
                  class="btn btn-secondary"
                  @click="clearSearch"
                  :disabled="form.processing"
                >
                  <i class="fas fa-refresh me-1"></i>重置
                </button>
              </div>
            </div>
          </div>
        </form>

        <!-- 分隔線 -->
        <hr />

        <!-- 下半：已關聯清單（可刪除/排序） -->
        <div class="mt-4">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">{{ contentLabel }}列表</h4>
            <div>
              <button class="btn btn-info me-2" @click="toggleSortMode" v-if="isEditing && !isSortMode && hasData">
                <i class="fa fa-sort"></i>
                更新排序
              </button>
            </div>
          </div>

          <div class="alert alert-info d-flex align-items-center" v-if="isEditing && isSortMode">
            <i class="fa fa-info-circle me-2"></i>
            <div>
              <strong>排序模式已啟用</strong> - 拖曳表格列來調整{{ contentLabel }}順序
              <button class="btn btn-sm btn-success ms-3" @click.stop="saveSortOrder">
                <i class="fa fa-check"></i> 儲存排序
              </button>
              <button class="btn btn-sm btn-secondary ms-2" @click.stop="cancelSortMode">
                <i class="fa fa-times"></i> 取消
              </button>
            </div>
          </div>

          <div v-if="!isEditing" class="alert alert-secondary">
            儲存主題後即可在此管理{{ contentLabel }}的關聯、刪除與排序。
          </div>
          <DataTable
            v-else
            class="table table-bordered table-striped table-vcenter"
            :class="{ 'sortable-table': isSortMode }"
            :columns="relationTableColumns"
            :options="relationTableOptions"
            ref="relationTable"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, inject, onMounted, computed, nextTick, onBeforeUnmount } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import BreadcrumbItem from '@/Shared/Admin/Partials/BreadcrumbItem.vue'
import Select2Input from '@/Plugin/Select2Input.vue'
import { FormValidator, useSubmitForm } from '@/utils'
import DataTablesCore from 'datatables.net-bs5'
import DataTable from 'datatables.net-vue3'
import DataTableHelper from '@/utils/datatableHelper'
import Sortable from 'sortablejs'

DataTable.use(DataTablesCore)

// Props（父頁需傳入對應資料）
const props = defineProps({
  contentType: { type: String, required: true, validator: v => ['drama', 'program', 'radio'].includes(v) },
  // for both create/edit
  categories: { type: Object, default: () => ({ main: [], sub: [] }) },
  dramas: { type: Array, default: () => [] },
  programs: { type: Array, default: () => [] },
  radios: { type: Array, default: () => [] },
  // edit mode
  themeId: { type: [Number, String], default: null },
  themeName: { type: Object, default: null }
})

const isProgram = computed(() => props.contentType === 'program')
const isRadio = computed(() => props.contentType === 'radio')
const contentLabel = computed(() => {
  if (isRadio.value) return '廣播'
  return isProgram.value ? '節目' : '影音'
})

// 路由名稱（依 contentType 切換）
const routeNames = computed(() => {
  if (isRadio.value) {
    return {
      index: 'admin.radio-themes',
      store: 'admin.radio-themes.store',
      update: 'admin.radio-themes.update',
      ajaxList: 'admin.radio-themes.ajax-list',
      removeRelation: 'admin.radio-themes.remove-radio',
      sortRelation: 'admin.radio-themes-relation.sort'
    }
  }
  if (isProgram.value) {
    return {
      index: 'admin.program-themes',
      store: 'admin.program-themes.store',
      update: 'admin.program-themes.update',
      ajaxList: 'admin.program-themes.ajax-list',
      removeRelation: 'admin.program-themes.remove-program',
      sortRelation: 'admin.program-themes.sort-programs'
    }
  }
  return {
    index: 'admin.drama-themes',
    store: 'admin.drama-themes.store',
    update: 'admin.drama-themes.update',
    ajaxList: 'admin.drama-themes.ajax-list',
    removeRelation: 'admin.drama-themes.remove-drama',
    sortRelation: 'admin.drama-themes-relation.sort'
  }
})

// 注入
const route = inject('route')
const sweetAlert = inject('$sweetAlert')
const isLoading = inject('isLoading')

// 引入 submitForm 方法
const { submitForm: performSubmit } = useSubmitForm()

// 狀態
const isEditing = computed(() => !!props.themeId)
const searchForm = ref({ main_category_id: '', sub_category_id: '' })
const select2Ref = ref(null)
const selectedContentId = ref(null)
const select2Options = ref([])
const relationTable = ref(null)
const dt = ref(null)
const isSortMode = ref(false)
const sortableInstance = ref(null)
const hasData = ref(false)

// 分類過濾
const filteredSubcategories = computed(() => {
  const mainId = parseInt(searchForm.value.main_category_id || 0)
  return (props.categories.sub || []).filter(sub => parseInt(sub.parent_id) === mainId)
})

// 名稱 + 單一內容欄位
const form = useForm({
  name: {
    zh_TW: props.themeName?.zh_TW || '',
    en: props.themeName?.en || ''
  },
  drama_id: (isProgram.value || isRadio.value) ? undefined : null,
  program_id: isProgram.value ? null : undefined,
  radio_id: isRadio.value ? null : undefined
})

const rules = computed(() => ({
  'name.zh_TW': ['required', 'string', ['max', 255]],
  'name.en': ['required', 'string', ['max', 255]],
  [isRadio.value ? 'radio_id' : (isProgram.value ? 'program_id' : 'drama_id')]: ['required']
}))
const validator = new FormValidator(form, () => rules.value)

const contentIdField = computed(() => {
  if (isRadio.value) return 'radio_id'
  return isProgram.value ? 'program_id' : 'drama_id'
})

// 載入下拉選項（依分類篩選）
const loadSelectOptions = async () => {
  const mainId = parseInt(searchForm.value.main_category_id || 0)
  const subId = parseInt(searchForm.value.sub_category_id || 0)

  // 根據 contentType 選擇資料來源
  let source
  if (isRadio.value) {
    source = props.radios
  } else if (isProgram.value) {
    source = props.programs
  } else {
    source = props.dramas
  }

  let list = Array.isArray(source) ? [...source] : []

  // 不篩選時顯示全部
  if (!mainId && !subId) {
    // 顯示全部選項
  } else {
    if (mainId) list = list.filter(o => parseInt(o.category_id || 0) === mainId)
    if (subId) list = list.filter(o => parseInt(o.subcategory_id || 0) === subId)
  }

  select2Options.value = list.map(item => ({
    value: item.id,
    text: item.name_zh_tw || item.title?.zh_TW || item.name?.zh_TW || item.title || `#${item.id}`
  }))

  await nextTick()
}

const onMainCategoryChange = () => {
  searchForm.value.sub_category_id = ''
  selectedContentId.value = null
  loadSelectOptions()
}

// 重置搜尋
const clearSearch = () => {
  searchForm.value.main_category_id = ''
  searchForm.value.sub_category_id = ''
  selectedContentId.value = null
  loadSelectOptions()
}

const validateSelected = async () => {
  try {
    if (isRadio.value) {
      await validator.singleField('radio_id')
    } else if (isProgram.value) {
      await validator.singleField('program_id')
    } else {
      await validator.singleField('drama_id')
    }
  } catch {}
}

// 返回列表
const goBack = () => { router.get(route(routeNames.value.index)) }

// 提交表單（完全依照原本動線，沒有確認提示）
const submitForm = async () => {
  form.clearErrors()

  // 設置表單值，確保數字類型
  if (isRadio.value) {
    form.radio_id = selectedContentId.value !== null ? +selectedContentId.value : null
  } else if (isProgram.value) {
    form.program_id = selectedContentId.value !== null ? +selectedContentId.value : null
  } else {
    form.drama_id = selectedContentId.value !== null ? +selectedContentId.value : null
  }

  try {
    // 執行驗證
    const hasErrors = await validator.hasErrors()
    if (!hasErrors) {
      // 設定提交參數
      const url = isEditing.value 
        ? route(routeNames.value.update, props.themeId) 
        : route(routeNames.value.store)
      const method = isEditing.value ? 'put' : 'post'

      // 直接提交，沒有確認提示，編輯成功後重新載入表格
      performSubmit({ 
        form, 
        url, 
        method,
        callback: () => {
          // 編輯模式下，提交成功後重新載入表格
          if (isEditing.value) {
            reloadTable()
          }
        }
      })
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

// DataTable：已關聯清單（依 contentType 顯示對應欄位）
const relationTableColumns = computed(() => [
  {
    title: '#', data: null, orderable: false, width: '60px', className: 'text-center',
    render: (data, type, row, meta) => {
      if (isSortMode.value) {
        // 同時掛 relation id 與內容 id，方便兩種排序格式
        const contentId = row[contentIdField.value]
        return `<div class="sort-handle" data-id="${row.id}" data-content-id="${contentId}" style="cursor: move;"><i class="fa fa-grip-vertical"></i></div>`
      }
      return meta.row + 1
    }
  },
  { title: `${contentLabel.value}名稱（中文）`, data: `${isRadio.value ? 'radio' : (isProgram.value ? 'program' : 'drama')}_name`, width: '300px' },
  { title: '排序', data: 'sort_order', className: 'text-center', width: '100px', visible: !isSortMode.value },
  {
    title: '操作', data: null, orderable: false, width: '100px', className: 'text-center',
    render: (row) => {
      if (isSortMode.value) return '-'
      // drama: 刪 relation； program: 刪 program 關聯（需 program_id）
      const relationId = row.id
      const contentId = row[contentIdField.value]
      return `<button type="button" class="btn btn-sm btn-danger remove-btn" data-relation-id="${relationId}" data-content-id="${contentId}"><i class=\"fa fa-trash\"></i></button>`
    }
  }
])

const relationTableOptions = computed(() => ({
  ...DataTableHelper.getBaseOptions(),
  searching: false,
  paging: true,
  pageLength: 10,
  ajax: (data, callback) => {
    const extraParams = { theme_id: props.themeId }
    DataTableHelper.fetchTableData(
      route(routeNames.value.ajaxList),
      data,
      callback,
      [],
      '',
      extraParams,
      () => sweetAlert?.error({ msg: `${contentLabel.value}列表載入失敗，請重新整理頁面` }),
      true
    )
  },
  drawCallback: () => {
    DataTableHelper.defaultDrawCallback()
    const tableInstance = getDataTableInstance()
    hasData.value = !!tableInstance && tableInstance.rows().data().length > 0

    // 綁定刪除
    const root = relationTable.value?.$el
    root?.querySelectorAll('.remove-btn').forEach(btn => {
      if (btn.dataset.bound) return
      btn.dataset.bound = '1'
      btn.addEventListener('click', () => {
        const relationId = parseInt(btn.getAttribute('data-relation-id'))
        const contentId = parseInt(btn.getAttribute('data-content-id'))
        removeRelation(relationId, contentId)
      })
    })

    // 排序初始化
    if (isSortMode.value) nextTick(() => initSortable())
  },
  order: [[2, 'asc']]
}))

const getDataTableInstance = () => {
  if (dt.value && typeof dt.value.page !== 'undefined') return dt.value
  if (relationTable.value && relationTable.value.dt) return relationTable.value.dt
  if (relationTable.value && relationTable.value.$el) {
    const node = relationTable.value.$el.querySelector('table')
    if (node && window.$ && window.$(node).DataTable) {
      try { return window.$(node).DataTable() } catch {}
    }
  }
  return null
}

// 刪除關聯（依照原本動線）
const removeRelation = (relationId, contentId) => {
  const title = `確定要從主題中移除此${contentLabel.value}嗎？`
  sweetAlert.deleteConfirm(title, () => {
    isLoading.value = true
    if (isProgram.value) {
      // 節目主題：傳送 program_id
      router.delete(route(routeNames.value.removeRelation, props.themeId), {
        data: { program_id: contentId },
        onSuccess: (finalRes) => {
          const res = finalRes.props.flash?.result || finalRes.props.result
          if (res && res.status) {
            sweetAlert.resultData(res)
            reloadTable()
          } else {
            sweetAlert.error({ msg: '移除失敗，請重試！' })
          }
        },
        onError: () => {
          sweetAlert.error({ msg: '移除失敗，請重試！' })
        },
        onFinish: () => {
          isLoading.value = false
        }
      })
    } else {
      // 影音/廣播主題：使用 relationId
      router.delete(route(routeNames.value.removeRelation, relationId), {
        onSuccess: (finalRes) => {
          const res = finalRes.props.flash?.result || finalRes.props.result
          if (res && res.status) {
            sweetAlert.resultData(res)
            reloadTable()
          } else {
            sweetAlert.error({ msg: '移除失敗，請重試！' })
          }
        },
        onError: () => {
          sweetAlert.error({ msg: '移除失敗，請重試！' })
        },
        onFinish: () => {
          isLoading.value = false
        }
      })
    }
  })
}

// 排序模式切換/保存
const toggleSortMode = () => {
  isSortMode.value = true
  const api = getDataTableInstance()
  if (api && api.page) api.page.len(9999).order([2, 'asc']).draw(false)
}

const cancelSortMode = () => {
  isSortMode.value = false
  if (sortableInstance.value) { try { sortableInstance.value.destroy() } catch {}; sortableInstance.value = null }
  const api = getDataTableInstance()
  if (api && api.page) api.page.len(10).order([2, 'asc']).draw(false)
}

const saveSortOrder = async () => {
  if (!sortableInstance.value) {
    sweetAlert.error({ msg: '排序功能未初始化' })
    return
  }

  // 確認提示（按照原本動線，動態顯示內容類型）
  sweetAlert.confirm(`確定更新${contentLabel.value}排序嗎？`, () => {
    const tbody = relationTable.value?.$el?.querySelector('tbody')
    if (!tbody) {
      sweetAlert.error({ msg: '找不到表格內容' })
      return
    }

    const handles = tbody.querySelectorAll('.sort-handle')
    const relationIds = Array.from(handles).map(h => parseInt(h.getAttribute('data-id'))).filter(n => !isNaN(n))
    const contentIds = Array.from(handles).map(h => parseInt(h.getAttribute('data-content-id'))).filter(n => !isNaN(n))

    if (relationIds.length === 0 && contentIds.length === 0) {
      sweetAlert.error({ msg: '無法取得排序資料' })
      return
    }

    isLoading.value = true
    if (isProgram.value) {
      // 節目：以 program ids 排序
      router.post(route(routeNames.value.sortRelation, { id: props.themeId }), {
        programs: contentIds
      }, {
        preserveState: true,
        onSuccess: (response) => {
          const res = response.props.flash?.result
          sweetAlert.resultData(res, null, () => {
            if (res?.status) {
              cancelSortMode()
              reloadTable()
            }
          })
        },
        onError: () => {
          sweetAlert.error({ msg: '排序失敗，請重試！' })
        },
        onFinish: () => {
          isLoading.value = false
        }
      })
    } else if (isRadio.value) {
      // 廣播：以 relation ids 排序
      router.put(route(routeNames.value.sortRelation), {
        radio_ids: relationIds,
        themeId: props.themeId
      }, {
        preserveState: true,
        onSuccess: (response) => {
          const res = response.props.flash?.result
          sweetAlert.resultData(res, null, () => {
            if (res?.status) {
              cancelSortMode()
              reloadTable()
            }
          })
        },
        onError: () => {
          sweetAlert.error({ msg: '排序失敗，請重試！' })
        },
        onFinish: () => {
          isLoading.value = false
        }
      })
    } else {
      // 影音：以 relation ids 排序
      router.put(route(routeNames.value.sortRelation), {
        drama_ids: relationIds,
        themeId: props.themeId
      }, {
        preserveState: true,
        onSuccess: (response) => {
          const res = response.props.flash?.result
          sweetAlert.resultData(res, null, () => {
            if (res?.status) {
              cancelSortMode()
              reloadTable()
            }
          })
        },
        onError: () => {
          sweetAlert.error({ msg: '排序失敗，請重試！' })
        },
        onFinish: () => {
          isLoading.value = false
        }
      })
    }
  })
}

const initSortable = () => {
  const tbody = relationTable.value?.$el?.querySelector('tbody')
  if (!tbody) return
  if (sortableInstance.value) { 
    try { 
      sortableInstance.value.destroy() 
    } catch {}
    sortableInstance.value = null 
  }
  try {
    sortableInstance.value = Sortable.create(tbody, { 
      animation: 150, 
      handle: '.sort-handle', 
      ghostClass: 'sortable-ghost' 
    })
  } catch (error) {
    console.error('初始化 Sortable 失敗:', error)
  }
}

const reloadTable = () => {
  const api = getDataTableInstance()
  if (api?.ajax?.reload) api.ajax.reload(null, false)
}

onMounted(async () => {
  await loadSelectOptions()
  if (props.themeId && relationTable.value) {
    try { dt.value = await DataTableHelper.createDataTable(relationTable.value) } catch {}
  }
})

onBeforeUnmount(() => { if (sortableInstance.value) { try { sortableInstance.value.destroy() } catch {} } })
</script>

<style scoped>
.sortable-table tbody tr { cursor: move; }
.sortable-ghost { opacity: 0.5; background: #f8f9fa; }
</style>