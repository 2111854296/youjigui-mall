<script setup lang="ts">
definePageMeta({ layout: 'channel' })
useHead({ title: '图片广告中心' })

const route = useRoute()
const config = useRuntimeConfig()
const { api } = useApi()
const { user } = useAuth()
const msg = ref('')
const err = ref('')
const loading = ref(false)
const form = reactive({
  place_code: '',
  title: '',
  image: '',
  contact_name: '',
  contact_phone: '',
  remark: '',
})
const hoverCode = ref('')

const { data } = await useAsyncData('ad-positions', () =>
  $fetch(`${config.public.apiBase}/ad-positions`),
)
const positions = computed(() => (data.value as any)?.data || [])
const openAreas = reactive<Record<string, boolean>>({})

function areaTitle(p: { name?: string; code?: string }) {
  const name = String(p?.name || '')
  const dotted = name.match(/^(.*)·第\d+[位张]$/)
  if (dotted) return dotted[1]
  const pageRank = name.match(/^(.*?)第\d+页/)
  if (pageRank && pageRank[1] && /^rank_/.test(String(p?.code || ''))) return `${pageRank[1]}列表`
  return ''
}

const tableRows = computed(() => {
  const list = positions.value as any[]
  const buckets = new Map<string, any[]>()
  for (const p of list) {
    const title = areaTitle(p)
    if (!title) continue
    if (!buckets.has(title)) buckets.set(title, [])
    buckets.get(title)!.push(p)
  }
  const emitted = new Set<string>()
  const rows: Array<{ type: 'single' | 'head' | 'child'; key: string; place?: any; title?: string; open?: boolean }> = []
  for (const p of list) {
    const title = areaTitle(p)
    const mates = title ? buckets.get(title) || [] : []
    if (!title || mates.length < 2) {
      rows.push({ type: 'single', key: p.code, place: p })
      continue
    }
    if (emitted.has(title)) continue
    emitted.add(title)
    const open = !!openAreas[title]
    rows.push({ type: 'head', key: `g:${title}`, title, open })
    if (open) {
      for (const child of mates) rows.push({ type: 'child', key: child.code, place: child })
    }
  }
  return rows
})

function toggleArea(title: string) {
  openAreas[title] = !openAreas[title]
}

const followEl = ref<HTMLElement | null>(null)

function pickPlace(code: string) {
  form.place_code = code
  nextTick(() => {
    const el = followEl.value
    if (!el) return
    const rect = el.getBoundingClientRect()
    const seen = rect.top < window.innerHeight - 80 && rect.bottom > 80
    if (!seen) el.scrollIntoView({ behavior: 'smooth', block: 'start' })
  })
}

type AdSpot = { page: string; kind: string; hint: string; where: string; slot?: number; cat?: string }

function spotOf(p: { code?: string; name?: string; size?: string }): AdSpot {
  const code = String(p.code || '')
  const name = String(p.name || '')
  const size = String(p.size || '')
  const dims = /^\s*(\d+)\s*[x×]\s*(\d+)/i.exec(size)
  const isTall = !!(dims && Number(dims[2]) > Number(dims[1]) + 40)
  if (/^home_top/.test(code) || /头图|头牌/.test(name)) {
    return { page: '首页', kind: 'top', hint: '页头区域的头牌位', where: '首页最上方页头' }
  }
  if (code === 'home_banner' || /首页轮播|首页横幅/.test(name)) {
    return { page: '首页', kind: 'banner', hint: '分类栏与右侧栏中间的轮播大图', where: '首页正中间：左边是分类，右边是「您好，欢迎回来」' }
  }
  if (code === 'home_right' || /首页右侧/.test(name)) {
    return { page: '首页', kind: 'right', hint: '首页轮播右侧的竖向展位', where: '首页轮播大图的右侧' }
  }
  if (code === 'home_portal' || /分类展位/.test(name)) {
    return { page: '首页', kind: 'portal', hint: '首页轮播下面那三个小图', where: '首页正中间，轮播大图下面、品牌推荐下面的三个小图' }
  }
  if (code.startsWith('sell_elite') || code.startsWith('elite_')) {
    const parts = name.split('·').map((s) => s.trim()).filter(Boolean)
    const slot = Number((/第(\d+)/.exec(parts[parts.length - 1] || '') || [])[1] || (/_(\d)$/.exec(code) || [])[1] || 0)
    const cat = parts.length >= 3 ? parts[1] : ''
    const where = cat
      ? `产品分类「${cat}」推荐供应，从左往右第 ${slot} 张广告图`
      : (slot ? `产品分类页「推荐供应」这一排，从左往右第 ${slot} 张广告图` : '产品分类页「推荐供应」这一排的广告图')
    return { page: '产品分类', kind: 'elite', hint: '推荐供应里可购买的广告图', where, slot }
  }
  if (code.startsWith('floor_')) {
    const parts = name.split('·').map((s) => s.trim()).filter(Boolean)
    const slot = Number((/第(\d+)/.exec(parts[parts.length - 1] || '') || [])[1] || (/_(\d)$/.exec(code) || [])[1] || 0)
    const parent = parts.length >= 2 ? parts[1].replace(/第\d+位/, '').trim() : '该品类'
    const child = parts.length >= 4 ? parts[2] : ''
    const where = child
      ? `首页材料产品「${parent}」的「${child}」，从左往右第 ${slot} 个商品`
      : (slot ? `首页材料产品「${parent}」这一排，从左往右第 ${slot} 个商品` : `首页材料产品「${parent}」这一排的 5 个商品位`)
    return { page: '首页', kind: 'floor', hint: '材料产品板块里可购买的推广商品位', where, slot }
  }
  if (/排名|首条/.test(name) || code.startsWith('rank_')) {
    const pageNo = code === 'rank_sell' ? '1' : ((/^rank_[a-z]+_(\d)$/.exec(code) || [])[1] || '')
    const page = /求购|buy/.test(code + name) ? '求购列表' : /公司|企业|company/.test(code + name) ? '名企推荐' : '供应列表'
    const where = pageNo && page === '供应列表' ? `供应列表第 ${pageNo} 页，从上往下第一条` : '列表靠前的推广位'
    return { page, kind: 'rank', hint: `${where}，标成推广，可付费购买`, where }
  }
  if (code.startsWith('quote_') || /^行情详情·/.test(name)) {
    const parts = name.split('·').map((s) => s.trim()).filter(Boolean)
    const slot = Number((/第(\d+)/.exec(parts[parts.length - 1] || '') || [])[1] || (/_(\d)$/.exec(code) || [])[1] || 0)
    const cat = parts.length >= 2 ? parts[1] : '该品种'
    const where = slot
      ? `价格行情「${cat}」详情页右侧，其它品种下面从上往下第 ${slot} 个广告位`
      : `价格行情「${cat}」详情页右侧，其它品种下面的三个广告位`
    return { page: '价格行情', kind: 'quote-ad', hint: '行情详情页右侧对应品种的产品广告位', where, slot, cat }
  }
  if (code.startsWith('sample_floor_') || /^样品专区·/.test(name)) {
    const parts = name.split('·').map((s) => s.trim()).filter(Boolean)
    const slot = Number((/第(\d+)/.exec(parts[parts.length - 1] || '') || [])[1] || (/_(\d)$/.exec(code) || [])[1] || 0)
    const cat = parts.length >= 2 ? parts[1] : '该分类'
    const where = slot
      ? `样品专区「${cat}」这一排，从左往右第 ${slot} 个商品。这一排在顶部横幅下面`
      : `样品专区「${cat}」这一排的 5 个商品位，在顶部横幅下面`
    return { page: '样品专区', kind: 'sample-floor', hint: '样品分类楼层里可购买的推广商品位', where, slot, cat }
  }
  if (code === 'sample_top' || /样品/.test(name)) {
    return { page: '样品专区', kind: 'sample', hint: '样品专区页标题下面的通栏横幅', where: '样品专区页标题下面的通栏横幅。横幅下面是各分类楼层，样品分类在更下面、商品列表左边' }
  }
  if (code === 'mall_top' || /商城/.test(name)) {
    return { page: '优质产品', kind: 'channel', hint: '商城页顶部横幅轮播', where: '优质产品页，标题下面、商品列表上面的横幅' }
  }
  if (code === 'company_top' || /企业库/.test(name)) {
    return { page: '名企推荐', kind: 'channel', hint: '企业库页顶部横幅轮播', where: '名企推荐页，标题下面、企业卡片上面的横幅' }
  }
  if (code === 'news_top' || /资讯/.test(name)) {
    return { page: '资讯', kind: 'channel', hint: '资讯页顶部横幅轮播', where: '资讯页左上角，右边挨着行情卡片' }
  }
  if (code === 'sell_right' || code === 'buy_right' || /右侧/.test(name) || isTall) {
    const page = /求购|buy/.test(code + name) ? '求购' : '供应'
    return { page, kind: 'side', hint: `${page}详情页右侧竖向展位`, where: `打开任意一条${page}，页面右侧：卖家信息下面、温馨提示上面` }
  }
  if (/供应/.test(name) || code === 'sell_top') {
    return { page: '供应', kind: 'channel', hint: '供应页顶部横幅轮播', where: '供应页标题下面的横幅' }
  }
  if (code === 'footer' || /页脚|底部/.test(name)) {
    return { page: '全站', kind: 'footer', hint: '页面底部通栏', where: '每个页面最底部' }
  }
  return { page: '对应页面', kind: 'channel', hint: '该广告位在页面中的大致区域', where: '红框圈出的区域' }
}

const hoverPlace = computed(() => positions.value.find((p: any) => p.code === hoverCode.value) || null)
const selectedPlace = computed(() => positions.value.find((p: any) => p.code === form.place_code) || null)
const previewPlace = computed(() => hoverPlace.value || selectedPlace.value)
const previewSpot = computed(() => (previewPlace.value ? spotOf(previewPlace.value) : null))
const homeCats = [
  { name: '有机硅单体/中间体', on: true },
  { name: '硅油' },
  { name: '硅橡胶' },
  { name: '有机硅助剂/填料' },
  { name: '机械设备' },
  { name: '硅烷' },
  { name: '胶粘剂' },
  { name: '硅树脂' },
]
const zoneLabel = computed(() => `${previewPlace.value?.name || '广告'}广告位`)
const channelTabs = computed(() => {
  const code = previewPlace.value?.code || ''
  if (code === 'company_top') return ['全部企业', '有机硅单体', '硅油', '硅橡胶', '企业入驻']
  if (code === 'news_top') return ['资讯首页', '行业动态', '价格解读', '市场提示']
  return ['全部商品', '新品上市', '热销优选', '今日特价']
})

const bookings = ref<any[]>([])
async function loadBookings() {
  if (!user.value) {
    bookings.value = []
    return
  }
  try {
    const res = await api('/member/ad-bookings')
    bookings.value = (res as any)?.data || []
  } catch {
    bookings.value = []
  }
}

const fromElite = computed(() =>
  route.query.from === 'elite' || route.query.place === 'elite' || route.query.place === 'company_top',
)

watch(positions, (list) => {
  if (!list.length) return
  if (form.place_code) return
  const q = String(route.query.place || '')
  let hit = q && q !== 'elite'
    ? list.find((p: any) => p.code === q)
    : null
  if (!hit && fromElite.value) {
    hit = list.find((p: any) => p.code === 'company_top')
      || list.find((p: any) => /company|elite|名企/i.test(String(p.code) + String(p.name)))
  }
  form.place_code = hit?.code || list[0].code
  const title = areaTitle(list.find((p: any) => p.code === form.place_code) || {})
  if (title) openAreas[title] = true
}, { immediate: true })

watch(user, (u) => {
  if (!u) return
  if (!form.contact_name) form.contact_name = u.name || u.username || ''
  if (!form.contact_phone) form.contact_phone = u.mobile || ''
}, { immediate: true })

onMounted(() => {
  loadBookings()
  if (fromElite.value) {
    form.title = form.title || '首页名企推荐位招租'
    form.remark = form.remark || '咨询首页名企推荐广告位'
  }
})

async function submit() {
  err.value = ''
  msg.value = ''
  if (!user.value) {
    return navigateTo({ path: '/login', query: { redirect: route.fullPath } })
  }
  if (!(user.value as any).is_enterprise && !(user.value as any).capabilities?.ads) {
    await appFail('广告预定仅对企业会员开放，请先升级为企业会员', '需要企业会员')
    return navigateTo('/member/grade')
  }
  if (!form.place_code || !form.title.trim()) {
    await appFail('请填写广告位与投放标题', '提交失败')
    return
  }
  if (!String(form.contact_phone || '').trim()) {
    await appFail('请填写联系电话', '提交失败')
    return
  }
  loading.value = true
  try {
    await api('/member/ad-bookings', { method: 'POST', body: { ...form } })
    await appOk('广告预定已提交，等待审核。', '提交成功')
    form.title = ''
    form.image = ''
    form.remark = ''
    await loadBookings()
  } catch (e: any) {
    await appFail(apiErrorText(e, '提交失败'), '提交失败')
  } finally {
    loading.value = false
  }
}

function statusLabel(s: string) {
  return ({ pending: '待审核', approved: '已通过', rejected: '已拒绝' } as Record<string, string>)[s] || s
}
</script>

<template>
  <div class="ch-portal">
    <div class="crumb-row">
      <div class="container">
        <div class="crumb-tabs">
          <NuxtLink to="/">首页</NuxtLink>
          <span class="on">图片广告中心</span>
          <NuxtLink to="/member/spread">搜索排名中心</NuxtLink>
        </div>
      </div>
    </div>

    <div v-if="fromElite" class="tip-bar">
      <div class="container">
        <b>名企推荐：</b>
        您正在咨询首页名企推荐展位 · 提交后运营将与您联系确认档期与报价
      </div>
    </div>

    <div class="section">
      <div class="container">
        <div class="ads-grid">
          <div class="dt-card">
            <div class="dt-h">广告位一览</div>
            <table class="ads-table">
              <thead>
                <tr><th>广告位</th><th>建议尺寸</th><th>在投</th></tr>
              </thead>
              <tbody>
                <tr
              v-for="row in tableRows"
              :key="row.key"
              :class="{
                fold: row.type === 'head',
                open: row.type === 'head' && row.open,
                on: row.place && form.place_code === row.place.code,
                hot: row.place && hoverCode === row.place.code,
              }"
              @click="row.type === 'head' ? toggleArea(row.title || '') : pickPlace(row.place.code)"
              @mouseenter="row.place && (hoverCode = row.place.code)"
              @mouseleave="hoverCode = ''"
            >
                  <td :colspan="row.type === 'head' ? 3 : 1">
                    <template v-if="row.type === 'head'">{{ row.title }}<span class="caret">{{ row.open ? '▲' : '▼' }}</span></template>
                    <template v-else>{{ row.place.name }}</template>
                  </td>
                  <template v-if="row.type !== 'head'">
                    <td>{{ row.place.size || '--' }}</td>
                    <td>{{ row.place.active_count ?? 0 }}</td>
                  </template>
                </tr>
                <tr v-if="!positions.length">
                  <td colspan="3" class="empty" style="padding:16px 0">暂无广告位配置</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div ref="followEl" class="dt-card ads-follow">
            <div v-if="previewPlace && previewSpot" class="ad-preview">
              <div class="ad-preview-hd">广告位置示意图</div>
              <div class="ad-preview-name">
                {{ previewPlace.name }}<span v-if="previewPlace.size"> · {{ previewPlace.size }}</span>
              </div>
              <div class="page-sk" aria-hidden="true">
                <div v-if="previewSpot.kind === 'banner' || previewSpot.kind === 'portal'" class="mock-home">
                  <aside class="mh-cats">
                    <b>全部产品分类</b>
                    <span v-for="cat in homeCats" :key="cat.name" :class="{ on: cat.on }">{{ cat.name }}</span>
                  </aside>
                  <div class="mh-mid">
                    <div :class="{ mark: previewSpot.kind === 'banner' }">
                      <div class="mh-zone banner">
                        <span class="arr">‹</span>
                        <b>{{ previewSpot.kind === 'banner' ? zoneLabel : '首页轮播广告位' }}</b>
                        <span class="arr">›</span>
                        <span class="dots"><i class="on" /><i /><i /><i /><i /></span>
                      </div>
                    </div>
                    <div class="mh-detail">
                      <div class="mh-title">有机硅单体/中间体</div>
                      <div class="mh-line"><em>品牌推荐：</em><i /><i /></div>
                      <div class="mh-cards">
                        <div v-for="n in 3" :key="n" class="mh-card" :class="{ mark: previewSpot.kind === 'portal' }">
                          <b v-if="previewSpot.kind === 'portal'">广告图</b>
                        </div>
                      </div>
                      <div class="mh-line"><em>子分类：</em><i /></div>
                      <div class="mh-line"><em>热门供应</em><i /></div>
                    </div>
                  </div>
                  <aside class="mh-rail">
                    <div class="mh-hello">
                      <b>您好，欢迎回来</b>
                      <p>进入商务中心管理供应、采购与企业信息</p>
                      <div class="mh-btns"><span>商务中心</span><span class="ghost">发供应</span></div>
                      <div class="mh-pub"><b>免费发布供应 / 求购</b><small>直达数万行业买家</small></div>
                    </div>
                    <div class="mh-notice">
                      <b>网站公告 <small>更多</small></b>
                      <span>如何让您发布的信息排名更靠前</span>
                      <span>忘记了登录名或密码该如何找回</span>
                      <span>联系方式变更怎么办？</span>
                      <span>VIP会员可享首页推荐位优先展示</span>
                    </div>
                    <div class="mh-stats">
                      <div><b>281+</b><small>入驻企业</small></div>
                      <div><b>864+</b><small>硅橡胶产品</small></div>
                      <div><b>8</b><small>大产品品类</small></div>
                    </div>
                  </aside>
                </div>
                <div v-else-if="previewSpot.kind === 'sample'" class="mock-sample">
                  <div class="mp-tabs">
                    <span class="on">全部商品</span>
                    <span>新品上市</span>
                    <span>热销优选</span>
                    <span>今日特价</span>
                  </div>
                  <div class="mp-title">样品专区</div>
                  <div class="mark">
                    <div class="mh-zone banner slim">
                      <span class="arr">‹</span>
                      <b>样品专区轮播广告位</b>
                      <span class="arr">›</span>
                      <span class="dots"><i class="on" /><i /><i /><i /></span>
                    </div>
                  </div>
                  <div class="ss-hot"><b>样品推荐</b><span>DMC</span><span>107胶</span><span>硅橡胶</span></div>
                  <div class="ss-floors">
                    <div v-for="name in ['有机硅单体（样品）', '硅油（样品）']" :key="name" class="ss-floor">
                      <aside>{{ name }}</aside>
                      <div class="ss-cards"><i v-for="n in 5" :key="name + n" /></div>
                    </div>
                  </div>
                  <div class="ss-list">
                    <aside>
                      <b>样品分类</b>
                      <span class="on">全部样品</span>
                      <span>硅油</span>
                      <span>硅橡胶</span>
                    </aside>
                    <div class="ss-grid"><i v-for="n in 4" :key="'g' + n" /></div>
                  </div>
                </div>
                <div v-else-if="previewSpot.kind === 'sample-floor'" class="mock-sfloor">
                  <div class="sf-crumb">样品专区 / {{ previewSpot.cat || '分类' }}</div>
                  <div class="sf-row">
                    <aside>
                      <b>{{ previewSpot.cat || '分类' }}（样品）</b>
                      <span>DMC</span>
                      <span>107胶</span>
                      <span>生胶</span>
                      <em>查看更多</em>
                    </aside>
                    <div class="sf-cards">
                      <div v-for="n in 5" :key="n" class="sf-card" :class="{ mark: previewSpot.slot === n }">
                        <i />
                        <b>{{ previewSpot.slot === n ? '推广' : '样品' }}</b>
                        <small>第{{ n }}位</small>
                      </div>
                    </div>
                  </div>
                </div>
                <div v-else-if="previewSpot.kind === 'quote-ad'" class="mock-quote">
                  <div class="mq-crumb">首页 / 价格行情 / {{ previewSpot.cat || '品种' }}</div>
                  <div class="mq-body">
                    <div class="mq-main">
                      <div class="mq-hero">
                        <b>{{ previewSpot.cat || '品种' }}</b>
                        <span>均价</span>
                      </div>
                      <div class="mq-chart">近 7 日价格走势</div>
                    </div>
                    <aside class="mq-side">
                      <div class="mq-others">
                        <b>其它品种</b>
                        <span>相关品种</span>
                        <span>相关品种</span>
                      </div>
                      <div v-for="n in 3" :key="n" class="mq-slot" :class="{ mark: previewSpot.slot === n }">
                        <b>广告位</b>
                        <small>{{ previewSpot.cat || '品种' }} · 第{{ n }}位</small>
                      </div>
                    </aside>
                  </div>
                </div>
                <div v-else-if="previewSpot.kind === 'channel'" class="mock-page">
                  <div class="mp-tabs">
                    <span v-for="(tab, i) in channelTabs" :key="tab" :class="{ on: i === 0 }">{{ tab }}</span>
                  </div>
                  <div class="mp-title">{{ previewSpot.page }}</div>
                  <div v-if="previewPlace.code === 'news_top'" class="news-top">
                    <div class="mark">
                      <div class="mh-zone banner slim">
                        <span class="arr">‹</span>
                        <b>{{ zoneLabel }}</b>
                        <span class="arr">›</span>
                        <span class="dots"><i class="on" /><i /><i /></span>
                      </div>
                    </div>
                    <div class="quote-sk">
                      <b>DMC</b>
                      <em>市场报价</em>
                      <strong>——</strong>
                      <small>切换品种</small>
                    </div>
                  </div>
                  <div v-else class="mark">
                    <div class="mh-zone banner slim">
                      <span class="arr">‹</span>
                      <b>{{ zoneLabel }}</b>
                      <span class="arr">›</span>
                      <span class="dots"><i class="on" /><i /><i /><i /></span>
                    </div>
                  </div>
                  <div v-if="previewPlace.code === 'company_top'" class="co-row">
                    <div v-for="name in ['硅创科技', '强力化工', '有机硅厂']" :key="name" class="co-card">
                      <i /><b>{{ name }}</b><small>入驻企业</small>
                    </div>
                  </div>
                  <div v-else-if="previewPlace.code === 'news_top'" class="news-list">
                    <div v-for="title in ['DMC 市场周报', '硅油价格解读', '下游采购提示']" :key="title" class="news-row">
                      <i /><div><b>{{ title }}</b><small>行业资讯</small></div>
                    </div>
                  </div>
                  <div v-else class="mc-grid">
                    <div v-for="name in ['DMC', '107胶', '硅橡胶', '白炭黑']" :key="name" class="mc-card">
                      <i /><b>{{ name }}</b>
                    </div>
                  </div>
                </div>
                <div v-else-if="previewSpot.kind === 'side'" class="mock-side">
                  <div class="ms-crumb">首页 / 产品分类 / <b>{{ previewSpot.page }}详情</b></div>
                  <div class="ms-body">
                    <div class="ms-main">
                      <div class="ms-gallery">
                        <i class="ms-photo" />
                        <span /><span /><span />
                      </div>
                      <div class="ms-info">
                        <b class="ms-name">二甲基硅油</b>
                        <span class="ms-meta">硅油 · 浏览 128</span>
                        <span class="ms-price">¥12,800<small>/吨</small></span>
                        <ul class="ms-kv">
                          <li><em>起订量</em>1吨</li>
                          <li><em>供货量</em>现货</li>
                          <li><em>发货期</em>3天</li>
                          <li><em>地区</em>山东</li>
                        </ul>
                        <div class="ms-acts"><i>立即询盘</i><i class="soft">收藏</i></div>
                      </div>
                    </div>
                    <aside class="ms-rail">
                      <div class="ms-seller">
                        <b>卖家信息</b>
                        <strong>会员商家</strong>
                        <p><em>联系人</em>——</p>
                        <p><em>地区</em>山东</p>
                        <p><em>手机</em>登录后可见</p>
                        <span>进入旺铺</span>
                      </div>
                      <div class="mark">
                        <div class="mh-zone banner tall">
                          <b>{{ zoneLabel }}</b>
                          <small>卖家信息下面</small>
                        </div>
                      </div>
                      <div class="ms-tip">
                        <b>温馨提示</b>
                        <span>通过平台询盘更安全</span>
                        <span>大额交易先查资质</span>
                      </div>
                    </aside>
                  </div>
                </div>
                <div v-else-if="previewSpot.kind === 'elite'" class="mock-elite">
                  <div class="el-head"><b>推荐供应</b><span>产品分类</span></div>
                  <div class="el-cards">
                    <div v-for="n in 4" :key="n" class="el-slot" :class="{ mark: !previewSpot.slot || previewSpot.slot === n }">
                      <b>第{{ n }}张</b>
                      <span>广告图</span>
                    </div>
                  </div>
                </div>
                <div v-else-if="previewSpot.kind === 'floor'" class="mock-floor">
                  <aside class="fl-side">材料<br>产品</aside>
                  <div class="fl-cards">
                    <div v-for="n in 5" :key="n" class="fl-slot" :class="{ mark: !previewSpot.slot || previewSpot.slot === n }">
                      <b>第{{ n }}位</b>
                      <span>推广商品</span>
                    </div>
                  </div>
                </div>
                <div v-else-if="previewSpot.kind === 'rank'" class="mock-rank">
                  <aside class="rk-cats">
                    <b>供应分类</b>
                    <span class="on">全部</span>
                    <span>硅油</span>
                    <span>硅橡胶</span>
                    <span>硅烷</span>
                  </aside>
                  <div class="rk-list">
                    <div class="rk-filter">
                      <span class="on">全部供应</span>
                      <span>标价货源</span>
                      <span>VIP供应</span>
                    </div>
                    <div class="mark">
                      <div class="mr-row on"><em>第1条</em>{{ zoneLabel }}</div>
                    </div>
                    <div class="mr-row"><i /><b>甲基硅油</b><small>¥9,800</small></div>
                    <div class="mr-row"><i /><b>107胶</b><small>面议</small></div>
                    <div class="mr-row"><i /><b>气相白炭黑</b><small>¥6,500</small></div>
                  </div>
                </div>
                <div v-else class="mock-page">
                  <div class="mp-title">{{ previewSpot.page }}</div>
                  <div class="mark">
                    <div class="mh-zone banner slim">
                      <span class="arr">‹</span>
                      <b>{{ zoneLabel }}</b>
                      <span class="arr">›</span>
                    </div>
                  </div>
                </div>
              </div>
              <p class="ad-preview-where">位置：{{ previewSpot.where }}</p>
            </div>
            <div class="dt-h">广告预定</div>
            <div v-if="msg" class="alert alert-success">{{ msg }}</div>
            <div v-if="err" class="alert alert-error">{{ err }}</div>
            <form class="mf-form" @submit.prevent="submit">
              <section class="mf-sec">
                <div class="mf-sec-hd">投放信息</div>
                <div class="mf-sec-bd">
                  <div class="form-row">
                    <label>广告位 <em>*</em></label>
                    <select v-model="form.place_code" required>
                      <option v-for="p in positions" :key="p.code" :value="p.code">{{ p.name }}</option>
                    </select>
                  </div>
                  <div class="form-row">
                    <label>投放标题 <em>*</em></label>
                    <input v-model="form.title" required placeholder="例如：首页名企推荐位招租">
                  </div>
                  <div class="mf-pics one" style="margin-bottom:12px">
                    <ImageUpload v-model="form.image" label="物料图片" hint="选填；有物料图更便于审核排期" />
                  </div>
                  <div class="mf-grid-2">
                    <div class="form-row">
                      <label>联系人 <em>*</em></label>
                      <input v-model="form.contact_name" required placeholder="怎么称呼您">
                    </div>
                    <div class="form-row">
                      <label>电话 <em>*</em></label>
                      <input v-model="form.contact_phone" required placeholder="方便回访的手机号">
                    </div>
                  </div>
                  <div class="form-row">
                    <label>备注</label>
                    <textarea v-model="form.remark" rows="3" placeholder="投放周期、预算等补充说明" />
                  </div>
                </div>
              </section>
              <div class="mf-actions">
                <button class="btn" type="submit" :disabled="loading">
                  {{ loading ? '提交中…' : '提交预定' }}
                </button>
                <NuxtLink v-if="!user" to="/login" class="btn btn-outline">先登录</NuxtLink>
              </div>
            </form>

            <div v-if="bookings.length" class="mf-list" style="margin-top:16px">
              <div class="list-hd">我的预定</div>
              <div v-for="b in bookings" :key="b.id" class="mf-item">
                <div class="left">
                  <div>
                    <div class="ttl">{{ b.title }}</div>
                    <div class="sub">{{ b.place_code }} · {{ statusLabel(b.status) }}</div>
                  </div>
                </div>
                <a v-if="b.image" :href="b.image" target="_blank" rel="noopener">查看物料</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.ads-grid {
  display: grid;
  grid-template-columns: 1.15fr 1fr;
  gap: 16px;
  align-items: start;
}
.ads-follow {
  position: sticky;
  top: 64px;
  z-index: 2;
  max-height: calc(100vh - 80px);
  overflow: auto;
}
.ads-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.ads-table th,
.ads-table td {
  padding: 10px 8px;
  border-bottom: 1px dashed var(--line, #e9eef6);
  text-align: left;
}
.ads-table th { color: var(--ink-3, #98a5bc); font-weight: 600; font-size: 12px; }
.ads-table tbody tr { cursor: pointer; transition: background .15s; }
.ads-table tbody tr:hover,
.ads-table tbody tr.on,
.ads-table tbody tr.hot { background: #f0f5fe; }
.ads-table tbody tr.fold td { font-weight: 800; color: #13294b; background: #f7f9fc; }
.ads-table tbody tr.fold .caret { margin-left: 6px; font-weight: 700; color: #5a6b85; font-size: 12px; }
.ad-preview {
  margin: 0 0 14px;
  padding: 12px;
  border: 1px solid #d7e4fb;
  border-radius: 10px;
  background: #f7faff;
}
.ad-preview-hd { font-size: 13px; font-weight: 700; color: #123c8f; }
.ad-preview-name { margin-top: 2px; font-size: 12px; color: #5a6b85; }
.ad-preview-where { margin: 8px 0 0; font-size: 13px; line-height: 1.5; font-weight: 700; color: #123c8f; }
.page-sk {
  margin-top: 10px;
  padding: 8px;
  border-radius: 8px;
  background: #eef3fb;
  border: 1px solid #e3ebf6;
}
.mark {
  border: 3px solid #e10600;
  border-radius: 10px;
  padding: 3px;
  background: #fff;
}
.mh-zone.banner {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 92px;
  border-radius: 8px;
  background: #2f7bff;
  color: #fff;
  font-size: 13px;
  font-weight: 800;
  letter-spacing: .04em;
}
.mh-zone.banner b { font-weight: 800; }
.mh-zone .arr {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  width: 16px;
  height: 16px;
  border-radius: 50%;
  background: rgba(255,255,255,.22);
  font-size: 12px;
  line-height: 16px;
  text-align: center;
}
.mh-zone .arr:first-child { left: 6px; }
.mh-zone .arr:nth-of-type(2) { right: 6px; }
.mh-zone .dots { position: absolute; right: 10px; bottom: 6px; display: flex; gap: 3px; }
.mh-zone .dots i { width: 4px; height: 4px; border-radius: 50%; background: rgba(255,255,255,.45); }
.mh-zone .dots i.on { width: 10px; border-radius: 4px; background: #fff; }
.mh-zone.slim { min-height: 64px; font-size: 13px; }
.mh-zone.tall { min-height: 160px; writing-mode: horizontal-tb; }
.mock-home {
  display: grid;
  grid-template-columns: 92px 1fr 118px;
  gap: 6px;
  min-height: 250px;
}
.mh-cats {
  background: linear-gradient(180deg, #1746c0, #12379a);
  color: #fff;
  border-radius: 8px;
  padding: 8px 0;
  display: flex;
  flex-direction: column;
}
.mh-cats b { font-size: 11px; padding: 0 8px 6px; }
.mh-cats span { font-size: 10px; line-height: 1.7; padding: 1px 8px; color: rgba(255,255,255,.9); }
.mh-cats span.on { background: rgba(255,255,255,.16); border-radius: 4px; }
.mh-mid { display: flex; flex-direction: column; gap: 6px; min-width: 0; }
.mh-detail { background: #fff; border-radius: 8px; padding: 6px; flex: 1; }
.mh-title { font-size: 11px; font-weight: 800; color: #1d4fd7; }
.mh-line { display: flex; align-items: center; gap: 4px; margin-top: 4px; }
.mh-line em { font-style: normal; font-size: 9px; color: #5a6b85; flex: none; }
.mh-line i { flex: 1; height: 4px; border-radius: 2px; background: repeating-linear-gradient(90deg, #d5deea 0 4px, transparent 4px 7px); }
.mh-cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 4px; margin-top: 6px; }
.mh-card { height: 36px; border-radius: 4px; background: #2f7bff; display: flex; align-items: center; justify-content: center; }
.mh-card.mark { background: #fff; color: #13294b; font-size: 11px; }
.mock-elite { display: flex; flex-direction: column; gap: 8px; }
.el-head { display: flex; justify-content: space-between; font-size: 13px; color: #13294b; }
.el-head span { color: #5a6b85; font-size: 12px; }
.el-cards { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 6px; }
.el-slot {
  min-height: 72px;
  border-radius: 6px;
  background: #f4f7fb;
  border: 1px solid var(--line);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 4px;
  font-size: 12px;
  color: #5a6b85;
}
.el-slot b { color: #13294b; }
.el-slot.mark,
.fl-slot.mark {
  border: 3px solid #e10600;
  background: #fff;
}
.mh-rail { display: flex; flex-direction: column; gap: 4px; min-width: 0; }
.mh-hello {
  background: linear-gradient(135deg, #1d4fd7, #2f6fe8 55%, #3f86f9);
  color: #fff;
  border-radius: 8px;
  padding: 8px;
}
.mh-hello b { display: block; font-size: 11px; line-height: 1.35; }
.mh-hello p { margin: 3px 0 0; font-size: 9px; color: #d6e4ff; line-height: 1.35; }
.mh-btns { display: flex; gap: 4px; margin-top: 6px; }
.mh-btns span { flex: 1; text-align: center; font-size: 10px; font-weight: 700; background: #fff; color: #1443b8; border-radius: 100px; padding: 3px 0; }
.mh-btns .ghost { background: transparent; color: #fff; border: 1px solid rgba(255,255,255,.7); }
.mh-pub { margin-top: 4px; background: rgba(255,255,255,.16); border-radius: 6px; padding: 4px 6px; text-align: center; }
.mh-pub b { font-size: 10px; }
.mh-pub small { display: block; font-size: 8px; color: #d6e4ff; }
.mh-notice { background: #fff; border-radius: 8px; padding: 6px; }
.mh-notice b { display: block; font-size: 11px; color: #13294b; margin-bottom: 2px; }
.mh-notice span { display: block; font-size: 9px; color: #5a6b85; line-height: 1.55; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.mh-notice span::before { content: "• "; color: #2468f2; font-weight: 800; }
.mh-stats { display: flex; background: #fff; border-radius: 8px; padding: 6px; }
.mh-stats div { flex: 1; text-align: center; }
.mh-stats b { display: block; font-size: 12px; color: #123c8f; }
.mh-stats small { font-size: 8px; color: #98a5bc; }
.mock-sample { display: flex; flex-direction: column; gap: 6px; }
.ss-hot { display: flex; align-items: center; gap: 8px; font-size: 10px; color: #5a6b85; }
.ss-hot b { color: #13294b; font-size: 11px; }
.ss-floors { display: flex; flex-direction: column; gap: 4px; }
.ss-floor { display: grid; grid-template-columns: 72px 1fr; gap: 4px; align-items: stretch; }
.ss-floor > aside {
  display: flex; align-items: center; justify-content: center;
  border-radius: 6px; background: linear-gradient(180deg, #1746c0, #12379a);
  color: #fff; font-size: 10px; font-weight: 700; text-align: center; padding: 6px 4px;
}
.ss-cards { display: grid; grid-template-columns: repeat(5, 1fr); gap: 4px; }
.ss-cards > i, .ss-slot {
  min-height: 36px; border-radius: 4px; background: #fff; border: 1px solid #e3ebf6;
}
.ss-slot {
  display: flex; align-items: center; justify-content: center;
  font-size: 10px; color: #5a6b85;
}
.ss-slot b { color: #13294b; font-weight: 700; }
.ss-slot.mark { border: 3px solid #e10600; }
.mock-quote { display: flex; flex-direction: column; gap: 6px; }
.mq-crumb { font-size: 10px; color: #8aa0c4; }
.mq-body { display: grid; grid-template-columns: 1.4fr 0.8fr; gap: 8px; align-items: start; }
.mq-hero {
  background: linear-gradient(135deg, #1d4fd7, #3b82f6);
  color: #fff; border-radius: 8px; padding: 10px 12px; min-height: 52px;
}
.mq-hero b { display: block; font-size: 12px; }
.mq-hero span { font-size: 10px; opacity: .85; }
.mq-chart {
  margin-top: 6px; height: 72px; border-radius: 8px; background: #fff;
  border: 1px solid #e3eaf5; color: #98a5bc; font-size: 10px;
  display: flex; align-items: flex-end; padding: 8px;
}
.mq-others {
  background: #fff; border: 1px solid #e3eaf5; border-radius: 8px; padding: 8px;
  display: flex; flex-direction: column; gap: 4px; margin-bottom: 6px;
}
.mq-others b { font-size: 11px; color: #13294b; }
.mq-others span { font-size: 10px; color: #8aa0c4; }
.mq-slot {
  background: #f7faff; border: 1px dashed #b9cbee; border-radius: 8px;
  min-height: 42px; margin-bottom: 6px; display: flex; flex-direction: column;
  align-items: center; justify-content: center; gap: 2px;
}
.mq-slot b { font-size: 12px; color: #1d4fd7; }
.mq-slot small { font-size: 9px; color: #8aa0c4; }
.mq-slot.mark { border: 3px solid #e10600; background: #fff5f5; }
.mq-slot.mark b, .mq-slot.mark small { color: #e10600; }
.mock-sfloor { display: flex; flex-direction: column; gap: 6px; }
.sf-crumb { font-size: 10px; color: #98a5bc; }
.sf-row { display: grid; grid-template-columns: 78px 1fr; gap: 6px; min-height: 132px; }
.sf-row > aside {
  display: flex; flex-direction: column; align-items: flex-start; gap: 3px;
  border-radius: 8px; padding: 8px 8px 6px;
  background: linear-gradient(180deg, #1746c0, #12379a); color: #fff;
}
.sf-row > aside b { font-size: 12px; line-height: 1.3; }
.sf-row > aside span { font-size: 10px; color: rgba(255,255,255,.88); }
.sf-row > aside em {
  margin-top: auto; font-style: normal; font-size: 10px;
  border: 1px solid rgba(255,255,255,.7); border-radius: 100px; padding: 1px 6px;
}
.sf-cards { display: grid; grid-template-columns: repeat(5, 1fr); gap: 4px; }
.sf-card {
  background: #fff; border: 1px solid #e3ebf6; border-radius: 6px;
  padding: 4px; display: flex; flex-direction: column; align-items: center; gap: 3px;
}
.sf-card i { display: block; width: 100%; height: 42px; border-radius: 4px; background: #e7eef8; }
.sf-card b { font-size: 10px; color: #13294b; }
.sf-card small { font-size: 9px; color: #98a5bc; }
.sf-card.mark { border: 3px solid #e10600; }
.sf-card.mark b { color: #e10600; }
.ss-list { display: grid; grid-template-columns: 72px 1fr; gap: 4px; }
.ss-list > aside {
  background: #fff; border-radius: 6px; padding: 6px;
  display: flex; flex-direction: column; gap: 2px;
}
.ss-list b { font-size: 10px; color: #13294b; }
.ss-list span { font-size: 9px; color: #5a6b85; }
.ss-list span.on { color: #1443b8; font-weight: 700; }
.ss-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 4px; }
.ss-grid i { height: 28px; border-radius: 4px; background: #fff; border: 1px solid #e3ebf6; }
.mock-page { display: flex; flex-direction: column; gap: 6px; }
.mp-tabs { display: flex; gap: 8px; overflow: hidden; }
.mp-tabs span { font-size: 10px; color: #5a6b85; white-space: nowrap; }
.mp-tabs span.on { color: #1443b8; font-weight: 800; border-bottom: 2px solid #2468f2; }
.mp-title { font-size: 13px; font-weight: 800; color: #13294b; }
.news-top { display: grid; grid-template-columns: 1.3fr .7fr; gap: 6px; align-items: stretch; }
.quote-sk { background: #fff; border: 1px solid #e9eef6; border-radius: 8px; padding: 8px; display: flex; flex-direction: column; gap: 4px; }
.quote-sk b { font-size: 13px; color: #13294b; }
.quote-sk em { font-style: normal; font-size: 9px; color: #2468f2; }
.quote-sk strong { font-size: 16px; color: #f04b3e; }
.quote-sk small { font-size: 9px; color: #98a5bc; }
.co-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; }
.co-card { background: #fff; border: 1px solid #e9eef6; border-radius: 6px; padding: 8px 6px; text-align: center; }
.co-card i { display: block; width: 28px; height: 28px; margin: 0 auto 4px; border-radius: 50%; background: #e7eef8; }
.co-card b { display: block; font-size: 11px; color: #13294b; }
.co-card small { display: block; margin-top: 2px; font-size: 9px; color: #98a5bc; }
.news-list { display: flex; flex-direction: column; gap: 4px; }
.news-row { display: grid; grid-template-columns: 46px 1fr; gap: 6px; background: #fff; border-radius: 6px; padding: 6px; }
.news-row i { height: 32px; border-radius: 4px; background: #e7eef8; }
.news-row b { display: block; font-size: 11px; color: #13294b; }
.news-row small { display: block; margin-top: 3px; font-size: 9px; color: #98a5bc; }
.mc-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; }
.mc-card { background: #fff; border: 1px solid #e9eef6; border-radius: 6px; padding: 6px; }
.mc-card i { display: block; height: 36px; border-radius: 4px; background: #e7eef8; }
.mc-card b { display: block; margin-top: 4px; font-size: 10px; color: #13294b; text-align: center; }
.mock-side { display: flex; flex-direction: column; gap: 6px; }
.ms-crumb { font-size: 10px; color: #98a5bc; }
.ms-crumb b { color: #1443b8; font-weight: 800; }
.ms-body { display: grid; grid-template-columns: 1.35fr .9fr; gap: 6px; align-items: start; }
.ms-main { display: grid; grid-template-columns: 72px 1fr; gap: 8px; background: #fff; border-radius: 8px; padding: 8px; }
.ms-gallery { display: flex; flex-direction: column; gap: 4px; }
.ms-photo { display: block; height: 72px; border-radius: 6px; background: #e7eef8; }
.ms-gallery span { display: block; height: 16px; border-radius: 3px; background: #eef3fb; border: 1px solid #e3ebf6; }
.ms-name { display: block; font-size: 12px; color: #13294b; line-height: 1.3; }
.ms-meta { display: block; margin-top: 2px; font-size: 9px; color: #98a5bc; }
.ms-price { display: block; margin-top: 4px; font-size: 14px; font-weight: 800; color: #f04b3e; }
.ms-price small { font-size: 10px; font-weight: 600; }
.ms-kv { list-style: none; margin: 6px 0 0; padding: 0; }
.ms-kv li { display: flex; gap: 6px; font-size: 10px; color: #13294b; line-height: 1.6; }
.ms-kv em { font-style: normal; width: 36px; color: #98a5bc; flex: none; }
.ms-acts { display: flex; gap: 4px; margin-top: 6px; }
.ms-acts i { font-style: normal; font-size: 10px; font-weight: 700; color: #fff; background: #2468f2; border-radius: 4px; padding: 3px 6px; }
.ms-acts i.soft { color: #1443b8; background: #eef3fb; }
.ms-rail { display: flex; flex-direction: column; gap: 6px; min-width: 0; }
.ms-seller, .ms-tip { background: #fff; border-radius: 6px; padding: 6px 8px; }
.ms-seller b, .ms-tip b { display: block; font-size: 11px; color: #13294b; }
.ms-seller strong { display: block; margin-top: 4px; font-size: 11px; color: #123c8f; }
.ms-seller p { margin: 2px 0 0; font-size: 10px; color: #13294b; }
.ms-seller p em { font-style: normal; color: #98a5bc; margin-right: 6px; }
.ms-seller > span { display: inline-block; margin-top: 4px; font-size: 10px; color: #1443b8; }
.ms-tip span { display: block; margin-top: 2px; font-size: 9px; color: #5a6b85; }
.ms-tip span::before { content: "• "; color: #2468f2; }
.mock-side .mh-zone.tall { min-height: 108px; font-size: 12px; padding: 8px 6px; text-align: center; line-height: 1.35; }
.mock-side .mh-zone.tall small { display: block; margin-top: 4px; font-size: 10px; font-weight: 600; color: rgba(255,255,255,.85); }
.mock-rank { display: grid; grid-template-columns: 72px 1fr; gap: 6px; }
.mock-floor { display: flex; gap: 8px; align-items: stretch; }
.fl-side {
  width: 64px;
  flex: 0 0 64px;
  border-radius: 8px;
  background: #1746c0;
  color: #fff;
  font-size: 12px;
  font-weight: 800;
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  line-height: 1.35;
}
.fl-cards { flex: 1; display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 6px; }
.fl-slot {
  min-height: 78px;
  border-radius: 6px;
  background: #f4f7fb;
  border: 1px solid var(--line);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 4px;
  font-size: 12px;
  color: #5a6b85;
}
.fl-slot b { color: #13294b; }
.rk-cats { background: #1746c0; color: #fff; border-radius: 8px; padding: 8px 0; display: flex; flex-direction: column; }
.rk-cats b { font-size: 10px; padding: 0 8px 4px; }
.rk-cats span { font-size: 10px; line-height: 1.7; padding: 0 8px; }
.rk-cats span.on { background: rgba(255,255,255,.16); }
.rk-list { display: flex; flex-direction: column; gap: 6px; min-width: 0; }
.rk-filter { display: flex; gap: 8px; }
.rk-filter span { font-size: 10px; color: #5a6b85; }
.rk-filter span.on { color: #1443b8; font-weight: 800; }
.mr-row { display: flex; align-items: center; gap: 8px; background: #fff; border: 1px solid #e9eef6; border-radius: 6px; min-height: 28px; padding: 6px 8px; font-size: 12px; color: #5a6b85; }
.mr-row.on { justify-content: center; color: #fff; font-weight: 800; border: 0; background: #2f7bff; }
.mr-row.on em { font-style: normal; margin-right: 6px; padding: 0 5px; border-radius: 4px; background: rgba(255,255,255,.2); font-size: 10px; }
.mr-row i { width: 36px; height: 28px; border-radius: 4px; background: #e7eef8; flex: none; }
.mr-row b { flex: 1; font-size: 12px; color: #13294b; font-weight: 700; }
.mr-row small { font-size: 11px; color: #f04b3e; font-weight: 700; }
.ads-table code {
  font-size: 11.5px;
  background: #f3f6fb;
  padding: 2px 6px;
  border-radius: 4px;
  color: var(--ink-2, #5a6b85);
}
.list-hd {
  font-size: 13px;
  font-weight: 700;
  color: var(--ink-2, #5a6b85);
  margin-bottom: 8px;
}
@media (max-width: 960px) {
  .ads-grid { grid-template-columns: 1fr; }
  .ads-follow { position: static; max-height: none; overflow: visible; }
}
</style>
