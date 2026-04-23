<!--
  TranslatableEditor.vue — 多語系 CKEditor4 富文本編輯器元件
  ===========================================================
  根據 config/translatable.php 自動產生對應語言的 CKEditor 欄位。

  用法：
    <TranslatableEditor
      v-model="form.description"   ← 傳入 { zh_TW: '...', en: '...' } 的物件
      label="商品描述"              ← 標題（可選）
    />
-->
<template>
    <div class="translatable-editor">
        <div
            v-for="(localeCfg, localeKey) in locales"
            :key="localeKey"
            class="mb-4"
        >
            <label class="form-label fw-semibold">
                {{ label ? `${label}（${localeCfg.label}）` : localeCfg.label }}
            </label>
            <CKEditor4
                :modelValue="modelValue[localeKey] || ''"
                @update:modelValue="onInput(localeKey, $event)"
            />
        </div>
    </div>
</template>

<script>
import { usePage } from "@inertiajs/vue3";
import { computed } from "vue";
import CKEditor4 from "@/Plugin/CKEditor4.vue";

export default {
    name: "TranslatableEditor",
    components: { CKEditor4 },
    props: {
        modelValue: { type: Object, default: () => ({}) },
        label:      { type: String, default: '' },
    },
    emits: ['update:modelValue'],
    setup(props, { emit }) {
        const page = usePage();

        const locales = computed(() => page.props.translatableLocales || { zh_TW: { label: '中文', placeholder: '請輸入中文' } });

        const onInput = (localeKey, value) => {
            const updated = { ...props.modelValue, [localeKey]: value };
            emit('update:modelValue', updated);
        };

        return {
            locales,
            onInput,
        };
    },
};
</script>
