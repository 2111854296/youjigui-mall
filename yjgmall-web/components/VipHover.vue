<script setup lang="ts">
const { user } = useAuth()
const { api } = useApi()
const open = ref(false)
const info = ref<any>(null)
const loading = ref(false)
const root = ref<HTMLElement | null>(null)
const pos = ref({ top: 0, left: 0 })
let loadedFor = 0
let hideTimer: ReturnType<typeof setTimeout> | null = null

import { memberTier, memberTierLabel } from '~/utils/memberTier'

/** 和角标同一套：普通、白银、黄金、星钻。没开通、也没有有效期就是普通会员。 */
const displayGrade = computed(() => (user.value ? memberTierLabel(memberTier(user.value)) : ''))

const expireText = computed(() => {
  const raw = String(info.value?.vip_expire_at || '').trim()
  if (!raw) return info.value?.is_paid ? '长期有效' : '未开通'
  const day = raw.slice(0, 10)
  const end = new Date(raw.replace(/-/g, '/')).getTime()
  if (Number.isFinite(end) && end < Date.now()) return `${day}（已到期）`
  return day
})

function place() {
  const el = root.value
  if (!el) return
  const r = el.getBoundingClientRect()
  const width = 280
  let left = r.left
  if (left + width > window.innerWidth - 8) left = window.innerWidth - width - 8
  if (left < 8) left = 8
  pos.value = { top: r.bottom + 8, left }
}

async function show() {
  if (hideTimer) {
    clearTimeout(hideTimer)
    hideTimer = null
  }
  open.value = true
  place()
  const id = Number(user.value?.id || 0)
  if (!id || loadedFor === id || loading.value) return
  loading.value = true
  try {
    const res = await api<any>('/vip/me')
    info.value = res?.data || res
    loadedFor = id
  } catch {
    info.value = null
  } finally {
    loading.value = false
  }
}

function hideSoon() {
  if (hideTimer) clearTimeout(hideTimer)
  hideTimer = setTimeout(() => { open.value = false }, 200)
}

function onScroll() {
  if (open.value) place()
}

onMounted(() => {
  window.addEventListener('scroll', onScroll, true)
  window.addEventListener('resize', onScroll)
})
onBeforeUnmount(() => {
  if (hideTimer) clearTimeout(hideTimer)
  window.removeEventListener('scroll', onScroll, true)
  window.removeEventListener('resize', onScroll)
})
</script>

<template>
  <span ref="root" class="vip-hover" @mouseenter="show" @mouseleave="hideSoon">
    <slot />
  </span>
  <Teleport to="body">
    <div
      v-show="open"
      class="vip-pop"
      :style="{ top: pos.top + 'px', left: pos.left + 'px' }"
      @mouseenter="show"
      @mouseleave="hideSoon"
    >
      <template v-if="!user">
        <p>登录后可查看会员有效期和功能剩余额度。</p>
      </template>
      <template v-else-if="loading && !info">
        <p>正在读取…</p>
      </template>
      <template v-else-if="info">
        <p>当前档位 <b>{{ displayGrade || info.grade_name || '普通会员' }}</b></p>
        <p>有效期 {{ expireText }}</p>
        <p>
          联系方式剩余 {{ info.contact_remain_total ?? 0 }} / {{ info.contact_quota_month ?? 0 }} 次
          <template v-if="info.contact_bonus_left">（含赠送 {{ info.contact_bonus_left }}）</template>
        </p>
        <p>行情可回溯 {{ info.quote_history_days || 7 }} 天</p>
        <p>授权席位 {{ info.seats_used ?? 0 }} / {{ info.seat_count ?? 1 }}</p>
      </template>
      <template v-else>
        <p>暂时无法读取会员额度。</p>
      </template>
      <NuxtLink class="vip-up" to="/member/grade">升级会员</NuxtLink>
    </div>
  </Teleport>
</template>

<style>
.vip-hover { display: inline-flex; align-items: center; vertical-align: middle; }
.vip-pop {
  position: fixed;
  z-index: 4000;
  width: 280px;
  padding: 12px 14px;
  background: #fff;
  border: 1px solid var(--line, #e9eef6);
  border-radius: 10px;
  box-shadow: 0 8px 24px rgba(15, 35, 70, 0.16);
  color: var(--ink, #13294b);
  font-size: 12.5px;
  line-height: 1.55;
  font-weight: 500;
}
.vip-pop p { margin: 0 0 4px; white-space: normal; }
.vip-pop b { font-weight: 700; }
.vip-pop .vip-up {
  display: inline-flex;
  margin-top: 8px;
  padding: 4px 12px;
  border-radius: 100px;
  background: var(--blue, #2468f2);
  color: #fff !important;
  font-size: 12.5px;
  font-weight: 700;
  text-decoration: none;
}
.vip-pop .vip-up:hover { background: var(--blue-deep, #1443b8); color: #fff !important; }
</style>
