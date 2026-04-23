// utils/slimUtils.js

/**
 * 安全地獲取 Slim 組件的值
 * @param {Object} slimRef - Slim 組件的 ref 引用
 * @returns {string} Slim 的值，如果獲取失敗則返回空字符串
 */
export const getSlimValue = (slimRef) => {
  try {
    // 檢查 ref 是否存在
    if (!slimRef) {
      console.warn('Slim ref 不存在');
      return '';
    }

    // 優先使用暴露的方法
    if (typeof slimRef.getSlimValue === 'function') {
      return slimRef.getSlimValue();
    }

    // 備用方法：直接查詢 DOM
    try {
      const slimInput = slimRef?.slimImg?.$el?.querySelector('input[name="slim"]');
      return slimInput ? slimInput.value : '';
    } catch (domError) {
      console.warn('無法從 DOM 獲取 Slim 值:', domError);
      return '';
    }
  } catch (error) {
    console.error('獲取 Slim 值時發生錯誤:', error);
    return '';
  }
};

/**
 * 設置 Slim 組件的值
 * @param {Object} slimRef - Slim 組件的 ref 引用
 * @param {string} value - 要設置的值
 * @returns {boolean} 是否設置成功
 */
export const setSlimValue = (slimRef, value) => {
  try {
    if (!slimRef) {
      console.warn('Slim ref 不存在');
      return false;
    }

    // 優先使用暴露的方法
    if (typeof slimRef.setDate === 'function') {
      slimRef.setDate(value);
      return true;
    }

    // 備用方法：直接設置 DOM
    try {
      const slimInput = slimRef?.slimImg?.$el?.querySelector('input[name="slim"]');
      if (slimInput) {
        slimInput.value = value;
        return true;
      }
    } catch (domError) {
      console.warn('無法設置 DOM Slim 值:', domError);
    }

    return false;
  } catch (error) {
    console.error('設置 Slim 值時發生錯誤:', error);
    return false;
  }
};

/**
 * 清空 Slim 組件的值
 * @param {Object} slimRef - Slim 組件的 ref 引用
 * @returns {boolean} 是否清空成功
 */
export const clearSlimValue = (slimRef) => {
  try {
    if (!slimRef) {
      console.warn('Slim ref 不存在');
      return false;
    }

    // 優先使用暴露的方法
    if (typeof slimRef.clear === 'function') {
      slimRef.clear();
      return true;
    }

    // 備用方法：設置為空值
    return setSlimValue(slimRef, '');
  } catch (error) {
    console.error('清空 Slim 值時發生錯誤:', error);
    return false;
  }
};

/**
 * 檢查 Slim 組件是否有值
 * @param {Object} slimRef - Slim 組件的 ref 引用
 * @returns {boolean} 是否有值
 */
export const hasSlimValue = (slimRef) => {
  const value = getSlimValue(slimRef);
  return value && value.trim() !== '';
};

/**
 * 安全地銷毀 Slim 組件
 * @param {Object} slimRef - Slim 組件的 ref 引用
 * @returns {Promise<boolean>} 是否銷毀成功
 */
export const destroySlim = (slimRef) => {
  try {
    if (slimRef && typeof slimRef.safeDestroy === 'function') {
      slimRef.safeDestroy();
    }
    return true;
  } catch (error) {
    console.warn('清理 Slim 時發生錯誤（已忽略）:', error);
    return true;
  }
};

/**
 * 批量處理多個 Slim 組件的值
 * @param {Object} slimRefs - Slim 組件 ref 的對象，key 為欄位名，value 為 ref
 * @returns {Object} 包含所有 Slim 值的對象
 */
export const getBatchSlimValues = (slimRefs) => {
  const values = {};

  try {
    for (const [key, slimRef] of Object.entries(slimRefs)) {
      values[key] = getSlimValue(slimRef);
    }
  } catch (error) {
    console.error('批量獲取 Slim 值時發生錯誤:', error);
  }

  return values;
};

/**
 * 用於 Vue 組合式 API 的 Slim 工具 Hook
 * @param {Object} slimRef - Slim 組件的 ref 引用
 * @returns {Object} 包含各種 Slim 操作方法的對象
 */
export const useSlim = (slimRef) => {
  return {
    getValue: () => getSlimValue(slimRef),
    setValue: (value) => setSlimValue(slimRef, value),
    clearValue: () => clearSlimValue(slimRef),
    hasValue: () => hasSlimValue(slimRef),
    destroy: () => destroySlim(slimRef),
  };
};
