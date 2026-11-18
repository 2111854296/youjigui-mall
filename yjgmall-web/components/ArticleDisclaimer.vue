<script setup lang="ts">
const props = defineProps<{
  title?: string
  url?: string
}>()

const pageUrl = computed(() => {
  if (props.url) return props.url
  if (!import.meta.client) return ''
  return window.location.href
})
</script>

<template>
  <aside class="art-disclaimer" aria-label="免责声明">
    <p>
      <b>免责声明：</b>有机硅商城一直努力为您提供准确、可靠的信息，力求内容客观、中立，但由于市场信息变化较快，我们无法保证信息在任何时点都完整无误，相关内容也可能根据实际情况适时调整。平台上的信息仅供您参考，不构成任何投资、经营或其他决策建议，请您结合自身情况独立判断，据此作出的任何决策及相应结果，均与有机硅商城无关。
    </p>
    <p>
      本文内容版权归有机硅商城所有，仅供授权网站用户阅读使用。如需转载或引用，欢迎通过官方渠道与我们联系取得授权；未经书面许可，请勿擅自传播、发布、复制或改写本文。感谢您的理解与配合，我们也会对侵犯平台权益或歪曲原文原意的行为保留依法追究的权利。
    </p>
    <p v-if="title" class="meta-line"><span>本文标题：</span>{{ title }}</p>
    <p v-if="pageUrl" class="meta-line">
      <span>本文地址：</span>
      <a :href="pageUrl" target="_blank" rel="noopener noreferrer">{{ pageUrl }}</a>
    </p>
  </aside>
</template>

<style scoped>
.art-disclaimer {
  margin-top: 28px;
  padding: 16px 18px;
  border: 1px solid #f0d4b5;
  border-radius: 10px;
  background: #fff8f0;
  color: #8a4b1a;
  font-size: 13.5px;
  line-height: 1.75;
}
.art-disclaimer p {
  margin: 0 0 10px;
}
.art-disclaimer p:last-child {
  margin-bottom: 0;
}
.art-disclaimer b {
  font-weight: 700;
  color: #7a3f12;
}
.art-disclaimer .meta-line {
  margin-top: 8px;
  margin-bottom: 0;
  word-break: break-all;
}
.art-disclaimer .meta-line span {
  font-weight: 600;
}
.art-disclaimer a {
  color: #c45a12;
  text-decoration: underline;
  text-underline-offset: 2px;
}
.art-disclaimer a:hover {
  color: #a3460a;
}
</style>
