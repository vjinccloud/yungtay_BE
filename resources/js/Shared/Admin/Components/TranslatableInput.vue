<!--
  TranslatableInput.vue — 多語系文字輸入元件
  =========================================
  根據 config/translatable.php 自動產生對應語言的 input 欄位。

  用法：
    <TranslatableInput
      v-model="form.name"       ← 傳入 { zh_TW: '...', en: '...' } 的物件
      label="商品名稱"           ← 欄位標題
      placeholder="請輸入名稱"    ← 自訂 placeholder（可選，預設用 config）
      :required="true"           ← 主語系是否必填（預設 true）
      :errors="form.errors"      ← Inertia form errors（可選）
      errorPrefix="name"         ← 錯誤前綴 key（可選，如 'name' → errors['name.zh_TW']）
      inputClass=""              ← 額外 input class（可選）
      size="sm"                  ← 'sm' | 'default'（可選）
    />
-->
<template>
    <div class="translatable-input">
        <label v-if="label" class="form-label fw-semibold">
            <span v-if="required" class="text-danger">* </span>{{ label }}
        </label>
        <div
            v-for="(localeCfg, localeKey) in locales"
            :key="localeKey"
            class="mb-2"
        >
            <div class="input-group" :class="inputGroupClass">
                <span class="input-group-text" style="min-width: 70px; justify-content: center;">
                    {{ localeCfg.label }}
                </span>
                <input
                    :type="type"
                    class="form-control"
                    :class="[
                        sizeClass,
                        inputClass,
                        { 'is-invalid': hasError(localeKey) }
                    ]"
                    :value="modelValue[localeKey] || ''"
                    @input="onInput(localeKey, $event.target.value)"
                    :placeholder="getPlaceholder(localeCfg, localeKey)"
                />
                <div v-if="hasError(localeKey)" class="invalid-feedback">
                    {{ getError(localeKey) }}
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { usePage } from "@inertiajs/vue3";
import { computed } from "vue";

export default {
    name: "TranslatableInput",
    props: {
        modelValue:  { type: Object, default: () => ({}) },
        label:       { type: String, default: '' },
        placeholder: { type: String, default: '' },
        required:    { type: Boolean, default: true },
        errors:      { type: Object, default: () => ({}) },
        errorPrefix: { type: String, default: '' },
        inputClass:  { type: String, default: '' },
        type:        { type: String, default: 'text' },
        size:        { type: String, default: '' },  // 'sm' or ''
    },
    emits: ['update:modelValue'],
    setup(props, { emit }) {
        const page = usePage();

        const locales = computed(() => page.props.translatableLocales || { zh_TW: { label: '中文', placeholder: '請輸入中文' } });
        const primary = computed(() => page.props.translatablePrimary || 'zh_TW');

        const sizeClass = computed(() => props.size === 'sm' ? 'form-control-sm' : '');
        const inputGroupClass = computed(() => props.size === 'sm' ? 'input-group-sm' : '');

        const onInput = (localeKey, value) => {
            const updated = { ...props.modelValue, [localeKey]: value };
            emit('update:modelValue', updated);
        };

        const getPlaceholder = (localeCfg, localeKey) => {
            if (props.placeholder) {
                return `${props.placeholder}（${localeCfg.label}）`;
            }
            return localeCfg.placeholder || '';
        };

        const hasError = (localeKey) => {
            if (!props.errorPrefix || !props.errors) return false;
            return !!props.errors[`${props.errorPrefix}.${localeKey}`];
        };

        const getError = (localeKey) => {
            if (!props.errorPrefix || !props.errors) return '';
            return props.errors[`${props.errorPrefix}.${localeKey}`] || '';
        };

        return {
            locales,
            primary,
            sizeClass,
            inputGroupClass,
            onInput,
            getPlaceholder,
            hasError,
            getError,
        };
    },
};
</script>

<style scoped>
.translatable-input .input-group-text {
    font-size: 0.85rem;
    background: #f0f2f5;
    color: #555;
    font-weight: 500;
}
</style>
