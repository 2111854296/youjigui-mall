<script setup lang="ts">
import { unwrapDestoonLazyHtml } from '~/utils/richHtml'

definePageMeta({ layout: 'home' })

const route = useRoute()
const config = useRuntimeConfig()
const { api, token } = useApi('front')
const { user } = useAuth('front')
const id = computed(() => String(route.params.id || ''))
const buyLoading = ref(false)
const buyMsg = ref('')
const buyErr = ref('')

const { data, error, refresh } = await useAsyncData(
  () => `news-${id.value}`,
  () =>
    $fetch(`${config.public.apiBase}/news/${id.value}`, {
      headers: token.value ? { Authorization: `Bearer ${token.value}` } : {},
    }),
  { watch: [id, token] },
)

const item = computed(() => (data.value as any)?.data)
useHead(() => ({ title: item.value?.title || '资讯详情' }))

/** 去掉正文开头与页面 h1 重复的标题；顺带展开 Destoon lazy 占位图 */
const bodyHtml = computed(() => {
  let html = unwrapDestoonLazyHtml(String(item.value?.content || ''))
  const title = String(item.value?.title || '').trim()
  if (!html || !title) return html
  const esc = title.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
  html = html.replace(
    new RegExp(
      `^\\s*(?:<(?:p|div|h[1-6])[^>]*>\\s*)?(?:<strong>|<b>)?\\s*${esc}\\s*(?:</strong>|</b>)?\\s*(?:</(?:p|div|h[1-6])>)?`,
      'i',
    ),
    '',
  )
  return html
})

const reportLocked = computed(() => !!item.value?.is_report && !!item.value?.report_locked)
const reportAccess = computed(() => String(item.value?.report_access || ''))
const reportPrice = computed(() => {
  const n = Number(item.value?.report_price)
  return Number.isFinite(n) && n > 0 ? n : 19.8
})

async function buyReport() {
  buyMsg.value = ''
  buyErr.value = ''
  if (!user.value) {
    return navigateTo({ path: '/login', query: { redirect: route.fullPath } })
  }
  buyLoading.value = true
  try {
    const res = await api<{ data?: { id?: number }; message?: string }>('/vip/buy-report', {
      method: 'POST',
      body: { article_id: Number(id.value) },
    })
    const oid = res?.data?.id
    if (oid) return navigateTo(`/member/fund-pay/${oid}`)
    await refresh()
    await appOk(res?.message || res?.data?.message || '您已可阅读本报告。', '购买成功')
  } catch (e: any) {
    await appFail(apiErrorText(e, '购买失败'), '购买失败')
  } finally {
    buyLoading.value = false
  }
}

function newsDate(n: any) {
  const s = String(n?.published_at || n?.created_at || '')
  return s ? s.slice(5, 10) : ''
}

function excludeSelf(list: any[]) {
  return (list || []).filter((n) => String(n.id) !== id.value)
}

const { data: sideRes } = await useAsyncData(
  () => `news-detail-side-${id.value}`,
  async () => {
    const base = config.public.apiBase
    const [latest, hot] = await Promise.all([
      $fetch(`${base}/news`, { query: { per_page: 20, sort: 'new' } }),
      $fetch(`${base}/news`, { query: { per_page: 12, sort: 'hot' } }),
    ])
    return { latest, hot }
  },
  { watch: [id] },
)

const categoryId = computed(() => Number(item.value?.category?.id || item.value?.category_id || 0))

const { data: catRes } = await useAsyncData(
  () => `news-detail-cat-${id.value}-${categoryId.value}`,
  async () => {
    const cid = categoryId.value
    if (!cid) return null
    return $fetch(`${config.public.apiBase}/news`, {
      query: { per_page: 12, sort: 'new', category: cid },
    })
  },
  { watch: [id, categoryId] },
)

const latestNews = computed(() => excludeSelf(((sideRes.value as any)?.latest?.data || []) as any[]).slice(0, 15))
const hotNews = computed(() => {
  const ids = new Set(latestNews.value.map((n) => String(n.id)))
  return excludeSelf(((sideRes.value as any)?.hot?.data || []) as any[])
    .filter((n) => !ids.has(String(n.id)))
    .slice(0, 10)
})
const sameCatNews = computed(() => {
  const used = new Set([
    ...latestNews.value.map((n) => String(n.id)),
    ...hotNews.value.map((n) => String(n.id)),
  ])
  return excludeSelf(((catRes.value as any)?.data || []) as any[])
    .filter((n) => !used.has(String(n.id)))
    .slice(0, 10)
})
const catName = computed(() => String(item.value?.category?.name || '').trim())
</script>

<template>
  <div class="news-portal">
    <div class="crumb-row">
      <div class="container">
        <div class="crumb-tabs">
          <NuxtLink to="/">首页</NuxtLink>
          <NuxtLink to="/news">硅业智讯</NuxtLink>
          <span class="on">{{ item?.category?.name || '未分类' }}</span>
        </div>
      </div>
    </div>

    <div class="tip-bar">
      <div class="container">
        <b>阅读提示：</b>
        行情与资讯仅供参考，请以企业实际成交与官方公告为准。
      </div>
    </div>

    <div class="section">
      <div class="container">
        <div v-if="error" class="art-card empty">文章不存在或已下架</div>
        <div v-else-if="item" class="art-layout">
          <article class="art-card">
            <h1>{{ item.title }}</h1>
            <div class="art-meta">
              <span class="cat">{{ item.category?.name || '未分类' }}</span>
              <span v-if="item.is_report" class="cat report">研究报告</span>
              <span>{{ String(item.published_at || '').slice(0, 16) || '-' }}</span>
              <span>浏览 {{ item.views ?? 0 }}</span>
            </div>
            <div class="art-body" v-html="bodyHtml" />

            <div v-if="reportLocked" class="report-gate">
              <div class="gate-fade" aria-hidden="true" />
              <div class="gate-box">
                <b>后续内容已锁定</b>
                <p v-if="reportAccess === 'guest'">非会员仅可查看导读以上内容，其余需升级会员后观看。</p>
                <p v-else>普通会员和白银会员需购买单个报告后才能阅读全文。黄金、星钻会员可直接阅读。</p>
                <div v-if="buyMsg" class="alert alert-success">{{ buyMsg }}</div>
                <div v-if="buyErr" class="alert alert-error">{{ buyErr }}</div>
                <div class="gate-acts">
                  <template v-if="reportAccess === 'guest'">
                    <NuxtLink class="btn btn--primary" to="/member/grade">升级会员</NuxtLink>
                    <NuxtLink class="btn btn--join" :to="{ path: '/login', query: { redirect: route.fullPath } }">登录</NuxtLink>
                  </template>
                  <template v-else>
                    <button type="button" class="btn btn--primary" :disabled="buyLoading" @click="buyReport">
                      {{ buyLoading ? '提交中…' : `对公付款 ¥${reportPrice}` }}
                    </button>
                    <NuxtLink class="btn btn--join" to="/member/grade">升级黄金/星钻</NuxtLink>
                  </template>
                </div>
              </div>
            </div>

            <ArticleDisclaimer :title="item.title" />

            <NuxtLink class="more-link" to="/news" style="display:inline-block;margin-top:22px">← 返回资讯列表</NuxtLink>
          </article>

          <aside class="art-side">
            <div class="side-stack">
              <div class="blk">
                <div class="b-head"><b>最新资讯</b></div>
                <NuxtLink
                  v-for="n in latestNews"
                  :key="'n' + n.id"
                  class="li-row"
                  :to="`/news/${n.id}`"
                >
                  <span class="t">{{ n.title }}</span>
                  <time v-if="newsDate(n)">{{ newsDate(n) }}</time>
                </NuxtLink>
                <p v-if="!latestNews.length" class="empty" style="padding:16px 0">暂无更多</p>
                <div class="b-foot"><NuxtLink to="/news">更多资讯 ›</NuxtLink></div>
              </div>

              <div v-if="hotNews.length" class="blk">
                <div class="b-head"><b>热门阅读</b></div>
                <NuxtLink
                  v-for="n in hotNews"
                  :key="'h' + n.id"
                  class="li-row"
                  :to="`/news/${n.id}`"
                >
                  <span class="t">{{ n.title }}</span>
                  <time v-if="newsDate(n)">{{ newsDate(n) }}</time>
                </NuxtLink>
                <div class="b-foot"><NuxtLink to="/news/rank">阅读排行 ›</NuxtLink></div>
              </div>

              <div v-if="sameCatNews.length" class="blk">
                <div class="b-head"><b>{{ catName ? `${catName}推荐` : '同类推荐' }}</b></div>
                <NuxtLink
                  v-for="n in sameCatNews"
                  :key="'c' + n.id"
                  class="li-row"
                  :to="`/news/${n.id}`"
                >
                  <span class="t">{{ n.title }}</span>
                  <time v-if="newsDate(n)">{{ newsDate(n) }}</time>
                </NuxtLink>
                <div class="b-foot">
                  <NuxtLink :to="categoryId ? `/news?category=${categoryId}` : '/news'">更多同类 ›</NuxtLink>
                </div>
              </div>
            </div>
          </aside>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.art-meta .cat.report {
  background: #eef4ff;
  color: #1443b8;
}
.report-gate {
  position: relative;
  margin-top: 8px;
}
.gate-fade {
  height: 56px;
  margin-top: -56px;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0), #fff 85%);
  pointer-events: none;
}
.gate-box {
  border: 1px solid #d7e3f5;
  border-radius: 12px;
  background: #f7faff;
  padding: 18px 16px 16px;
  text-align: center;
}
.gate-box b {
  display: block;
  font-size: 16px;
  color: #123c8f;
}
.gate-box > p {
  margin: 8px 0 0;
  font-size: 13.5px;
  line-height: 1.6;
  color: #5a6b85;
}
.gate-acts {
  margin-top: 14px;
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  justify-content: center;
}
.gate-note {
  margin: 12px 0 0;
  font-size: 12px;
  color: #98a5bc;
}
.alert {
  margin: 10px auto 0;
  max-width: 420px;
  text-align: left;
}
</style>
