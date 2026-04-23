<!-- Modules/ProductListing/Vue/Form.vue -->
<!-- 商品上架管理 - 新增/編輯（三個 Tab：商品簡介、商品規格、介紹編輯器） -->
<template>
    <div class="content">
        <BreadcrumbItem />

        <!-- Tabs Navigation -->
        <ul class="nav nav-tabs nav-tabs-alt mb-0" role="tablist">
            <li class="nav-item">
                <a class="nav-link" :class="{ active: activeTab === 'info' }" href="#" @click.prevent="activeTab = 'info'">
                    <i class="fa fa-clipboard-list me-1"></i> 商品簡介
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" :class="{ active: activeTab === 'spec' }" href="#" @click.prevent="activeTab = 'spec'">
                    <i class="fa fa-th me-1"></i> 商品規格
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" :class="{ active: activeTab === 'editor' }" href="#" @click.prevent="activeTab = 'editor'">
                    <i class="fa fa-edit me-1"></i> 介紹編輯器
                </a>
            </li>
        </ul>

        <!-- ===== Tab 1: 商品簡介 ===== -->
        <div class="block block-rounded block-rounded-top-0" v-show="activeTab === 'info'">
            <div class="block-header block-header-default">
                <h3 class="block-title">基本資訊</h3>
            </div>
            <div class="block-content block-content-full">
                <!-- 主圖 -->
                <div class="row mb-4">
                    <label class="col-sm-2 col-form-label">主圖</label>
                    <div class="col-sm-10">
                        <div v-if="form.main_image" class="mb-2 position-relative d-inline-block">
                            <img :src="form.main_image" class="img-thumbnail" style="max-height: 150px;" />
                            <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0" @click="form.main_image = null">
                                <i class="fa fa-times"></i>
                            </button>
                        </div>
                        <div>
                            <label class="btn btn-sm btn-success">
                                <i class="fa fa-upload me-1"></i> 上傳主圖
                                <input type="file" class="d-none" accept="image/*" @change="uploadMainImage" />
                            </label>
                            <span class="text-muted small ms-2">限制 1 張</span>
                        </div>
                    </div>
                </div>

                <!-- 狀態 -->
                <div class="row mb-3">
                    <label class="col-sm-2 col-form-label"><span class="text-danger">*</span> 狀態</label>
                    <div class="col-sm-10">
                        <select class="form-select" v-model="form.status">
                            <option :value="1">上架</option>
                            <option :value="0">下架</option>
                        </select>
                    </div>
                </div>

                <!-- 商品類型 -->
                <div class="row mb-3">
                    <label class="col-sm-2 col-form-label"><span class="text-danger">*</span> 商品類型</label>
                    <div class="col-sm-10">
                        <select class="form-select" v-model="form.type">
                            <option value="regular">一般商品</option>
                            <option value="gift">贈品</option>
                        </select>
                    </div>
                </div>

                <!-- 商品分類（贈品不需要分類） -->
                <div class="row mb-3" v-if="form.type !== 'gift'">
                    <label class="col-sm-2 col-form-label">商品分類</label>
                    <div class="col-sm-10">
                        <TreeCheckbox
                            v-model="form.category_ids"
                            :nodes="categories"
                            label=""
                        />
                        <small class="text-muted mt-1 d-block">可勾選多個分類</small>
                    </div>
                </div>

                <!-- 商品名稱 -->
                <div class="row mb-3">
                    <label class="col-sm-2 col-form-label"></label>
                    <div class="col-sm-10">
                        <TranslatableInput
                            v-model="form.name"
                            label="商品名稱"
                            placeholder="請輸入商品名稱"
                            :required="true"
                        />
                    </div>
                </div>

                <!-- 售價 -->
                <div class="row mb-3">
                    <label class="col-sm-2 col-form-label"><span class="text-danger">*</span> 售價</label>
                    <div class="col-sm-10">
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" v-model.number="form.price" min="0" step="1" placeholder="0" />
                        </div>
                    </div>
                </div>

                <!-- 庫存（無規格時顯示） -->
                <div v-if="!form.spec_combination_id" class="row mb-3">
                    <label class="col-sm-2 col-form-label">庫存</label>
                    <div class="col-sm-10">
                        <input type="number" class="form-control" v-model.number="form.stock" min="0" step="1" placeholder="0" style="max-width: 200px;" />
                    </div>
                </div>

                <!-- 是否熱銷 -->
                <div class="row mb-3">
                    <label class="col-sm-2 col-form-label">是否熱銷</label>
                    <div class="col-sm-10">
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" v-model="form.is_hot" />
                            <label class="form-check-label">{{ form.is_hot ? '是' : '否' }}</label>
                        </div>
                    </div>
                </div>

                <!-- 多張圖片 -->
                <div class="row mb-3">
                    <label class="col-sm-2 col-form-label">商品圖片</label>
                    <div class="col-sm-10">
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            <div v-for="(img, idx) in form.gallery_images" :key="idx" class="position-relative d-inline-block">
                                <img :src="img.image_path" class="img-thumbnail" style="width: 100px; height: 100px; object-fit: cover;" />
                                <button type="button" class="btn btn-xs btn-danger position-absolute top-0 end-0" @click="removeGalleryImage(idx)">
                                    <i class="fa fa-times" style="font-size: .7rem;"></i>
                                </button>
                            </div>
                        </div>
                        <label class="btn btn-sm btn-outline-primary">
                            <i class="fa fa-images me-1"></i> 上傳圖片
                            <input type="file" class="d-none" accept="image/*" multiple @change="uploadGalleryImages" />
                        </label>
                        <span class="text-muted small ms-2">最多 10 張</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== Tab 2: 商品規格 ===== -->
        <div class="block block-rounded block-rounded-top-0" v-show="activeTab === 'spec'">
            <div class="block-header block-header-default">
                <h3 class="block-title">商品規格</h3>
            </div>
            <div class="block-content block-content-full">
                <!-- 選擇規格組合 -->
                <div class="row mb-4">
                    <label class="col-sm-2 col-form-label">規格組合</label>
                    <div class="col-sm-10">
                        <div class="d-flex align-items-center gap-2">
                            <select class="form-select" v-model="form.spec_combination_id" @change="onCombinationChange" style="max-width: 400px;">
                                <option :value="null">不使用規格</option>
                                <option v-for="combo in combinations" :key="combo.id" :value="combo.id">
                                    {{ combo.name_zh || combo.label }} ({{ combo.label }})
                                </option>
                            </select>
                            <button
                                v-if="form.spec_combination_id"
                                class="btn btn-sm btn-outline-primary"
                                @click="generateSkus"
                                :disabled="isGenerating"
                            >
                                <i class="fa fa-sync-alt me-1"></i>
                                {{ isGenerating ? '產生中...' : '重新產生 SKU' }}
                            </button>
                        </div>
                        <small class="text-muted mt-1 d-block">選擇後將自動產生所有規格值的組合（笛卡爾積）</small>
                    </div>
                </div>

                <!-- SKU 矩陣 -->
                <div v-if="form.skus.length > 0">
                    <!-- 批次設定 -->
                    <div class="card border mb-3">
                        <div class="card-body py-2 px-3">
                            <div class="d-flex align-items-center flex-wrap gap-3">
                                <strong class="text-dark"><i class="fa fa-layer-group me-1"></i> 批次設定</strong>
                                <div class="d-flex align-items-center gap-2">
                                    <label class="form-label mb-0 small">價格</label>
                                    <div class="input-group input-group-sm" style="width: 140px;">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control" v-model.number="batchPrice" min="0" step="1" placeholder="價格" />
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <label class="form-label mb-0 small">庫存</label>
                                    <input type="number" class="form-control form-control-sm" v-model.number="batchStock" min="0" placeholder="庫存" style="width: 100px;" />
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-success" @click="applyBatch">
                                    <i class="fa fa-check-double me-1"></i> 套用至全部
                                </button>
                                <small class="text-muted">僅套用有填寫的欄位</small>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-vcenter mb-0">
                            <thead class="bg-body-light">
                                <tr>
                                    <th>規格組合</th>
                                    <th style="width: 150px;">SKU 編號</th>
                                    <th style="width: 120px;">價格</th>
                                    <th style="width: 100px;">庫存</th>
                                    <th style="width: 80px;" class="text-center">狀態</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(sku, i) in form.skus" :key="i">
                                    <td>
                                        <div class="d-flex flex-wrap gap-1">
                                            <span
                                                v-for="(item, vi) in (sku.items || [])"
                                                :key="vi"
                                                class="spec-chip"
                                                :class="'spec-chip-' + (vi % 4)"
                                            >
                                                <span class="spec-chip-group">{{ item.group_name }}</span>
                                                <span class="spec-chip-value">{{ item.value_name }}</span>
                                            </span>
                                        </div>
                                        <span v-if="!sku.items || sku.items.length === 0" class="text-muted">
                                            {{ sku.combination_label }}
                                        </span>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm" v-model="form.skus[i].sku" placeholder="選填" />
                                    </td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm" v-model.number="form.skus[i].price" min="0" step="1" placeholder="0" />
                                    </td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm" v-model.number="form.skus[i].stock" min="0" placeholder="0" />
                                    </td>
                                    <td class="text-center">
                                        <div class="form-check form-switch d-flex justify-content-center">
                                            <input class="form-check-input" type="checkbox" v-model="form.skus[i].status" />
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-muted small mt-2">
                        <i class="fa fa-info-circle me-1"></i>
                        共 {{ form.skus.length }} 組 SKU，請填入各規格的價格與庫存
                    </div>
                </div>

                <div v-else-if="form.spec_combination_id" class="text-center text-muted py-4">
                    <i class="fa fa-sync-alt me-1"></i>
                    請點擊「重新產生 SKU」建立規格組合矩陣
                </div>

                <div v-else class="text-center text-muted py-4">
                    <i class="fa fa-info-circle me-1"></i>
                    請先在上方選擇規格組合
                </div>
            </div>
        </div>

        <!-- ===== Tab 3: 介紹編輯器 ===== -->
        <div class="block block-rounded block-rounded-top-0" v-show="activeTab === 'editor'">
            <div class="block-header block-header-default">
                <h3 class="block-title">商品描述</h3>
            </div>
            <div class="block-content block-content-full">
                <TranslatableEditor
                    v-model="form.description"
                    label="商品描述"
                />
            </div>
        </div>

        <!-- ===== 底部按鈕 ===== -->
        <div class="block block-rounded">
            <div class="block-content block-content-full text-end">
                <Link :href="route('admin.product-listings.index')" class="btn btn-secondary me-2">
                    <i class="fa fa-arrow-left me-1"></i> 返回列表
                </Link>
                <button class="btn btn-primary" @click="submitForm" :disabled="isSubmitting">
                    <i class="fa fa-check me-1"></i>
                    {{ isSubmitting ? '儲存中...' : (isEdit ? '更新商品' : '新增商品') }}
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, reactive, inject } from "vue";
import { Link, router, usePage } from "@inertiajs/vue3";
import Layout from "@/Shared/Admin/Layout.vue";
import BreadcrumbItem from "@/Shared/Admin/Partials/BreadcrumbItem.vue";
import TranslatableInput from "@/Shared/Admin/Components/TranslatableInput.vue";
import TranslatableEditor from "@/Shared/Admin/Components/TranslatableEditor.vue";
import TreeCheckbox from "@/Shared/Admin/Components/TreeCheckbox.vue";

export default {
    components: { BreadcrumbItem, Link, TranslatableInput, TranslatableEditor, TreeCheckbox },
    props: {
        data:         { type: Object, default: null },
        isEdit:       { type: Boolean, default: false },
        combinations: { type: Array, default: () => [] },
        categories:   { type: Array, default: () => [] },
    },
    setup(props) {
        const sweetAlert = inject('$sweetAlert');
        const page = usePage();

        const activeTab = ref('info');
        const isSubmitting = ref(false);
        const isGenerating = ref(false);
        const batchPrice = ref(null);
        const batchStock = ref(null);

        // ===== 根據 config 動態建立多語欄位預設值 =====
        const locales = page.props.translatableLocales || { zh_TW: { label: '中文' } };
        const primaryLocale = page.props.translatablePrimary || 'zh_TW';

        const buildTranslatable = (field) => {
            const obj = {};
            for (const key of Object.keys(locales)) {
                obj[key] = props.data?.[field]?.[key] || '';
            }
            return obj;
        };

        // ===== Form Data =====
        const d = props.data;
        const form = reactive({
            name:                buildTranslatable('name'),
            type:                d?.type ?? 'regular',
            status:              d?.status ?? 1,
            price:               d?.price ?? 0,
            stock:               d?.stock ?? 0,
            is_hot:              d?.is_hot ?? false,
            seq:                 d?.seq ?? 0,
            category_ids:        d?.category_ids ?? [],
            spec_combination_id: d?.spec_combination_id ?? null,
            description:         buildTranslatable('description'),
            main_image:     d?.main_image || null,
            gallery_images: d?.gallery_images || [],
            skus:           d?.skus || [],
        });

        // ===== 圖片上傳 =====
        const uploadMainImage = (e) => {
            const file = e.target.files[0];
            if (!file) return;

            const fd = new FormData();
            fd.append('image', file);

            axios.post(route('admin.api.product-listings.upload-image'), fd)
                .then(res => {
                    if (res.data.status) {
                        form.main_image = res.data.path;
                    }
                })
                .catch(() => { sweetAlert.error({ msg: '上傳失敗' }); });

            e.target.value = '';
        };

        const uploadGalleryImages = (e) => {
            const files = Array.from(e.target.files);
            if (!files.length) return;

            const remaining = 10 - form.gallery_images.length;
            if (remaining <= 0) {
                sweetAlert.error({ msg: '已達 10 張上限' });
                return;
            }

            const uploads = files.slice(0, remaining);
            uploads.forEach(file => {
                const fd = new FormData();
                fd.append('image', file);

                axios.post(route('admin.api.product-listings.upload-image'), fd)
                    .then(res => {
                        if (res.data.status) {
                            form.gallery_images.push({
                                image_path: res.data.path,
                                seq: form.gallery_images.length,
                            });
                        }
                    })
                    .catch(() => { sweetAlert.error({ msg: '圖片上傳失敗' }); });
            });

            e.target.value = '';
        };

        const removeGalleryImage = (idx) => {
            form.gallery_images.splice(idx, 1);
        };

        // ===== 規格組合 =====
        const onCombinationChange = () => {
            if (form.spec_combination_id) {
                generateSkus();
            } else {
                form.skus = [];
            }
        };

        const generateSkus = () => {
            if (!form.spec_combination_id) return;

            isGenerating.value = true;
            axios.post(route('admin.api.product-listings.generate-sku-matrix'), {
                combination_id: form.spec_combination_id,
            })
            .then(res => {
                if (res.data.status) {
                    // 保留舊的價格/庫存/SKU (如有匹配的 spec_value_ids)
                    const oldSkus = [...form.skus];
                    const newSkus = res.data.data.map(newSku => {
                        const oldMatch = oldSkus.find(old =>
                            JSON.stringify(old.spec_value_ids) === JSON.stringify(newSku.spec_value_ids)
                        );
                        if (oldMatch) {
                            return {
                                ...newSku,
                                sku:    oldMatch.sku || newSku.sku,
                                price:  oldMatch.price ?? newSku.price,
                                stock:  oldMatch.stock ?? newSku.stock,
                                status: oldMatch.status ?? newSku.status,
                            };
                        }
                        return newSku;
                    });
                    form.skus = newSkus;
                } else {
                    sweetAlert.error({ msg: res.data.msg || '產生失敗' });
                }
            })
            .catch(err => {
                sweetAlert.error({ msg: err.response?.data?.msg || '產生 SKU 失敗' });
            })
            .finally(() => { isGenerating.value = false; });
        };

        // ===== 批次設定 =====
        const applyBatch = () => {
            const hasPrice = batchPrice.value !== null && batchPrice.value !== '';
            const hasStock = batchStock.value !== null && batchStock.value !== '';

            if (!hasPrice && !hasStock) {
                sweetAlert.error({ msg: '請至少填寫價格或庫存' });
                return;
            }

            form.skus.forEach(sku => {
                if (hasPrice) sku.price = Number(batchPrice.value);
                if (hasStock) sku.stock = Number(batchStock.value);
            });

            sweetAlert.success({ msg: `已套用至 ${form.skus.length} 組 SKU` });
        };

        // ===== 送出 =====
        const submitForm = () => {
            if (!form.name[primaryLocale]?.trim()) {
                sweetAlert.error({ msg: '請輸入商品名稱' });
                activeTab.value = 'info';
                return;
            }

            isSubmitting.value = true;

            const payload = {
                name:                form.name,
                type:                form.type,
                status:              form.status,
                price:               form.price,
                stock:               form.stock,
                is_hot:              form.is_hot,
                seq:                 form.seq,
                category_ids:        form.category_ids,
                spec_combination_id: form.spec_combination_id,
                description:         form.description,
                main_image:          form.main_image,
                gallery_images:      form.gallery_images,
                skus:                form.skus.map(s => ({
                    spec_value_ids:    s.spec_value_ids,
                    combination_label: s.combination_label,
                    sku:               s.sku || null,
                    price:             s.price ?? 0,
                    stock:             s.stock ?? 0,
                    status:            s.status ?? true,
                })),
            };

            if (props.isEdit) {
                router.put(route('admin.product-listings.update', props.data.id), payload, {
                    onSuccess: () => { isSubmitting.value = false; },
                    onError: () => {
                        isSubmitting.value = false;
                        sweetAlert.error({ msg: '更新失敗' });
                    },
                });
            } else {
                router.post(route('admin.product-listings.store'), payload, {
                    onSuccess: () => { isSubmitting.value = false; },
                    onError: () => {
                        isSubmitting.value = false;
                        sweetAlert.error({ msg: '新增失敗' });
                    },
                });
            }
        };

        return {
            activeTab,
            form,
            isSubmitting,
            isGenerating,
            batchPrice,
            batchStock,
            applyBatch,
            // image
            uploadMainImage,
            uploadGalleryImages,
            removeGalleryImage,
            // spec
            onCombinationChange,
            generateSkus,
            // submit
            submitForm,
            // props passthrough
            isEdit: props.isEdit,
            combinations: props.combinations,
            categories: props.categories,
        };
    },
    layout: Layout,
};
</script>

<style scoped>
.nav-tabs-alt .nav-link {
    border: none;
    border-bottom: 2px solid transparent;
    color: #6c757d;
    padding: 12px 20px;
    font-weight: 500;
}
.nav-tabs-alt .nav-link.active {
    color: #3b82f6;
    border-bottom-color: #3b82f6;
    background: transparent;
}
.nav-tabs-alt .nav-link:hover:not(.active) {
    color: #1e3a5f;
    border-bottom-color: #dee2e6;
}
.block-rounded-top-0 {
    border-top-left-radius: 0 !important;
    border-top-right-radius: 0 !important;
}

/* Spec combination chips */
.spec-chip {
    display: inline-flex;
    align-items: center;
    border-radius: 6px;
    overflow: hidden;
    font-size: 0.8rem;
    line-height: 1;
    border: 1px solid rgba(0,0,0,.08);
}
.spec-chip-group {
    padding: 4px 6px;
    font-weight: 600;
    color: #fff;
    white-space: nowrap;
}
.spec-chip-value {
    padding: 4px 8px;
    font-weight: 500;
    white-space: nowrap;
}

/* Color variants */
.spec-chip-0 { border-color: #6366f1; }
.spec-chip-0 .spec-chip-group { background: #6366f1; }
.spec-chip-0 .spec-chip-value { background: #eef2ff; color: #4338ca; }

.spec-chip-1 { border-color: #0ea5e9; }
.spec-chip-1 .spec-chip-group { background: #0ea5e9; }
.spec-chip-1 .spec-chip-value { background: #e0f2fe; color: #0369a1; }

.spec-chip-2 { border-color: #10b981; }
.spec-chip-2 .spec-chip-group { background: #10b981; }
.spec-chip-2 .spec-chip-value { background: #ecfdf5; color: #047857; }

.spec-chip-3 { border-color: #f59e0b; }
.spec-chip-3 .spec-chip-group { background: #f59e0b; }
.spec-chip-3 .spec-chip-value { background: #fffbeb; color: #b45309; }
</style>
