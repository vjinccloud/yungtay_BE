<!-- resources/js/InertiaPages/Admin/Members/Show.vue -->
<template>
    <div class="content">
        <BreadcrumbItem />

        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <Link class="btn btn-sm btn-alt-secondary" :href="route('admin.members')">
                        <i class="fa fa-arrow-left me-1"></i>
                        返回列表
                    </Link>
                </h3>
            </div>

            <div class="block-content">
                <!-- 基本資訊 -->
                <h5 class="mb-3">基本資訊</h5>

                <div class="row mb-3">
                    <div class="col-sm-3">
                        <strong>姓名：</strong>
                    </div>
                    <div class="col-sm-9">
                        {{ member.name || '-' }}
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-sm-3">
                        <strong>Email：</strong>
                    </div>
                    <div class="col-sm-9">
                        <a :href="`mailto:${member.email}`" class="text-primary">
                            {{ member.email || '-' }}
                        </a>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-sm-3">
                        <strong>手機：</strong>
                    </div>
                    <div class="col-sm-9">
                        {{ member.phone || '-' }}
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-sm-3">
                        <strong>身份證字號：</strong>
                    </div>
                    <div class="col-sm-9">
                        {{ member.id_number || '-' }}
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-sm-3">
                        <strong>性別：</strong>
                    </div>
                    <div class="col-sm-9">
                        {{ getGenderText(member.gender) }}
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-sm-3">
                        <strong>生日：</strong>
                    </div>
                    <div class="col-sm-9">
                        {{ formatDate(member.birthdate) }}
                        <span  class="text-muted ms-2">(年齡：{{ member.age }}歲)</span>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-sm-3">
                        <strong>居住地區：</strong>
                    </div>
                    <div class="col-sm-9">
                        {{ member.full_address || '-' }}
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-3">
                        <strong>會員狀態：</strong>
                    </div>
                    <div class="col-sm-9">
                        <span
                        class="badge me-2"
                        :class="member.is_active ? 'bg-success' : 'bg-secondary'"
                        >
                            <i :class="member.is_active ? 'fa fa-check' : 'fa fa-times'" class="me-1"></i>
                            {{ member.is_active ? '啟用' : '停用' }}
                        </span>
                    </div>
                </div>

                <hr class="my-4">

                <!-- 註冊資訊 -->
                <h5 class="mb-3">註冊資訊</h5>

                <div class="row mb-3">
                    <div class="col-sm-3">
                        <strong>註冊方式：</strong>
                    </div>
                    <div class="col-sm-9">
                        {{ member.registration_type || '-' }}
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-sm-3">
                        <strong>驗證狀態：</strong>
                    </div>
                    <div class="col-sm-9">
                        <span
                            class="badge"
                            :class="getVerificationBadgeClass(member.verification_status)"
                        >
                            {{ member.verification_status || '-' }}
                        </span>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-sm-3">
                        <strong>Email驗證時間：</strong>
                    </div>
                    <div class="col-sm-9">
                        {{ formatDateTime(member.email_verified_at) }}
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-sm-3">
                        <strong>註冊時間：</strong>
                    </div>
                    <div class="col-sm-9">
                        {{ formatDateTime(member.created_at) }}
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-sm-3">
                        <strong>最後更新：</strong>
                    </div>
                    <div class="col-sm-9">
                        {{ formatDateTime(member.updated_at) }}
                    </div>
                </div>

                <div class="row mb-3" v-if="member.last_login_at">
                    <div class="col-sm-3">
                        <strong>最後登入：</strong>
                    </div>
                    <div class="col-sm-9">
                        {{ formatDateTime(member.last_login_at) }}
                        <span v-if="member.login_count" class="text-muted ms-2">(登入次數：{{ member.login_count }})</span>
                    </div>
                </div>

                <!-- 社群帳號資訊 -->
                <div v-if="member.socialAccounts && member.socialAccounts.length > 0">
                    <hr class="my-4">
                    <h5 class="mb-3">社群帳號</h5>

                    <div v-for="account in member.socialAccounts" :key="account.id" class="row mb-3">
                        <div class="col-sm-3">
                            <strong>{{ getSocialProviderText(account.provider) }}：</strong>
                        </div>
                        <div class="col-sm-9">
                            {{ account.provider_id }}
                            <small class="text-muted ms-2">
                                (連結時間：{{ formatDateTime(account.created_at) }})
                            </small>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>

<script setup>
import Layout from "@/Shared/Admin/Layout.vue";
import BreadcrumbItem from "@/Shared/Admin/Partials/BreadcrumbItem.vue";
import { Link } from "@inertiajs/vue3";

// 接收 props
const props = defineProps({
    member: {
        type: Object,
        required: true
    }
});

// 格式化日期時間
const formatDateTime = (dateTime) => {
    if (!dateTime) return '-';
    return new Date(dateTime).toLocaleString('zh-TW', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
};

// 格式化日期
const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('zh-TW');
};

// 取得性別文字
const getGenderText = (gender) => {
    const genderMap = {
        'male': '男性',
        'female': '女性',
        'other': '其他'
    };
    return genderMap[gender] || '-';
};

// 取得驗證狀態樣式
const getVerificationBadgeClass = (status) => {
    const statusMap = {
        '已完成': 'bg-success',
        '待驗證': 'bg-warning',
        '待補充資料': 'bg-info'
    };
    return statusMap[status] || 'bg-secondary';
};

// 取得社群平台文字
const getSocialProviderText = (provider) => {
    const providerMap = {
        'google': 'Google',
        'line': 'LINE',
        'facebook': 'Facebook'
    };
    return providerMap[provider] || provider?.toUpperCase() || '-';
};
</script>

<script>
export default {
    layout: Layout,
};
</script>
