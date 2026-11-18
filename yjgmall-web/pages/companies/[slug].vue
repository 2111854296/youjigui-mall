<script setup lang="ts">
definePageMeta({ layout: 'company' })

const route = useRoute()
const config = useRuntimeConfig()
const { token } = useApi()
const slug = route.params.slug as string

const { data, error } = await useAsyncData(
  () => `company-${slug}-${token.value ? 'own' : 'pub'}`,
  () => $fetch(`${config.public.apiBase}/companies/${encodeURIComponent(slug)}`, {
    headers: token.value ? { Authorization: `Bearer ${token.value}` } : {},
  }),
)

const item = computed(() => (data.value as any)?.data)

/** 保留后台模板字段，前台统一走官网蓝白风格 */
function normalizeTemplate(raw?: string | null): 'atelier' | 'forge' | 'ledger' {
  const t = String(raw || 'atelier').toLowerCase()
  if (t === 'forge' || t === 'orange') return 'forge'
  if (t === 'ledger' || t === 'blue') return 'ledger'
  return 'atelier'
}

const template = computed(() => normalizeTemplate(item.value?.template))

useHead(() => ({ title: item.value?.name || '企业主页' }))
</script>

<template>
  <div>
    <div v-if="error" class="alert alert-error">
      企业不存在或未审核通过
      <div class="err-back">
        <NuxtLink to="/member/shop">返回旺铺设置</NuxtLink>
      </div>
    </div>
    <div v-else-if="!item" class="quiet">加载中…</div>
    <template v-else>
      <div v-if="item.owner_preview" class="preview-note">
        审核通过前只有你能看到这页，访客暂时打不开。
        <NuxtLink to="/member/shop">返回修改</NuxtLink>
      </div>
      <CompanyShopView :item="item" :template="template" />
    </template>
  </div>
</template>

<style scoped>
.quiet { color: #666; padding: 24px 0; }
.err-back { margin-top: 8px; }
.err-back a, .preview-note a { margin-left: 8px; color: var(--blue, #2468f2); }
.preview-note {
  margin: 12px 0;
  padding: 10px 14px;
  border: 1px solid #b9cdf6;
  background: #f8faff;
  color: #123c8f;
  border-radius: 6px;
  font-size: 14px;
}
</style>
