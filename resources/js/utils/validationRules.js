const isEmpty = (value) => {
  if (value === null || value === undefined) return true;
  if (typeof value === 'string' && value.trim() === '') return true;
  if (Array.isArray(value) && value.length === 0) return true;
  return false;
};

export const validationRules = {
  required: (value) => {
    return !isEmpty(value) || '此欄位為必填欄位。';
  },

  string: (value) => {
    return isEmpty(value) || typeof value === 'string' || '此欄位必須是文字格式。';
  },

  email: (value) => {
    return isEmpty(value) || /\S+@\S+\.\S+/.test(value) || '請輸入有效的電子郵件地址。';
  },

  max: (value, maxLength, customMessage, form) => {
    // 支援字串長度和數字大小的最大值驗證
    if (isEmpty(value)) return true;

    // 確保 customMessage 是字串才使用
    const message = typeof customMessage === 'string' ? customMessage : '';

    if (typeof value === 'string') {
      return value.length <= maxLength || message || `最大長度為 ${maxLength} 個字元。`;
    }

    if (typeof value === 'number') {
      return value <= maxLength || message || `數值不能大於 ${maxLength}。`;
    }

    return message || `此欄位的最大值為 ${maxLength}。`;
  },

  min: (value, minLength, customMessage, form) => {
    // 支援字串長度和數字大小的最小值驗證
    if (isEmpty(value)) return true;

    // 確保 customMessage 是字串才使用
    const message = typeof customMessage === 'string' ? customMessage : '';

    if (typeof value === 'string') {
      return value.length >= minLength || message || `最小長度為 ${minLength} 個字元。`;
    }

    if (typeof value === 'number') {
      return value >= minLength || message || `數值不能小於 ${minLength}。`;
    }

    return message || `此欄位的最小值為 ${minLength}。`;
  },

  // 新增：專門用於數字範圍驗證的規則
  minValue: (value, minValue, customMessage, form) => {
    if (isEmpty(value)) return true;
    const numValue = Number(value);
    if (isNaN(numValue)) return '此欄位必須是數字。';

    // 確保 customMessage 是字串才使用
    const message = typeof customMessage === 'string' ? customMessage : '';
    return numValue >= minValue || message || `數值不能小於 ${minValue}。`;
  },

  maxValue: (value, maxValue, customMessage, form) => {
    if (isEmpty(value)) return true;
    const numValue = Number(value);
    if (isNaN(numValue)) return '此欄位必須是數字。';

    // 確保 customMessage 是字串才使用
    const message = typeof customMessage === 'string' ? customMessage : '';
    return numValue <= maxValue || message || `數值不能大於 ${maxValue}。`;
  },

  // 新增：數字範圍驗證（同時檢查最小和最大值）
  between: (value, minValue, maxValue, customMessage, form) => {
    if (isEmpty(value)) return true;
    const numValue = Number(value);
    if (isNaN(numValue)) return '此欄位必須是數字。';

    // 確保 customMessage 是字串才使用
    const message = typeof customMessage === 'string' ? customMessage : '';
    return (numValue >= minValue && numValue <= maxValue) ||
      message ||
      `數值必須介於 ${minValue} 到 ${maxValue} 之間。`;
  },

  // 新增：數字驗證
  numeric: (value) => {
    if (isEmpty(value)) return true;
    return !isNaN(Number(value)) || '此欄位必須是數字。';
  },

  // 新增：整數驗證
  integer: (value) => {
    if (isEmpty(value)) return true;
    const numValue = Number(value);
    return (Number.isInteger(numValue)) || '此欄位必須是整數。';
  },

  regex: (value, regex, message) => {
    return isEmpty(value) || regex.test(value) || message || '格式不正確。';
  },

  confirmed: (value, fieldName, message, form) => {
    const confirmationValue = form[fieldName];
    return value === confirmationValue || message || '與確認欄位不一致。';
  },
};
