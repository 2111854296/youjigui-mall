<script setup lang="ts">
definePageMeta({ layout: 'channel' })
useHead({ title: '名企推荐' })

const route = useRoute()
const router = useRouter()
const config = useRuntimeConfig()

const page = computed(() => Number(route.query.page || 1))
const q = computed(() => (route.query.q as string) || '')
const category = computed(() => route.query.category as string | undefined)
const draftQ = ref(q.value)
watch(q, (v) => { draftQ.value = v })

const { data, pending, error } = await useAsyncData(
  'companies-list',
  () =>
    $fetch(`${config.public.apiBase}/companies`, {
      query: {
        page: page.value,
        q: q.value || undefined,
        category: category.value || undefined,
      },
    }),
  { watch: [page, q, category] },
)

const items = computed(() => (data.value as any)?.data || [])
const meta = computed(() => (data.value as any)?.meta || null)
const featured = computed(() => (data.value as any)?.featured || [])
const floors = computed(() => (data.value as any)?.floors || [])
const categories = computed(() => (data.value as any)?.categories || [])
const activeRoot = computed(() => (data.value as any)?.active_root_id)
const hasFilter = computed(() => !!(q.value || category.value))
const showFloors = computed(() => !hasFilter.value && page.value === 1)

function findCatName(id: string | undefined) {
  if (!id) return ''
  for (const c of categories.value) {
    if (String(c.id) === id) return String(c.name || '')
    for (const ch of c.children || []) {
      if (String(ch.id) === id) return String(ch.name || '')
    }
  }
  return ''
}

const listTitle = computed(() => {
  const name = findCatName(category.value)
  if (name) return `${name}入驻企业`
  if (q.value) return `「${q.value}」入驻企业`
  return '企业名录'
})

function catOn(c: any) {
  const id = String(category.value || '')
  if (!id) return false
  if (String(c.id) === id) return true
  if (activeRoot.value != null && String(activeRoot.value) === String(c.id)) return true
  return (c.children || []).some((ch: any) => String(ch.id) === id)
}

const SAFE_THUMB = '/img/logo-yjg.png'

function logoOf(c: any, _i = 0) {
  const t = String(c?.logo || '')
  if (!t || /\/img\/products\/p\d+/i.test(t) || /\.svg($|\?)/i.test(t) || /placehold/i.test(t)) return SAFE_THUMB
  return t
}

function applyFilter() {
  router.push({
    path: '/companies',
    query: {
      q: draftQ.value || undefined,
      category: category.value || undefined,
      page: undefined,
    },
  })
}

function resetFilter() {
  router.push({ path: '/companies' })
}

function tagsOf(c: any) {
  const area = String(c?.area || '').trim()
  const mode = String(c?.business_mode || c?.company_type || '').trim()
  const raw = c?.tags
  const parts = (Array.isArray(raw) ? raw : String(raw || c?.business || '').split(/[,，、|/]/))
    .map((s: any) => String(s).trim())
    .filter(Boolean)
  const out: string[] = []
  const push = (s: string) => {
    const t = s.replace(/\s+/g, '')
    if (!t || t === area || t === '地区不限' || out.includes(t) || t.length > 8) return
    out.push(t)
  }
  if (mode) push(mode)
  for (const p of parts) push(p)
  return out.slice(0, 4)
}
</script>

<template>
  <div class="ch-portal">
    <div class="crumb-row">
      <div class="container">
        <div class="crumb-tabs">
          <NuxtLink to="/companies" :class="{ on: !category && !q }">全部企业</NuxtLink>
          <NuxtLink
            v-for="c in categories.slice(0, 8)"
            :key="'t'+c.id"
            :to="{ path: '/companies', query: { category: c.id } }"
            :class="{ on: catOn(c) }"
          >{{ c.name }}</NuxtLink>
          <NuxtLink to="/register">企业入驻</NuxtLink>
        </div>
      </div>
    </div>

    <div class="tip-bar">
      <div class="container">
        <b>名企提示：</b>
        精选优质供应商按经营领域分区展示；VIP 会员可享首页推荐位优先展示。
      </div>
    </div>

    <div class="section">
      <div class="container">
        <div class="sec-head">
          <div>
            <h2>名企推荐</h2>
            <p class="sub">入驻企业 · 厂家直达，查看主营与联系方式，进入企业主页深入了解</p>
          </div>
          <NuxtLink class="more-link" to="/ads?place=company_top&from=elite">名企展位招租 ›</NuxtLink>
        </div>

        <div class="banner-slot">
          <ChannelBanner position="company_top" size="large" />
        </div>

        <div class="blk" style="margin-bottom:16px">
          <div class="b-head">
            <b>行业分类</b>
            <span class="hint">点击筛选对应领域企业</span>
          </div>
          <div class="chips">
            <NuxtLink to="/companies" class="chip" :class="{ active: !category }">全部行业</NuxtLink>
            <template v-for="c in categories" :key="c.id">
              <NuxtLink
                class="chip"
                :class="{ active: catOn(c) }"
                :to="{ path: '/companies', query: { category: c.id } }"
              >{{ c.name }}</NuxtLink>
              <NuxtLink
                v-for="ch in c.children || []"
                :key="ch.id"
                class="chip"
                :class="{ active: String(category) === String(ch.id) }"
                :to="{ path: '/companies', query: { category: ch.id } }"
              >{{ ch.name }}</NuxtLink>
            </template>
          </div>
          <div class="filter-tools" style="margin-top:12px">
            <form class="kw" @submit.prevent="applyFilter">
              <input v-model="draftQ" placeholder="关键词：企业名称 / 主营">
              <button type="submit">搜索</button>
            </form>
            <button v-if="hasFilter" class="btn-soft" type="button" @click="resetFilter">重置</button>
          </div>
        </div>

        <section v-if="showFloors && featured.length" style="margin-bottom:20px">
          <div class="sec-head">
            <div>
              <h2>优选企业</h2>
              <p class="sub">平台精选入驻厂家，品质与服务更有保障</p>
            </div>
          </div>
          <div class="company-grid">
            <NuxtLink
              v-for="(c, i) in featured"
              :key="'f'+c.id"
              class="company"
              :to="`/companies/${c.slug || c.id}`"
            >
              <div class="c-top">
                <div class="photo"><img :src="logoOf(c, Number(i))" :alt="c.name" loading="lazy"></div>
                <div class="c-info">
                  <b>
                    <span>{{ c.name }}</span>
                    <MemberBadge v-if="c.member_badge" :grade="c.member_grade" :label="c.member_badge" />
                    <i v-if="c.is_certified || c.vip" class="vip">名企</i>
                  </b>
                  <div class="sub">{{ c.area || '地区不限' }} · {{ c.scale || '规模未填' }}</div>
                  <div class="tags">
                    <span v-for="(t, ti) in tagsOf(c)" :key="ti">{{ t }}</span>
                  </div>
                </div>
              </div>
              <p class="desc">{{ c.intro || c.business || '暂无企业简介' }}</p>
              <span class="shop">进入店铺</span>
            </NuxtLink>
          </div>
        </section>

        <template v-if="showFloors">
          <PortalFloor
            v-for="(floor, idx) in floors"
            :key="floor.category?.id || idx"
            :floor="floor"
            base-path="/companies"
          />
        </template>

        <div class="sec-head" style="margin-top:8px">
          <div>
            <h2>{{ listTitle }}</h2>
            <p class="sub">
              <template v-if="meta?.total != null">共 {{ meta.total }} 家企业</template>
              <template v-else>浏览入驻企业，进入主页了解更多</template>
            </p>
          </div>
        </div>

        <div class="company-grid" :class="{ 'is-loading': pending }">
          <NuxtLink
            v-for="(c, i) in items"
            :key="c.id"
            class="company"
            :to="`/companies/${c.slug || c.id}`"
          >
            <div class="c-top">
              <div class="photo"><img :src="logoOf(c, Number(c.id || i))" :alt="c.name" loading="lazy"></div>
              <div class="c-info">
                <b>
                  <span>{{ c.name }}</span>
                  <MemberBadge v-if="c.member_badge" :grade="c.member_grade" :label="c.member_badge" />
                  <i v-if="c.is_certified || c.vip" class="vip">名企</i>
                </b>
                <div class="sub">{{ c.area || c.address || '地区不限' }}</div>
                <div class="tags">
                  <span v-for="(t, ti) in tagsOf(c)" :key="ti">{{ t }}</span>
                </div>
              </div>
            </div>
            <p class="desc">主营：{{ c.business || c.intro || '-' }}</p>
            <span class="shop">进入店铺</span>
          </NuxtLink>
        </div>
        <div v-if="showFloors && !hasFilter" class="company hire company-hire-banner">
          <span class="hi">
            <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
          </span>
          <b>广告位 · 虚位以待</b>
          <p>名企推荐黄金展位招租<br>VIP 会员优先展示</p>
          <NuxtLink to="/ads?place=company_top&from=elite">立即咨询 →</NuxtLink>
        </div>
        <p v-if="!items.length" class="empty">暂无企业</p>
        <div class="pager-wrap">
          <AppPager :meta="meta" :pending="pending" />
        </div>
      </div>
    </div>
  </div>
</template>
