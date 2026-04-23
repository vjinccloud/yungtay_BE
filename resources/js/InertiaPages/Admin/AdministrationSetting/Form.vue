<template>
  <div class="content">
    <Head>
      <link rel="stylesheet" href="/plugins/jstree/themes/default/style.min.css" />
    </Head>
    <BreadcrumbItem />
    <div class="block block-rounded">
      <div class="block-header block-header-default">
        <h3 class="block-title">
          <Link class="btn btn-sm btn-alt-secondary" :href="route('admin.administration-settings')">
            <i class="fa fa-arrow-left me-1"></i>
            返回列表
          </Link>
        </h3>
      </div>
      
      <div class="block-content">
        <div class="row items-push">
          <!-- 表單區塊 -->
          <div class="col-md-6">
            <div class="row push">
              <div class="col-lg-12 col-xl-12">
                <div class="mb-4">
                  <label class="form-label" for="example-text-input" >角色名稱<span class="text-danger">*</span></label>
                  <input type="text" class="form-control " id="" name=""
                    placeholder="角色名稱"
                     @blur="validator.singleField('name')"
                    :class="form.errors.name?'parsley-error':''"
                    v-model="form.name"
                    required
                  >
                  <div v-if="form.errors.name" class="text-danger">
                    {{ form.errors.name }}
                    </div>
                </div>
                <div class="mb-4">
                  <label class="form-label" for="example-textarea-input">角色描述</label>
                  <textarea class="form-control" id="e" name="" rows="4"
                    placeholder="角色描述"
                    v-model="form.description"
                  ></textarea>
                </div>
              </div>
            </div>
          </div>
          <!-- 樹狀結構區塊 -->
          <div class="col-md-6" >
            <label for="checkTree2" class="form-label">權限管理</label>
            <div id="data" class="demo"></div>
          </div>
        </div>
        <div class="row items-push">
          <div class="text-end">
            <button type="button" class="btn btn-secondary me-2" @click="back"><i class="fa fa-arrow-left me-1"></i>回上一頁</button>
            <button type="button" class="btn btn-primary"  @click="submitForm" > <i class="fa fa-save me-1"></i>儲存</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
  import { useForm, usePage, router, Head, Link } from "@inertiajs/vue3";
  import Layout from "@/Shared/Admin/Layout.vue";
  import BreadcrumbItem from "@/Shared/Admin/Partials/BreadcrumbItem.vue";
  import { ref, reactive, onMounted, nextTick,inject,computed ,toRefs } from "vue";
  import { FormValidator, useSubmitForm } from '@/utils/index.js';
  const { submitForm: performSubmit } = useSubmitForm();

  // Props
  const page = usePage();
  const menu = computed(() => page.props.menu);
  const permissions = computed(() => page.props.role_permissions);
  const role = page.props?.role;

  // 表单数据
  const form = useForm({
    id: null,
    name: "",
    description: "",
    selectedIds:[],
    confirm: false,
  });
  const isEditing = ref(false);
  if(role){
    form.id = role.id;
    form.name = role.name;
    form.description = role.description;
    isEditing.value = true;
  }

  const isLoading = inject('isLoading');

  onMounted(() => {
    const script = document.createElement("script");
    script.src = "/plugins/jstree/jstree.min.js"; // 确保路径正确
    script.async = true;
    document.body.appendChild(script);

    script.onload = () => {
      const treeElement = document.querySelector("#data");
      if (treeElement) {
        $(treeElement)
          .jstree({
            core: {
              data: menu.value,
            },
            checkbox: {
              keep_selected_style: true,
              three_state: false, // 禁用三态逻辑
              cascade: "down", // 禁用级联
            },
            plugins: ["checkbox"],
          })
          .on("loaded.jstree", function () {
            $(treeElement).jstree().open_all();

            // 首先选中所有节点
            $(treeElement).jstree("check_all");

            // 获取所有节点
            let allNodes = $(treeElement).jstree(true).get_json("#", { flat: true });

            // 遍历所有节点，如果不在 permissions 数组中，则取消选中
            allNodes.forEach((node) => {
              if (!permissions.value.includes(node.id)) {
                $(treeElement).jstree("uncheck_node", node.id);
              }
            });
          })
          .on("select_node.jstree", function (event, data) {
            const node = data.node;
            const parent = data.instance.get_node(node.parent);
            if (parent.id !== "#" && parent && !data.instance.is_checked(parent.id)) {
              const childrenId = parent.children;
              data.instance.check_node(parent.id);
              if (childrenId.length > 0) {
                childrenId.forEach((nodeId) => {
                  if (nodeId !== node.id) {
                    $(treeElement).jstree("uncheck_node", nodeId);
                  }
                });
              }
            }
          });
      } else {
        console.error("#data 元素未找到");
      }
    };
  });

  const getSelectedNodeIds = () => {
    const treeElement = document.querySelector("#data");
    if (!treeElement) {
      console.error("#data element not found");
      return [];
    }

    let selectedNodeIds = $(treeElement).jstree("get_checked");
    let resultSet = new Set(selectedNodeIds);

    selectedNodeIds.forEach((nodeId) => {
      let node = $(treeElement).jstree().get_node(nodeId);
      while (node && node.id !== "#") {
        resultSet.add(node.id);
        node = $(treeElement).jstree().get_node(node.parent);
      }
    });

    return Array.from(resultSet);
  };

  router.on('finish', () => {
        isLoading.value = false;
  });


  const getRules = () => ({
    name: ['required'],
  });

  const validator = new FormValidator(form, getRules);

  // 提交表单
  const submitForm = async () => {
    form.selectedIds = getSelectedNodeIds();
    //isLoading.value = true;
    form.confirm = false;
    const url = isEditing.value
        ?  route('admin.administration-settings.update',form.id) // 编辑时的 API URL
        : route('admin.administration-settings.store');// 新增时的 API URL

    const method =isEditing.value ? 'put' : 'post';
    const hasErrors = await validator.hasErrors();
    if (!hasErrors) {
        performSubmit({ form, url, method });
    }
  };




  const back = () =>{
    window.history.back();
  };


</script>

<script>
export default {
  layout: Layout,
};
</script>

<style scoped>
/* 添加你的样式 */
</style>
