<!-- Modules/OrderManagement/Vue/Index.vue -->
<!-- 訂單管理 - 列表頁（含批次操作） -->
<template>
    <div class="content">
        <BreadcrumbItem />

        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">訂單管理</h3>
            </div>

            <div class="block-content block-content-full">
                <!-- 出貨流程指示器 -->
                <div class="workflow-stepper d-flex align-items-start justify-content-center mb-4">
                    <template v-for="(step, idx) in workflowSteps" :key="step.key">
                        <div class="wf-step text-center" :class="{ 'wf-active': currentStep === step.key }" @click="switchStep(step.key)">
                            <div class="wf-dot" :class="'wf-dot-' + step.color">
                                <i :class="step.icon"></i>
                            </div>
                            <div class="fs-xs fw-semibold mt-1">{{ step.label }}</div>
                            <div class="mt-1" v-if="step.count > 0">
                                <span class="badge rounded-pill fs-xs" :class="'bg-' + step.color">{{ step.count }} 筆</span>
                            </div>
                        </div>
                        <div class="wf-connector" v-if="idx < workflowSteps.length - 1">
                            <i class="fa fa-chevron-right text-muted"></i>
                        </div>
                    </template>
                </div>

                <!-- 流程頁籤 -->
                <ul class="nav nav-tabs nav-tabs-alt mb-0">
                    <li class="nav-item">
                        <a class="nav-link" :class="{ active: currentStep === 'all' }" href="javascript:void(0)" @click="switchStep('all')">
                            <i class="fa fa-list me-1"></i> 全部
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" :class="{ active: currentStep === 'ready_to_ship' }" href="javascript:void(0)" @click="switchStep('ready_to_ship')">
                            <i class="fa fa-clipboard-check me-1"></i> ① 篩選出貨
                            <span class="badge bg-info ms-1" v-if="stepCounts.ready_to_ship > 0">{{ stepCounts.ready_to_ship }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" :class="{ active: currentStep === 'awaiting_shipment' }" href="javascript:void(0)" @click="switchStep('awaiting_shipment')">
                            <i class="fa fa-box-open me-1"></i> ② 待出貨
                            <span class="badge bg-primary ms-1" v-if="stepCounts.awaiting_shipment > 0">{{ stepCounts.awaiting_shipment }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" :class="{ active: currentStep === 'shipped' }" href="javascript:void(0)" @click="switchStep('shipped')">
                            <i class="fa fa-truck me-1"></i> ③ 已出貨
                            <span class="badge bg-indigo ms-1" v-if="stepCounts.shipped > 0">{{ stepCounts.shipped }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" :class="{ active: currentStep === 'completed' }" href="javascript:void(0)" @click="switchStep('completed')">
                            <i class="fa fa-check-circle me-1"></i> ④ 已完成
                        </a>
                    </li>
                    <!-- 分隔線 -->
                    <li class="nav-item ms-auto">
                        <a class="nav-link" :class="{ active: isOtherStep }" href="javascript:void(0)" @click="switchStep('other_pending')">
                            <i class="fa fa-folder-open me-1"></i> 其他
                            <span class="badge bg-secondary ms-1" v-if="otherTotalCount > 0">{{ otherTotalCount }}</span>
                        </a>
                    </li>
                </ul>

                <!-- 其他分頁的子頁籤 -->
                <div v-if="isOtherStep" class="d-flex align-items-center gap-2 py-2 px-1 bg-body-light border-bottom">
                    <span class="fs-sm text-muted me-1"><i class="fa fa-filter me-1"></i>子分類：</span>
                    <button
                        v-for="sub in otherSubSteps"
                        :key="sub.key"
                        class="btn btn-sm"
                        :class="currentStep === sub.key ? 'btn-' + sub.color : 'btn-outline-' + sub.color"
                        @click="switchStep(sub.key)"
                    >
                        <i :class="sub.icon" class="me-1"></i>
                        {{ sub.label }}
                        <span class="badge bg-white text-dark ms-1" v-if="sub.count > 0">{{ sub.count }}</span>
                    </button>
                </div>

                <!-- 步驟操作說明 -->
                <div v-if="currentStepGuide" class="step-guide p-3 mb-3 border-start border-4" :class="currentStepGuide.cls">
                    <div class="d-flex align-items-start">
                        <i :class="currentStepGuide.icon" class="me-2 mt-1 fs-5"></i>
                        <div>
                            <div class="fw-bold mb-1">{{ currentStepGuide.title }}</div>
                            <div class="fs-sm text-muted">{{ currentStepGuide.desc }}</div>
                        </div>
                    </div>
                </div>

                <!-- 篩選列 -->
                <div class="row g-2 mb-3">
                    <div class="col-md-3">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="fa fa-search"></i></span>
                            <input 
                                type="text" 
                                class="form-control" 
                                v-model="searchKeyword"
                                placeholder="搜尋訂單號、姓名、手機..."
                                @keyup.enter="doSearch"
                            />
                        </div>
                    </div>
                    <div class="col-md-2" v-if="currentStep === 'all'">
                        <select class="form-select form-select-sm" v-model="filterStatus" @change="doSearch">
                            <option value="">全部狀態</option>
                            <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">
                                {{ opt.label }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-2" v-if="currentStep === 'all' || currentStep === 'ready_to_ship'">
                        <select class="form-select form-select-sm" v-model="filterPayment" @change="doSearch">
                            <option value="">全部付款方式</option>
                            <option v-for="opt in paymentMethodOptions" :key="opt.value" :value="opt.value">
                                {{ opt.label }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select form-select-sm" v-model="filterShipping" @change="doSearch">
                            <option value="">全部物流方式</option>
                            <option v-for="opt in shippingMethodOptions" :key="opt.value" :value="opt.value">
                                {{ opt.label }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="date" class="form-control form-control-sm" v-model="filterDateFrom" placeholder="開始日期" />
                    </div>
                    <div class="col-md-1">
                        <button class="btn btn-sm btn-alt-secondary w-100" @click="resetFilters" title="清除篩選">
                            <i class="fa fa-times"></i>
                        </button>
                    </div>
                </div>

                <!-- 批次操作工具列 -->
                <transition name="slide-down">
                    <div class="batch-toolbar mb-3 p-3 bg-body-light rounded border" v-if="selectedIds.length > 0">
                        <div class="d-flex align-items-center flex-wrap gap-2">
                            <div class="fw-semibold text-primary">
                                <i class="fa fa-check-square me-1"></i>
                                已選取 <span class="badge bg-primary">{{ selectedIds.length }}</span> 筆訂單
                            </div>
                            <div class="vr mx-2 d-none d-md-block"></div>
                            <!-- Step 1: 標記待出貨 -->
                            <template v-if="currentStep === 'ready_to_ship'">
                                <button class="btn btn-sm btn-primary" @click="batchMarkAwaitingShipment">
                                    <i class="fa fa-box-open me-1"></i> 標記為待出貨
                                </button>
                            </template>
                            <!-- Step 2: 確認已出貨 + 列印 -->
                            <template v-if="currentStep === 'awaiting_shipment'">
                                <button class="btn btn-sm btn-indigo" @click="batchShip">
                                    <i class="fa fa-truck me-1"></i> 確認已出貨
                                </button>
                                <button class="btn btn-sm btn-alt-primary" @click="batchPrint">
                                    <i class="fa fa-print me-1"></i> 列印託運單
                                </button>
                            </template>
                            <!-- Step 3: 更新物流 -->
                            <template v-if="currentStep === 'shipped'">
                                <button class="btn btn-sm btn-alt-info" @click="batchUpdateStatus">
                                    <i class="fa fa-sync me-1"></i> 更新物流狀態
                                </button>
                                <button class="btn btn-sm btn-alt-primary" @click="batchPrint">
                                    <i class="fa fa-print me-1"></i> 重印託運單
                                </button>
                            </template>
                            <!-- 全部 -->
                            <template v-if="currentStep === 'all'">
                                <button class="btn btn-sm btn-primary" @click="batchMarkAwaitingShipment">
                                    <i class="fa fa-box-open me-1"></i> 標記待出貨
                                </button>
                                <button class="btn btn-sm btn-indigo" @click="batchShip">
                                    <i class="fa fa-truck me-1"></i> 確認已出貨
                                </button>
                                <button class="btn btn-sm btn-alt-primary" @click="batchPrint">
                                    <i class="fa fa-print me-1"></i> 列印託運單
                                </button>
                            </template>
                            <!-- 其他：待付款 → 可批次取消 -->
                            <template v-if="currentStep === 'other_pending'">
                                <button class="btn btn-sm btn-danger" @click="batchCancel">
                                    <i class="fa fa-ban me-1"></i> 批次取消訂單
                                </button>
                            </template>
                            <!-- 其他：退款申請 → 可批次核准退款 -->
                            <template v-if="currentStep === 'other_refund_requested'">
                                <button class="btn btn-sm btn-warning" @click="batchApproveRefund">
                                    <i class="fa fa-undo me-1"></i> 批次核准退款
                                </button>
                            </template>
                            <button class="btn btn-sm btn-alt-warning" @click="batchExport">
                                <i class="fa fa-file-excel me-1"></i> 匯出
                            </button>
                            <div class="ms-auto">
                                <button class="btn btn-sm btn-alt-secondary" @click="clearSelection">
                                    <i class="fa fa-times me-1"></i> 取消選取
                                </button>
                            </div>
                        </div>
                    </div>
                </transition>

                <!-- DataTable -->
                <DataTable
                    class="table table-bordered table-striped table-vcenter table-hover"
                    :columns="columns"
                    :options="options"
                    ref="table"
                />
            </div>
        </div>

        <!-- 批次出貨確認 Modal -->
        <div class="modal fade" id="batchShipModal" tabindex="-1" role="dialog" ref="batchShipModal">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title"><i class="fa fa-truck me-2"></i>批次出貨確認</h5>
                        <button type="button" class="btn-close btn-close-white" @click="closeBatchShipModal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info d-flex align-items-start">
                            <i class="fa fa-info-circle me-2 mt-1"></i>
                            <div>
                                共 <strong>{{ batchShipOrders.length }}</strong> 筆訂單將執行出貨，請確認以下資訊。
                                系統會自動根據物流方式呼叫綠界建立物流訂單並取得託運單號。
                            </div>
                        </div>
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>訂單號</th>
                                    <th>買家</th>
                                    <th>物流方式</th>
                                    <th>收件資訊</th>
                                    <th>狀態</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="o in batchShipOrders" :key="o.id">
                                    <td class="fw-semibold">{{ o.order_no }}</td>
                                    <td>{{ o.buyer_name }}</td>
                                    <td>
                                        <span class="badge" :class="o.shipping_method?.startsWith('cvs') ? 'bg-success' : 'bg-info'">
                                            {{ o.shipping_method_label }}
                                        </span>
                                    </td>
                                    <td class="fs-sm">
                                        <template v-if="o.shipping_method?.startsWith('cvs')">
                                            {{ o.receiver_store_name || '—' }}
                                        </template>
                                        <template v-else>
                                            {{ o.receiver_address || '宅配地址' }}
                                        </template>
                                    </td>
                                    <td>
                                        <span class="badge bg-warning"><i class="fa fa-clock me-1"></i>待出貨</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-alt-secondary" @click="closeBatchShipModal">取消</button>
                        <button type="button" class="btn btn-primary" @click="confirmBatchShip">
                            <i class="fa fa-truck me-1"></i> 確認批次出貨
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 批次列印 Modal -->
        <div class="modal fade" id="batchPrintModal" tabindex="-1" role="dialog" ref="batchPrintModal">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title"><i class="fa fa-print me-2"></i>批次列印託運單</h5>
                        <button type="button" class="btn-close btn-close-white" @click="closeBatchPrintModal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning d-flex align-items-start">
                            <i class="fa fa-exclamation-triangle me-2 mt-1"></i>
                            <div>
                                將開啟列印頁面，包含 <strong>{{ selectedIds.length }}</strong> 筆託運單。
                                請確認印表機已就緒。
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">列印格式</label>
                            <select class="form-select" v-model="printFormat">
                                <option value="thermal">熱感標籤（超商專用 100x150mm）</option>
                                <option value="a4">A4 紙張（宅配專用）</option>
                                <option value="auto">自動偵測（依物流方式）</option>
                            </select>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="markShipped" v-model="markShippedAfterPrint" />
                            <label class="form-check-label" for="markShipped">列印後自動標記為「已出貨」</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <div class="text-muted fs-xs me-auto">
                            <i class="fa fa-info-circle me-1"></i> 列印功能需串接綠界物流 API 後啟用
                        </div>
                        <button type="button" class="btn btn-alt-secondary" @click="closeBatchPrintModal">取消</button>
                        <button type="button" class="btn btn-info text-white" @click="confirmBatchPrint">
                            <i class="fa fa-print me-1"></i> 開始列印
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, reactive, computed, onMounted, inject, nextTick } from "vue";
import { Link, router } from "@inertiajs/vue3";
import Layout from "@/Shared/Admin/Layout.vue";
import BreadcrumbItem from "@/Shared/Admin/Partials/BreadcrumbItem.vue";
import DataTablesCore from "datatables.net-bs5";
import DataTable from "datatables.net-vue3";
import DataTableHelper from "@/utils/datatableHelper.js";

DataTable.use(DataTablesCore);

export default {
    components: { BreadcrumbItem, DataTable, Link },
    props: {
        list: { type: Object, default: null },
        statusOptions: { type: Array, default: () => [] },
        paymentMethodOptions: { type: Array, default: () => [] },
        shippingMethodOptions: { type: Array, default: () => [] },
    },
    setup(props) {
        const table = ref(null);
        const rows = ref([]);
        const sweetAlert = inject('$sweetAlert');

        // 篩選狀態
        const searchKeyword = ref('');
        const filterStatus = ref('');
        const filterPayment = ref('');
        const filterShipping = ref('');
        const filterDateFrom = ref('');
        const filterDateTo = ref('');
        const currentStep = ref('all');

        // 批次選取
        const selectedIds = ref([]);

        // 批次列印
        const printFormat = ref('auto');
        const markShippedAfterPrint = ref(true);
        const batchShipModal = ref(null);
        const batchPrintModal = ref(null);

        // 出貨流程步驟
        const workflowSteps = computed(() => {
            const allOrders = props.list?.data || [];
            return [
                {
                    key: 'ready_to_ship',
                    label: '篩選出貨',
                    icon: 'fa fa-clipboard-check',
                    color: 'info',
                    count: allOrders.filter(o => o.status === 'paid' || (o.status === 'pending' && o.payment_method === 'cod')).length,
                },
                {
                    key: 'awaiting_shipment',
                    label: '待出貨',
                    icon: 'fa fa-box-open',
                    color: 'primary',
                    count: allOrders.filter(o => o.status === 'awaiting_shipment').length,
                },
                {
                    key: 'shipped',
                    label: '已出貨',
                    icon: 'fa fa-truck',
                    color: 'indigo',
                    count: allOrders.filter(o => o.status === 'shipped').length,
                },
                {
                    key: 'completed',
                    label: '已完成',
                    icon: 'fa fa-check-circle',
                    color: 'success',
                    count: allOrders.filter(o => o.status === 'completed').length,
                },
            ];
        });

        // 各步驟筆數
        const stepCounts = computed(() => {
            const allOrders = props.list?.data || [];
            return {
                ready_to_ship: allOrders.filter(o => o.status === 'paid' || (o.status === 'pending' && o.payment_method === 'cod')).length,
                awaiting_shipment: allOrders.filter(o => o.status === 'awaiting_shipment').length,
                shipped: allOrders.filter(o => o.status === 'shipped').length,
                completed: allOrders.filter(o => o.status === 'completed').length,
                pending: allOrders.filter(o => o.status === 'pending' && o.payment_method !== 'cod').length,
                cancelled: allOrders.filter(o => o.status === 'cancelled').length,
                refund_requested: allOrders.filter(o => o.status === 'refund_requested').length,
                refunded: allOrders.filter(o => o.status === 'refunded').length,
            };
        });

        // 「其他」分頁相關
        const otherStepKeys = ['other_pending', 'other_cancelled', 'other_refund_requested', 'other_refunded'];
        const isOtherStep = computed(() => otherStepKeys.includes(currentStep.value));

        const otherSubSteps = computed(() => [
            { key: 'other_pending', label: '待付款', icon: 'fa fa-clock', color: 'warning', count: stepCounts.value.pending },
            { key: 'other_cancelled', label: '已取消', icon: 'fa fa-ban', color: 'danger', count: stepCounts.value.cancelled },
            { key: 'other_refund_requested', label: '退款申請', icon: 'fa fa-undo', color: 'orange', count: stepCounts.value.refund_requested },
            { key: 'other_refunded', label: '已退款', icon: 'fa fa-receipt', color: 'secondary', count: stepCounts.value.refunded },
        ]);

        const otherTotalCount = computed(() => {
            return stepCounts.value.pending + stepCounts.value.cancelled
                + stepCounts.value.refund_requested + stepCounts.value.refunded;
        });

        // 步驟操作說明
        const currentStepGuide = computed(() => {
            const guides = {
                ready_to_ship: {
                    icon: 'fa fa-clipboard-check text-info',
                    title: 'STEP 1：篩選要出貨的訂單',
                    desc: '這裡顯示「已付款」及「貨到付款」的訂單。可依配送方式篩選後，勾選訂單並點擊「標記為待出貨」。',
                    cls: 'bg-info bg-opacity-10 border-info',
                },
                awaiting_shipment: {
                    icon: 'fa fa-box-open text-primary',
                    title: 'STEP 2：包貨 → 確認出貨',
                    desc: '這裡顯示所有「待出貨」訂單。包裝完成後，勾選訂單並點擊「確認已出貨」，系統會建立物流訂單並產生託運單。',
                    cls: 'bg-primary bg-opacity-10 border-primary',
                },
                shipped: {
                    icon: 'fa fa-truck text-indigo',
                    title: 'STEP 3：物流配送中',
                    desc: '已出貨的訂單會由系統自動追蹤物流狀態。當買家取貨或簽收後，訂單將自動標記為「已完成」。',
                    cls: 'step-guide-indigo',
                },
                completed: {
                    icon: 'fa fa-check-circle text-success',
                    title: 'STEP 4：訂單已完成',
                    desc: '買家已取貨或已送達。訂單流程結束。',
                    cls: 'bg-success bg-opacity-10 border-success',
                },
                other_pending: {
                    icon: 'fa fa-clock text-warning',
                    title: '待付款訂單',
                    desc: '買家尚未完成付款的訂單。等待買家付款後，會自動歸入出貨流程的「篩選出貨」步驟。',
                    cls: 'bg-warning bg-opacity-10 border-warning',
                },
                other_cancelled: {
                    icon: 'fa fa-ban text-danger',
                    title: '已取消訂單',
                    desc: '買家或管理員已取消的訂單。此類訂單不會進入出貨流程。',
                    cls: 'bg-danger bg-opacity-10 border-danger',
                },
                other_refund_requested: {
                    icon: 'fa fa-undo text-orange',
                    title: '退款申請中',
                    desc: '買家已提出退款申請，等待審核處理。',
                    cls: 'step-guide-orange',
                },
                other_refunded: {
                    icon: 'fa fa-receipt text-secondary',
                    title: '已退款訂單',
                    desc: '已完成退款處理的訂單。',
                    cls: 'bg-secondary bg-opacity-10 border-secondary',
                },
            };
            return guides[currentStep.value] || null;
        });

        // 取得被選取的訂單（用於批次出貨 modal）
        const batchShipOrders = computed(() => {
            const allOrders = props.list?.data || [];
            return allOrders.filter(o => selectedIds.value.includes(o.id));
        });

        // 切換步驟
        const switchStep = (step) => {
            currentStep.value = step;
            selectedIds.value = [];
            filterStatus.value = '';
            // 清除 checkbox UI
            document.querySelectorAll('.batch-select-row').forEach(c => { c.checked = false; });
            const selectAllCb = document.querySelector('.batch-select-all');
            if (selectAllCb) selectAllCb.checked = false;
            // 重新載入 DataTable
            if (dt) dt.draw();
        };

        // DataTable columns — 加入勾選欄位
        const columns = [
            {
                title: '<input type="checkbox" class="form-check-input batch-select-all" />',
                data: null,
                orderable: false,
                searchable: false,
                width: "36px",
                className: "text-center",
                render: (data, type, row) => {
                    const checked = selectedIds.value.includes(row.id) ? 'checked' : '';
                    return `<input type="checkbox" class="form-check-input batch-select-row" data-id="${row.id}" ${checked} />`;
                }
            },
            {
                title: "訂單編號",
                data: "order_no",
                width: "140px",
                render: (data, type, row) => {
                    return `<a href="javascript:void(0)" class="fw-semibold text-primary view-btn" data-id="${row.id}">${data}</a>`;
                }
            },
            {
                title: "買家",
                data: "buyer_name",
                render: (data, type, row) => {
                    return `
                        <div class="fw-semibold">${data}</div>
                        <div class="fs-xs text-muted">${row.buyer_phone || ''}</div>
                    `;
                }
            },
            {
                title: "金額",
                data: "total_amount",
                width: "100px",
                className: "text-end",
                render: (data) => {
                    return `<span class="fw-semibold">$${Number(data).toLocaleString()}</span>`;
                }
            },
            {
                title: "商品數",
                data: "items_count",
                width: "70px",
                className: "text-center",
                render: (data) => `<span class="badge bg-light text-dark">${data} 件</span>`
            },
            {
                title: "狀態",
                data: "status_label",
                width: "90px",
                className: "text-center",
                render: (data, type, row) => {
                    return `<span class="badge bg-${row.status_color}">${data}</span>`;
                }
            },
            {
                title: "付款方式",
                data: "payment_method_label",
                width: "100px",
                className: "text-center",
            },
            {
                title: "物流方式",
                data: "shipping_method_label",
                width: "120px",
                className: "text-center",
                render: (data, type, row) => {
                    const icon = row.shipping_method?.startsWith('cvs') ? 'fa-store' : 'fa-home';
                    return `<span class="fs-sm"><i class="fa ${icon} me-1 text-muted"></i>${data}</span>`;
                }
            },
            {
                title: "物流狀態",
                data: null,
                width: "110px",
                className: "text-center",
                orderable: false,
                render: (data, type, row) => {
                    if (row.logistics_status_name) {
                        const pulseClass = row.logistics_auto_updating ? 'logistics-pulse' : '';
                        return `
                            <div class="${pulseClass}">
                                <span class="badge bg-info fs-xs">${row.logistics_status_name}</span>
                                ${row.logistics_auto_updating ? '<div class="fs-xs text-success mt-1"><i class="fa fa-sync fa-spin"></i> 自動追蹤中</div>' : ''}
                            </div>`;
                    }
                    if (row.status === 'paid' || row.status === 'awaiting_shipment') {
                        return '<span class="text-muted fs-xs">待出貨</span>';
                    }
                    return '<span class="text-muted fs-xs">—</span>';
                }
            },
            {
                title: "建立時間",
                data: "created_at",
                width: "140px",
                className: "text-center fs-sm",
            },
            {
                title: "操作",
                data: null,
                orderable: false,
                className: "text-center",
                width: "130px",
                render: (data, type, row) => {
                    let btns = `<button type="button" class="btn btn-sm btn-alt-info view-btn me-1" data-id="${row.id}" title="檢視詳情"><i class="fa fa-eye"></i></button>`;
                    // 可出貨的訂單顯示快速出貨按鈕
                    if (row.status === 'paid' || row.status === 'awaiting_shipment') {
                        btns += `<button type="button" class="btn btn-sm btn-alt-primary quick-ship-btn" data-id="${row.id}" title="快速出貨"><i class="fa fa-truck"></i></button>`;
                    }
                    // 已出貨的顯示列印按鈕
                    if (row.status === 'shipped') {
                        btns += `<button type="button" class="btn btn-sm btn-alt-success print-waybill-btn" data-id="${row.id}" title="列印託運單"><i class="fa fa-print"></i></button>`;
                    }
                    return `<div class="btn-group">${btns}</div>`;
                }
            },
        ];

        const viewOrder = (id) => {
            router.get(route('admin.orders.show', id));
        };

        const options = reactive({
            ...DataTableHelper.getBaseOptions(),
            ajax: (data, callback) => {
                // 傳送步驟篩選參數
                const step = currentStep.value;
                let stepFilter = '';
                if (step === 'all') {
                    stepFilter = '';
                } else if (step.startsWith('other_')) {
                    stepFilter = step.replace('other_', '');
                } else {
                    stepFilter = step;
                }
                const extra = { step_filter: stepFilter };
                if (filterStatus.value)   extra.status = filterStatus.value;
                if (filterPayment.value)  extra.payment_method = filterPayment.value;
                if (filterShipping.value) extra.shipping_method = filterShipping.value;
                if (filterDateFrom.value) extra.date_from = filterDateFrom.value;
                if (filterDateTo.value)   extra.date_to = filterDateTo.value;
                DataTableHelper.fetchTableData(
                    route("admin.orders.index"),
                    data,
                    callback,
                    rows,
                    'list',
                    extra
                );
            },
            drawCallback: function () {
                DataTableHelper.defaultDrawCallback();

                // 綁定「詳情」按鈕
                document.querySelectorAll('.view-btn').forEach(btn => {
                    btn.addEventListener('click', function () {
                        const id = this.dataset.id;
                        if (id) viewOrder(id);
                    });
                });

                // 綁定快速出貨按鈕
                document.querySelectorAll('.quick-ship-btn').forEach(btn => {
                    btn.addEventListener('click', function () {
                        const id = this.dataset.id;
                        if (id) quickShipSingle(id);
                    });
                });

                // 綁定列印按鈕
                document.querySelectorAll('.print-waybill-btn').forEach(btn => {
                    btn.addEventListener('click', function () {
                        const id = this.dataset.id;
                        if (id) printSingleWaybill(id);
                    });
                });

                // 綁定批次勾選 - 單行
                document.querySelectorAll('.batch-select-row').forEach(cb => {
                    cb.addEventListener('change', function () {
                        const id = parseInt(this.dataset.id);
                        if (this.checked) {
                            if (!selectedIds.value.includes(id)) selectedIds.value.push(id);
                        } else {
                            selectedIds.value = selectedIds.value.filter(x => x !== id);
                        }
                        syncSelectAll();
                    });
                });

                // 綁定批次勾選 - 全選
                document.querySelectorAll('.batch-select-all').forEach(cb => {
                    cb.addEventListener('change', function () {
                        const checkboxes = document.querySelectorAll('.batch-select-row');
                        if (this.checked) {
                            checkboxes.forEach(c => {
                                c.checked = true;
                                const id = parseInt(c.dataset.id);
                                if (!selectedIds.value.includes(id)) selectedIds.value.push(id);
                            });
                        } else {
                            checkboxes.forEach(c => { c.checked = false; });
                            selectedIds.value = [];
                        }
                    });
                });
            },
            order: [[9, "desc"]], // 依建立時間倒序（因新增了 checkbox 欄位，index 移到 9）
        });

        const syncSelectAll = () => {
            const allCheckboxes = document.querySelectorAll('.batch-select-row');
            const selectAllCb = document.querySelector('.batch-select-all');
            if (selectAllCb && allCheckboxes.length > 0) {
                selectAllCb.checked = Array.from(allCheckboxes).every(c => c.checked);
            }
        };

        const clearSelection = () => {
            selectedIds.value = [];
            document.querySelectorAll('.batch-select-row').forEach(c => { c.checked = false; });
            const selectAllCb = document.querySelector('.batch-select-all');
            if (selectAllCb) selectAllCb.checked = false;
        };

        // === 共用：批次更新狀態 ===
        const doBatchStatusUpdate = (ids, status, note, successMsg) => {
            router.post(route('admin.orders.batch-status'), {
                ids,
                status,
                note,
            }, {
                preserveState: true,
                onSuccess: () => {
                    sweetAlert.success({ msg: successMsg });
                    clearSelection();
                    if (dt) dt.draw();
                },
                onError: (errors) => {
                    sweetAlert.error({ msg: '操作失敗：' + (Object.values(errors).flat().join(', ') || '未知錯誤') });
                },
            });
        };

        // 批次標記待出貨
        const batchMarkAwaitingShipment = () => {
            sweetAlert.confirm(
                `確定要將 ${selectedIds.value.length} 筆訂單標記為「待出貨」嗎？`,
                () => {
                    doBatchStatusUpdate(
                        selectedIds.value,
                        'awaiting_shipment',
                        '標記為待出貨',
                        `已標記 ${selectedIds.value.length} 筆訂單為待出貨`
                    );
                }
            );
        };

        // 快速出貨（單筆）
        const quickShipSingle = (id) => {
            sweetAlert.confirm(
                '確定要將此訂單標記為「已出貨」嗎？',
                () => {
                    router.post(route('admin.orders.update-status', id), {
                        status: 'shipped',
                        note: '快速出貨',
                    }, {
                        preserveState: true,
                        onSuccess: () => {
                            sweetAlert.success({ msg: '已標記為已出貨' });
                            if (dt) dt.draw();
                        },
                        onError: (errors) => {
                            sweetAlert.error({ msg: '操作失敗：' + (Object.values(errors).flat().join(', ') || '未知錯誤') });
                        },
                    });
                }
            );
        };

        // 列印單筆託運單（目前無物流串接，提示未來功能）
        const printSingleWaybill = (id) => {
            sweetAlert.confirm(
                '確定要列印此訂單的託運單嗎？\n（列印功能需串接綠界物流後啟用）',
                () => { sweetAlert.success({ msg: '列印託運單 — 需串接綠界物流 API 後啟用' }); }
            );
        };

        // 批次出貨
        const batchShip = () => {
            const modal = new bootstrap.Modal(document.getElementById('batchShipModal'));
            modal.show();
        };
        const closeBatchShipModal = () => {
            const modal = bootstrap.Modal.getInstance(document.getElementById('batchShipModal'));
            if (modal) modal.hide();
        };
        const confirmBatchShip = () => {
            closeBatchShipModal();
            doBatchStatusUpdate(
                selectedIds.value,
                'shipped',
                '批次出貨',
                `已將 ${selectedIds.value.length} 筆訂單標記為已出貨`
            );
        };

        // 批次列印（需物流串接）
        const batchPrint = () => {
            const modal = new bootstrap.Modal(document.getElementById('batchPrintModal'));
            modal.show();
        };
        const closeBatchPrintModal = () => {
            const modal = bootstrap.Modal.getInstance(document.getElementById('batchPrintModal'));
            if (modal) modal.hide();
        };
        const confirmBatchPrint = () => {
            closeBatchPrintModal();
            sweetAlert.success({ msg: `列印 ${selectedIds.value.length} 筆託運單 — 需串接綠界物流 API 後啟用` });
        };

        // 批次更新物流狀態（需物流串接）
        const batchUpdateStatus = () => {
            sweetAlert.confirm(
                `確定要查詢 ${selectedIds.value.length} 筆訂單的最新物流狀態嗎？\n（需串接綠界物流 API 後啟用）`,
                () => { sweetAlert.success({ msg: '更新物流狀態 — 需串接綠界物流 API 後啟用' }); }
            );
        };

        // 批次匯出（TODO）
        const batchExport = () => {
            sweetAlert.success({ msg: `匯出 ${selectedIds.value.length} 筆訂單 — 功能開發中` });
        };

        // 批次取消訂單（其他 > 待付款）
        const batchCancel = () => {
            sweetAlert.confirm(
                `確定要取消 ${selectedIds.value.length} 筆待付款訂單嗎？\n取消後庫存會自動回復。`,
                () => {
                    doBatchStatusUpdate(
                        selectedIds.value,
                        'cancelled',
                        '批次取消訂單',
                        `已取消 ${selectedIds.value.length} 筆訂單`
                    );
                }
            );
        };

        // 批次核准退款（其他 > 退款申請）
        const batchApproveRefund = () => {
            sweetAlert.confirm(
                `確定要核准 ${selectedIds.value.length} 筆退款申請嗎？`,
                () => {
                    doBatchStatusUpdate(
                        selectedIds.value,
                        'refunded',
                        '批次核准退款',
                        `已核准 ${selectedIds.value.length} 筆退款`
                    );
                }
            );
        };

        const doSearch = () => {
            if (dt) {
                dt.search(searchKeyword.value || '').draw();
            }
        };

        const resetFilters = () => {
            searchKeyword.value = '';
            filterStatus.value = '';
            filterPayment.value = '';
            filterShipping.value = '';
            filterDateFrom.value = '';
            filterDateTo.value = '';
            currentStep.value = 'all';
            if (dt) dt.search('').draw();
        };

        let dt;
        onMounted(async () => {
            const el = table.value;
            if (el) {
                await DataTableHelper.createDataTable(el);
                dt = el.dt;
            }
        });

        return {
            table, rows,
            searchKeyword, filterStatus, filterPayment, filterShipping,
            filterDateFrom, filterDateTo, currentStep,
            workflowSteps, stepCounts, currentStepGuide,
            isOtherStep, otherSubSteps, otherTotalCount,
            columns, options,
            selectedIds, batchShipOrders,
            printFormat, markShippedAfterPrint,
            batchShipModal, batchPrintModal,
            switchStep, doSearch, resetFilters,
            clearSelection,
            batchMarkAwaitingShipment,
            batchShip, closeBatchShipModal, confirmBatchShip,
            batchPrint, closeBatchPrintModal, confirmBatchPrint,
            batchUpdateStatus, batchExport,
            batchCancel, batchApproveRefund,
        };
    },
    layout: Layout,
};
</script>

<style scoped>
@import 'bootstrap';
@import 'datatables.net-bs5';

/* 出貨流程步驟指示器 */
.wf-step {
    flex: 0 0 auto;
    padding: 0 16px;
    cursor: pointer;
    transition: all 0.2s ease;
    opacity: 0.55;
}
.wf-step:hover { opacity: 0.85; }
.wf-step.wf-active { opacity: 1; }
.wf-dot {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    font-size: 16px;
    color: #fff;
    transition: all 0.3s ease;
    border: 3px solid transparent;
}
.wf-dot-info { background: #0dcaf0; }
.wf-dot-primary { background: #0d6efd; }
.wf-dot-indigo { background: #6366f1; }
.wf-dot-success { background: #198754; }
.wf-step.wf-active .wf-dot {
    transform: scale(1.15);
    box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.2);
}
.wf-connector {
    display: flex;
    align-items: center;
    padding: 0 4px;
    padding-bottom: 28px;
    font-size: 12px;
}

/* 步驟說明 */
.step-guide {
    border-radius: 0 4px 4px 0;
}
.step-guide-indigo {
    background-color: rgba(99, 102, 241, 0.1) !important;
    border-color: #6366f1 !important;
}
.step-guide-orange {
    background-color: rgba(253, 126, 20, 0.1) !important;
    border-color: #fd7e14 !important;
}

.text-orange { color: #fd7e14 !important; }
.bg-orange { background-color: #fd7e14 !important; }
.btn-orange { background-color: #fd7e14; border-color: #fd7e14; color: #fff; }
.btn-orange:hover { background-color: #e8720e; border-color: #e8720e; color: #fff; }
.btn-outline-orange { color: #fd7e14; border-color: #fd7e14; }
.btn-outline-orange:hover { background-color: #fd7e14; border-color: #fd7e14; color: #fff; }

.ring-2 {
    box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.25);
}

.text-indigo { color: #6366f1 !important; }
.bg-indigo { background-color: #6366f1 !important; }
.btn-indigo {
    background-color: #6366f1;
    border-color: #6366f1;
    color: #fff;
}
.btn-indigo:hover {
    background-color: #4f46e5;
    border-color: #4f46e5;
    color: #fff;
}

/* 批次工具列動畫 */
.slide-down-enter-active,
.slide-down-leave-active {
    transition: all 0.25s ease;
}
.slide-down-enter-from,
.slide-down-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}

/* 物流自動追蹤脈衝動畫 */
.logistics-pulse {
    animation: logisticsPulse 2s ease-in-out infinite;
}
@keyframes logisticsPulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

/* 批次工具列 */
.batch-toolbar {
    background: linear-gradient(135deg, #f0f4ff 0%, #e8f0fe 100%) !important;
    border-color: #c2d5f7 !important;
}
</style>
