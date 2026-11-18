<script setup lang="ts">
definePageMeta({ layout: 'channel' })
const route = useRoute()
const config = useRuntimeConfig()
const { api, token } = useApi()
const { user } = useAuth()
const id = route.params.id as string
const msg = ref('')
const err = ref('')
const loading = ref(false)
const showQuote = ref(false)
const unlockLoading = ref(false)
const unlockErr = ref('')
const unlockedContact = ref<any>(null)
const quotaKey = `buy-unlock-quota-${id}`

function restoreQuotaMsg() {
  if (!import.meta.client) return
  const cached = sessionStorage.getItem(quotaKey)
  if (cached) unlockErr.value = cached
}

function rememberQuota(message: string) {
  unlockErr.value = message
  if (import.meta.client && /次数已用完|购买次数包|升级会员/.test(message)) {
    sessionStorage.setItem(quotaKey, message)
  }
}

function clearQuota() {
  unlockErr.value = ''
  if (import.meta.client) sessionStorage.removeItem(quotaKey)
}
const form = reactive({
  price: '',
  unit: '吨',
  quantity: '',
  content: '',
  contact_name: user.value?.name || '',
  contact_phone: user.value?.mobile || '',
})

const { data, error, refresh } = await useAsyncData(`buy-${id}`, () =>
  $fetch(`${config.public.apiBase}/buy/${id}`, {
    headers: token.value ? { Authorization: `Bearer ${token.value}` } : {},
  }),
)
const item = computed(() => (data.value as any)?.data)
useHead(() => ({ title: item.value?.title || '采购详情' }))

const displayMobile = computed(() => {
  if (unlockedContact.value?.mobile) return unlockedContact.value.mobile
  if (item.value?.contact_unlocked) return item.value?.user?.mobile || '-'
  return item.value?.user?.mobile || '未解锁'
})

const unlockQuotaExhausted = computed(() =>
  /次数已用完|升级会员|购买次数包/.test(String(unlockErr.value || '')),
)

async function unlockContact() {
  if (!user.value) {
    return navigateTo({ path: '/login', query: { redirect: `/buy/${id}` } })
  }
  unlockLoading.value = true
  try {
    const res = await api('/vip/unlock-contact', {
      method: 'POST',
      body: { target_type: 'purchase', target_id: Number(id) },
    })
    const body = res as any
    const failMsg = String(body?.message || body?.data?.message || '')
    if (body?.code === 'quota_exceeded' || /次数已用完/.test(failMsg)) {
      rememberQuota(failMsg || '本月联系方式查看次数已用完，请升级会员、购买次数包或下月再试')
      return
    }
    unlockedContact.value = body?.data?.contact || body?.contact
    clearQuota()
    await refresh()
  } catch (e: any) {
    const message = e?.data?.message || e?.data?.data?.message || '解锁失败，请升级会员或检查额度'
    rememberQuota(message)
  } finally {
    unlockLoading.value = false
  }
}

const fallback = '/img/logo-yjg.png'
const cover = computed(() => {
  const t = String(item.value?.thumb || '')
  if (t && !/\.svg($|\?)/i.test(t) && !/placehold/i.test(t) && !/\/img\/products\/p\d+/i.test(t)) return t
  return fallback
})

const contentIsHtml = computed(() => /<[a-z][\s\S]*>/i.test(String(item.value?.content || '')))

const qtyText = computed(() => {
  const q = String(item.value?.quantity || '').trim()
  const u = String(item.value?.unit || '').trim()
  if (!q) return '-'
  if (!u || q.includes(u)) return q
  return `${q} ${u}`
})

function onImgErr(e: Event) {
  const el = e.target as HTMLImageElement
  if (!el || el.dataset.fb) return
  el.dataset.fb = '1'
  el.src = fallback
}

function openQuote() {
  msg.value = ''
  err.value = ''
  form.contact_name = user.value?.name || form.contact_name
  form.contact_phone = user.value?.mobile || form.contact_phone
  showQuote.value = true
  if (import.meta.client) document.body.style.overflow = 'hidden'
}

function closeQuote() {
  showQuote.value = false
  if (import.meta.client) document.body.style.overflow = ''
}

function onKeydown(e: KeyboardEvent) {
  if (e.key === 'Escape' && showQuote.value) closeQuote()
}
onMounted(() => {
  restoreQuotaMsg()
  if (import.meta.client) {
    window.addEventListener('keydown', onKeydown)
    window.addEventListener('pageshow', restoreQuotaMsg)
  }
})
onBeforeUnmount(() => {
  if (import.meta.client) {
    window.removeEventListener('keydown', onKeydown)
    window.removeEventListener('pageshow', restoreQuotaMsg)
    document.body.style.overflow = ''
  }
})

async function submitQuote() {
  err.value = ''
  msg.value = ''
  loading.value = true
  try {
    await api(`/buy/${id}/quote`, {
      method: 'POST',
      body: { ...form, price: form.price || null },
      auth: !!user.value,
    })
    form.content = ''
    await appOk('报价已提交，采购方将收到站内消息。', '提交成功')
    closeQuote()
  } catch (e: any) {
    await appFail(apiErrorText(e, '提交失败'), '提交失败')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="ch-portal buy-detail">
    <div class="crumb-row">
      <div class="container">
        <div class="crumb-tabs">
          <NuxtLink to="/">首页</NuxtLink>
          <NuxtLink to="/buy">供需对接</NuxtLink>
          <span class="on">求购详情</span>
        </div>
      </div>
    </div>

    <div class="tip-bar buy">
      <div class="container">
        <b>报价提示：</b>
        请如实填写货源与交期，通过平台报价更利于达成合作。
      </div>
    </div>

    <div class="section">
      <div class="container">
        <div v-if="error" class="blk empty">信息不存在或已下架</div>
        <div v-else-if="item" class="dt-layout">
          <div class="dt-main">
            <div class="dt-card">
              <div class="dt-media">
                <div class="dt-gallery">
                  <div class="main">
                    <ImageZoom :src="cover" :alt="item.title" @error="onImgErr" />
                  </div>
                </div>
                <div class="dt-sum">
                  <h1>
                    {{ item.title }}
                    <span v-if="item.is_urgent" class="dt-tag urg">急</span>
                  </h1>
                  <p class="meta">
                    {{ item.category?.name || '未分类' }}
                    · 浏览 {{ item.views ?? 0 }}
                    · 报价 {{ item.quotes_count ?? 0 }}
                    · 刷新 {{ item.refreshed_at || '-' }}
                  </p>
                  <div class="price" :class="{ ask: !item.budget }">
                    {{ item.budget ? `预算 ¥${item.budget}` : '预算面议' }}
                  </div>
                  <table class="dt-kv">
                    <tbody>
                      <tr><th>数量</th><td>{{ qtyText }}</td></tr>
                      <tr><th>地区</th><td>{{ item.area || '地区不限' }}</td></tr>
                      <tr><th>交期</th><td>{{ item.days ? `${item.days}天内` : '-' }}</td></tr>
                      <tr><th>采购商</th><td>{{ item.user?.company_name || item.user?.name || '-' }}</td></tr>
                    </tbody>
                  </table>
                  <div class="dt-acts">
                    <button type="button" class="btn btn--primary" @click="openQuote">我要报价</button>
                    <FavoriteButton type="purchase" :id="item.id" />
                  </div>
                </div>
              </div>
            </div>

            <div v-if="item.summary" class="dt-card">
              <div class="dt-h">摘要</div>
              <div class="dt-body">{{ item.summary }}</div>
            </div>

            <div class="dt-card">
              <div class="dt-h">详细说明</div>
              <div v-if="contentIsHtml" class="dt-body" v-html="item.content" />
              <div v-else class="dt-body plain">{{ item.content || '暂无详细说明' }}</div>
            </div>
          </div>

          <aside class="dt-side">
            <div class="seller">
              <div class="sh">采购商信息</div>
              <div class="sb">
                <div class="sn">{{ item.user?.company_name || item.user?.name || '采购会员' }}</div>
                <p><em>联系人</em>{{ item.user?.name || '-' }}</p>
                <p><em>地区</em>{{ item.area || '地区不限' }}</p>
                <p><em>手机</em>{{ displayMobile }}</p>
                <div v-if="!item.contact_unlocked && !unlockedContact" class="unlock-box">
                  <p class="hint">联系方式按会员档位计次解锁（同内容不重复扣次）</p>
                  <div v-if="unlockErr" class="alert alert-error unlock-err">
                    <template v-if="unlockQuotaExhausted">
                      本月联系方式查看次数已用完，<a href="/member/grade">请升级会员</a>、<a href="/member/contact-packs">购买次数包</a>或下月再试
                    </template>
                    <template v-else>{{ unlockErr }}</template>
                  </div>
                  <button
                    type="button"
                    class="btn btn--primary"
                    :disabled="unlockLoading"
                    @click="unlockContact"
                  >{{ unlockLoading ? '解锁中…' : '解锁联系方式' }}</button>
                  <NuxtLink class="more-link" to="/member/grade">查看会员权益 ›</NuxtLink>
                </div>
              </div>
            </div>
            <AdSlot position="buy_right" title="采购推广" />
            <div class="tips">
              <b>温馨提示</b>
              <ul>
                <li>报价请如实填写交期与货源</li>
                <li>优先通过平台沟通更安全</li>
                <li>警惕脱离平台的私下转账</li>
              </ul>
            </div>
          </aside>
        </div>
      </div>
    </div>

    <div v-if="item" class="sticky-cta">
      <div class="container sticky-inner">
        <div class="sticky-info">
          <b>{{ item.title }}</b>
          <span>{{ item.budget ? `预算 ¥${item.budget}` : '预算面议' }}</span>
        </div>
        <button type="button" class="btn btn--primary" @click="openQuote">我要报价</button>
      </div>
    </div>

    <Teleport to="body">
      <div
        v-if="showQuote"
        class="quote-overlay"
        role="dialog"
        aria-modal="true"
        @click.self="closeQuote"
      >
        <div class="quote-modal">
          <div class="quote-hd">
            <h3>我要报价</h3>
            <button type="button" class="quote-x" aria-label="关闭" @click="closeQuote">×</button>
          </div>
          <div v-if="msg" class="alert alert-success">{{ msg }}</div>
          <div v-if="err" class="alert alert-error">{{ err }}</div>
          <form class="quote-form" @submit.prevent="submitQuote">
            <div class="form-row"><label>报价</label><input v-model="form.price" type="number" step="0.01" placeholder="选填，可面议"></div>
            <div class="form-row"><label>单位</label><input v-model="form.unit"></div>
            <div class="form-row"><label>可供数量</label><input v-model="form.quantity" placeholder="如 10吨"></div>
            <div class="form-row"><label>联系人 <em>*</em></label><input v-model="form.contact_name" required></div>
            <div class="form-row"><label>电话 <em>*</em></label><input v-model="form.contact_phone" required></div>
            <div class="form-row"><label>说明 <em>*</em></label><textarea v-model="form.content" required rows="4" placeholder="请说明货源、交期等" /></div>
            <div class="quote-actions">
              <button type="button" class="btn btn--ghost" @click="closeQuote">取消</button>
              <button class="btn btn--primary" type="submit" :disabled="loading">
                {{ loading ? '提交中…' : '提交报价' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
.buy-detail { padding-bottom: 72px; }
.unlock-box { margin: 10px 0 4px; display: flex; flex-direction: column; gap: 8px; align-items: flex-start; }
.unlock-box .hint { margin: 0; font-size: 12.5px; color: var(--ink-3); line-height: 1.45; }
.unlock-err { width: 100%; box-sizing: border-box; line-height: 1.55; }
.unlock-err a {
  color: #1d4ed8;
  font-weight: 700;
  text-decoration: underline;
  text-underline-offset: 2px;
}
.unlock-err a:hover { color: #1e40af; }
.quote-form .form-row { margin-bottom: 12px; }
.quote-form label {
  display: block;
  font-size: 13px;
  color: var(--ink-3);
  margin-bottom: 4px;
}
.quote-form label em { color: #c2410c; font-style: normal; }
.quote-form input,
.quote-form textarea {
  width: 100%;
  border: 1px solid var(--line);
  border-radius: 8px;
  padding: 8px 10px;
  font: inherit;
  box-sizing: border-box;
}
.quote-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 4px;
}
.btn--ghost {
  background: #fff;
  color: var(--ink-2);
  border: 1px solid var(--line);
  padding: 10px 18px;
  border-radius: 10px;
  font: inherit;
  font-weight: 600;
  cursor: pointer;
}
.sticky-cta {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 40;
  background: rgba(255, 255, 255, .94);
  border-top: 1px solid var(--line);
  backdrop-filter: blur(8px);
  box-shadow: 0 -6px 20px rgba(15, 35, 70, .06);
}
.sticky-inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 10px 0;
}
.sticky-info { display: flex; flex-direction: column; gap: 2px; font-size: 13px; color: var(--ink-2); min-width: 0; }
.sticky-info b {
  color: var(--ink);
  font-size: 14px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  max-width: 60vw;
}
.quote-overlay {
  position: fixed;
  inset: 0;
  z-index: 1200;
  background: rgba(15, 28, 52, .48);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px 16px;
  box-sizing: border-box;
}
.quote-modal {
  width: min(520px, 100%);
  max-height: min(90vh, 760px);
  overflow: auto;
  background: #fff;
  border-radius: 14px;
  box-shadow: 0 24px 64px rgba(15, 35, 70, .22);
  padding: 20px 22px 22px;
}
.quote-hd {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
}
.quote-hd h3 {
  margin: 0;
  font-size: 17px;
  font-weight: 800;
  padding-left: 10px;
  border-left: 3px solid var(--blue);
}
.quote-x {
  border: 0;
  background: transparent;
  font-size: 26px;
  color: var(--ink-3);
  cursor: pointer;
  line-height: 1;
}
</style>
