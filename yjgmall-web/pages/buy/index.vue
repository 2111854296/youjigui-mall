<script setup lang="ts">
definePageMeta({ layout: 'channel' })
useHead({ title: '供需对接' })

const route = useRoute()
const router = useRouter()
const config = useRuntimeConfig()
const page = computed(() => Number(route.query.page || 1))
const category = computed(() => route.query.category as string | undefined)
const q = computed(() => (route.query.q as string) || '')
const area = computed(() => (route.query.area as string) || '')
const urgent = computed(() => route.query.urgent === '1')
const sort = computed(() => (route.query.sort as string) || 'refresh')

const { data } = await useAsyncData(
  () => `buy-${page.value}-${category.value}-${q.value}-${area.value}-${urgent.value}-${sort.value}`,
  () =>
    $fetch(`${config.public.apiBase}/buy`, {
      query: {
        page: page.value,
        category: category.value,
        q: q.value || undefined,
        area: area.value || undefined,
        urgent: urgent.value ? 1 : undefined,
        sort: sort.value,
      },
    }),
  { watch: [page, category, q, area, urgent, sort] },
)

const { data: sellRes } = await useAsyncData('buy-page-sell-latest', () =>
  $fetch(`${config.public.apiBase}/sell`, { query: { page: 1, per_page: 32 } }),
)

const items = computed(() => (data.value as any)?.data || [])
const meta = computed(() => (data.value as any)?.meta || null)
const categories = computed(() => (data.value as any)?.categories || [])
const areas = computed(() => (data.value as any)?.filters?.areas || [])
const floors = computed(() => (data.value as any)?.floors || [])
const urgentItems = computed(() => (data.value as any)?.urgent || [])
const sellLatest = computed(() => (sellRes.value as any)?.data || [])

const draftQ = ref(q.value)
const draftArea = ref(area.value)
const draftSort = ref(sort.value)
const draftUrgent = ref(urgent.value)
watch(q, (v) => { draftQ.value = v })
watch(area, (v) => { draftArea.value = v })
watch(sort, (v) => { draftSort.value = v })
watch(urgent, (v) => { draftUrgent.value = v })

const SAFE_THUMB = '/img/logo-yjg.png'

function thumbOf(item: any, _i = 0) {
  const t = String(item?.thumb || '')
  if (!t || /\/img\/products\/p\d+/i.test(t) || /\.svg($|\?)/i.test(t) || /placehold/i.test(t)) return SAFE_THUMB
  return t
}

function apply() {
  router.push({
    path: '/buy',
    query: {
      category: category.value,
      q: draftQ.value || undefined,
      area: draftArea.value || undefined,
      sort: draftSort.value || undefined,
      urgent: draftUrgent.value ? '1' : undefined,
      page: undefined,
    },
  })
}

function patchCat(id?: string | number) {
  router.push({
    path: '/buy',
    query: {
      category: id != null ? String(id) : undefined,
      urgent: urgent.value ? '1' : undefined,
    },
  })
}

const showFloors = computed(() => !category.value && !q.value && !area.value && !urgent.value && page.value === 1)
</script>

<template>
  <div class="ch-portal">
    <div class="crumb-row">
      <div class="container">
        <div class="crumb-tabs">
          <NuxtLink to="/buy" :class="{ on: !urgent && !category && !q }">全部求购</NuxtLink>
          <NuxtLink :to="{ path: '/buy', query: { urgent: '1' } }" :class="{ on: urgent }">紧急采购</NuxtLink>
          <NuxtLink to="/sell">最新供应</NuxtLink>
          <NuxtLink to="/member/purchases/create">发布求购</NuxtLink>
          <NuxtLink to="/member/supplies/create">发布供应</NuxtLink>
        </div>
      </div>
    </div>

    <div class="tip-bar buy">
      <div class="container">
        <b>供求提示：</b>
        最新供应与求购实时更新；紧急求购优先展示，供应商可直接报价对接。
      </div>
    </div>

    <div class="section">
      <div class="container">
        <div class="sec-head">
          <div>
            <h2>供需对接</h2>
            <p class="sub">供应与求购双通道，高效对接有机硅产业链上下游</p>
          </div>
          <NuxtLink class="more-link" to="/member/purchases/create">免费发布求购 ›</NuxtLink>
        </div>

        <!-- 首页同款双栏：供应 + 求购 -->
        <div v-if="showFloors" class="supply-grid" style="margin-bottom:18px">
          <div class="supply-box">
            <div class="s-head">
              <b>最新供应</b>
              <NuxtLink class="more-link" to="/sell">更多 ›</NuxtLink>
            </div>
            <RollList v-if="sellLatest.length" :rows="sellLatest" :page-size="8">
              <template #default="{ row }">
                <NuxtLink class="t" :to="`/sell/${row.id}`">{{ row.title }}</NuxtLink>
                <span class="tag">{{ row.area || '不限' }}</span>
              </template>
            </RollList>
            <ul v-else>
              <li><span class="t" style="color:var(--ink-3)">暂无供应</span></li>
            </ul>
          </div>
          <div class="supply-box buy">
            <div class="s-head">
              <b>最新求购</b>
              <NuxtLink class="more-link" to="/buy">更多 ›</NuxtLink>
            </div>
            <RollList v-if="items.length" :rows="items" :page-size="8">
              <template #default="{ row }">
                <NuxtLink class="t" :to="`/buy/${row.id}`">{{ row.title }}</NuxtLink>
                <span class="tag">{{ row.area || '不限' }}</span>
              </template>
            </RollList>
            <ul v-else>
              <li><span class="t" style="color:var(--ink-3)">暂无求购</span></li>
            </ul>
          </div>
        </div>

        <div v-if="showFloors && urgentItems.length" class="blk" style="margin-bottom:16px">
          <div class="b-head">
            <b class="accent-up">紧急采购</b>
            <NuxtLink class="more-link" :to="{ path: '/buy', query: { urgent: '1' } }">更多 ›</NuxtLink>
          </div>
          <div class="goods-grid">
            <NuxtLink
              v-for="(item, i) in urgentItems.slice(0, 4)"
              :key="'u'+item.id"
              class="g-card"
              :to="`/buy/${item.id}`"
            >
              <div class="g-img"><img :src="thumbOf(item, Number(i))" :alt="item.title" loading="lazy"></div>
              <div class="g-name"><span class="tag urg">急</span>{{ item.title }}</div>
              <div class="g-price">{{ item.budget ? `预算¥${item.budget}` : '预算面议' }}</div>
              <div class="g-meta">
                <span>{{ item.quantity || '-' }} {{ item.unit || '' }}</span>
                <span>{{ item.area || '地区不限' }}</span>
              </div>
            </NuxtLink>
          </div>
        </div>

        <template v-if="showFloors">
          <PortalFloor
            v-for="(floor, idx) in floors"
            :key="floor.category?.id || idx"
            :floor="floor"
            base-path="/buy"
          />
        </template>

        <div class="blk" style="margin-bottom:14px;margin-top:8px">
          <div class="b-head">
            <b>筛选条件</b>
            <span class="hint">分类 / 地区 / 排序</span>
          </div>
          <div class="chips" style="margin-bottom:10px">
            <button type="button" class="chip" :class="{ active: !category && !urgent }" @click="router.push('/buy')">全部</button>
            <NuxtLink class="chip danger" :class="{ active: urgent }" :to="{ path: '/buy', query: { urgent: '1' } }">紧急采购</NuxtLink>
            <template v-for="c in categories" :key="c.id">
              <button
                type="button"
                class="chip"
                :class="{ active: String(category) === String(c.id) }"
                @click="patchCat(c.id)"
              >{{ c.name }}</button>
              <button
                v-for="ch in c.children || []"
                :key="ch.id"
                type="button"
                class="chip"
                :class="{ active: String(category) === String(ch.id) }"
                @click="patchCat(ch.id)"
              >{{ ch.name }}</button>
            </template>
          </div>
          <form class="filter-tools" @submit.prevent="apply">
            <div class="kw">
              <input v-model="draftQ" placeholder="搜采购关键词">
              <button type="submit">搜索</button>
            </div>
            <select v-model="draftArea">
              <option value="">全部地区</option>
              <option v-for="a in areas" :key="a" :value="a">{{ a }}</option>
            </select>
            <select v-model="draftSort">
              <option value="refresh">最新刷新</option>
              <option value="budget_desc">预算高到低</option>
              <option value="hits">人气</option>
            </select>
            <label class="chk"><input v-model="draftUrgent" type="checkbox"> 仅紧急</label>
            <button class="btn-go" type="submit">筛选</button>
          </form>
        </div>

        <div class="list-panel">
          <div class="lp-hd">
            <h2>{{ showFloors ? '最新采购' : '采购列表' }}</h2>
            <span v-if="meta?.total != null" class="meta">共 {{ meta.total }} 条</span>
          </div>

          <NuxtLink
            v-for="(item, i) in items"
            :key="item.id"
            class="lp-item"
            :to="`/buy/${item.id}`"
          >
            <div class="thumb">
              <img :src="thumbOf(item, Number(i))" :alt="item.title" loading="lazy">
            </div>
            <div class="body">
              <div class="tt">
                <span v-if="item.is_urgent" class="tag urg">急</span>{{ item.title }}
              </div>
              <div class="sum">
                {{ item.quantity || '-' }} {{ item.unit || '' }}
                · {{ item.user?.company_name || item.user?.name || '采购商' }}
              </div>
              <div class="foot">
                <span>{{ item.area || '地区不限' }}</span>
                <span>{{ item.refreshed_at?.slice(0, 10) || '-' }}</span>
                <span class="price" :class="{ ask: !item.budget }">{{ item.budget ? `¥${item.budget}` : '预算面议' }}</span>
              </div>
            </div>
          </NuxtLink>
          <p v-if="!items.length" class="empty">暂无采购信息</p>
          <div class="pager-wrap">
            <AppPager :meta="meta" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
