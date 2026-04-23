import $ from "jquery";
import { router } from "@inertiajs/vue3";
import { nextTick } from "vue";
import { Tooltip } from "bootstrap";

if (typeof window !== "undefined") {
  window.$ = window.jQuery = $;
}

/* -------------------------------------------------
 * Tooltip：在特定範圍清理 / 初始化（僅限表格 wrapper）
 * ------------------------------------------------- */
export function destroyAllTooltips(rootEl = document, removeDom = true) {
  const root = rootEl instanceof Element ? rootEl : document;

  root.querySelectorAll("[data-bs-toggle='tooltip']").forEach((el) => {
    try { Tooltip.getInstance(el)?.dispose(); } catch {}
  });

  if (removeDom) {
    // 保險：移除可能殘留在 body 的 tooltip 浮層
    document.querySelectorAll(".tooltip").forEach((t) => t.remove());
  }
}

export function defaultDrawCallback(rootEl = document) {
  const root = rootEl instanceof Element ? rootEl : document;

  root.querySelectorAll("[data-bs-toggle='tooltip']").forEach((el) => {
    // 先把舊實例清掉，避免重複綁定
    try { Tooltip.getInstance(el)?.dispose(); } catch {}

    // 避免瀏覽器原生 title 影響
    const rawTitle = el.getAttribute("title");
    if (rawTitle) {
      el.setAttribute("data-bs-title", rawTitle);
      el.removeAttribute("title");
    }

    new Tooltip(el, {
      container: "body",
      boundary: document.body,
      trigger: "hover focus",
      fallbackPlacements: ["top", "bottom", "right", "left"],
      delay: { show: 120, hide: 80 },
    });
  });
}

/* -------------------------------------------------
 * Chrome Autofill Guard：接管 DataTables 搜尋欄
 * ------------------------------------------------- */
function wireDataTableSearchInput(dt) {
  // dt: DataTables API instance
  const wrapper = dt.table().container(); // 該表格的 wrapper
  const input = wrapper.querySelector(".dataTables_filter input");
  if (!input) return;

  // 解除 DataTables 預設的 keyup/input 綁定（避免 autofill 觸發連續搜尋）
  $(input).off(".DT");

  // 衛生處理：關閉自動完成與語系校正、隨機 name、拔 id
  input.setAttribute("type", "text");
  input.setAttribute("autocomplete", "off");
  input.setAttribute("autocapitalize", "off");
  input.setAttribute("autocorrect", "off");
  input.setAttribute("spellcheck", "false");
  input.setAttribute("inputmode", "search");
  input.removeAttribute("id");
  input.setAttribute("name", "dt_search_" + Math.random().toString(36).slice(2, 8));

  // 初始化短暫只讀，擋掉載入瞬間的 autofill
  const GUARD_MS = 800;
  input.readOnly = true;
  setTimeout(() => { input.readOnly = false; }, GUARD_MS);

  // 忽略剛載入那段時間的非使用者事件
  const wiredAt = Date.now();
  let lastSent = input.value;

  const doSearch = (val) => {
    if (val === lastSent) return; // 同值不重送
    lastSent = val;
    dt.search(val).draw();
  };

  // 簡單 debounce 輸入事件
  let timer = null;
  const debouncedInput = () => {
    clearTimeout(timer);
    timer = setTimeout(() => doSearch(input.value.trim()), 250);
  };

  // 僅處理使用者輸入；忽略載入前 GUARD_MS 與非使用者事件
  input.addEventListener("input", (e) => {
    if (Date.now() - wiredAt < GUARD_MS) return;
    if (!e.isTrusted) return; // 多數 autofill 非使用者事件
    debouncedInput();
  });

  // Enter 立即搜尋
  input.addEventListener("keydown", (e) => {
    if (e.key === "Enter") {
      e.preventDefault();
      doSearch(input.value.trim());
    }
  });

  // Focus 時清掉預設空白搜尋（若有使用 oSearch: " "）
  input.addEventListener("focus", () => {
    if (input.value === " ") input.value = "";
  });
}

/* -------------------------------------------------
 * DataTables 基礎設定（保留原有動線）
 * ------------------------------------------------- */
export function getBaseOptions() {
  return {
    serverSide: true,
    processing: true,
    pageLength: 10,
    lengthMenu: [10, 25, 50, 100],
    stateSave: true,
    stateSaveParams(settings, data) {
      data.search.search = "";
    },
    stateLoadParams(settings, data) {
      data.search.search = "";
    },
    oSearch: { sSearch: " " },

    language: {
      lengthMenu: "_MENU_",
      search: "_INPUT_",
      searchPlaceholder: "請輸入關鍵字",
      info: "Page <strong>_PAGE_</strong> of <strong>_PAGES_</strong>",
      infoEmpty: "",
      zeroRecords: "無資料",
      emptyTable: "無資料",
      paginate: {
        first: '<i class="fa fa-angle-double-left"></i>',
        previous: '<i class="fa fa-angle-left"></i>',
        next: '<i class="fa fa-angle-right"></i>',
        last: '<i class="fa fa-angle-double-right"></i>',
      },
    },

    // 重繪前：清理「本表格 wrapper」內的 tooltip
    preDrawCallback: function () {
      const wrapper = this?.nTableWrapper || document;
      destroyAllTooltips(wrapper);
    },

    // 重繪後：在「本表格 wrapper」內重新初始化 tooltip，並接管搜尋欄
    drawCallback: function () {
      const wrapper = this?.nTableWrapper || document;
      defaultDrawCallback(wrapper);
      wireDataTableSearchInput(this.api()); // 防止 DataTables 重新綁回預設事件
    },

    initComplete: function () {
      setTimeout(() => {
        const wrapper = this?.nTableWrapper || document;
        const searchInput = wrapper.querySelector(".dataTables_filter input");
        if (!searchInput) return;

        // 1) type 改 text，避免瀏覽器干擾
        searchInput.setAttribute("type", "text");

        // 2) 破壞 Chrome autocomplete 的關聯
        searchInput.setAttribute("autocomplete", "off");
        searchInput.removeAttribute("id");
        searchInput.setAttribute(
          "name",
          "dt_search_" + Math.random().toString(36).substr(2, 6)
        );

        // 3) 關閉其他自動更正
        searchInput.setAttribute("autocapitalize", "off");
        searchInput.setAttribute("autocorrect", "off");
        searchInput.setAttribute("spellcheck", "false");

        // 4) 清空並重畫（不重跑 ajax）
        searchInput.value = "";
        this.api().search("").draw(false);

        // 5) 使用者點進去再清一次
        searchInput.addEventListener("focus", () => {
          searchInput.value = "";
          this.api().search("").draw(false);
        });
      }, 1000);

      // — 視覺與頁長同步（保留原本動線） —
      const tableWrapper = $(this.$el || this);
      tableWrapper.find(".form-select").removeClass("form-select-sm");
      tableWrapper.find(".form-control-sm").removeClass("form-control-sm");
      try {
        const lengthSelect = tableWrapper.find(".dataTables_length select");
        const currentLen = this.api().page.len();
        if (lengthSelect && lengthSelect.length) {
          lengthSelect.val(String(currentLen));
        }
      } catch (e) { /* ignore */ }

      // 初次完成後接管搜尋欄（再次保險）
      wireDataTableSearchInput(this.api());

      this.api().search("").draw();
    },
  };
}

/* -------------------------------------------------
 * DataTables 請求：Inertia
 * ------------------------------------------------- */
export function fetchTableDataInertia(
  url,
  data,
  callback,
  rows,
  responseKey = "adminUsers",
  extraParams = {},
  onError
) {
  const searchQuery = data.search.value;
  const form = {
    page: data.start / data.length + 1,
    length: data.length,
    sortColumn: data.order?.[0]?.column
      ? data.columns[data.order[0].column].data
      : null,
    sortDirection: data.order?.[0]?.dir || null,
    search: searchQuery,
    ...extraParams,
  };

  router.get(url, form, {
    preserveState: true,
    only: [responseKey],
    onSuccess: (response) => {
      const result = response.props[responseKey];
      if (!result) {
        console.error(`響應中未找到鍵：${responseKey}`);
        callback({
          draw: data.draw,
          recordsTotal: 0,
          recordsFiltered: 0,
          data: [],
        });
        rows.value = [];
        return;
      }
      callback({
        draw: data.draw,
        recordsTotal: result.total,
        recordsFiltered: result.total,
        data: result.data,
      });
      rows.value = [...result.data];
    },
    onError: (error) => {
      callback({
        draw: data.draw,
        recordsTotal: 0,
        recordsFiltered: 0,
        data: [],
      });
      rows.value = [];
      console.error("AJAX 請求失敗:", error);
      if (onError) onError(error);
    },
  });
}

/* -------------------------------------------------
 * DataTables 請求：AJAX
 * ------------------------------------------------- */
export function fetchTableDataAjax(
  url,
  data,
  callback,
  rows,
  extraParams = {},
  onError
) {
  const searchQuery = data.search.value;
  const params = {
    page: data.start / data.length + 1,
    length: data.length,
    sortColumn: data.order?.[0]?.column
      ? data.columns[data.order[0].column].data
      : null,
    sortDirection: data.order?.[0]?.dir || null,
    search: searchQuery,
    ...extraParams,
  };

  const queryString = new URLSearchParams(params).toString();

  fetch(`${url}?${queryString}`, {
    method: "GET",
    headers: {
      Accept: "application/json",
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
      "X-CSRF-TOKEN":
        document.querySelector("meta[name='csrf-token']")?.getAttribute("content") || "",
    },
  })
    .then((response) => response.json())
    .then((result) => {
      callback({
        draw: data.draw,
        recordsTotal: result.total,
        recordsFiltered: result.total,
        data: result.data,
      });
      rows.value = [...result.data];
    })
    .catch((error) => {
      console.error("AJAX 請求失敗:", error);
      callback({
        draw: data.draw,
        recordsTotal: 0,
        recordsFiltered: 0,
        data: [],
      });
      rows.value = [];
      if (onError) onError(error);
    });
}

/* -------------------------------------------------
 * 統一入口：Inertia / AJAX
 * ------------------------------------------------- */
export function fetchTableData(
  url,
  data,
  callback,
  rows,
  responseKey = "adminUsers",
  extraParams = {},
  onError,
  useAjax = false
) {
  if (useAjax) {
    fetchTableDataAjax(url, data, callback, rows, extraParams, onError);
  } else {
    fetchTableDataInertia(
      url,
      data,
      callback,
      rows,
      responseKey,
      extraParams,
      onError
    );
  }
}

/* -------------------------------------------------
 * 綁定列表按鈕事件（保留既有 hide 行為）
 * ------------------------------------------------- */
export function bindTableButtonEvents(callbacks = {}) {
  const { edit = null, delete: deleteCallback = null, check = null, play = null, show = null, viewLogs = null } =
    callbacks;

  const hideAllTooltips = () => {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((btn) => {
      try { Tooltip.getInstance(btn)?.hide(); } catch {}
    });
  };

  const editRows = document.querySelectorAll(".edit-btn");
  editRows.forEach((row) => {
    const id = row.getAttribute("data-id");
    row.addEventListener("click", () => {
      hideAllTooltips();
      if (edit) edit(id);
    });
  });

  if (deleteCallback) {
    const deleteRows = document.querySelectorAll(".delete-btn");
    deleteRows.forEach((row) => {
      const id = row.getAttribute("data-id");
      row.addEventListener("click", (event) => {
        hideAllTooltips();
        event.currentTarget.blur();
        deleteCallback(id);
      });
    });
  }

  if (play && typeof play === "function") {
    const playRows = document.querySelectorAll(".play-btn");
    playRows.forEach((row) => {
      const id = row.getAttribute("data-id");
      row.addEventListener("click", (event) => {
        hideAllTooltips();
        event.currentTarget.blur();
        play(id);
      });
    });
  }

  if (show && typeof show === "function") {
    const showRows = document.querySelectorAll(".show-btn");
    showRows.forEach((row) => {
      const id = row.getAttribute("data-id");
      row.addEventListener("click", (event) => {
        hideAllTooltips();
        event.currentTarget.blur();
        show(id);
      });
    });
  }

  if (viewLogs && typeof viewLogs === "function") {
    const viewLogsRows = document.querySelectorAll(".view-logs-btn");
    viewLogsRows.forEach((row) => {
      const id = row.getAttribute("data-id");
      const title = row.getAttribute("data-title");
      row.addEventListener("click", (event) => {
        hideAllTooltips();
        event.currentTarget.blur();
        viewLogs(id, title);
      });
    });
  }

  document.querySelectorAll(".checked-btn").forEach((button) => {
    button.addEventListener("change", (event) => {
      const id = event.target.dataset.id;
      if (check) check(id);
    });
  });
}

/* -------------------------------------------------
 * 建立 DataTable（保留原有流程）
 * ------------------------------------------------- */
export function createDataTable(el, onReady) {
  return nextTick(() => {
    if (!el || !el.dt) {
      console.error("DataTable element or dt instance not found");
      return null;
    }

    const dt = el.dt;

    dt.on("init.dt", () => {
      try {
        const tableWrapper = $(el.$el);
        tableWrapper.find(".form-select").removeClass("form-select-sm");
        tableWrapper.find(".form-control-sm").removeClass("form-control-sm");

        // 依原本需求：清 state 與搜尋
        dt.state.clear();
        dt.search("").draw(false);

        if (typeof onReady === "function") onReady(dt);
      } catch (error) {
        console.error("DataTable 初始化錯誤:", error);
      }
    });

    // 銷毀時清掉該表格範圍的 tooltip（不影響其他區塊）
    dt.on("destroy.dt", () => {
      const wrapper = dt.table().container();
      destroyAllTooltips(wrapper);
    });

    return dt;
  });
}

export default {
  getBaseOptions,
  fetchTableData,
  fetchTableDataInertia,
  fetchTableDataAjax,
  bindTableButtonEvents,
  createDataTable,
  defaultDrawCallback,
  destroyAllTooltips,
};
