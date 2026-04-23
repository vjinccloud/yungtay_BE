<!-- resources/js/InertiaPages/Admin/Categories/Form.vue -->
<template>
    <div class="content">
      <BreadcrumbItem :title="props.categoryTitle" />

      <div class="block block-rounded">
        <div class="block-header block-header-default">
          <h3 class="block-title">
            <Link class="btn btn-sm btn-alt-secondary" :href="route(routeName)">
              <i class="fa fa-arrow-left me-1"></i>
              返回列表
            </Link>
          </h3>
        </div>
        
        <div class="block-content block-content-full">
          <form @submit.prevent="onSubmit">
            <input type="hidden" v-model="form.type" />
            <input type="hidden" v-model="form.seq" />

            <!-- 分類標題 (根據是否允許子分類顯示不同文字) -->
            <div class="mb-4">
              <h5 class="mb-2">{{ props.allowSubcategories === false ? '分類' : '主分類' }}</h5>
              <div class="row g-3">
                <div class="col-12">
                  <input
                    v-model="form.parent_name['zh_TW']"
                    @blur="validator.singleField('parent_name.zh_TW')"
                    type="text"
                    class="form-control"
                    :placeholder="props.allowSubcategories === false ? '請輸入分類名稱' : '請輸入主分類名稱'"
                    :class="{ 'is-invalid': !! form.errors.parent_name?.zh_TW }"
                  />
                  <div v-if="form.errors.parent_name?.zh_TW" class="invalid-feedback">
                    {{ form.errors.parent_name?.zh_TW }}
                  </div>
                </div>
                <!-- 英文欄位已隱藏（僅需中文）
                <div class="col-sm-6" v-if="props.categoryType !== 'news'">
                  <input
                    v-model="form.parent_name.en"
                    @blur="validator.singleField('parent_name.en')"
                    type="text"
                    class="form-control"
                    :placeholder="props.allowSubcategories === false ? '請輸入英文分類' : '請輸入英文主分類'"
                    :class="{ 'is-invalid': !!form.errors.parent_name?.en }"
                  />
                  <div v-if="form.errors.parent_name?.en" class="invalid-feedback">
                    {{ form.errors.parent_name?.en }}
                  </div>
                </div>
                -->
              </div>
            </div>

            <!-- 子分類列表 (只有允許子分類時才顯示) -->
            <div class="mb-4" v-if="props.allowSubcategories !== false">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="mb-0">子分類</h5>
                <button type="button" class="btn btn-sm btn-success" @click="addChild">
                  <i class="fa-solid fa-plus"></i> 新增子分類
                </button>
              </div>

              <draggable v-model="form.children" item-key="uid" @end="onDragEnd">
                <template #item="{ element: child, index }">
                  <div class="card mb-2 p-3" :class="{ 'border-primary': index === draggingIndex }"
                       @mouseenter="draggingIndex = index" @mouseleave="draggingIndex = null">
                    <!-- 移除 hidden input，ID 已經在 child 物件中 -->
                    <div class="d-flex align-items-center mb-2">
                      <i class="fa-solid fa-grip-lines me-2"></i>
                      <strong class="fs-5 fw-bold text-primary border-bottom border-2 pb-1">
                        子分類 #{{ index + 1 }}
                      </strong>
                      <!-- 修改刪除按鈕邏輯 -->
                      <button
                        type="button"
                        class="btn-close ms-auto"
                        @click="handleRemoveChild(index, child)"
                        :disabled="isRemoving"
                      ></button>
                    </div>
                    <div class="row g-3">
                      <div class="col-md-6">
                        <input
                          v-model="child.name['zh_TW']"
                          @blur="validator.singleField(`children[${index}].name.zh_TW`)"
                          type="text"
                          class="form-control"
                          placeholder="請輸入繁體名稱"
                          :class="{ 'is-invalid': !!form.errors.children?.[index]?.name?.zh_TW }"
                        />
                        <div v-if="form.errors.children?.[index]?.name?.zh_TW" class="invalid-feedback">
                            {{ form.errors.children[index]?.name?.zh_TW }}
                        </div>
                      </div>
                      <div class="col-md-6">
                        <input
                          v-model="child.name.en"
                          @blur="validator.singleField(`children[${index}].name.en`)"
                          type="text"
                          class="form-control"
                          placeholder="請輸入英文名稱"
                          :class="{ 'is-invalid': !!form.errors.children?.[index]?.name?.en }"
                        />
                        <div v-if="form.errors.children?.[index]?.name?.en" class="invalid-feedback">
                          {{ form.errors.children?.[index]?.name?.en }}
                        </div>
                      </div>
                    </div>
                  </div>
                </template>
              </draggable>
            </div>

            <!-- 狀態 -->
            <div class="form-check form-switch mb-4">
              <input
                v-model="form.status"
                type="checkbox"
                id="status"
                class="form-check-input"
              />
              <label class="form-check-label" for="status">
                {{ form.status ? '啟用' : '停用' }}
              </label>
            </div>

            <!-- 按鈕 -->
            <div class="text-end">
                <button type="button" class="btn btn-secondary me-2" @click="back">
                    <i class="fa fa-arrow-left me-1"></i>
                    回上一頁
                </button>
                <button
                    type="button"
                    class="btn btn-primary"
                    :disabled="form.processing"
                    @click="onSubmit"
                >
                    <span v-if="form.processing">
                        <i class="fa fa-spinner fa-spin me-1"></i>
                        處理中...
                    </span>
                    <span v-else>
                        <i class="fa fa-save me-1"></i>
                        儲存
                    </span>
                </button>
            </div>
          </form>
        </div>
      </div>
    </div>
</template>

<script setup>
  import { ref, inject } from 'vue';
  import { useForm, router, Link } from '@inertiajs/vue3';
  import draggable from 'vuedraggable';
  import { FormValidator, useSubmitForm } from '@/utils';
  import Layout from '@/Shared/Admin/Layout.vue';
  import BreadcrumbItem from '@/Shared/Admin/Partials/BreadcrumbItem.vue';
  import { nanoid } from 'nanoid';

  const props = defineProps({
    categoryType:  { type: String, required: true },
    categoryTitle: { type: String, required: true },
    routeName:     { type: String, required: true },
    isEditing:     { type: Boolean, default: false },
    category:      { type: Object, default: () => ({ children: [] }) },
    nextSeq:       { type: Number, default: 1 },
    allowSubcategories: { type: Boolean, default: true }, // 預設允許子分類
    requireSubcategories: { type: Boolean, default: true }, // 預設子分類必填
  });

  // 注入服務
  const sweetAlert = inject('$sweetAlert');

  // 響應式變數
  const isRemoving = ref(false);

  // 驗證規則
  const getRules = () => {
    const rules = {
      'parent_name.zh_TW': ['required','string',['max',255]],
      seq:                 ['required'],
    };

    // news 類型不需要英文欄位
    if (props.categoryType !== 'news') {
      rules['parent_name.en'] = ['required','string',['max',255]];
    }

    // 只要允許子分類，就加入 children 驗證（有子分類時名稱必填）
    // requireSubcategories 只控制是否可以刪到 0 個，不影響驗證規則
    if (props.allowSubcategories !== false) {
      rules.children = {
        '*': {
          name: {
            'zh_TW': ['required'],
            en:      ['required']
          }
        }
      };
    }

    return rules;
  };

  // 初始化表單
  const initialChildren = props.allowSubcategories !== false
    ? (props.isEditing && props.category.children?.length
        ? props.category.children.map(c => ({
            uid:   nanoid(),
            id:    c.id, // 保留原始 ID
            name:  { 'zh_TW': c.name['zh_TW'], en: c.name.en },
            seq:   c.seq,
          }))
        // 子分類必填時預設一個空子分類，非必填時預設空陣列
        : (props.requireSubcategories !== false ? [{ uid: nanoid(), name:{'zh_TW':'',en:''}, seq:0 }] : []))
    : []; // 不允許子分類時，children 為空陣列

  const formData = {
    type: props.categoryType,
    parent_name: {
      'zh_TW': props.category.parent_name?.['zh_TW'] ?? '',
      en:      props.category.parent_name?.en       ?? '',
    },
    seq:      props.category.seq ?? props.nextSeq ?? 1,
    status:   props.category.status ?? true,
  };
  
  // 只有允許子分類時才加入 children
  if (props.allowSubcategories !== false) {
    formData.children = initialChildren;
  }
  
  const form = useForm(formData);

  const validator = new FormValidator(form, getRules);
  const { submitForm } = useSubmitForm();
  const draggingIndex = ref(null);

  // 新增子分類
  function addChild() {
    if (props.allowSubcategories === false) return; // 不允許子分類時直接返回
    if (!form.children) form.children = []; // 確保 children 存在
    form.children.push({ uid: nanoid(), name:{'zh_TW':'',en:''}, seq: form.children.length });
  }

  // 處理移除子分類的邏輯
  function handleRemoveChild(index, child) {
    // 新增模式：直接從陣列中移除
    if (!props.isEditing) {
      removeChildFromArray(index);
      return;
    }

    // 編輯模式：如果是新增的子分類（沒有 ID），直接移除
    if (!child.id) {
      removeChildFromArray(index);
      return;
    }

    // 編輯模式：如果是既有的子分類（有 ID），透過 API 刪除
    removeExistingChild(child.id, child.name['zh_TW'] || child.name.en, index);
  }

  // 從陣列中移除子分類
  function removeChildFromArray(index) {
    // 只有子分類必填時才限制至少要有一個
    if (props.requireSubcategories !== false && form.children.length === 1) {
      sweetAlert.error({ msg: '至少要有一個子分類' });
      return;
    }
    form.children.splice(index, 1);
    reorderChildren();
  }

  // 透過 API 刪除既有的子分類
  function removeExistingChild(childId, childName, index) {
    const title = `確定要刪除子分類「${childName}」嗎？`;
    const message = '刪除後如果有影音正在使用此分類將無法復原';

    sweetAlert.deleteConfirm(title, () => {
      isRemoving.value = true;

      router.delete(route(`${props.routeName}.delete-child`, childId), {
        preserveState: true,
        onSuccess: (response) => {
          const result = response.props.flash?.result || response.props.result;

          if (result) {
            // 無論成功或失敗都顯示訊息
            if (result.status) {
              // 刪除成功，從前端陣列中移除
              removeChildFromArray(index);
              sweetAlert.success(result);
            } else {
              // 刪除失敗，顯示錯誤訊息（如：子分類正在被使用）
              sweetAlert.error(result);
            }
          } else {
            sweetAlert.error({ msg: '刪除失敗' });
          }
        },
        onError: (errors) => {
          console.error('刪除子分類失敗:', errors);

          // 處理後端回傳的錯誤訊息
          if (errors.message) {
            sweetAlert.error({ msg: errors.message });
          } else {
            sweetAlert.error({ msg: '刪除失敗，請重試！' });
          }
        },
        onFinish: () => {
          isRemoving.value = false;
        }
      });
    });
  }

  // 重新排序子分類
  function reorderChildren() {
    if (!form.children || form.children.length === 0) return;
    form.children.forEach((c, i) => c.seq = i);
  }

  // 拖曳結束處理
  function onDragEnd() {
    reorderChildren();
    draggingIndex.value = null;
  }

  // 提交表單
  async function onSubmit() {
    form.clearErrors();
    const hasErr = await validator.hasErrors();
    if (hasErr) return;

    submitForm({
        form,
        url: props.isEditing
        ? route(`${props.routeName}.update`, props.category.id)
        : route(`${props.routeName}.store`),
        method: props.isEditing ? 'put' : 'post',
    });
  }

  const back = () => router.visit(route(props.routeName));
  </script>

  <script>
  export default { layout: Layout };
  </script>
