// bootstrapModal.js
import { Modal } from "bootstrap";

export function createModal(modalElement, options = {}) {
  if (!modalElement) {
    throw new Error("modalElement is required to initialize the modal.");
  }

  // 初始化模態框
  const modalInstance = new Modal(modalElement, {
    backdrop: options.backdrop || "static",
    keyboard: options.keyboard || false,
    ...options,
  });

  // 提供封裝的操作方法
  return {
    show: () => modalInstance.show(), // 開啟模態框
    hide: () => modalInstance.hide(), // 關閉模態框
    dispose: () => modalInstance.dispose(), // 銷毀模態框實例
    instance: modalInstance, // 返回原始模態框實例（如果需要更多操作）
  };
}
