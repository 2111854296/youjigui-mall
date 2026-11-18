<script setup lang="ts">
definePageMeta({ layout: 'channel' })
useHead({ title: '企业会员权益' })

const config = useRuntimeConfig()
const { user, fetchMe } = useAuth()
const { api } = useApi()

const { data } = await useAsyncData('vip-grades', () =>
  $fetch(`${config.public.apiBase}/vip/grades`),
)
const grades = computed(() => ((data.value as any)?.data || []) as any[])
const entitlement = ref<any>(null)
const openPayOrders = ref<any[]>([])

async function loadEntitlement() {
  if (!user.value) {
    entitlement.value = null
    openPayOrders.value = []
    return
  }
  try {
    const res = await api('/vip/me')
    entitlement.value = (res as any)?.data || res
  } catch {
    entitlement.value = (user.value as any)?.vip_entitlement || null
  }
  try {
    const res = await api<{ data: any[] }>('/vip/pay/orders')
    const list = res.data || []
    openPayOrders.value = list.filter((o) => ['pending_pay', 'pending_review'].includes(String(o.status)))
  } catch {
    openPayOrders.value = []
  }
}

const paidGrades = computed(() => grades.value.filter((g) => Number(g.price_year) > 0))

const TIER_RANK: Record<string, number> = { free: 0, silver: 1, gold: 2, diamond: 3 }
function gradeRank(g: any): number {
  const code = String(g?.code || g?.grade_code || g?.member_grade || '')
  if (code && code in TIER_RANK) return TIER_RANK[code]
  const name = String(g?.name || g?.grade_name || '')
  if (name.includes('星钻')) return 3
  if (name.includes('黄金')) return 2
  if (name.includes('白银')) return 1
  const level = Number(g?.level)
  if (Number.isFinite(level) && level > 0) return level
  return 0
}
const currentRank = computed(() => {
  const e = entitlement.value || (user.value as any)?.vip_entitlement || null
  if (!e) return 0
  const rank = gradeRank(e)
  if (rank > 0) return rank
  return e.is_paid ? 1 : 0
})
type TierCta = { kind: 'open' | 'renew' | 'upgrade' | 'blocked' | 'free'; label: string; disabled: boolean }
function tierCta(g: any): TierCta {
  const price = Number(g?.price_year) || 0
  const rank = gradeRank(g)
  const cur = currentRank.value
  if (price <= 0) {
    if (cur > 0) return { kind: 'blocked', label: '不可购买', disabled: true }
    return { kind: 'free', label: '当前可免费使用', disabled: true }
  }
  if (cur <= 0) return { kind: 'open', label: '立即开通', disabled: false }
  if (rank === cur) return { kind: 'renew', label: '立即续费', disabled: false }
  if (rank > cur) return { kind: 'upgrade', label: '会员升级', disabled: false }
  return { kind: 'blocked', label: '不可购买', disabled: true }
}
const purchasableGrades = computed(() =>
  paidGrades.value.filter((g) => !tierCta(g).disabled),
)

const ROWS: { section?: string; label: string; key: string; format?: string }[] = [
  { section: '一、基础数据权益', label: '实时报价 / 今日行情', key: 'quote_card', format: 'bool' },
  { section: '', label: '行业资讯浏览', key: 'news_browse', format: 'bool' },
  { section: '', label: '行情深度数据', key: 'quote_history_days', format: 'history' },
  { section: '二、撮合核心权益', label: '查看采购需求列表', key: 'buy_list', format: 'bool' },
  { section: '', label: '查看采购联系方式', key: 'contact_quota_month', format: 'contact' },
  { section: '', label: '企业授权席位共享解锁', key: 'share_unlock', format: 'bool' },
  { section: '三、发布与曝光', label: '发布信息（不限条数）', key: 'publish_unlimited', format: 'bool' },
  { section: '', label: '信息曝光权重', key: 'exposure_weight', format: 'exposure' },
  { section: '', label: '信息置顶 / 加急', key: 'top_price', format: 'top' },
  { section: '四、招聘专区权益', label: '同时在线职位数', key: 'jobs_online_limit', format: 'online' },
  { section: '', label: '完整简历查看', key: 'resume_full', format: 'resume_full' },
  { section: '', label: '简历联系方式/月', key: 'resume_contact_quota_month', format: 'resume_contact' },
  { section: '', label: '招聘置顶', key: 'job_sticky', format: 'job_sticky' },
  { section: '五、付费增值', label: '行业报告', key: 'industry_report', format: 'bool' },
  { section: '', label: '优质供应商推荐', key: 'supplier_recommend', format: 'bool' },
  { section: '六、团队与席位', label: '授权席位数（HR）', key: 'seat_count', format: 'seat' },
  { section: '七、专属会员特权', label: '一对一专属客服', key: 'exclusive_cs', format: 'bool' },
  { section: '', label: '官方 VIP 标识', key: 'badge', format: 'badge' },
]

function cellValue(g: any, row: (typeof ROWS)[0]): string {
  const lim = g
  const matrix = lim.matrix || {}
  switch (row.format) {
    case 'bool': {
      const v = matrix[row.key]
      if (typeof v === 'boolean') return v ? '✓' : '✗'
      if (row.key === 'quote_card' || row.key === 'news_browse' || row.key === 'buy_list' || row.key === 'publish_unlimited' || row.key === 'share_unlock') return '✓'
      return '✗'
    }
    case 'history': {
      const d = Number(lim.quote_history_days || 0)
      if (d <= 7) return '近7天'
      if (d <= 366) return '近1年'
      if (d <= 800) return '近2年'
      return '近5年'
    }
    case 'contact': {
      const n = Number(lim.contact_quota_month || 0)
      if (n <= 0) return '认证赠送5次/月内'
      return `${n} 次/月`
    }
    case 'exposure': {
      const w = Number(lim.exposure_weight || 0)
      if (w >= 30) return '优先加权'
      if (w >= 20) return '进阶加权'
      if (w >= 10) return '标准加权'
      return '基础'
    }
    case 'top': {
      if (Number(lim.top_gift_per_month || 0) > 0) return `每月赠送${lim.top_gift_per_month}条`
      const p = Number(lim.top_price || 0)
      return p > 0 ? `${p}元/条` : '—'
    }
    case 'online': {
      const n = Number(lim.jobs_online_limit ?? 5)
      return n <= 0 ? '不限' : `${n} 个`
    }
    case 'resume_full': {
      const code = String(lim.code || '')
      return code && code !== 'free' ? '✓ 完整' : '仅脱敏摘要'
    }
    case 'resume_contact': {
      const n = Number(lim.resume_contact_quota_month || 0)
      if (String(lim.code) === 'free') return '1次/月 + 认证赠送5次'
      return n > 0 ? `${n} 次/月` : '—'
    }
    case 'job_sticky': {
      const gift = Number(lim.job_sticky_per_month || 0)
      if (gift > 0) return `每月赠送${gift}条`
      const p = Number(lim.top_price || 0)
      return p > 0 ? `${p}元/条` : '—'
    }
    case 'seat':
      return `${Number(lim.seat_count || 1)} 人`
    case 'badge':
      return lim.badge ? `${lim.badge}标识` : '✗'
    default:
      return String(lim[row.key] ?? '—')
  }
}

function highlightPerks(g: any): string[] {
  const n = Number(g.contact_quota_month || 0)
  const seats = Number(g.seat_count || 1)
  const days = Number(g.quote_history_days || 7)
  const items: string[] = []
  if (Number(g.price_year) <= 0) {
    items.push('基础行情与资讯')
    items.push('认证后可获赠解锁次数')
    return items
  }
  items.push(n > 0 ? `联系方式 ${n} 次/月` : '联系方式额度')
  items.push(days > 7 ? `行情回溯近${days <= 366 ? '1年' : days <= 800 ? '2年' : '5年'}` : '行情近7天')
  if (seats > 1) items.push(`企业席位 ${seats} 人`)
  if (g.badge) items.push(`${g.badge} 官方标识`)
  return items.slice(0, 4)
}

const showApply = ref(false)
const msg = ref('')
const err = ref('')
const loading = ref(false)
const form = reactive({
  vip_grade_id: null as number | null,
  company_name: user.value?.company_name || '',
  contact_name: user.value?.name || user.value?.username || '',
  phone: '',
  mobile: user.value?.mobile || '',
  email: user.value?.email || '',
  qq: user.value?.qq || '',
  remark: '',
})

const selectedGrade = computed(() =>
  grades.value.find((g) => g.id === form.vip_grade_id) || null,
)

function openApply(id: number | null) {
  if (id == null) return
  if (!user.value) {
    return navigateTo({ path: '/login', query: { redirect: '/member/grade' } })
  }
  const g = grades.value.find((row) => row.id === id)
  const cta = g ? tierCta(g) : null
  if (!cta || cta.disabled) {
    err.value = '当前档位不可购买，请选择续费或更高档位升级'
    return
  }
  form.vip_grade_id = id
  form.company_name = user.value?.company_name || form.company_name
  form.contact_name = user.value?.name || user.value?.username || form.contact_name
  form.mobile = user.value?.mobile || form.mobile
  form.email = user.value?.email || form.email
  form.qq = user.value?.qq || form.qq
  msg.value = ''
  err.value = ''
  showApply.value = true
  if (import.meta.client) {
    document.body.style.overflow = 'hidden'
  }
}

function closeApply() {
  showApply.value = false
  if (import.meta.client) {
    document.body.style.overflow = ''
  }
}

function onKeydown(e: KeyboardEvent) {
  if (e.key === 'Escape' && showApply.value) closeApply()
}

onMounted(() => {
  if (import.meta.client) window.addEventListener('keydown', onKeydown)
  loadEntitlement()
  const hash = String(useRoute().hash || '').replace(/^#/, '')
  if (hash) {
    nextTick(() => {
      setTimeout(() => document.getElementById(hash)?.scrollIntoView({ behavior: 'smooth', block: 'start' }), 80)
    })
  }
})
onBeforeUnmount(() => {
  if (import.meta.client) {
    window.removeEventListener('keydown', onKeydown)
    document.body.style.overflow = ''
  }
})
watch(user, () => { loadEntitlement() })

async function submit() {
  if (!user.value) {
    return navigateTo({ path: '/login', query: { redirect: '/member/grade' } })
  }
  err.value = ''
  msg.value = ''
  loading.value = true
  try {
    const res = await api<{ data: { id: number } }>('/vip/pay/orders', {
      method: 'POST',
      body: { ...form },
    })
    const oid = (res as any)?.data?.id
    closeApply()
    if (oid) {
      return navigateTo(`/member/vip-pay/${oid}`)
    }
    await appOk('订单已创建。', '提交成功')
  } catch (e: any) {
    await appFail(apiErrorText(e, '下单失败'), '提交失败')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="ch-portal grade-page">
    <div class="crumb-row">
      <div class="container">
        <div class="crumb-tabs">
          <NuxtLink to="/">首页</NuxtLink>
          <NuxtLink to="/member">商务中心</NuxtLink>
          <span class="on">企业会员权益</span>
        </div>
      </div>
    </div>

    <div class="tip-bar">
      <div class="container">
        <b>权益说明：</b>
        额度为企业共享；同内容解锁一次后会员期内不再扣次。正式价格以后台配置为准。
      </div>
    </div>

    <div class="section">
      <div class="container">
        <div v-if="entitlement" class="dt-card status-card">
          <div class="dt-h">我的权益</div>
          <p class="meta-line">
            当前档位 <b>{{ entitlement.grade_name || '未开通' }}</b>
            <template v-if="entitlement.badge"> · {{ entitlement.badge }}</template>
            <template v-if="entitlement.vip_expire_at"> · 到期 {{ String(entitlement.vip_expire_at).slice(0, 10) }}</template>
          </p>
          <p class="meta-line">
            本月联系方式额度：已用 {{ entitlement.contact_used_month ?? 0 }} /
            配额 {{ entitlement.contact_quota_month ?? 0 }}
            · 剩余 {{ entitlement.contact_remain_total ?? 0 }}
            <template v-if="entitlement.contact_bonus_left">
              （含认证赠送 {{ entitlement.contact_bonus_left }}）
            </template>
          </p>
          <p class="meta-line">行情可回溯 {{ entitlement.quote_history_days || 7 }} 天</p>
          <p class="meta-line links">
            <NuxtLink to="/member/seats">席位管理</NuxtLink>
            · <NuxtLink to="/member/invite">拉新邀请</NuxtLink>
            · <NuxtLink to="/member/contact-packs">购买次数包</NuxtLink>
            · <NuxtLink to="/member/vip-pay">我的对公付款</NuxtLink>
            · <NuxtLink to="/member/grade#vip-pay-note">对公付款说明</NuxtLink>
          </p>
        </div>

        <div v-if="openPayOrders.length" class="dt-card open-pay-card">
          <div class="dt-h">进行中的对公付款</div>
          <ul class="open-pay-list">
            <li v-for="o in openPayOrders" :key="o.id">
              <div>
                <b>{{ o.grade_name }}</b>
                · ¥{{ o.amount_yuan }}
                · <span class="st">{{ o.status_label }}</span>
              </div>
              <NuxtLink :to="`/member/vip-pay/${o.id}`">
                {{ o.status === 'pending_review' ? '查看审核进度' : '继续付款' }}
              </NuxtLink>
            </li>
          </ul>
        </div>

        <div class="dt-card pricing-card">
          <div class="dt-h">选择会员档位</div>
          <p class="pricing-lead">未开通点「立即开通」；当前档「立即续费」；更高档「会员升级」；更低档不可购买。对公转账并上传凭证，财务审核通过后生效。</p>
          <div class="tier-grid">
            <article
              v-for="g in grades"
              :key="g.code || g.id"
              class="tier"
              :class="{
                on: showApply && form.vip_grade_id === g.id,
                free: Number(g.price_year) <= 0,
                featured: Number(g.price_year) > 0 && paidGrades[1] && g.id === paidGrades[1]?.id,
                current: currentRank > 0 && gradeRank(g) === currentRank,
                blocked: tierCta(g).disabled && Number(g.price_year) > 0,
              }"
            >
              <div class="tier-name">{{ g.name }}</div>
              <div class="tier-price">
                <template v-if="Number(g.price_year) > 0">
                  <span class="yen">¥</span>{{ g.price_year }}<span class="unit">/年</span>
                </template>
                <template v-else>免费</template>
              </div>
              <ul class="tier-perks">
                <li v-for="(p, i) in highlightPerks(g)" :key="i">{{ p }}</li>
              </ul>
              <button
                v-if="!tierCta(g).disabled"
                type="button"
                class="tier-cta"
                :class="'is-' + tierCta(g).kind"
                @click="openApply(g.id)"
              >
                {{ tierCta(g).label }}
              </button>
              <div
                v-else
                class="tier-cta is-static"
                :class="'is-' + tierCta(g).kind"
              >{{ tierCta(g).kind === 'free' ? '免费档' : (tierCta(g).kind === 'blocked' ? '低于当前档' : tierCta(g).label) }}</div>
            </article>
          </div>
        </div>

        <div class="dt-card cmp-card">
          <div class="dt-h">会员组权益对比</div>
          <p class="pricing-lead">详细对照如下；可购买档位可从表头进入付款流程。</p>
          <div class="table-wrap">
            <table class="cmp">
              <thead>
                <tr>
                  <th>项目</th>
                  <th
                    v-for="g in grades"
                    :key="g.code || g.id"
                    :class="{ 'is-current-col': currentRank > 0 && gradeRank(g) === currentRank }"
                  >
                    <div class="gn">
                      {{ g.name }}
                      <span
                        v-if="currentRank > 0 && gradeRank(g) === currentRank"
                        class="cur-tag"
                      >当前</span>
                    </div>
                    <div class="gp">
                      {{ Number(g.price_year) > 0 ? `¥${g.price_year}/年` : '免费' }}
                    </div>
                    <button
                      v-if="!tierCta(g).disabled"
                      type="button"
                      class="th-cta"
                      :class="'is-' + tierCta(g).kind"
                      @click="openApply(g.id)"
                    >
                      {{ tierCta(g).kind === 'renew' ? '立即续费' : (tierCta(g).kind === 'upgrade' ? '升级此档' : '立即开通') }}
                    </button>
                    <span
                      v-else
                      class="th-cta"
                      :class="['is-' + tierCta(g).kind, Number(g.price_year) <= 0 ? 'is-free' : 'is-blocked']"
                    >{{ Number(g.price_year) > 0 ? '低于当前档' : '免费档' }}</span>
                  </th>
                </tr>
              </thead>
              <tbody>
                <template v-for="(row, idx) in ROWS" :key="idx">
                  <tr v-if="row.section" class="sec">
                    <td :colspan="grades.length + 1">{{ row.section }}</td>
                  </tr>
                  <tr>
                    <td class="lab">{{ row.label }}</td>
                    <td v-for="g in grades" :key="(g.code || g.id) + '-' + idx">
                      {{ cellValue(g, row) }}
                    </td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>
        </div>

        <div id="vip-pay-note" class="dt-card pay-note-card">
          <div class="dt-h">对公付款说明</div>
          <ol class="pay-note-ol">
            <li>选择付费档位并填写开通资料后，系统生成带倒计时的对公付款订单。</li>
            <li>请按收银台展示的户名、账号、金额转账，备注中填写订单号以便财务核对。</li>
            <li>转账后须上传银行回单 / 网银成功截图；财务审核通过后自动开通会员权益。</li>
            <li>未完成的订单可在「会员升级」页顶部或侧栏「VIP对公付款」中找回继续操作。</li>
            <li>若凭证被驳回，请按驳回原因修正后重新上传；超时未传凭证的订单需重新下单。</li>
          </ol>
          <p class="meta-line links">
            <NuxtLink to="/member/vip-pay">查看我的对公付款订单</NuxtLink>
          </p>
        </div>
      </div>
    </div>

    <Teleport to="body">
      <div
        v-if="showApply"
        class="apply-overlay"
        role="dialog"
        aria-modal="true"
        aria-labelledby="apply-title"
        @click.self="closeApply"
      >
        <div class="apply-modal">
          <div class="apply-hd">
            <h3 id="apply-title">确认开通资料</h3>
            <button type="button" class="apply-x" aria-label="关闭" @click="closeApply">×</button>
          </div>
          <p v-if="selectedGrade" class="apply-pick">
            {{ tierCta(selectedGrade).kind === 'renew' ? '续费档位' : (tierCta(selectedGrade).kind === 'upgrade' ? '升级档位' : '开通档位') }}：
            <b>{{ selectedGrade.name }}</b>
            · ¥{{ selectedGrade.price_year }}/年
          </p>
          <p class="apply-note">提交后将进入对公转账收银台（含支付倒计时），转账并上传凭证后由财务审核开通。</p>
          <div v-if="msg" class="alert alert-success">{{ msg }}</div>
          <div v-if="err" class="alert alert-error">{{ err }}</div>
          <form class="apply" @submit.prevent="submit">
            <div class="form-grid">
              <div class="form-row">
                <label>开通档位</label>
                <select v-model="form.vip_grade_id" required>
                  <option v-for="g in purchasableGrades" :key="g.id" :value="g.id">
                    {{ g.name }}（¥{{ g.price_year }}/年）· {{ tierCta(g).label }}
                  </option>
                </select>
              </div>
              <div class="form-row"><label>公司全称</label><input v-model="form.company_name" placeholder="开票抬头建议与此一致"></div>
              <div class="form-row"><label>联系人 <em>*</em></label><input v-model="form.contact_name" required></div>
              <div class="form-row"><label>手机</label><input v-model="form.mobile"></div>
              <div class="form-row"><label>电话</label><input v-model="form.phone"></div>
              <div class="form-row"><label>Email</label><input v-model="form.email" type="email"></div>
              <div class="form-row full"><label>开票/备注</label><textarea v-model="form.remark" rows="3" placeholder="如需开票请填写抬头、税号、邮箱等" /></div>
            </div>
            <div class="apply-actions">
              <button type="button" class="btn btn--ghost" @click="closeApply">取消</button>
              <button class="btn btn--primary" type="submit" :disabled="loading">
                {{ loading ? '生成订单…' : '去付款' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
.status-card,
.pricing-card,
.cmp-card,
.open-pay-card,
.pay-note-card { margin-bottom: 14px; }
.open-pay-list { list-style: none; margin: 0; padding: 0; display: grid; gap: 10px; }
.open-pay-list li {
  display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;
  padding: 10px 12px; border-radius: 10px; background: #f6f9fe; border: 1px solid #e9eef6;
  font-size: 13.5px;
}
.open-pay-list a { color: var(--blue); font-weight: 700; text-decoration: none; }
.open-pay-list .st { color: #c2410c; font-weight: 700; }
.pay-note-ol { margin: 0 0 10px; padding-left: 1.2em; color: var(--ink-2); font-size: 13.5px; line-height: 1.65; }
.meta-line { margin: 0 0 8px; font-size: 13.5px; color: var(--ink-2); line-height: 1.5; }
.meta-line b { color: var(--ink); }
.meta-line.links a { color: var(--blue); font-weight: 600; text-decoration: none; }
.apply-note {
  margin: 0 0 12px;
  font-size: 12.5px;
  color: var(--ink-3);
  line-height: 1.5;
  padding: 8px 10px;
  background: #f6f9fe;
  border-radius: 8px;
}
.pricing-lead {
  margin: -4px 0 14px;
  font-size: 13px;
  color: var(--ink-3);
  line-height: 1.45;
}
.tier-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 12px;
}
.tier {
  border: 1.5px solid var(--line);
  border-radius: 12px;
  padding: 16px 14px 14px;
  background: #fff;
  display: flex;
  flex-direction: column;
  min-height: 100%;
  transition: border-color .15s, box-shadow .15s;
}
.tier.on {
  border-color: var(--blue);
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--blue) 18%, transparent);
}
.tier.featured { background: linear-gradient(180deg, #F5F8FF 0%, #fff 48%); }
.tier-name { font-weight: 800; color: var(--ink); font-size: 15px; line-height: 1.3; }
.tier-price {
  margin: 10px 0 12px;
  font-size: 26px;
  font-weight: 800;
  color: var(--blue);
  letter-spacing: -0.02em;
  line-height: 1;
}
.tier-price .yen { font-size: 14px; margin-right: 1px; }
.tier-price .unit { font-size: 12px; font-weight: 600; color: var(--ink-3); margin-left: 2px; }
.tier.free .tier-price { font-size: 20px; color: var(--ink-2); }
.tier-perks {
  list-style: none;
  margin: 0 0 14px;
  padding: 0;
  flex: 1;
}
.tier-perks li {
  position: relative;
  padding: 0 0 6px 12px;
  font-size: 12.5px;
  color: var(--ink-2);
  line-height: 1.4;
}
.tier-perks li::before {
  content: '';
  position: absolute;
  left: 0;
  top: 7px;
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: var(--blue);
  opacity: .55;
}
.tier.current { border-color: #2468f2; box-shadow: 0 0 0 1px rgba(36, 104, 242, .18); }
.tier.blocked { opacity: .9; }
.tier-cta {
  width: 100%;
  margin-top: auto;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 38px;
  padding: 0 14px;
  border: none;
  border-radius: 10px;
  font: inherit;
  font-size: 13px;
  font-weight: 700;
  letter-spacing: .02em;
  cursor: pointer;
  transition: transform .12s ease, box-shadow .12s ease, filter .12s ease;
}
.tier-cta.is-open,
.tier-cta.is-renew,
.tier-cta.is-upgrade {
  color: #fff;
  background: linear-gradient(180deg, #3b82f6 0%, #2468f2 100%);
  box-shadow: 0 6px 14px rgba(36, 104, 242, .22);
}
.tier-cta.is-upgrade {
  background: linear-gradient(180deg, #3b82f6 0%, #2468f2 100%);
  box-shadow: 0 6px 14px rgba(36, 104, 242, .22);
}
.tier-cta.is-renew {
  background: linear-gradient(180deg, #2563eb 0%, #1d4ed8 100%);
}
.tier-cta.is-open:hover,
.tier-cta.is-renew:hover,
.tier-cta.is-upgrade:hover {
  filter: brightness(1.04);
  transform: translateY(-1px);
}
.tier-cta.is-static {
  cursor: default;
  box-shadow: none;
  transform: none;
  font-weight: 600;
}
.tier-cta.is-free,
.tier-cta.is-blocked {
  background: #f3f5f8;
  color: #8b97a8;
  border: 1px solid #e7ebf1;
}
.table-wrap { overflow-x: auto; }
.cmp { width: 100%; border-collapse: collapse; font-size: 13px; }
.cmp th, .cmp td {
  border-bottom: 1px solid var(--line);
  padding: 12px 10px;
  text-align: center;
  vertical-align: middle;
}
.cmp th:first-child, .cmp td.lab { text-align: left; color: var(--ink-2); font-weight: 600; white-space: nowrap; }
.cmp th { background: #F8FAFD; }
.cmp th.is-current-col {
  background: linear-gradient(180deg, #eef4ff 0%, #f8fafd 100%);
  box-shadow: inset 0 -2px 0 #2468f2;
}
.cmp .gn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  font-weight: 800;
  color: var(--ink);
}
.cmp .cur-tag {
  display: inline-flex;
  align-items: center;
  height: 18px;
  padding: 0 6px;
  border-radius: 999px;
  background: #2468f2;
  color: #fff;
  font-size: 11px;
  font-weight: 700;
  line-height: 1;
}
.cmp .gp { font-size: 12px; color: var(--blue); margin-top: 4px; font-weight: 600; }
.th-cta {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  margin-top: 10px;
  min-width: 92px;
  min-height: 30px;
  padding: 0 14px;
  border: none;
  border-radius: 999px;
  font: inherit;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: .02em;
  line-height: 1;
  cursor: pointer;
  transition: transform .12s ease, filter .12s ease, box-shadow .12s ease;
}
.th-cta.is-open,
.th-cta.is-renew,
.th-cta.is-upgrade {
  color: #fff;
  background: linear-gradient(180deg, #3b82f6 0%, #2468f2 100%);
  box-shadow: 0 4px 10px rgba(36, 104, 242, .2);
}
.th-cta.is-upgrade {
  background: linear-gradient(180deg, #3b82f6 0%, #2468f2 100%);
  box-shadow: 0 4px 10px rgba(36, 104, 242, .2);
}
.th-cta.is-renew {
  background: linear-gradient(180deg, #2563eb 0%, #1d4ed8 100%);
}
.th-cta.is-open:hover,
.th-cta.is-renew:hover,
.th-cta.is-upgrade:hover {
  filter: brightness(1.05);
  transform: translateY(-1px);
}
.th-cta.is-free,
.th-cta.is-blocked {
  min-width: 84px;
  background: #eef1f5;
  color: #8a96a8;
  box-shadow: none;
  cursor: default;
  font-weight: 600;
}
.cmp tr.sec td {
  background: #EEF4FF;
  color: var(--blue-ink);
  font-weight: 800;
  text-align: left;
  padding: 8px 10px;
}

.apply-overlay {
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
.apply-modal {
  width: min(640px, 100%);
  max-height: min(90vh, 820px);
  overflow: auto;
  background: #fff;
  border-radius: 14px;
  box-shadow: 0 24px 64px rgba(15, 35, 70, .22);
  padding: 20px 22px 22px;
}
.apply-hd {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 8px;
}
.apply-hd h3 {
  margin: 0;
  font-size: 17px;
  font-weight: 800;
  color: var(--ink);
  padding-left: 10px;
  border-left: 3px solid var(--blue);
  line-height: 1.2;
}
.apply-x {
  border: 0;
  background: transparent;
  font-size: 26px;
  line-height: 1;
  color: var(--ink-3);
  cursor: pointer;
  padding: 0 4px;
}
.apply-pick {
  margin: 0 0 12px;
  font-size: 13.5px;
  color: var(--ink-2);
}
.apply-pick b { color: var(--ink); }
.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0 14px;
}
.form-grid .form-row { margin-bottom: 12px; }
.form-grid .form-row.full { grid-column: 1 / -1; }
.apply label { display: block; font-size: 13px; color: var(--ink-3); margin-bottom: 4px; }
.apply label em { color: #c2410c; font-style: normal; }
.apply input, .apply select, .apply textarea {
  width: 100%; box-sizing: border-box; padding: 8px 10px;
  border: 1px solid var(--line); border-radius: 8px; font: inherit;
}
.apply-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 6px;
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

@media (max-width: 980px) {
  .tier-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 640px) {
  .tier-grid { grid-template-columns: 1fr; }
  .form-grid { grid-template-columns: 1fr; }
  .apply-modal { padding: 16px; }
}
</style>
