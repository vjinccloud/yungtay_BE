<template>
  <slim-cropper
    :options="slimOptions"
    ref="slimImg"
    @instance-created="handleInstanceCreated"
    :class="$attrs.class"
  >
    <input type="file" name="slim" />
  </slim-cropper>
</template>

<script setup>
import { ref, reactive, onMounted, watch, onBeforeUnmount,  } from 'vue';
import SlimCropper from '../../plugins/slim/slim.vue';
import { loadCss } from '@/utils/scriptLoader.js';

const props = defineProps({
  label: {
    type: String,
    default: '圖片拖移至此，請上傳指定尺寸後可裁切。',
  },
  accept: {
    type: String,
    default: '*',
  },
  width: {
    type: Number,
    default: 0,
  },
  height: {
    type: Number,
    default: 0,
  },
  ratio: {
    type: String,
    default: '1:1',
  },
  url: {
    type: String,
    default: '',
  },
  server: {
    type: Boolean,
    default: false,
  },
  initialImage: {
    type: String,
    default: '',
  },
  maxFileSize: {
    type: [String, Number, Boolean],
    default: '10', // 預設 10MB
  },
});

const emits = defineEmits(['cleared', 'update:modelValue', 'update:cleared']);
const slimImg = ref(null);
const instanceSlim = ref(null);
const isDestroyed = ref(false);

const slimInit = (data, slim) => {
  if (!isDestroyed.value) {
    instanceSlim.value = slim;
  }
};

const handleRemove = () => {
  emits('cleared');
  emits('update:cleared', true);
  console.log('Slim image removed');
};

const slimOptions = {
  // 只有當 width 和 height 都大於 0 時才強制尺寸
  ...(props.width > 0 && props.height > 0 && {
    forceSize: {
      width: props.width,
      height: props.height,
    }
  }),
  label: props.label,
  ratio: props.ratio,
  buttonConfirmLabel: '確定',
  buttonCancelLabel: '取消',
  statusImageTooSmall: '圖片尺吋太小或過長',
  maxFileSize: props.maxFileSize,
  initialImage: props.initialImage,
  didInit: slimInit,
  didRemove: handleRemove,
};

const handleInstanceCreated = (instance) => {
  if (!isDestroyed.value) {
    instanceSlim.value = instance;
  }
};

const getSlimValue = () => {
  try {
    if (isDestroyed.value) return '';
    const input = slimImg.value?.$el?.querySelector?.('input[name="slim"]');
    if (!input) return '';
    return input.value ?? '';
  } catch (error) {
    console.warn('獲取 Slim 值時發生錯誤:', error);
    return '';
  }
};

const safeDestroy = () => {
  if (isDestroyed.value) return;
  isDestroyed.value = true;
  instanceSlim.value = null;
  slimImg.value = null;
  console.log('Slim 組件已清理');
};

onMounted(async () => {
  try {
    await loadCss('/plugins/slim/css/slim.min.css');
  } catch (error) {
    console.warn('載入 Slim CSS 時發生錯誤:', error);
  }
});

onBeforeUnmount(() => {
  safeDestroy();
});

defineExpose({
  slimImg,
  getSlimValue,
  safeDestroy,
  isDestroyed,
});
</script>

<style scoped>

</style>
