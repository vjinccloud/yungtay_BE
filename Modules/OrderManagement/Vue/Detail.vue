<!-- Modules/OrderManagement/Vue/Detail.vue -->
<!-- 訂單管理 - 訂單詳情頁（含物流出貨、託運單列印、物流追蹤） -->
<template>
    <div class="content">
        <BreadcrumbItem />

        <div class="row">
            <!-- 左欄：訂單主要資訊 -->
            <div class="col-xl-8">

                <!-- 訂單標頭 -->
                <div class="block block-rounded">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">
                            訂單 <span class="text-primary">{{ order.order_no }}</span>
                        </h3>
                        <div class="block-options">
                            <span class="badge fs-sm" :class="'bg-' + order.status_color">
                                {{ order.status_label }}
                            </span>
                        </div>
                    </div>
                    <div class="block-content pb-3">
                        <div class="row g-3">
                            <div class="col-6 col-md-3">
                                <div class="fs-sm text-muted">建立時間</div>
                                <div class="fw-semibold">{{ order.created_at }}</div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="fs-sm text-muted">付款時間</div>
                                <div class="fw-semibold">{{ order.paid_at || '—' }}</div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="fs-sm text-muted">出貨時間</div>
                                <div class="fw-semibold">{{ order.shipped_at || '—' }}</div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="fs-sm text-muted">最後更新</div>
                                <div class="fw-semibold">{{ order.updated_at }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 商品明細 -->
                <div class="block block-rounded">
                    <div class="block-header block-header-default">
                        <h3 class="block-title"><i class="fa fa-box me-1"></i> 商品明細</h3>
                    </div>
                    <div class="block-content">
                        <table class="table table-borderless table-vcenter mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 50%;">商品</th>
                                    <th class="text-center" style="width: 15%;">單價</th>
                                    <th class="text-center" style="width: 10%;">數量</th>
                                    <th class="text-end" style="width: 15%;">小計</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in order.items" :key="item.id">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="item-thumb me-3" v-if="item.product_image">
                                                <img :src="item.product_image" :alt="item.product_name" 
                                                     class="img-fluid rounded" style="width:48px; height:48px; object-fit:cover;" />
                                            </div>
                                            <div class="item-thumb me-3 bg-body-light rounded d-flex align-items-center justify-content-center" 
                                                 v-else style="width:48px; height:48px; flex-shrink:0;">
                                                <i class="fa fa-image text-muted"></i>
                                            </div>
                                            <div>
                                                <div class="fw-semibold">{{ item.product_name }}</div>
                                                <div class="fs-xs text-muted" v-if="item.product_sku">SKU: {{ item.product_sku }}</div>
                                                <div class="fs-xs text-muted" v-if="item.options">規格: {{ item.options }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">${{ Number(item.unit_price).toLocaleString() }}</td>
                                    <td class="text-center">{{ item.quantity }}</td>
                                    <td class="text-end fw-semibold">${{ Number(item.subtotal).toLocaleString() }}</td>
                                </tr>
                            </tbody>
                            <tfoot class="border-top">
                                <tr>
                                    <td colspan="3" class="text-end text-muted">商品小計</td>
                                    <td class="text-end">${{ Number(order.subtotal).toLocaleString() }}</td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end text-muted">運費</td>
                                    <td class="text-end">${{ Number(order.shipping_fee).toLocaleString() }}</td>
                                </tr>
                                <tr v-if="order.discount > 0">
                                    <td colspan="3" class="text-end text-danger">折扣</td>
                                    <td class="text-end text-danger">-${{ Number(order.discount).toLocaleString() }}</td>
                                </tr>
                                <tr class="fw-bold fs-5">
                                    <td colspan="3" class="text-end">訂單總計</td>
                                    <td class="text-end text-primary">${{ Number(order.total_amount).toLocaleString() }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- 出貨操作區塊（待出貨時顯示） -->
                <div class="block block-rounded" v-if="canShip">
                    <div class="block-header block-header-default bg-primary-light">
                        <h3 class="block-title"><i class="fa fa-truck me-1"></i> 出貨操作</h3>
                        <div class="block-options">
                            <span class="badge bg-warning">待出貨</span>
                        </div>
                    </div>
                    <div class="block-content pb-3">
                        <!-- 超商取貨出貨流程 -->
                        <div v-if="isCvs" class="shipping-cvs-section">
                            <div class="alert alert-info d-flex align-items-start mb-3">
                                <i class="fa fa-store me-2 mt-1"></i>
                                <div>
                                    <strong>超商取貨出貨流程：</strong>
                                    <ol class="mb-0 mt-1 ps-3 fs-sm">
                                        <li>確認取貨門市資訊正確</li>
                                        <li>點擊「一鍵出貨」建立綠界物流訂單</li>
                                        <li>列印託運單（熱感標籤）</li>
                                        <li>將包裹交至指定超商寄件</li>
                                    </ol>
                                </div>
                            </div>

                            <!-- 門市資訊確認 -->
                            <div class="p-3 border rounded mb-3 bg-body-light">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fs-sm text-muted">取貨門市</label>
                                        <div class="d-flex align-items-center">
                                            <i class="fa fa-store text-primary me-2"></i>
                                            <div>
                                                <div class="fw-semibold">{{ order.receiver_store_name || '—' }}</div>
                                                <div class="fs-xs text-muted">店號: {{ order.receiver_store_id || '—' }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fs-sm text-muted">收件人</label>
                                        <div class="fw-semibold">{{ order.receiver_name }}</div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fs-sm text-muted">聯絡電話</label>
                                        <div>{{ order.receiver_phone }}</div>
                                    </div>
                                </div>
                                <div class="mt-2 text-end">
                                    <button class="btn btn-sm btn-alt-secondary" @click="onAction('change_store')">
                                        <i class="fa fa-map-marker-alt me-1"></i> 變更門市
                                    </button>
                                </div>
                            </div>

                            <!-- 包裹資訊 -->
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label fs-sm">溫層</label>
                                    <select class="form-select form-select-sm" v-model="shipForm.temperature">
                                        <option value="0001">常溫</option>
                                        <option value="0002">冷藏</option>
                                        <option value="0003">冷凍</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fs-sm">預估重量</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" class="form-control" v-model="shipForm.weight" min="1" max="20" />
                                        <span class="input-group-text">公斤</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fs-sm">備註</label>
                                    <input type="text" class="form-control form-control-sm" v-model="shipForm.remark" placeholder="出貨備註" />
                                </div>
                            </div>

                            <!-- 出貨按鈕 -->
                            <div class="d-flex gap-2">
                                <button class="btn btn-primary btn-lg" @click="onAction('one_click_ship')">
                                    <i class="fa fa-rocket me-2"></i> 一鍵出貨
                                </button>
                            </div>
                        </div>

                        <!-- 宅配出貨流程 -->
                        <div v-else class="shipping-home-section">
                            <div class="alert alert-primary d-flex align-items-start mb-3">
                                <i class="fa fa-home me-2 mt-1"></i>
                                <div>
                                    <strong>宅配到府出貨流程：</strong>
                                    <ol class="mb-0 mt-1 ps-3 fs-sm">
                                        <li>確認收件地址與聯絡方式</li>
                                        <li>選擇配送時段</li>
                                        <li>點擊「一鍵出貨」建立物流訂單</li>
                                        <li>列印託運單（A4 格式）貼於包裹</li>
                                    </ol>
                                </div>
                            </div>

                            <!-- 收件地址確認 -->
                            <div class="p-3 border rounded mb-3 bg-body-light">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label fs-sm text-muted">收件人</label>
                                        <div class="fw-semibold">{{ order.receiver_name }}</div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fs-sm text-muted">聯絡電話</label>
                                        <div>{{ order.receiver_phone }}</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fs-sm text-muted">收件地址</label>
                                        <div class="fw-semibold">{{ order.receiver_address || '台北市信義區松仁路100號' }}</div>
                                    </div>
                                </div>
                            </div>

                            <!-- 配送設定 -->
                            <div class="row g-3 mb-3">
                                <div class="col-md-3">
                                    <label class="form-label fs-sm">溫層</label>
                                    <select class="form-select form-select-sm" v-model="shipForm.temperature">
                                        <option value="0001">常溫</option>
                                        <option value="0002">冷藏</option>
                                        <option value="0003">冷凍</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fs-sm">尺寸</label>
                                    <select class="form-select form-select-sm" v-model="shipForm.spec">
                                        <option value="0001">60cm</option>
                                        <option value="0002">90cm</option>
                                        <option value="0003">120cm</option>
                                        <option value="0004">150cm</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fs-sm">配送時段</label>
                                    <select class="form-select form-select-sm" v-model="shipForm.scheduleDelivery">
                                        <option value="4">不指定</option>
                                        <option value="1">13 時前</option>
                                        <option value="2">14~18 時</option>
                                        <option value="3">不限時</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fs-sm">預估重量</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" class="form-control" v-model="shipForm.weight" min="1" max="20" />
                                        <span class="input-group-text">kg</span>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-12">
                                    <label class="form-label fs-sm">出貨備註</label>
                                    <input type="text" class="form-control form-control-sm" v-model="shipForm.remark" placeholder="備註（選填）" />
                                </div>
                            </div>

                            <!-- 出貨按鈕 -->
                            <div class="d-flex gap-2">
                                <button class="btn btn-primary btn-lg" @click="onAction('one_click_ship')">
                                    <i class="fa fa-rocket me-2"></i> 一鍵出貨
                                </button>
                            </div>
                        </div>

                        <div class="text-muted fs-xs mt-3">
                            <i class="fa fa-info-circle me-1"></i>
                            出貨功能尚未啟用，目前僅展示 UI（待後端 API 開發完成）
                        </div>
                    </div>
                </div>

                <!-- 物流追蹤區塊（已出貨時顯示） -->
                <div class="block block-rounded" v-if="hasShipped">
                    <div class="block-header block-header-default">
                        <h3 class="block-title"><i class="fa fa-route me-1"></i> 物流追蹤</h3>
                        <div class="block-options d-flex align-items-center gap-2">
                            <span v-if="order.logistics_auto_tracking" class="badge bg-success">
                                <i class="fa fa-sync fa-spin me-1"></i> 自動追蹤中
                            </span>
                            <button class="btn btn-sm btn-alt-info" @click="onAction('refresh_logistics')">
                                <i class="fa fa-sync me-1"></i> 手動更新
                            </button>
                        </div>
                    </div>
                    <div class="block-content pb-3">
                        <!-- 物流進度條 -->
                        <div class="logistics-progress mb-4">
                            <div class="d-flex justify-content-between position-relative logistics-steps">
                                <div class="logistics-progress-bar">
                                    <div class="logistics-progress-fill" :style="{ width: logisticsProgress + '%' }"></div>
                                </div>
                                <div v-for="(step, idx) in logisticsSteps" :key="idx" 
                                     class="logistics-step text-center" 
                                     :class="{ 'active': idx <= currentLogisticsStep, 'current': idx === currentLogisticsStep }">
                                    <div class="logistics-step-dot">
                                        <i :class="step.icon"></i>
                                    </div>
                                    <div class="fs-xs mt-1 fw-semibold">{{ step.label }}</div>
                                    <div class="fs-xs text-muted" v-if="step.time">{{ step.time }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- 物流明細 -->
                        <div class="p-3 border rounded bg-body-light mb-3">
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <div class="fs-sm text-muted">託運單號</div>
                                    <div class="fw-semibold font-monospace">{{ order.logistics_id || 'ECL20260302001' }}</div>
                                </div>
                                <div class="col-md-4">
                                    <div class="fs-sm text-muted">物流狀態</div>
                                    <div>
                                        <span class="badge bg-info">{{ order.logistics_status_name || '運送中' }}</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="fs-sm text-muted">最後更新</div>
                                    <div class="fs-sm">{{ order.logistics_updated_at || '2026-03-02 14:30:00' }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- 物流軌跡 -->
                        <div class="logistics-timeline">
                            <div v-for="(track, idx) in logisticsTracking" :key="idx" 
                                 class="logistics-track-item d-flex align-items-start mb-2"
                                 :class="{ 'text-primary fw-semibold': idx === 0 }">
                                <div class="logistics-track-dot me-3" :class="{ 'active': idx === 0 }"></div>
                                <div>
                                    <div class="fs-sm">{{ track.description }}</div>
                                    <div class="fs-xs text-muted">{{ track.time }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- 託運單操作 -->
                        <div class="mt-3 pt-3 border-top d-flex gap-2 flex-wrap">
                            <button class="btn btn-sm btn-alt-primary" @click="onAction('print_waybill')">
                                <i class="fa fa-print me-1"></i> 列印託運單
                            </button>
                            <button class="btn btn-sm btn-alt-info" @click="onAction('reprint_waybill')">
                                <i class="fa fa-redo me-1"></i> 重新列印
                            </button>
                            <button class="btn btn-sm btn-alt-secondary" @click="onAction('copy_tracking')">
                                <i class="fa fa-copy me-1"></i> 複製追蹤連結
                            </button>
                        </div>

                        <!-- 自動追蹤設定 -->
                        <div class="mt-3 p-3 border rounded">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="fw-semibold fs-sm"><i class="fa fa-robot me-1 text-primary"></i> 物流狀態自動更新</div>
                                    <div class="fs-xs text-muted">系統每 30 分鐘自動向綠界查詢物流狀態，並在狀態變更時通知買家</div>
                                </div>
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input" id="autoTrackSwitch" 
                                           v-model="autoTrackEnabled" @change="onAction('toggle_auto_track')" />
                                    <label class="form-check-label" for="autoTrackSwitch"></label>
                                </div>
                            </div>
                            <div v-if="autoTrackEnabled" class="mt-2">
                                <div class="d-flex gap-3 fs-xs text-muted">
                                    <span><i class="fa fa-clock me-1"></i> 上次查詢: {{ order.logistics_last_checked || '5 分鐘前' }}</span>
                                    <span><i class="fa fa-bell me-1"></i> 已發送 2 則通知</span>
                                    <span class="text-success"><i class="fa fa-check-circle me-1"></i> 運作正常</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 狀態操作 -->
                <div class="block block-rounded">
                    <div class="block-header block-header-default">
                        <h3 class="block-title"><i class="fa fa-cog me-1"></i> 訂單操作</h3>
                    </div>
                    <div class="block-content pb-3">
                        <div class="row g-2">
                            <!-- 根據目前狀態顯示可用操作 -->
                            <div class="col-auto" v-if="order.status === 'pending'">
                                <button class="btn btn-info" @click="onAction('confirm_paid')">
                                    <i class="fa fa-check me-1"></i> 確認付款
                                </button>
                            </div>
                            <div class="col-auto" v-if="order.status === 'pending'">
                                <button class="btn btn-secondary" @click="onAction('cancel')">
                                    <i class="fa fa-times me-1"></i> 取消訂單
                                </button>
                            </div>
                            <div class="col-auto" v-if="order.status === 'paid'">
                                <button class="btn btn-primary" @click="onAction('mark_awaiting_shipment')">
                                    <i class="fa fa-box-open me-1"></i> 標記待出貨
                                </button>
                            </div>
                            <div class="col-auto" v-if="order.status === 'shipped'">
                                <button class="btn btn-success" @click="onAction('complete')">
                                    <i class="fa fa-check-double me-1"></i> 完成訂單
                                </button>
                            </div>
                            <div class="col-auto" v-if="order.status === 'refund_requested'">
                                <button class="btn btn-danger" @click="onAction('refund')">
                                    <i class="fa fa-undo me-1"></i> 執行退款
                                </button>
                            </div>
                            <div class="col-auto" v-if="order.status === 'refund_requested'">
                                <button class="btn btn-outline-secondary" @click="onAction('reject_refund')">
                                    <i class="fa fa-ban me-1"></i> 拒絕退款
                                </button>
                            </div>
                        </div>

                        <!-- 管理員備註 -->
                        <div class="mt-3">
                            <label class="form-label fs-sm text-muted">管理員備註</label>
                            <textarea class="form-control" rows="2" v-model="adminNote" placeholder="輸入備註..."></textarea>
                        </div>

                        <div class="text-muted fs-xs mt-2">
                            <i class="fa fa-info-circle me-1"></i>
                            操作功能尚未啟用，目前僅展示 UI（待後端 API 開發完成）
                        </div>
                    </div>
                </div>

            </div>

            <!-- 右欄：側邊資訊 -->
            <div class="col-xl-4">

                <!-- 買家資訊 -->
                <div class="block block-rounded">
                    <div class="block-header block-header-default">
                        <h3 class="block-title"><i class="fa fa-user me-1"></i> 買家資訊</h3>
                    </div>
                    <div class="block-content pb-3">
                        <table class="table table-borderless table-sm mb-0">
                            <tr>
                                <td class="text-muted" style="width:80px;">姓名</td>
                                <td class="fw-semibold">{{ order.buyer_name }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">手機</td>
                                <td>
                                    <a :href="'tel:' + order.buyer_phone">{{ order.buyer_phone }}</a>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Email</td>
                                <td>
                                    <a :href="'mailto:' + order.buyer_email" class="text-truncate d-block" style="max-width: 200px;">
                                        {{ order.buyer_email }}
                                    </a>
                                </td>
                            </tr>
                            <tr v-if="order.user_id">
                                <td class="text-muted">會員ID</td>
                                <td>#{{ order.user_id }}</td>
                            </tr>
                        </table>
                        <div v-if="order.buyer_note" class="mt-2 p-2 bg-body-light rounded">
                            <div class="fs-xs text-muted mb-1"><i class="fa fa-comment me-1"></i> 買家留言</div>
                            <div class="fs-sm">{{ order.buyer_note }}</div>
                        </div>
                    </div>
                </div>

                <!-- 付款資訊 -->
                <div class="block block-rounded">
                    <div class="block-header block-header-default">
                        <h3 class="block-title"><i class="fa fa-credit-card me-1"></i> 付款資訊</h3>
                    </div>
                    <div class="block-content pb-3">
                        <table class="table table-borderless table-sm mb-0">
                            <tr>
                                <td class="text-muted" style="width:100px;">付款方式</td>
                                <td class="fw-semibold">{{ order.payment_method_label }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">付款狀態</td>
                                <td>
                                    <span v-if="order.paid_at" class="badge bg-success">已付款</span>
                                    <span v-else class="badge bg-warning">未付款</span>
                                </td>
                            </tr>
                            <tr v-if="order.paid_at">
                                <td class="text-muted">付款時間</td>
                                <td class="fs-sm">{{ order.paid_at }}</td>
                            </tr>
                            <tr v-if="order.ecpay_merchant_trade_no">
                                <td class="text-muted">特店編號</td>
                                <td class="fs-sm font-monospace">{{ order.ecpay_merchant_trade_no }}</td>
                            </tr>
                            <tr v-if="order.ecpay_trade_no">
                                <td class="text-muted">綠界編號</td>
                                <td class="fs-sm font-monospace">{{ order.ecpay_trade_no }}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- 物流資訊 -->
                <div class="block block-rounded">
                    <div class="block-header block-header-default">
                        <h3 class="block-title"><i class="fa fa-truck me-1"></i> 物流資訊</h3>
                    </div>
                    <div class="block-content pb-3">
                        <table class="table table-borderless table-sm mb-0">
                            <tr>
                                <td class="text-muted" style="width:100px;">物流方式</td>
                                <td>
                                    <span class="fw-semibold">
                                        <i :class="isCvs ? 'fa fa-store' : 'fa fa-home'" class="me-1 text-primary"></i>
                                        {{ order.shipping_method_label }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">收件人</td>
                                <td>{{ order.receiver_name }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">聯絡電話</td>
                                <td>{{ order.receiver_phone }}</td>
                            </tr>
                            <tr v-if="order.receiver_store_name">
                                <td class="text-muted">取貨門市</td>
                                <td>
                                    <div class="fw-semibold">{{ order.receiver_store_name }}</div>
                                    <div class="fs-xs text-muted">店號: {{ order.receiver_store_id }}</div>
                                </td>
                            </tr>
                            <tr v-if="order.receiver_address">
                                <td class="text-muted">收件地址</td>
                                <td class="fs-sm">{{ order.receiver_address }}</td>
                            </tr>
                            <tr v-if="order.logistics_id">
                                <td class="text-muted">託運單號</td>
                                <td class="font-monospace fs-sm">{{ order.logistics_id }}</td>
                            </tr>
                            <tr v-if="order.logistics_status_name">
                                <td class="text-muted">物流狀態</td>
                                <td>
                                    <span class="badge bg-info">{{ order.logistics_status_name }}</span>
                                    <span v-if="order.logistics_auto_tracking" class="ms-1 text-success fs-xs">
                                        <i class="fa fa-sync fa-spin"></i>
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="order.shipped_at">
                                <td class="text-muted">出貨時間</td>
                                <td class="fs-sm">{{ order.shipped_at }}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- 發票資訊 -->
                <div class="block block-rounded">
                    <div class="block-header block-header-default">
                        <h3 class="block-title"><i class="fa fa-file-invoice me-1"></i> 發票資訊</h3>
                    </div>
                    <div class="block-content pb-3">
                        <table class="table table-borderless table-sm mb-0">
                            <tr>
                                <td class="text-muted" style="width:100px;">發票類型</td>
                                <td class="fw-semibold">{{ order.invoice_type }}</td>
                            </tr>
                            <tr v-if="order.invoice_no">
                                <td class="text-muted">發票號碼</td>
                                <td class="font-monospace">{{ order.invoice_no }}</td>
                            </tr>
                            <tr v-if="order.invoice_carrier_num">
                                <td class="text-muted">載具號碼</td>
                                <td class="font-monospace">{{ order.invoice_carrier_num }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">發票狀態</td>
                                <td>
                                    <span class="badge bg-success">{{ order.invoice_status }}</span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- 狀態歷程 -->
                <div class="block block-rounded">
                    <div class="block-header block-header-default">
                        <h3 class="block-title"><i class="fa fa-history me-1"></i> 狀態歷程</h3>
                    </div>
                    <div class="block-content pb-3">
                        <div class="timeline timeline-alt">
                            <div class="timeline-event" v-for="(log, idx) in statusLogs" :key="idx">
                                <div class="timeline-event-icon" :class="'bg-' + log.status_color">
                                    <i class="fa fa-circle fs-xs"></i>
                                </div>
                                <div class="timeline-event-block">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <span class="badge" :class="'bg-' + log.status_color">{{ log.status_label }}</span>
                                            <span class="fs-xs text-muted ms-1">{{ log.operator }}</span>
                                        </div>
                                        <span class="fs-xs text-muted">{{ log.created_at }}</span>
                                    </div>
                                    <p class="fs-sm mt-1 mb-0" v-if="log.note">{{ log.note }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- 返回按鈕 -->
        <div class="mb-4">
            <Link :href="route('admin.orders.index')" class="btn btn-alt-secondary">
                <i class="fa fa-arrow-left me-1"></i> 返回訂單列表
            </Link>
        </div>
    </div>
</template>

<script>
import { ref, computed, reactive, inject } from "vue";
import { Link, router } from "@inertiajs/vue3";
import Layout from "@/Shared/Admin/Layout.vue";
import BreadcrumbItem from "@/Shared/Admin/Partials/BreadcrumbItem.vue";

export default {
    components: { BreadcrumbItem, Link },
    props: {
        order: { type: Object, required: true },
        statusOptions: { type: Array, default: () => [] },
        statusLogs: { type: Array, default: () => [] },
        logisticsTracking: { type: Array, default: () => [] },
    },
    setup(props) {
        const sweetAlert = inject('$sweetAlert');
        const adminNote = ref(props.order.admin_note || '');
        const autoTrackEnabled = ref(props.order.logistics_auto_tracking ?? true);

        // 出貨表單
        const shipForm = reactive({
            temperature: '0001',
            weight: 5,
            spec: '0002',
            scheduleDelivery: '4',
            remark: '',
        });

        // 判斷是否為超商取貨
        const isCvs = computed(() => {
            return props.order.shipping_method?.startsWith('cvs');
        });

        // 是否可以出貨（awaiting_shipment 狀態）
        const canShip = computed(() => {
            return ['paid', 'awaiting_shipment'].includes(props.order.status);
        });

        // 是否已出貨
        const hasShipped = computed(() => {
            return ['shipped', 'completed'].includes(props.order.status);
        });

        // 物流進度步驟
        const logisticsSteps = computed(() => {
            if (isCvs.value) {
                return [
                    { label: '已寄件', icon: 'fa fa-box', time: props.order.shipped_at?.substring(5, 16) },
                    { label: '運送中', icon: 'fa fa-truck', time: '03-02 12:00' },
                    { label: '到店', icon: 'fa fa-store', time: '03-02 14:30' },
                    { label: '取貨', icon: 'fa fa-hand-holding', time: null },
                ];
            }
            return [
                { label: '已寄件', icon: 'fa fa-box', time: props.order.shipped_at?.substring(5, 16) },
                { label: '集貨中', icon: 'fa fa-warehouse', time: '03-02 12:00' },
                { label: '配送中', icon: 'fa fa-truck', time: '03-02 16:00' },
                { label: '已送達', icon: 'fa fa-home', time: null },
            ];
        });

        // 目前物流步驟 index（mock）
        const currentLogisticsStep = computed(() => {
            if (props.order.status === 'completed') return 3;
            return 2; // mock: 到店/配送中
        });

        // 物流進度百分比
        const logisticsProgress = computed(() => {
            return (currentLogisticsStep.value / 3) * 100;
        });

        // 物流追蹤記錄（使用 props 或 mock）
        const logisticsTracking = computed(() => {
            if (props.logisticsTracking?.length > 0) return props.logisticsTracking;
            return [
                { description: '門市已收到商品，等待取貨', time: '2026-03-02 14:30:00' },
                { description: '商品已送達門市', time: '2026-03-02 14:25:00' },
                { description: '配送員配送中', time: '2026-03-02 12:00:00' },
                { description: '商品已到達轉運中心', time: '2026-03-02 10:30:00' },
                { description: '賣家已出貨', time: '2026-03-02 09:15:00' },
            ];
        });

        const onAction = (action) => {
            const actionLabels = {
                confirm_paid: '確認付款',
                cancel: '取消訂單',
                mark_awaiting_shipment: '標記待出貨',
                ship: '確認出貨',
                complete: '完成訂單',
                refund: '執行退款',
                reject_refund: '拒絕退款',
                one_click_ship: '一鍵出貨',
                change_store: '變更取貨門市',
                print_waybill: '列印託運單',
                reprint_waybill: '重新列印託運單',
                copy_tracking: '複製追蹤連結',
                refresh_logistics: '手動更新物流狀態',
                toggle_auto_track: '切換自動追蹤',
            };

            if (action === 'toggle_auto_track') {
                sweetAlert.success({ msg: `自動追蹤已${autoTrackEnabled.value ? '開啟' : '關閉'} — 功能尚未啟用` });
                return;
            }

            if (action === 'copy_tracking') {
                sweetAlert.success({ msg: '追蹤連結已複製到剪貼簿 — 功能尚未啟用' });
                return;
            }

            sweetAlert.confirm(
                `確定要「${actionLabels[action] || action}」嗎？\n（此功能尚未啟用，僅展示 UI）`,
                () => {
                    sweetAlert.success({ msg: `操作「${actionLabels[action]}」— 功能尚未啟用` });
                }
            );
        };

        return {
            adminNote, autoTrackEnabled,
            shipForm,
            isCvs, canShip, hasShipped,
            logisticsSteps, currentLogisticsStep, logisticsProgress,
            logisticsTracking,
            onAction,
        };
    },
    layout: Layout,
};
</script>

<style scoped>
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
.bg-indigo { background-color: #6366f1 !important; }
.text-indigo { color: #6366f1 !important; }

.bg-primary-light {
    background-color: #e8f0fe !important;
}

/* Timeline */
.timeline {
    position: relative;
    padding-left: 24px;
}
.timeline::before {
    content: '';
    position: absolute;
    left: 8px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e4e7ed;
}
.timeline-event {
    position: relative;
    padding-bottom: 16px;
}
.timeline-event:last-child {
    padding-bottom: 0;
}
.timeline-event-icon {
    position: absolute;
    left: -24px;
    top: 2px;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 6px;
}
.timeline-event-block {
    padding-left: 8px;
}

/* 物流進度條 */
.logistics-progress {
    padding: 20px 30px;
}
.logistics-steps {
    z-index: 1;
}
.logistics-progress-bar {
    position: absolute;
    top: 15px;
    left: 40px;
    right: 40px;
    height: 4px;
    background: #e4e7ed;
    border-radius: 2px;
    z-index: 0;
}
.logistics-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #0d6efd, #6366f1);
    border-radius: 2px;
    transition: width 0.6s ease;
}
.logistics-step {
    position: relative;
    z-index: 1;
    flex: 1;
}
.logistics-step-dot {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #e4e7ed;
    color: #adb5bd;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    font-size: 12px;
    transition: all 0.3s ease;
    border: 3px solid #fff;
    box-shadow: 0 0 0 2px #e4e7ed;
}
.logistics-step.active .logistics-step-dot {
    background: #0d6efd;
    color: #fff;
    box-shadow: 0 0 0 2px #0d6efd;
}
.logistics-step.current .logistics-step-dot {
    background: #6366f1;
    color: #fff;
    box-shadow: 0 0 0 2px #6366f1, 0 0 8px rgba(99, 102, 241, 0.4);
    animation: dotPulse 2s ease-in-out infinite;
}
@keyframes dotPulse {
    0%, 100% { box-shadow: 0 0 0 2px #6366f1, 0 0 8px rgba(99, 102, 241, 0.4); }
    50% { box-shadow: 0 0 0 4px #6366f1, 0 0 16px rgba(99, 102, 241, 0.6); }
}

/* 物流軌跡 */
.logistics-timeline {
    position: relative;
    padding-left: 20px;
}
.logistics-timeline::before {
    content: '';
    position: absolute;
    left: 5px;
    top: 6px;
    bottom: 6px;
    width: 2px;
    background: #e4e7ed;
}
.logistics-track-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #e4e7ed;
    flex-shrink: 0;
    margin-top: 4px;
    position: relative;
    z-index: 1;
}
.logistics-track-dot.active {
    background: #0d6efd;
    box-shadow: 0 0 6px rgba(13, 110, 253, 0.4);
}
</style>
