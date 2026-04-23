import { validationRules } from './validationRules';

export class FormValidator {
  constructor(form, getRules) {
    this.form = form;
    this.getRules = getRules;
  }

  // 驗證單個欄位
  async validateField(value, rules = []) {
    const errors = [];
    for (const rule of rules) {
      if (typeof rule === 'string') {
        const validate = validationRules[rule];
        if (validate) {


          const result = validate(value, this.form);
          if (result !== true) errors.push(result);
        }
      } else if (Array.isArray(rule)) {
        const [ruleName, ...params] = rule;
        const validate = validationRules[ruleName];
        if (validate) {
          const result = await validate(value, ...params, this.form);
          if (result !== true) errors.push(result);
        }
      }
    }

    return errors;
  }

  async getValidateErr(fieldName) {
    // 1. 把 children[0] 轉成 children.0
    let normalized = fieldName.replace(/\[(\d+)\]/g, '.$1');
    // 2. 把 .0. 這種數字 index 換成 .*.
    const wildcardKey = normalized.replace(/\.\d+\./g, '.*.');

    const rulesMap = flattenRules(this.getRules());
    // 先找 exact match，找不到就用 wildcard match
    const fieldRules = rulesMap[normalized]
      ?? rulesMap[wildcardKey]
      ?? [];

    const value = getNestedValue(this.form, fieldName);
    return await this.validateField(value, fieldRules);
  }

  async singleField(fieldName) {
    const fieldErrors = await this.getValidateErr(fieldName);
    if (fieldErrors.length > 0) {
      setNestedValue(this.form.errors, fieldName, fieldErrors[0]);
    } else {
      deleteNestedValue(this.form.errors, fieldName);
    }
  }

  async hasErrors() {
    const rulesObj = this.getRules();
    const flatRules = flattenRules(rulesObj); // e.g. { 'parent_name.zh_TW': [...], 'children.*.name.zh_TW': [...] }

    for (const fieldName in flatRules) {
      if (fieldName.includes('*')) {
        // children.*.name.zh_TW → 先拆出陣列路徑 "children"
        const [arrKey] = fieldName.split('.');
        const arr = getNestedValue(this.form, arrKey);
        if (Array.isArray(arr)) {
          // 針對每個 index，都呼叫一次 singleField
          for (let i = 0; i < arr.length; i++) {
            // children.*.name.zh_TW → children.0.name.zh_TW, children.1.name.zh_TW, ...
            const concrete = fieldName.replace('*', i);
            await this.singleField(concrete);
          }
        }
      } else {
        // 沒有 wildcard，照常呼叫
        await this.singleField(fieldName);
      }
    }

    return Object.keys(this.form.errors).length > 0;
  }
}
// 取得巢狀值，如 'user.name' 或 'items[0].name'
function getNestedValue(obj, path) {
  const parts = path.replace(/\[(\d+)\]/g, '.$1').split('.');
  return parts.reduce((o, key) => (o != null ? o[key] : undefined), obj);
}

// 設定巢狀值
function setNestedValue(obj, path, value) {
  const parts = path.replace(/\[(\d+)\]/g, '.$1').split('.');
  let current = obj;
  for (let i = 0; i < parts.length - 1; i++) {
    if (!current[parts[i]]) current[parts[i]] = {};
    current = current[parts[i]];
  }
  current[parts[parts.length - 1]] = value;
}

// 刪除巢狀值
function deleteNestedValue(obj, path) {
  const parts = path.replace(/\[(\d+)\]/g, '.$1').split('.');
  let current = obj;
  const stack = [];

  for (let i = 0; i < parts.length - 1; i++) {
    if (!current[parts[i]]) return;
    stack.push({ obj: current, key: parts[i] });
    current = current[parts[i]];
  }

  // 刪除最終 key
  delete current[parts[parts.length - 1]];

  // 如果父層是空的，就遞迴刪除
  for (let i = stack.length - 1; i >= 0; i--) {
    const { obj, key } = stack[i];
    if (Object.keys(obj[key]).length === 0) {
      delete obj[key];
    } else {
      break;
    }
  }
}

function flattenRules(obj, parentKey = '', result = {}) {
  for (const key in obj) {
    const path = parentKey ? `${parentKey}.${key}` : key;
    if (Array.isArray(obj[key])) {
      result[path] = obj[key];
    } else if (typeof obj[key] === 'object') {
      flattenRules(obj[key], path, result);
    }
  }
  return result;
}
