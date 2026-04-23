/*
 * Slim v5.3.2 - Image Cropping Made Easy
 * Copyright (c) 2021 Rik Schennink - https://pqina.nl/slim
 */
<template>
    <div class="slim">
        <slot></slot>
    </div>
</template>
<script>
  // Slim (place slim CSS and slim.module.js file in same folder as this file)
  import Slim from './slim.module.js'

  export default {
    name: 'slim',
    props: {
      options: {
        type: Object,
        required: true
      }
    },
    data() {
      return {
        slimInstance: null
      }
    },
    mounted() {
      // 如果有初始圖片，先插入 img 標籤
      if (this.options.initialImage) {
        const img = document.createElement('img')
        img.setAttribute('alt', '')
        img.src = this.options.initialImage
        this.$el.appendChild(img)
      }
      // 建立 Slim 實例並存到組件內部
      this.slimInstance = new Slim(this.$el, this.options)
      this.$emit('instance-created', this.slimInstance)
    },
    beforeUnmount() {
      // 安全銷毀 Slim 實例
      if (this.slimInstance && typeof this.slimInstance.destroy === 'function') {
        try {
          this.slimInstance.destroy()
        } catch (e) {
          console.warn('Slim destroy 時發生錯誤，已忽略:', e)
        }
      }
      this.slimInstance = null
    }
  }
</script>

