<script setup lang="ts">
const props = withDefaults(defineProps<{
  type: 'supply' | 'purchase' | 'product' | 'quote' | 'resume' | 'job'
  id: number | string
  label?: string
  savedLabel?: string
  buttonClass?: string
}>(), {
  label: '收藏',
  savedLabel: '取消收藏',
  buttonClass: 'btn btn-outline',
})

const { user } = useAuth()
const { api } = useApi()
const route = useRoute()
const tip = ref('')
const loading = ref(false)
const saved = ref(false)
const favId = ref<number | null>(null)

async function loadState() {
  saved.value = false
  favId.value = null
  if (!user.value || !props.id) return
  try {
    const res = await api<{ data?: { favorited?: boolean; id?: number | null } }>('/member/favorites/check', {
      query: { type: props.type, id: Number(props.id) },
    })
    saved.value = Boolean(res?.data?.favorited)
    favId.value = res?.data?.id ? Number(res.data.id) : null
  } catch {
    saved.value = false
    favId.value = null
  }
}

async function toggle() {
  if (!user.value) {
    return navigateTo({ path: '/login', query: { redirect: route.fullPath } })
  }
  if (loading.value) return
  loading.value = true
  tip.value = ''
  try {
    if (saved.value && favId.value) {
      await api(`/member/favorites/${favId.value}`, { method: 'DELETE' })
      saved.value = false
      favId.value = null
      return
    }
    const res = await api<{ data?: { id?: number } }>('/member/favorites', {
      method: 'POST',
      body: { type: props.type, id: Number(props.id) },
    })
    saved.value = true
    favId.value = res?.data?.id ? Number(res.data.id) : favId.value
  } catch (e: any) {
    tip.value = e?.data?.message || '操作失败'
  } finally {
    loading.value = false
  }
}

watch(() => [user.value?.id, props.id], () => { loadState() }, { immediate: true })
</script>

<template>
  <span class="fav-btn" @click.stop>
    <button
      :class="[buttonClass, { 'is-saved': saved }]"
      type="button"
      :disabled="loading"
      @click="toggle"
    >{{ loading ? '…' : (saved ? savedLabel : label) }}</button>
    <span v-if="tip" class="muted">{{ tip }}</span>
  </span>
</template>

<style scoped>
.fav-btn { display: inline-flex; align-items: center; gap: 8px; }
.fav-btn .muted { font-size: 12px; }
.fav-btn .is-saved { background: #eef4ff; }
</style>
