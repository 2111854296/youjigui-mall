<script setup lang="ts">
import '~/assets/css/jobs-channel.css'
import {
  FIELD_CATS,
  HOT_CITIES,
  JOB_EDU_OPTS,
  JOB_EXP_OPTS,
  JOB_NATURE_OPTS,
  JOB_ROLE_CATS,
  JOB_SALARY_OPTS,
  JOB_SIZE_OPTS,
  PAGE_SIZE,
  timeAgo,
  type DemoJob,
  type DemoTalent,
} from '~/utils/jobsDemo'
import { mapApiJob, mapApiTalent } from '~/utils/jobsMap'
import { compactPageItems } from '~/utils/pager'

definePageMeta({ layout: 'channel' })
useHead({
  title: '招聘专区',
  meta: [{ name: 'description', content: '有机硅行业垂直招聘：找工作与企业招人双视角' }],
})

const config = useRuntimeConfig()
const { api, token } = useApi()
const { user, fetchMe } = useAuth()
const router = useRouter()

const view = ref<'job' | 'emp'>('job')
const jobs = ref<DemoJob[]>([])
const talents = ref<DemoTalent[]>([])
const sideJobs = ref<DemoJob[]>([])
const sideTalents = ref<DemoTalent[]>([])
const jobMeta = ref({ total: 0, last_page: 1 })
const talMeta = ref({ total: 0, last_page: 1 })
const jobFacets = ref<{ total: number; categories: Record<string, number> }>({ total: 0, categories: {} })
const loadingJobs = ref(false)
const loadingTalents = ref(false)
const vipGateOpen = ref(false)
const vipGateMsg = ref('当前会员权益不足，请升级企业会员后继续。')
const certGateOpen = ref(false)
const certGate = reactive({
  title: '需要企业认证',
  message: '「企业招人」仅对已认证企业开放。完成企业认证后，才能查看人才并主动联系。',
  action: '去认证企业',
  to: '/member/shop',
})
const entitlement = ref<any>(null)

const filter = reactive({
  city: '全部',
  role: '全部',
  salary: '全部',
  exp: '全部',
  edu: '全部',
  size: '全部',
  type: '全部',
  cat: '全部',
  kw: '',
  sort: 'time',
})
const talFilter = reactive({
  city: '全部',
  role: '全部',
  exp: '全部',
  edu: '全部',
  salary: '全部',
  st: '全部',
  cat: '全部',
  kw: '',
  sort: 'time',
})

const kwInput = ref('')
const talKwInput = ref('')
const cityLabel = ref('全国')
const talCityLabel = ref('全国')
const cityDropOpen = ref(false)
const talCityDropOpen = ref(false)
const cityMoreOpen = ref(false)
const curPage = ref(1)
const talPage = ref(1)
const toastMsg = ref('')
const toastOn = ref(false)
let toastTimer: ReturnType<typeof setTimeout> | null = null

const CITY_MAIN = ['全部', '东莞', '深圳', '广州', '上海', '苏州', '宁波', '宜昌', '淄博', '佛山']
const CITY_EXTRA = ['杭州', '惠州']

const companySide = computed(() => {
  const map = new Map<string, { n: string; j: number; g: number; slug?: string }>()
  for (const j of sideJobs.value) {
    const key = j.companySlug || j.co
    const cur = map.get(key) || { n: j.co, j: 0, g: 0, slug: j.companySlug }
    cur.j += 1
    if (j.gold) cur.g = 1
    if (!cur.slug && j.companySlug) cur.slug = j.companySlug
    if (j.co) cur.n = j.co
    map.set(key, cur)
  }
  return [...map.values()].sort((a, b) => b.j - a.j).slice(0, 8)
})

function companyShopJobsTo(c: { slug?: string; n: string }) {
  if (c.slug) return `/companies/${encodeURIComponent(c.slug)}?tab=jobs`
  return ''
}

function goCompanyShop(c: { slug?: string; n: string }, e?: Event) {
  e?.stopPropagation?.()
  const to = companyShopJobsTo(c)
  if (to) {
    navigateTo(to)
    return
  }
  toast('该企业暂未开通旺铺')
}

function toast(msg: string) {
  toastMsg.value = msg
  toastOn.value = true
  if (toastTimer) clearTimeout(toastTimer)
  toastTimer = setTimeout(() => {
    toastOn.value = false
  }, 1800)
}

function openVipGate(msg: string) {
  vipGateMsg.value = msg
  vipGateOpen.value = true
}

async function loadEntitlement() {
  if (!user.value) {
    entitlement.value = null
    return
  }
  try {
    const res = await api('/vip/me')
    entitlement.value = (res as any)?.data || res
  } catch {
    entitlement.value = (user.value as any)?.vip_entitlement || null
  }
}

function authHeaders() {
  return token.value ? { Authorization: `Bearer ${token.value}` } : {}
}

async function fetchJobs() {
  loadingJobs.value = true
  try {
    const q: Record<string, string | number> = {
      page: curPage.value,
      per_page: PAGE_SIZE,
      sort: filter.sort === 'salary' ? 'salary' : 'time',
    }
    if (filter.kw) q.q = filter.kw
    if (filter.city !== '全部') q.area = filter.city
    if (filter.role !== '全部') q.role = filter.role
    if (filter.edu !== '全部') q.education = filter.edu
    if (filter.exp !== '全部') q.experience = filter.exp
    if (filter.cat !== '全部') q.category = filter.cat
    if (filter.salary !== '全部') q.salary = filter.salary
    if (filter.size !== '全部') q.size = filter.size
    if (filter.type !== '全部') q.nature = filter.type
    const res = await $fetch<any>(`${config.public.apiBase}/jobs`, {
      query: q,
      headers: authHeaders(),
    })
    jobs.value = ((res?.data || []) as any[]).map(mapApiJob)
    jobMeta.value = {
      total: Number(res?.meta?.total ?? 0),
      last_page: Math.max(1, Number(res?.meta?.last_page ?? 1)),
    }
    if (res?.facets) {
      jobFacets.value = {
        total: Number(res.facets.total ?? jobMeta.value.total),
        categories: res.facets.categories || {},
      }
    }
  } catch {
    jobs.value = []
    jobMeta.value = { total: 0, last_page: 1 }
    toast('职位加载失败')
  } finally {
    loadingJobs.value = false
  }
}

async function fetchTalents() {
  loadingTalents.value = true
  try {
    const q: Record<string, string | number> = {
      page: talPage.value,
      per_page: PAGE_SIZE,
      sort: talFilter.sort === 'salary' ? 'salary' : 'time',
    }
    if (talFilter.kw) q.q = talFilter.kw
    if (talFilter.city !== '全部') q.area = talFilter.city
    if (talFilter.role !== '全部') q.role = talFilter.role
    if (talFilter.edu !== '全部') q.education = talFilter.edu
    if (talFilter.exp !== '全部') q.experience = talFilter.exp
    if (talFilter.cat !== '全部') q.category = talFilter.cat
    if (talFilter.salary !== '全部') q.salary = talFilter.salary
    const res = await $fetch<any>(`${config.public.apiBase}/talents`, {
      query: q,
      headers: authHeaders(),
    })
    talents.value = ((res?.data || []) as any[]).map(mapApiTalent)
    talMeta.value = {
      total: Number(res?.meta?.total ?? 0),
      last_page: Math.max(1, Number(res?.meta?.last_page ?? 1)),
    }
  } catch {
    talents.value = []
    talMeta.value = { total: 0, last_page: 1 }
    toast('人才加载失败')
  } finally {
    loadingTalents.value = false
  }
}

async function fetchSidePanels() {
  try {
    const jRes = await $fetch<any>(`${config.public.apiBase}/jobs`, {
      query: { per_page: 30, page: 1 },
      headers: authHeaders(),
    })
    sideJobs.value = ((jRes?.data || []) as any[]).map(mapApiJob)
  } catch {
    /* sidebar best-effort */
  }
  if (!talentReady.value) return
  try {
    const tRes = await $fetch<any>(`${config.public.apiBase}/talents`, {
      query: { per_page: 30, page: 1 },
      headers: authHeaders(),
    })
    sideTalents.value = ((tRes?.data || []) as any[]).map(mapApiTalent)
  } catch {
    /* talent sidebar needs certification */
  }
}

function switchView(v: 'job' | 'emp') {
  view.value = v
  cityDropOpen.value = false
  talCityDropOpen.value = false
  if (v === 'emp' && !talents.value.length) fetchTalents()
  if (import.meta.client) window.scrollTo({ top: 0 })
}

const talentReady = computed(() => {
  const u = user.value
  if (!u) return false
  if (u.is_admin) return true
  if (u.company_certified) return true
  return Boolean(u.is_enterprise && u.is_certified)
})

function openCertGate() {
  if (!user.value) {
    certGate.title = '请先登录'
    certGate.message = '企业招人仅对已认证企业开放。请先登录，再完成企业认证后查看人才。'
    certGate.action = '去登录'
    certGate.to = '/login?redirect=/jobs'
  } else if (!user.value.is_enterprise) {
    certGate.title = '需要企业认证'
    certGate.message = '当前账号还不是已认证企业，暂时不能查看人才库。请先完成企业认证，通过后再来招人。'
    certGate.action = '去认证企业'
    certGate.to = '/member/grade'
  } else {
    certGate.title = '企业尚未认证通过'
    certGate.message = '企业资料审核通过后，才能查看人才并主动联系。请完善企业资料并提交认证。'
    certGate.action = '去认证企业'
    certGate.to = '/member/shop'
  }
  certGateOpen.value = true
}

async function openEmpView() {
  if (!user.value) {
    openCertGate()
    return
  }
  await fetchMe(true)
  if (!talentReady.value) {
    openCertGate()
    return
  }
  switchView('emp')
}

function goCertify() {
  const to = certGate.to
  certGateOpen.value = false
  navigateTo(to)
}

function pickCity(c: string, forTalent = false) {
  if (forTalent) {
    talCityLabel.value = c
    talFilter.city = c === '全国' ? '全部' : c
    talCityDropOpen.value = false
    talPage.value = 1
    fetchTalents()
  } else {
    cityLabel.value = c
    filter.city = c === '全国' ? '全部' : c
    cityDropOpen.value = false
    curPage.value = 1
    fetchJobs()
  }
}

function setFilter(k: keyof typeof filter, v: string) {
  ;(filter as any)[k] = v
  curPage.value = 1
  fetchJobs()
}
function setTalFilter(k: keyof typeof talFilter, v: string) {
  ;(talFilter as any)[k] = v
  talPage.value = 1
  fetchTalents()
}

function goJobPage(p: number) {
  const n = Math.min(Math.max(1, p), jobPages.value)
  if (n === curPage.value) return
  curPage.value = n
  fetchJobs()
  nextTick(() => jobMainRef.value?.scrollIntoView({ behavior: 'smooth', block: 'start' }))
}
function goTalPage(p: number) {
  const n = Math.min(Math.max(1, p), talPages.value)
  if (n === talPage.value) return
  talPage.value = n
  fetchTalents()
  nextTick(() => talMainRef.value?.scrollIntoView({ behavior: 'smooth', block: 'start' }))
}

function doSearch() {
  filter.kw = kwInput.value.trim()
  curPage.value = 1
  fetchJobs()
  nextTick(() => jobMainRef.value?.scrollIntoView({ behavior: 'smooth', block: 'start' }))
}
function doTalSearch() {
  talFilter.kw = talKwInput.value.trim()
  talPage.value = 1
  fetchTalents()
  nextTick(() => talMainRef.value?.scrollIntoView({ behavior: 'smooth', block: 'start' }))
}
function hotKw(s: string, talent = false) {
  if (talent) {
    talKwInput.value = s
    doTalSearch()
  } else {
    kwInput.value = s
    doSearch()
  }
}

const filteredJobs = computed(() => jobs.value)
const filteredTalents = computed(() => talents.value)
const jobPages = computed(() => Math.max(1, jobMeta.value.last_page))
const talPages = computed(() => Math.max(1, talMeta.value.last_page))
const jobPageItems = computed(() => compactPageItems(curPage.value, jobPages.value))
const talPageItems = computed(() => compactPageItems(talPage.value, talPages.value))
const pageJobs = computed(() => jobs.value)
const pageTalents = computed(() => talents.value)

function catCount(cat: string) {
  if (cat === '全部') return jobFacets.value.total || jobMeta.value.total
  return Number(jobFacets.value.categories?.[cat] ?? 0)
}
function talCatCount(cat: string) {
  if (cat === '全部') return talMeta.value.total
  return sideTalents.value.filter((t) => t.field === cat).length
}

const hotJobs = computed(() => {
  const sticky = sideJobs.value.filter((j) => j.hot)
  return (sticky.length ? sticky : sideJobs.value).slice(0, 6)
})
const talHot = computed(() => [...sideTalents.value].sort((a, b) => b.sMax - a.sMax).slice(0, 5))
const hotSkills = computed(() => {
  const map: Record<string, number> = {}
  sideTalents.value.forEach((t) => t.skills.forEach((s) => {
    map[s] = (map[s] || 0) + 1
  }))
  return Object.entries(map)
    .sort((a, b) => b[1] - a[1])
    .slice(0, 10)
    .map((e) => e[0])
})

const onlineQuotaText = computed(() => {
  const e = entitlement.value
  if (!e) return '—'
  const lim = Number(e.jobs_online_limit ?? 0)
  const used = Number(e.jobs_online_used ?? 0)
  if (lim <= 0) return `${used}/∞`
  return `${used}/${lim}`
})
const contactRemainText = computed(() => {
  const e = entitlement.value
  if (!e) return '—'
  return String(e.resume_contact_remain_total ?? e.resume_contact_remain_month ?? '—')
})

const seekStats = reactive({ applies: 0, favJobs: 0, resumeViews: 0, chats: 0, favored: 0, notices: 0 })
const noticeCount = computed(() => seekStats.chats + seekStats.favored + seekStats.notices)
const publishOpen = ref(false)
const noticeOpen = ref(false)
const publishCat = ref('')
const resumeCats = FIELD_CATS.filter((c) => c !== '全部')

async function loadSeekStats() {
  if (!user.value) {
    seekStats.applies = 0
    seekStats.favJobs = 0
    seekStats.resumeViews = 0
    seekStats.chats = 0
    seekStats.favored = 0
    seekStats.notices = 0
    return
  }
  try {
    const [apps, favs, resumes, dash] = await Promise.all([
      api('/member/job-applications').catch(() => null),
      api('/member/favorites', { query: { type: 'job' } }).catch(() => null),
      api('/member/resumes').catch(() => null),
      api('/member/dashboard').catch(() => null),
    ])
    seekStats.applies = Number((apps as any)?.meta?.total ?? (apps as any)?.data?.length ?? 0)
    seekStats.favJobs = Number((favs as any)?.meta?.total ?? (favs as any)?.data?.length ?? 0)
    const list = ((resumes as any)?.data || []) as any[]
    seekStats.resumeViews = list.reduce((s, r) => s + Number(r.views || 0), 0)
    const stats = (dash as any)?.data?.stats || {}
    seekStats.favored = Number(stats.favored_new ?? 0)
    seekStats.chats = Number(stats.chats ?? 0)
    seekStats.notices = Number(stats.announces_new ?? 0)
  } catch {
    /* ignore */
  }
}

function openNotice() {
  if (!user.value) {
    navigateTo('/login?redirect=/jobs')
    return
  }
  noticeOpen.value = true
  loadSeekStats()
}

function openPublishResume() {
  if (!user.value) {
    navigateTo('/login?redirect=/jobs')
    return
  }
  publishCat.value = resumeCats[0] || ''
  publishOpen.value = true
}

function goPublishResume() {
  if (!publishCat.value) return
  publishOpen.value = false
  navigateTo({ path: '/member/resumes/create', query: { category: publishCat.value } })
}

async function toggleFav(j: DemoJob, e?: Event) {
  e?.stopPropagation?.()
  if (!user.value) {
    navigateTo('/login?redirect=/jobs')
    return
  }
  try {
    if (j.fav && (j as any).favId) {
      await api(`/member/favorites/${(j as any).favId}`, { method: 'DELETE' })
      j.fav = false
      ;(j as any).favId = null
      toast('已取消收藏')
      seekStats.favJobs = Math.max(0, seekStats.favJobs - 1)
      return
    }
    const res: any = await api('/member/favorites', { method: 'POST', body: { type: 'job', id: j.id } })
    j.fav = true
    ;(j as any).favId = res?.data?.id || null
    toast('已收藏职位，可在「我的收藏」查看')
    seekStats.favJobs += 1
  } catch (err: any) {
    toast(apiErrorText(err, '收藏失败'))
  }
}

async function toggleTalFav(t: DemoTalent, e?: Event) {
  e?.stopPropagation?.()
  if (!user.value) {
    navigateTo('/login?redirect=/jobs')
    return
  }
  try {
    if (t.fav && (t as any).favId) {
      await api(`/member/favorites/${(t as any).favId}`, { method: 'DELETE' })
      t.fav = false
      ;(t as any).favId = null
      toast('已取消收藏')
      return
    }
    const res: any = await api('/member/favorites', { method: 'POST', body: { type: 'resume', id: t.id } })
    t.fav = true
    ;(t as any).favId = res?.data?.id || null
    toast('已收藏人才，可在「我的收藏 → 人才」查看')
  } catch (err: any) {
    toast(apiErrorText(err, '收藏失败'))
  }
}

function goTalentPool() {
  openEmpView().then(() => {
    if (view.value === 'emp') {
      nextTick(() => talMainRef.value?.scrollIntoView({ behavior: 'smooth', block: 'start' }))
    }
  })
}

async function markFavStates() {
  if (!user.value) return
  try {
    await Promise.all([
      ...jobs.value.slice(0, 40).map(async (j) => {
        try {
          const res: any = await api('/member/favorites/check', { query: { type: 'job', id: j.id } })
          j.fav = Boolean(res?.data?.favorited)
          ;(j as any).favId = res?.data?.id || null
        } catch { /* */ }
      }),
      ...talents.value.slice(0, 40).map(async (t) => {
        try {
          const res: any = await api('/member/favorites/check', { query: { type: 'resume', id: t.id } })
          t.fav = Boolean(res?.data?.favorited)
          ;(t as any).favId = res?.data?.id || null
        } catch { /* */ }
      }),
    ])
  } catch { /* */ }
}

function goJob(id: number) {
  navigateTo(`/job/${id}`)
}
function goTalent(id: number) {
  navigateTo(`/talent/${id}`)
}

async function contactTalent(t: DemoTalent, e?: Event) {
  e?.stopPropagation?.()
  toast('站内交谈已关闭，请解锁电话或通过职位投递沟通')
}

function openPublish() {
  if (!user.value) {
    openCertGate()
    return
  }
  if (!talentReady.value) {
    openCertGate()
    return
  }
  navigateTo('/member/jobs/create')
}

function onDocClick(e: MouseEvent) {
  const t = e.target as HTMLElement
  if (!t.closest?.('.sb-city')) {
    cityDropOpen.value = false
    talCityDropOpen.value = false
  }
}
const jobMainRef = ref<HTMLElement | null>(null)
const talMainRef = ref<HTMLElement | null>(null)

onMounted(async () => {
  document.addEventListener('click', onDocClick)
  void fetchMe(true)
  await Promise.all([fetchJobs(), fetchSidePanels()])
  await Promise.all([loadEntitlement(), loadSeekStats()])
  markFavStates()
})
onBeforeUnmount(() => document.removeEventListener('click', onDocClick))

watch(view, async (v) => {
  if (v === 'emp' && !talents.value.length && !loadingTalents.value) {
    await fetchTalents()
    if (!sideTalents.value.length) await fetchSidePanels()
    markFavStates()
  }
})
function pickCat(cat: string, talent = false) {
  if (talent) {
    talFilter.cat = cat
    talPage.value = 1
    fetchTalents()
    nextTick(() => talMainRef.value?.scrollIntoView({ behavior: 'smooth', block: 'start' }))
  } else {
    filter.cat = cat
    curPage.value = 1
    fetchJobs()
    nextTick(() => jobMainRef.value?.scrollIntoView({ behavior: 'smooth', block: 'start' }))
  }
}
</script>

<template>
  <div class="jobs-ch">
    <!-- 找工作 -->
    <main v-show="view === 'job'">
      <section class="hero">
        <div class="container">
          <div class="hero-card">
            <div class="hero-cols">
              <div class="hero-left">
                <div class="view-switch">
                  <button type="button" class="view-tab active" @click="switchView('job')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7" /><path d="m21 21-4.35-4.35" /></svg>
                    找工作
                  </button>
                  <button type="button" class="view-tab" @click="openEmpView">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6" /></svg>
                    企业招人
                  </button>
                </div>
                <h1>找好工作，上有机硅商城</h1>
                <p class="sub">覆盖单体 · 硅油 · 硅橡胶 · 硅烷 · 胶粘剂等全产业链岗位</p>
                <div class="search-box">
                  <div class="sb-city" @click.stop>
                    <button type="button" class="sb-city-btn" @click="cityDropOpen = !cityDropOpen">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z" /><circle cx="12" cy="10" r="3" /></svg>
                      <span>{{ cityLabel }}</span>
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px"><path d="m6 9 6 6 6-6" /></svg>
                    </button>
                    <div v-show="cityDropOpen" class="city-drop">
                      <div class="cd-t">热门城市</div>
                      <div class="cd-grid">
                        <button
                          v-for="c in HOT_CITIES"
                          :key="c"
                          type="button"
                          :class="{ on: cityLabel === c }"
                          @click="pickCity(c)"
                        >{{ c }}</button>
                      </div>
                    </div>
                  </div>
                  <div class="sb-div" />
                  <div class="sb-input">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7" /><path d="m21 21-4.35-4.35" /></svg>
                    <input v-model="kwInput" type="text" placeholder="搜索职位、公司，如：硅橡胶研发 / 外贸 / DMC" @keydown.enter="doSearch">
                  </div>
                  <button type="button" class="sb-go" @click="doSearch">搜 索</button>
                </div>
                <div class="hot-search">
                  <b>热门搜索：</b>
                  <span v-for="h in ['硅油', '硅橡胶', 'DMC', '硅烷', '白炭黑']" :key="h" @click="hotKw(h)">{{ h }}</span>
                </div>
              </div>
              <div class="hero-side">
                <div class="resume-box">
                  <div class="av">
                    <span class="ava">
                      <img v-if="user?.avatar" :src="user.avatar" alt="">
                      <template v-else>{{ (user?.name || user?.username || '求').slice(0, 1) }}</template>
                    </span>
                    <div><b>我的求职中心</b><span>完善简历，更快被企业看见</span></div>
                  </div>
                  <div class="rb-stats">
                    <NuxtLink class="rb-stat" to="/member/job-applications"><b>{{ seekStats.applies }}</b><span>投递记录</span></NuxtLink>
                    <NuxtLink class="rb-stat" to="/member/favorites?type=job"><b>{{ seekStats.favJobs }}</b><span>收藏职位</span></NuxtLink>
                    <NuxtLink class="rb-stat" to="/member/resumes"><b>{{ seekStats.resumeViews }}</b><span>简历被看</span></NuxtLink>
                  </div>
                  <div class="rb-btns">
                    <NuxtLink class="b2" to="/member/resumes">完善简历</NuxtLink>
                    <NuxtLink class="b2" to="/member/job-applications">我的投递</NuxtLink>
                    <button type="button" class="b2 notice-btn" @click="openNotice">
                      消息通知
                      <i v-if="noticeCount > 0" class="rb-badge">{{ noticeCount > 99 ? '99+' : noticeCount }}</i>
                    </button>
                  </div>
                  <button type="button" class="rb-publish" @click="openPublishResume">发布简历</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="cat-sec container">
        <div class="cat-card">
          <button
            v-for="cat in FIELD_CATS"
            :key="'jc' + cat"
            type="button"
            class="cat-item"
            :class="{ on: filter.cat === cat }"
            @click="pickCat(cat)"
          >
            <span class="ic">
              <svg v-if="cat === '全部'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1" /><rect x="14" y="3" width="7" height="7" rx="1" /><rect x="3" y="14" width="7" height="7" rx="1" /><rect x="14" y="14" width="7" height="7" rx="1" /></svg>
              <svg v-else-if="cat.includes('单体')" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="5.5" cy="6" r="2.2" /><circle cx="18.5" cy="6" r="2.2" /><circle cx="12" cy="18" r="2.2" /><path d="M7.2 7.6 10.5 16M16.8 7.6 13.5 16M7.7 6h8.6" /></svg>
              <svg v-else-if="cat === '硅油'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3s5.5 6.5 5.5 10.5a5.5 5.5 0 0 1-11 0C6.5 9.5 12 3 12 3Z" /><path d="M9 14a3 3 0 0 0 6 0" /></svg>
              <svg v-else-if="cat === '硅橡胶'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="6.5" cy="12" r="2.8" /><circle cx="17.5" cy="12" r="2.8" /><path d="M9.3 12h5.4" /></svg>
              <svg v-else-if="cat.includes('助剂')" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v7" /><path d="M12 10c-4 0-7 2.2-7 5s3 6 7 6 7-3.2 7-6-3-5-7-5Z" /></svg>
              <svg v-else-if="cat === '机械设备'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3" /><path d="M12 2v3M12 19v3M2 12h3M19 12h3M4.9 4.9l2.1 2.1M17 17l2.1 2.1M19.1 4.9 17 7M7 17l-2.1 2.1" /></svg>
              <svg v-else-if="cat === '硅烷'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="6" cy="12" r="2.2" /><circle cx="18" cy="7" r="2.2" /><circle cx="18" cy="17" r="2.2" /><path d="M8.1 11.2 15.9 7.8M8.1 12.8l7.8 3.4" /></svg>
              <svg v-else-if="cat === '胶粘剂'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m13 2-3 6h4l-3 8 6-8h-4l3-6Z" /><path d="M5 21h14" /></svg>
              <svg v-else-if="cat === '硅树脂'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 3h6M10 3v5.5L4.5 18a2 2 0 0 0 1.7 3h11.6a2 2 0 0 0 1.7-3L14 8.5V3" /></svg>
              <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="5" cy="12" r="1.6" /><circle cx="12" cy="12" r="1.6" /><circle cx="19" cy="12" r="1.6" /></svg>
            </span>
            <b>{{ cat === '全部' ? '全部职位' : cat }}</b>
            <span>{{ catCount(cat) }}</span>
          </button>
        </div>
      </section>

      <section ref="jobMainRef" class="main container">
        <div class="grid">
          <div>
            <div class="filter-bar">
              <div class="f-row">
                <span class="f-label">地区</span>
                <div class="f-options" :class="{ open: cityMoreOpen }">
                  <button v-for="c in CITY_MAIN" :key="c" type="button" class="f-chip" :class="{ on: filter.city === c }" @click="setFilter('city', c)">{{ c === '全部' ? '不限' : c }}</button>
                  <button v-for="c in CITY_EXTRA" :key="c" type="button" class="f-chip extra" :class="{ on: filter.city === c }" @click="setFilter('city', c)">{{ c }}</button>
                  <button type="button" class="f-more" @click="cityMoreOpen = !cityMoreOpen">
                    {{ cityMoreOpen ? '收起' : '更多' }}
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m6 9 6 6 6-6" /></svg>
                  </button>
                </div>
              </div>
              <div class="f-row">
                <span class="f-label">职位</span>
                <div class="f-options">
                  <button
                    v-for="r in JOB_ROLE_CATS"
                    :key="'role-' + r"
                    type="button"
                    class="f-chip"
                    :class="{ on: filter.role === r }"
                    @click="setFilter('role', r)"
                  >{{ r === '全部' ? '不限' : r }}</button>
                </div>
              </div>
              <div class="f-row">
                <span class="f-label">薪资</span>
                <div class="f-options">
                  <button v-for="o in JOB_SALARY_OPTS" :key="o[0]" type="button" class="f-chip" :class="{ on: filter.salary === o[0] }" @click="setFilter('salary', o[0])">{{ o[1] }}</button>
                </div>
              </div>
              <div class="f-row">
                <span class="f-label">经验</span>
                <div class="f-options">
                  <button v-for="o in JOB_EXP_OPTS" :key="o[0]" type="button" class="f-chip" :class="{ on: filter.exp === o[0] }" @click="setFilter('exp', o[0])">{{ o[1] }}</button>
                </div>
              </div>
              <div class="f-row">
                <span class="f-label">学历</span>
                <div class="f-options">
                  <button v-for="o in JOB_EDU_OPTS" :key="o" type="button" class="f-chip" :class="{ on: filter.edu === o }" @click="setFilter('edu', o)">{{ o === '全部' ? '不限' : o }}</button>
                </div>
              </div>
              <div class="f-row">
                <span class="f-label">规模</span>
                <div class="f-options">
                  <button v-for="o in JOB_SIZE_OPTS" :key="o[0]" type="button" class="f-chip" :class="{ on: filter.size === o[0] }" @click="setFilter('size', o[0])">{{ o[1] }}</button>
                </div>
              </div>
              <div class="f-row">
                <span class="f-label">性质</span>
                <div class="f-options">
                  <button v-for="o in JOB_NATURE_OPTS" :key="o" type="button" class="f-chip" :class="{ on: filter.type === o }" @click="setFilter('type', o)">{{ o === '全部' ? '不限' : o }}</button>
                </div>
              </div>
            </div>

            <div class="list-head">
              <div class="n">共 <b>{{ jobMeta.total }}</b> 个职位</div>
              <div class="sort">
                <button type="button" :class="{ on: filter.sort === 'time' }" @click="setFilter('sort', 'time')">最新发布</button>
                <button type="button" :class="{ on: filter.sort === 'salary' }" @click="setFilter('sort', 'salary')">薪资最高</button>
              </div>
            </div>

            <div v-if="!pageJobs.length" class="panel empty">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="7" /><path d="m21 21-4.35-4.35M8 11h6" /></svg>
              <p>没有找到符合条件的职位，试试放宽筛选条件</p>
            </div>
            <article v-for="j in pageJobs" :key="j.id" class="job-card" role="link" tabindex="0" @click="goJob(j.id)" @keydown.enter="goJob(j.id)">
              <div class="job-top">
                <div class="job-title">
                  <h2><NuxtLink :to="`/job/${j.id}`" @click.stop>{{ j.t }}</NuxtLink></h2>
                  <span v-if="j.hot" class="badge badge-hot">急聘</span>
                  <span v-if="timeAgo(j.date) === '今天'" class="badge badge-new">新</span>
                </div>
                <div class="job-salary"><b>{{ j.salary }}</b><span>元/月</span></div>
              </div>
              <div class="job-meta">
                <span class="co"><span v-if="j.gold" class="v-icon">✓</span>{{ j.co }}</span>
                <span v-if="j.role" class="role-tag">{{ j.role }}</span>
                <span>
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z" /><circle cx="12" cy="10" r="3" /></svg>
                  {{ j.city }}
                </span>
                <span>
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="7" width="18" height="13" rx="2" /><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" /></svg>
                  {{ j.exp }}
                </span>
                <span>
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m2 8 10-5 10 5-10 5-10-5Z" /><path d="M6 11v5c0 1.5 2.7 3 6 3s6-1.5 6-3v-5" /></svg>
                  {{ j.edu }}
                </span>
                <span>
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.5" /><path d="M2.5 20c0-3.5 2.9-6 6.5-6s6.5 2.5 6.5 6M16 5.5a3 3 0 0 1 0 5.8M18 20c0-2.6-1-4.6-2.6-5.7" /></svg>
                  {{ j.size }}
                </span>
                <span>{{ j.type }}</span>
              </div>
              <div class="job-tags"><span v-for="tg in j.tags" :key="tg">{{ tg }}</span></div>
              <div class="job-foot">
                <span>{{ j.date }} 发布 · {{ timeAgo(j.date) }}<template v-if="j.role"> · {{ j.role }}</template><template v-if="j.field"> · {{ j.field }}领域</template></span>
                <button type="button" class="fav-btn" :class="{ on: j.fav }" @click="toggleFav(j, $event)">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 14c1.5-1.4 3-3.2 3-5.5A5 5 0 0 0 12 6 5 5 0 0 0 2 8.5c0 2.3 1.5 4.1 3 5.5l7 7 7-7Z" /></svg>
                  <span>{{ j.fav ? '已收藏' : '收藏' }}</span>
                </button>
              </div>
            </article>

            <div v-if="jobPages > 1" class="pager" @click.stop>
              <button type="button" :disabled="curPage <= 1" @click="goJobPage(curPage - 1)">‹</button>
              <template v-for="(p, i) in jobPageItems" :key="'jp' + i + '-' + p">
                <span v-if="p === '…'" class="pg-ellipsis" aria-hidden="true">…</span>
                <button v-else type="button" :class="{ on: curPage === p }" @click="goJobPage(p)">{{ p }}</button>
              </template>
              <button type="button" :disabled="curPage >= jobPages" @click="goJobPage(curPage + 1)">›</button>
            </div>
          </div>

          <aside class="aside">
            <div class="side-card">
              <h3>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 3 7v6c0 5 3.8 8.4 9 9 5.2-.6 9-4 9-9V7l-9-5Z" /></svg>
                金牌企业推荐
              </h3>
              <ul class="hot-company">
                <li
                  v-for="c in companySide"
                  :key="(c.slug || c.n)"
                  class="hc-link"
                  role="link"
                  tabindex="0"
                  :title="c.slug ? '查看企业人才招聘' : '该企业暂未开通旺铺'"
                  @click="goCompanyShop(c, $event)"
                  @keydown.enter="goCompanyShop(c)"
                >
                  <span class="hc-logo">{{ c.n.slice(0, 1) }}</span>
                  <div class="hc-info">
                    <b>{{ c.n }}</b>
                    <span><span v-if="c.g" class="v-icon">✓</span> {{ c.g ? '置顶企业' : '招聘企业' }}</span>
                  </div>
                  <span class="hc-jobs">{{ c.j }}个职位</span>
                </li>
                <li v-if="!companySide.length" class="muted" style="padding:8px 0;list-style:none">暂无企业数据</li>
              </ul>
            </div>
            <div class="side-card">
              <h3>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m13 2-3 6h4l-3 8 6-8h-4l3-6Z" /></svg>
                急聘专区
              </h3>
              <ul class="hot-job">
                <li v-for="j in hotJobs" :key="'hj' + j.id" class="hot-job-link" role="link" tabindex="0" @click="goJob(j.id)" @keydown.enter="goJob(j.id)">
                  <div class="hj-l"><b>{{ j.t }}</b><span>{{ j.co }} · {{ j.city }}</span></div>
                  <span class="hj-s">{{ j.salary }}</span>
                </li>
              </ul>
            </div>
          </aside>
        </div>
      </section>
    </main>

    <!-- 企业招人 -->
    <main v-show="view === 'emp'">
      <section class="hero">
        <div class="container">
          <div class="hero-card">
            <div class="hero-cols">
              <div class="hero-left">
                <div class="view-switch">
                  <button type="button" class="view-tab" @click="switchView('job')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7" /><path d="m21 21-4.35-4.35" /></svg>
                    找工作
                  </button>
                  <button type="button" class="view-tab active" @click="switchView('emp')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6" /></svg>
                    企业招人
                  </button>
                </div>
                <h1>有机硅行业人才，一网打尽</h1>
                <p class="sub">海量求职者简历，企业按需筛选、主动联系</p>
                <div class="search-box">
                  <div class="sb-city" @click.stop>
                    <button type="button" class="sb-city-btn" @click="talCityDropOpen = !talCityDropOpen">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z" /><circle cx="12" cy="10" r="3" /></svg>
                      <span>{{ talCityLabel }}</span>
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px"><path d="m6 9 6 6 6-6" /></svg>
                    </button>
                    <div v-show="talCityDropOpen" class="city-drop">
                      <div class="cd-t">热门城市</div>
                      <div class="cd-grid">
                        <button v-for="c in HOT_CITIES" :key="'tc' + c" type="button" :class="{ on: talCityLabel === c }" @click="pickCity(c, true)">{{ c }}</button>
                      </div>
                    </div>
                  </div>
                  <div class="sb-div" />
                  <div class="sb-input">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7" /><path d="m21 21-4.35-4.35" /></svg>
                    <input v-model="talKwInput" type="text" placeholder="搜索技能、期望职位，如：硅橡胶配方 / 外贸 / 偶联剂" @keydown.enter="doTalSearch">
                  </div>
                  <button type="button" class="sb-go" @click="doTalSearch">搜 人 才</button>
                </div>
                <div class="hot-search">
                  <b>热门搜索：</b>
                  <span v-for="h in ['硅油', '硅橡胶配方', '硅烷', '外贸', '质检']" :key="'th' + h" @click="hotKw(h, true)">{{ h }}</span>
                </div>
              </div>
              <div class="hero-side">
                <div class="resume-box">
                  <div class="av">
                    <span class="ava">
                      <img v-if="user?.avatar" :src="user.avatar" alt="">
                      <template v-else>{{ (user?.company_name || user?.name || '企').slice(0, 1) }}</template>
                    </span>
                    <div><b>企业招聘</b><span>发布职位，主动招到对的人</span></div>
                  </div>
                  <div class="rb-stats">
                    <NuxtLink class="rb-stat" to="/member/jobs"><b>{{ onlineQuotaText }}</b><span>在线职位</span></NuxtLink>
                    <NuxtLink class="rb-stat" to="/member/grade"><b>{{ contactRemainText }}</b><span>联系剩余</span></NuxtLink>
                    <button type="button" class="rb-stat" @click="goTalentPool"><b>{{ talMeta.total || sideTalents.length || '—' }}</b><span>人才库</span></button>
                  </div>
                  <div class="rb-btns">
                    <button type="button" class="b1" @click="openPublish">发布职位</button>
                    <NuxtLink class="b2" to="/member/jobs">职位管理</NuxtLink>
                  </div>
                  <p class="rb-extra">
                    <a href="#" @click.prevent="goTalentPool">进入人才库</a>
                    ·
                    <NuxtLink to="/member/favorites?type=resume">收藏的人才</NuxtLink>
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="cat-sec container">
        <div class="cat-card">
          <button
            v-for="cat in FIELD_CATS"
            :key="'tc' + cat"
            type="button"
            class="cat-item"
            :class="{ on: talFilter.cat === cat }"
            @click="pickCat(cat, true)"
          >
            <span class="ic">
              <svg v-if="cat === '全部'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.5" /><path d="M2.5 20c0-3.5 2.9-6 6.5-6s6.5 2.5 6.5 6M16 5.5a3 3 0 0 1 0 5.8M18 20c0-2.6-1-4.6-2.6-5.7" /></svg>
              <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="5.5" cy="6" r="2.2" /><circle cx="18.5" cy="6" r="2.2" /><circle cx="12" cy="18" r="2.2" /><path d="M7.2 7.6 10.5 16M16.8 7.6 13.5 16M7.7 6h8.6" /></svg>
            </span>
            <b>{{ cat === '全部' ? '全部人才' : cat }}</b>
            <span>{{ talCatCount(cat) }}</span>
          </button>
        </div>
      </section>

      <section ref="talMainRef" class="main container">
        <div class="grid">
          <div>
            <div class="filter-bar">
              <div class="f-row">
                <span class="f-label">地区</span>
                <div class="f-options">
                  <button v-for="c in CITY_MAIN" :key="'tfc' + c" type="button" class="f-chip" :class="{ on: talFilter.city === c }" @click="setTalFilter('city', c)">{{ c === '全部' ? '不限' : c }}</button>
                </div>
              </div>
              <div class="f-row">
                <span class="f-label">职位</span>
                <div class="f-options">
                  <button
                    v-for="r in JOB_ROLE_CATS"
                    :key="'tr-' + r"
                    type="button"
                    class="f-chip"
                    :class="{ on: talFilter.role === r }"
                    @click="setTalFilter('role', r)"
                  >{{ r === '全部' ? '不限' : r }}</button>
                </div>
              </div>
              <div class="f-row">
                <span class="f-label">经验</span>
                <div class="f-options">
                  <button v-for="o in JOB_EXP_OPTS" :key="'te' + o[0]" type="button" class="f-chip" :class="{ on: talFilter.exp === o[0] }" @click="setTalFilter('exp', o[0])">{{ o[1] }}</button>
                </div>
              </div>
              <div class="f-row">
                <span class="f-label">学历</span>
                <div class="f-options">
                  <button v-for="o in JOB_EDU_OPTS" :key="'ted' + o" type="button" class="f-chip" :class="{ on: talFilter.edu === o }" @click="setTalFilter('edu', o)">{{ o === '全部' ? '不限' : o }}</button>
                </div>
              </div>
              <div class="f-row">
                <span class="f-label">期望薪资</span>
                <div class="f-options">
                  <button v-for="o in JOB_SALARY_OPTS" :key="'ts' + o[0]" type="button" class="f-chip" :class="{ on: talFilter.salary === o[0] }" @click="setTalFilter('salary', o[0])">{{ o[1] }}</button>
                </div>
              </div>
              <div class="f-row">
                <span class="f-label">求职状态</span>
                <div class="f-options">
                  <button v-for="o in [['全部','不限'],['离职-随时到岗','离职-随时到岗'],['在职-看机会','在职-看机会'],['应届生','应届生']]" :key="'tst' + o[0]" type="button" class="f-chip" :class="{ on: talFilter.st === o[0] }" @click="setTalFilter('st', o[0])">{{ o[1] }}</button>
                </div>
              </div>
            </div>

            <div class="list-head">
              <div class="n">共 <b>{{ talMeta.total }}</b> 位人才</div>
              <div class="sort">
                <button type="button" :class="{ on: talFilter.sort === 'time' }" @click="setTalFilter('sort', 'time')">最新更新</button>
                <button type="button" :class="{ on: talFilter.sort === 'salary' }" @click="setTalFilter('sort', 'salary')">期望薪资</button>
              </div>
            </div>

            <div v-if="!pageTalents.length" class="panel empty">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="7" /><path d="m21 21-4.35-4.35M8 11h6" /></svg>
              <p>没有找到符合条件的人才，试试放宽筛选条件</p>
            </div>
            <article v-for="t in pageTalents" :key="t.id" class="job-card rc-card" role="link" tabindex="0" @click="goTalent(t.id)" @keydown.enter="goTalent(t.id)">
              <div class="job-top">
                <div class="rc-id">
                  <span class="rc-ava">{{ t.av }}</span>
                  <div class="rc-name">
                    <h2><NuxtLink :to="`/talent/${t.id}`" @click.stop>{{ t.name }}</NuxtLink></h2>
                    <span class="badge" :class="t.stCls">{{ t.st }}</span>
                  </div>
                </div>
                <div class="job-salary"><b>{{ t.salary }}</b><span>期望月薪</span></div>
              </div>
              <div class="job-meta">
                <span class="rc-pos">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="7" width="18" height="13" rx="2" /><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" /></svg>
                  期望：{{ t.pos }}
                </span>
                <span v-if="t.role" class="role-tag">{{ t.role }}</span>
                <span>
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 2" /></svg>
                  {{ t.exp }}
                </span>
                <span>{{ t.edu }}</span>
                <span>{{ t.city }}</span>
              </div>
              <div class="job-tags"><span v-for="s in t.skills" :key="s" class="act">{{ s }}</span></div>
              <div class="job-foot">
                <span>{{ t.date }} 更新 · {{ timeAgo(t.date) }}<template v-if="t.role"> · {{ t.role }}</template><template v-if="t.field"> · {{ t.field }}领域</template></span>
                <div class="rc-actions" @click.stop>
                  <button type="button" class="fav-btn" :class="{ on: t.fav }" @click="toggleTalFav(t, $event)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 14c1.5-1.4 3-3.2 3-5.5A5 5 0 0 0 12 6 5 5 0 0 0 2 8.5c0 2.3 1.5 4.1 3 5.5l7 7 7-7Z" /></svg>
                    <span>{{ t.fav ? '已收藏' : '收藏' }}</span>
                  </button>
                  <button type="button" class="btn btn-primary btn-pill rc-contact" @click="contactTalent(t, $event)">联系 TA</button>
                </div>
              </div>
            </article>

            <div v-if="talPages > 1" class="pager" @click.stop>
              <button type="button" :disabled="talPage <= 1" @click="goTalPage(talPage - 1)">‹</button>
              <template v-for="(p, i) in talPageItems" :key="'tp' + i + '-' + p">
                <span v-if="p === '…'" class="pg-ellipsis" aria-hidden="true">…</span>
                <button v-else type="button" :class="{ on: talPage === p }" @click="goTalPage(p)">{{ p }}</button>
              </template>
              <button type="button" :disabled="talPage >= talPages" @click="goTalPage(talPage + 1)">›</button>
            </div>
          </div>

          <aside class="aside">
            <div class="side-card">
              <h3>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.5" /><path d="M2.5 20c0-3.5 2.9-6 6.5-6s6.5 2.5 6.5 6" /></svg>
                推荐人才
              </h3>
              <ul class="hot-job">
                <li v-for="t in talHot" :key="'thot' + t.id" class="hot-job-link" role="link" tabindex="0" @click="goTalent(t.id)" @keydown.enter="goTalent(t.id)">
                  <div class="hj-l"><b>{{ t.name }} · {{ t.pos }}</b><span>{{ t.city }} · {{ t.exp }}</span></div>
                  <span class="hj-s">{{ t.salary }}</span>
                </li>
              </ul>
            </div>
            <div class="side-card">
              <h3>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m13 2-3 6h4l-3 8 6-8h-4l3-6Z" /></svg>
                热门技能
              </h3>
              <div class="skill-cloud">
                <span v-for="s in hotSkills" :key="s" @click="hotKw(s, true)">{{ s }}</span>
              </div>
            </div>
          </aside>
        </div>
      </section>
    </main>

    <VipGateModal v-model="vipGateOpen" title="会员权益不足" :message="vipGateMsg" @join="router.push('/member/grade')" />
    <div v-if="noticeOpen" class="jcert-mask" @click.self="noticeOpen = false">
      <div class="jcert-box" role="dialog" aria-modal="true" aria-label="消息通知">
        <button type="button" class="jcert-x" aria-label="关闭" @click="noticeOpen = false">×</button>
        <h3>消息通知</h3>
        <div class="notice-list">
          <NuxtLink to="/member/chat" @click="noticeOpen = false">
            <span>线上沟通的消息</span><b>{{ seekStats.chats }}</b>
          </NuxtLink>
          <NuxtLink to="/member/resumes" @click="noticeOpen = false">
            <span>被收藏</span><b>{{ seekStats.favored }}</b>
          </NuxtLink>
          <NuxtLink to="/announce" @click="noticeOpen = false">
            <span>平台公告</span><b>{{ seekStats.notices }}</b>
          </NuxtLink>
        </div>
      </div>
    </div>
    <div v-if="publishOpen" class="jcert-mask" @click.self="publishOpen = false">
      <div class="jcert-box" role="dialog" aria-modal="true" aria-label="发布简历">
        <button type="button" class="jcert-x" aria-label="关闭" @click="publishOpen = false">×</button>
        <h3>发布简历</h3>
        <p>先选择所属行业。提交后由平台审核，通过并公开的简历会出现在企业招人对应行业里。</p>
        <div class="pub-cats">
          <button
            v-for="c in resumeCats"
            :key="'pc' + c"
            type="button"
            :class="{ on: publishCat === c }"
            @click="publishCat = c"
          >{{ c }}</button>
        </div>
        <div class="jcert-acts">
          <button type="button" class="jcert-ghost" @click="publishOpen = false">取消</button>
          <button type="button" class="jcert-go" :disabled="!publishCat" @click="goPublishResume">去填写简历</button>
        </div>
      </div>
    </div>
    <div v-if="certGateOpen" class="jcert-mask" @click.self="certGateOpen = false">
      <div class="jcert-box" role="dialog" aria-modal="true" :aria-label="certGate.title">
        <button type="button" class="jcert-x" aria-label="关闭" @click="certGateOpen = false">×</button>
        <h3>{{ certGate.title }}</h3>
        <p>{{ certGate.message }}</p>
        <div class="jcert-acts">
          <button type="button" class="jcert-ghost" @click="certGateOpen = false">稍后再说</button>
          <button type="button" class="jcert-go" @click="goCertify">{{ certGate.action }}</button>
        </div>
      </div>
    </div>
    <div class="toast" :class="{ show: toastOn }">{{ toastMsg }}</div>
  </div>
</template>

<style scoped>
.jcert-mask {
  position: fixed;
  inset: 0;
  z-index: 80;
  display: grid;
  place-items: center;
  padding: 24px;
  background: rgba(19, 41, 75, 0.45);
}
.jcert-box {
  width: min(420px, 100%);
  background: #fff;
  border-radius: 16px;
  padding: 22px 22px 18px;
  box-shadow: 0 18px 48px rgba(19, 41, 75, 0.2);
  position: relative;
}
.jcert-x {
  position: absolute;
  top: 10px;
  right: 12px;
  border: 0;
  background: transparent;
  font-size: 22px;
  line-height: 1;
  color: #98a5bc;
  cursor: pointer;
}
.jcert-box h3 {
  margin: 0 28px 0 0;
  font-size: 18px;
  color: #13294b;
}
.jcert-box p {
  margin: 10px 0 0;
  font-size: 14px;
  line-height: 1.6;
  color: #5a6b85;
}
.jcert-acts {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 18px;
}
.jcert-ghost,
.jcert-go {
  height: 36px;
  padding: 0 16px;
  border-radius: 999px;
  font: inherit;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
}
.jcert-ghost {
  border: 1px solid #d6e4ff;
  background: #fff;
  color: #1d4fd7;
}
.jcert-go:disabled { opacity: 0.5; cursor: default; }
.pub-cats {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 14px;
}
.pub-cats button {
  border: 1px solid #d6e4ff;
  background: #f6f9fe;
  color: #1443b8;
  border-radius: 999px;
  padding: 6px 12px;
  font: inherit;
  font-size: 13px;
  cursor: pointer;
}
.pub-cats button.on {
  background: #2468f2;
  border-color: #2468f2;
  color: #fff;
}
.notice-list { margin-top: 14px; display: flex; flex-direction: column; }
.notice-list a {
  display: flex; justify-content: space-between; align-items: center;
  padding: 12px 2px; border-bottom: 1px solid #eef2f8;
  color: #13294b; text-decoration: none; font-size: 14px; font-weight: 600;
}
.notice-list a:last-child { border-bottom: 0; }
.notice-list b { color: #2468f2; font-size: 16px; }
</style>
