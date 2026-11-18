<script setup lang="ts">
import { ref, computed } from "vue";
import { onLoad, onShow } from "@dcloudio/uni-app";
import { api, mediaUrl } from "@/utils/request";
import { memberBadgeUrl } from "@/utils/memberBadge";
import { contentNodes } from "@/utils/content";
import { openAdLink } from "@/utils/adLink";
import { errMsg } from "@/utils/auth";

const item = ref<any>(null);
const loading = ref(true);
const loadError = ref("");
const tab = ref("home");
const statusBar = ref(20);
const activePage = ref<any>(null);
const activeNews = ref<any>(null);
const siteQ = ref("");
const currentSlug = ref("");

const baseTabs = [
  { key: "home", label: "首页" },
  { key: "about", label: "公司介绍" },
  { key: "products", label: "供应产品" },
  { key: "news", label: "企业新闻" },
  { key: "honors", label: "荣誉资质" },
  { key: "jobs", label: "人才招聘" },
  { key: "credit", label: "诚信档案" },
  { key: "contact", label: "联系方式" },
];

const tabs = computed(() => {
  const list = [...baseTabs];
  for (const p of item.value?.pages || []) {
    list.push({ key: `page-${p.id}`, label: p.title || "单页" });
  }
  return list;
});

const companyName = computed(() => item.value?.company || item.value?.name || "企业主页");

const tagline = computed(() => {
  const t =
    item.value?.business ||
    item.value?.main_products ||
    item.value?.summary ||
    item.value?.area ||
    "";
  return String(t || "有机硅材料供应商").slice(0, 48);
});

const introShort = computed(() => {
  const raw = String(item.value?.introduce || item.value?.intro || item.value?.content || item.value?.about || "");
  const plain = raw.replace(/<[^>]+>/g, " ").replace(/\s+/g, " ").trim();
  if (!plain) return tagline.value;
  return plain.length > 96 ? plain.slice(0, 96) + "…" : plain;
});

/** 真实旺铺广告图；占位图不作为主视觉拉伸 */
const hasCustomBanner = computed(() => {
  const b = String(item.value?.banner || item.value?.thumb || "").trim();
  return Boolean(b) && !/company-skin\//i.test(b) && !/\/img\/banner\/co\d/i.test(b);
});

const bannerSrc = computed(() => {
  if (!hasCustomBanner.value) return "";
  return mediaUrl(item.value?.banner || item.value?.thumb);
});

const logoSrc = computed(() => mediaUrl(item.value?.logo || item.value?.thumb));
const badgeSrc = computed(() => memberBadgeUrl(item.value?.member_grade));

const supplies = computed(() => item.value?.supplies || []);
const products = computed(() => item.value?.products || []);
const newsList = computed(() => item.value?.news || []);
const honorList = computed(() => item.value?.honors || []);
const jobList = computed(() => item.value?.jobs || []);

const homeShowcase = computed(() => {
  const rows: { key: string; id: number | string; title: string; thumb: string; price: string; kind: "sell" | "mall" }[] = [];
  for (const s of supplies.value.slice(0, 6)) {
    rows.push({
      key: "s" + s.id,
      id: s.id,
      title: s.title,
      thumb: s.thumb,
      price: s.price ? `¥${s.price}` : "面议",
      kind: "sell",
    });
  }
  if (rows.length < 6) {
    for (const p of products.value.slice(0, 6 - rows.length)) {
      rows.push({
        key: "p" + p.id,
        id: p.id,
        title: p.title,
        thumb: p.thumb,
        price: p.price ? `¥${p.price}` : "面议",
        kind: "mall",
      });
    }
  }
  return rows;
});

const filteredSupplies = computed(() => {
  const q = siteQ.value.trim().toLowerCase();
  if (!q) return supplies.value;
  return supplies.value.filter((s: any) => String(s.title || "").toLowerCase().includes(q));
});
const filteredProducts = computed(() => {
  const q = siteQ.value.trim().toLowerCase();
  if (!q) return products.value;
  return products.value.filter((p: any) => String(p.title || "").toLowerCase().includes(q));
});

async function loadShop(slug: string, force = false) {
  if (!slug) {
    loading.value = false;
    loadError.value = "缺少企业标识";
    return;
  }
  if (!force && currentSlug.value === slug && item.value) return;
  currentSlug.value = slug;
  loading.value = true;
  loadError.value = "";
  activePage.value = null;
  activeNews.value = null;
  try {
    const res = await api<any>(`/companies/${encodeURIComponent(slug)}`);
    item.value = res?.data || res;
    uni.setNavigationBarTitle({ title: companyName.value });
  } catch (e: any) {
    item.value = null;
    loadError.value = errMsg(e, "企业不存在或未开通旺铺");
    uni.showToast({ title: loadError.value, icon: "none" });
  } finally {
    loading.value = false;
  }
}

onLoad(async (q) => {
  const sys = uni.getSystemInfoSync();
  statusBar.value = sys.statusBarHeight || 20;
  if (q?.tab) tab.value = String(q.tab);
  await loadShop(q?.slug ? String(q.slug) : "", true);
});

onShow(() => {
  const pages = getCurrentPages();
  const cur: any = pages[pages.length - 1];
  const slug = cur?.options?.slug ? String(cur.options.slug) : currentSlug.value;
  if (slug && slug !== currentSlug.value) {
    loadShop(slug, true);
  }
});

function setTab(key: string) {
  tab.value = key;
  activePage.value = null;
  activeNews.value = null;
  if (key.startsWith("page-")) {
    const id = key.replace("page-", "");
    activePage.value = (item.value?.pages || []).find((p: any) => String(p.id) === id) || null;
  }
}

function openNews(n: any) {
  activeNews.value = n;
  tab.value = "news";
}

function searchSite() {
  setTab("products");
}

function back() {
  const pages = getCurrentPages();
  if (pages.length > 1) uni.navigateBack();
  else uni.switchTab({ url: "/pages/companies/index" });
}

function goSell(id: number | string) {
  uni.navigateTo({ url: `/pages/sell/detail?id=${id}` });
}
function goMall(id: number | string) {
  uni.navigateTo({ url: `/pages/mall/detail?id=${id}` });
}
function goJob(id: number | string) {
  uni.navigateTo({ url: `/pages/job/detail?id=${id}` });
}
function openShowcase(row: { kind: "sell" | "mall"; id: number | string }) {
  if (row.kind === "mall") goMall(row.id);
  else goSell(row.id);
}

function inquiry() {
  const title = encodeURIComponent(companyName.value);
  const cid = item.value?.id || "";
  uni.navigateTo({
    url: `/pages/inquiry/index?type=company&id=${cid}&title=${title}`,
  });
}

function openLink(url: string) {
  openAdLink(url);
}

function onLogoErr(e: any) {
  try {
    e.target.src = mediaUrl("/img/logo-yjg.png");
  } catch {
    /* ignore */
  }
}
</script>

<template>
  <view class="shop">
    <view class="nav" :style="{ paddingTop: statusBar + 'px' }">
      <view class="nav-inner">
        <view class="back" @click="back">‹ 返回</view>
        <text class="nav-title">{{ companyName }}</text>
        <view class="nav-act" @click="inquiry">询盘</view>
      </view>
    </view>

    <view v-if="loading" class="center"><u-loading-icon color="#2769dc" /></view>
    <view v-else-if="loadError" class="center err-box">
      <text class="err-txt">{{ loadError }}</text>
      <view class="err-act">
        <u-button type="primary" color="#2769dc" text="返回企业库" @click="back" />
      </view>
    </view>
    <template v-else-if="item">
      <view class="mast">
        <view class="mast-logo">
          <image v-if="badgeSrc" class="mast-badge" :src="badgeSrc" mode="heightFix" />
          <image class="logo" :src="logoSrc" mode="aspectFit" @error="onLogoErr" />
        </view>
        <view class="mast-body">
          <text class="mast-name">{{ companyName }}</text>
          <text class="mast-tag">{{ tagline }}</text>
          <view class="mast-meta">
            <text v-if="item.area" class="chip">{{ item.area }}</text>
            <text v-if="item.credit?.is_certified" class="chip chip-ok">已认证</text>
            <text class="chip">企业主页</text>
          </view>
        </view>
      </view>

      <view v-if="hasCustomBanner" class="banner-wrap">
        <image class="banner" :src="bannerSrc" mode="aspectFill" />
      </view>

      <view class="find-bar">
        <u-search
          v-model="siteQ"
          placeholder="查找产品"
          :show-action="true"
          action-text="搜索"
          bg-color="#f3f5f9"
          shape="square"
          @search="searchSite"
          @custom="searchSite"
        />
      </view>

      <scroll-view scroll-x class="tabs-scroll" :show-scrollbar="false" enable-flex>
        <view class="tabs">
          <view
            v-for="t in tabs"
            :key="t.key"
            class="tab"
            :class="{ on: tab === t.key }"
            @click="setTab(t.key)"
          >
            <text class="tab-txt">{{ t.label }}</text>
          </view>
        </view>
      </scroll-view>

      <view v-show="tab === 'home'" class="panel">
        <view class="sec">
          <view class="section-hd">
            <text class="title">公司简介</text>
            <text class="more" @click="setTab('about')">更多 ›</text>
          </view>
          <text class="intro">{{ introShort }}</text>
          <view class="quick">
            <text class="q-btn primary" @click="setTab('products')">供应产品</text>
            <text class="q-btn" @click="setTab('about')">公司介绍</text>
            <text class="q-btn" @click="inquiry">在线询盘</text>
          </view>
        </view>

        <view class="sec">
          <view class="section-hd">
            <text class="title">主营产品</text>
            <text class="more" @click="setTab('products')">更多 ›</text>
          </view>
          <view class="grid">
            <view
              v-for="row in homeShowcase"
              :key="row.key"
              class="g-item"
              @click="openShowcase(row)"
            >
              <view class="g-pic">
                <image class="g-img" :src="mediaUrl(row.thumb)" mode="aspectFill" />
              </view>
              <text class="g-title">{{ row.title }}</text>
              <text class="price">{{ row.price }}</text>
            </view>
          </view>
          <view v-if="!homeShowcase.length" class="empty muted">暂无产品</view>
        </view>

        <view v-if="newsList.length" class="sec">
          <view class="section-hd">
            <text class="title">企业新闻</text>
            <text class="more" @click="setTab('news')">更多 ›</text>
          </view>
          <view
            v-for="(n, i) in newsList.slice(0, 4)"
            :key="n.id || i"
            class="news-mini"
            @click="openNews(n)"
          >
            <text class="news-mini-t">{{ n.title }}</text>
            <text v-if="n.created_at" class="muted news-mini-d">{{ String(n.created_at).slice(0, 10) }}</text>
          </view>
        </view>

        <view v-if="(item.links || []).length" class="sec shop-links">
          <view class="section-hd"><text class="title">友情链接</text></view>
          <view class="link-wrap">
            <text
              v-for="(l, i) in item.links"
              :key="l.id || i"
              class="shop-link"
              @click="openLink(l.url)"
            >{{ l.name }}</text>
          </view>
        </view>
      </view>

      <view v-show="tab === 'about'" class="panel card-pad">
        <rich-text :nodes="contentNodes(item.introduce || item.intro || item.content || item.about, '暂无介绍')" />
      </view>

      <view v-show="tab === 'products'" class="panel">
        <view v-for="(s, i) in filteredSupplies" :key="s.id || i" class="list-row" @click="goSell(s.id)">
          <image class="list-thumb" :src="mediaUrl(s.thumb)" mode="aspectFill" />
          <view class="list-body">
            <text class="list-title">{{ s.title }}</text>
            <text class="price">{{ s.price ? `¥${s.price}` : "面议" }}</text>
          </view>
        </view>
        <view v-for="(p, i) in filteredProducts" :key="'mp' + (p.id || i)" class="list-row" @click="goMall(p.id)">
          <image class="list-thumb" :src="mediaUrl(p.thumb)" mode="aspectFill" />
          <view class="list-body">
            <text class="list-title">{{ p.title }}</text>
            <text class="price">{{ p.price ? `¥${p.price}` : "面议" }}</text>
          </view>
        </view>
        <view v-if="!filteredSupplies.length && !filteredProducts.length" class="empty muted">
          {{ siteQ.trim() ? "未找到相关产品" : "暂无供应" }}
        </view>
      </view>

      <view v-show="tab === 'news'" class="panel">
        <view v-if="activeNews" class="card-pad">
          <text class="page-title">{{ activeNews.title }}</text>
          <text v-if="activeNews.created_at" class="muted news-date">{{ String(activeNews.created_at).slice(0, 16) }}</text>
          <rich-text :nodes="contentNodes(activeNews.content || activeNews.summary)" />
          <view class="contact-act">
            <u-button text="返回新闻列表" plain color="#2769dc" @click="activeNews = null" />
          </view>
        </view>
        <template v-else>
          <view
            v-for="(n, i) in newsList"
            :key="n.id || i"
            class="news-row"
            @click="openNews(n)"
          >
            <text class="list-title">{{ n.title }}</text>
            <u-icon name="arrow-right" color="#ccc" size="14" />
          </view>
          <view v-if="!newsList.length" class="empty muted">暂无新闻</view>
        </template>
      </view>

      <view v-show="tab === 'honors'" class="panel">
        <view v-for="(h, i) in honorList" :key="h.id || i" class="honor-row">
          <image v-if="h.image" class="honor-img" :src="mediaUrl(h.image)" mode="aspectFit" />
          <text class="list-title">{{ h.title }}</text>
        </view>
        <view v-if="!honorList.length" class="empty muted">暂无荣誉资质</view>
      </view>

      <view v-show="tab === 'jobs'" class="panel">
        <view v-for="j in jobList" :key="j.id" class="list-row" @click="goJob(j.id)">
          <view class="list-body">
            <text class="list-title">{{ j.title }}</text>
            <text class="muted">{{ j.salary || j.area || "面议" }}</text>
          </view>
          <u-icon name="arrow-right" color="#ccc" size="14" />
        </view>
        <view v-if="!jobList.length" class="empty muted">暂无招聘职位</view>
      </view>

      <view v-show="tab === 'credit'" class="panel card-pad">
        <view class="kv"><text class="k">信用指数</text><text>{{ item.credit?.credit_score ?? 0 }}</text></view>
        <view class="kv"><text class="k">认证</text><text>{{ item.credit?.is_certified ? "企业已认证" : "普通会员" }}</text></view>
        <view v-if="item.credit?.vip_years" class="kv"><text class="k">会员年限</text><text>{{ item.credit.vip_years }} 年</text></view>
        <view v-if="item.credit?.member_since" class="kv">
          <text class="k">注册时间</text>
          <text>{{ String(item.credit.member_since).slice(0, 10) }}</text>
        </view>
      </view>

      <view v-show="tab === 'contact'" class="panel card-pad">
        <view class="kv"><text class="k">联系人</text><text>{{ item.contact || item.truename || "—" }}</text></view>
        <view class="kv"><text class="k">电话</text><text>{{ item.phone || item.telephone || item.mobile || "—" }}</text></view>
        <view class="kv"><text class="k">地址</text><text>{{ item.address || item.area || "—" }}</text></view>
        <view class="kv"><text class="k">邮箱</text><text>{{ item.email || "—" }}</text></view>
        <view v-if="item.website" class="kv"><text class="k">网站</text><text>{{ item.website }}</text></view>
        <view class="contact-act">
          <u-button type="primary" color="#2769dc" text="联系企业 / 询盘" @click="inquiry" />
        </view>
      </view>

      <view v-if="activePage" class="panel card-pad">
        <text class="page-title">{{ activePage.title }}</text>
        <rich-text :nodes="contentNodes(activePage.content)" />
      </view>
    </template>
  </view>
</template>

<style lang="scss" scoped>
.shop { min-height: 100vh; background: #f3f5f9; padding-bottom: 48rpx; }
.nav { background: #2769dc; color: #fff; position: sticky; top: 0; z-index: 20; }
.nav-inner {
  height: 88rpx; display: flex; align-items: center; justify-content: space-between; padding: 0 20rpx;
}
.back, .nav-act { width: 100rpx; font-size: 26rpx; flex-shrink: 0; }
.nav-act { text-align: right; opacity: 0.95; }
.nav-title {
  flex: 1; text-align: center; font-size: 30rpx; font-weight: 600;
  overflow: hidden; white-space: nowrap; text-overflow: ellipsis; padding: 0 8rpx;
}

.mast {
  display: flex; gap: 20rpx; align-items: center;
  padding: 28rpx 24rpx; background: #fff;
}
.mast-logo { display: flex; flex-direction: column; align-items: center; gap: 8rpx; flex-shrink: 0; }
.mast-badge { height: 36rpx; width: 140rpx; }
.logo {
  width: 112rpx; height: 112rpx; border-radius: 12rpx;
  background: #f3f5f9; border: 1rpx solid #e8ecf1; flex-shrink: 0;
}
.mast-body { flex: 1; min-width: 0; }
.mast-name {
  display: block; font-size: 32rpx; font-weight: 700; color: #1a1a1a;
  line-height: 1.35; margin-bottom: 8rpx;
}
.mast-tag {
  display: block; font-size: 24rpx; color: #64748b; line-height: 1.4;
  margin-bottom: 12rpx;
}
.mast-meta { display: flex; flex-wrap: wrap; gap: 10rpx; }
.chip {
  font-size: 20rpx; color: #475569; background: #f1f5f9;
  padding: 4rpx 12rpx; border-radius: 6rpx;
}
.chip-ok { color: #0f766e; background: #ecfdf5; }

.banner-wrap {
  height: 220rpx; background: #e8eef7; overflow: hidden;
}
.banner { width: 100%; height: 220rpx; display: block; }

.find-bar {
  background: #fff; padding: 16rpx 20rpx 8rpx;
  border-top: 1rpx solid #eef1f5;
}

.tabs-scroll {
  background: #fff; border-bottom: 1rpx solid #e8ecf1;
  white-space: nowrap; width: 100%;
}
.tabs { display: inline-flex; padding: 0 8rpx; }
.tab {
  flex-shrink: 0; padding: 24rpx 22rpx 20rpx;
  border-bottom: 4rpx solid transparent;
}
.tab-txt {
  font-size: 26rpx; color: #64748b; white-space: nowrap; line-height: 1.2;
}
.tab.on { border-bottom-color: #2769dc; }
.tab.on .tab-txt { color: #2769dc; font-weight: 700; }

.panel { background: #fff; margin-top: 12rpx; }
.sec { padding-bottom: 8rpx; }
.sec + .sec { border-top: 1rpx solid #f1f4f7; }
.card-pad { padding: 24rpx; }
.section-hd {
  display: flex; align-items: center; justify-content: space-between;
  padding: 24rpx 24rpx 12rpx;
}
.title {
  font-size: 30rpx; font-weight: 700; color: #1a1a1a;
  padding-left: 14rpx; border-left: 6rpx solid #2769dc; line-height: 1.2;
}
.more { font-size: 24rpx; color: #2769dc; padding: 8rpx 0 8rpx 16rpx; }
.intro {
  display: block; padding: 0 24rpx 16rpx; font-size: 26rpx; color: #475569; line-height: 1.55;
}
.quick {
  display: flex; gap: 12rpx; padding: 0 24rpx 24rpx; flex-wrap: wrap;
}
.q-btn {
  font-size: 24rpx; color: #2769dc; border: 1rpx solid #c5d6f5;
  background: #f5f8ff; padding: 12rpx 22rpx; border-radius: 8rpx;
}
.q-btn.primary {
  color: #fff; background: #2769dc; border-color: #2769dc;
}

.grid {
  display: flex; flex-wrap: wrap; padding: 0 16rpx 20rpx;
}
.g-item {
  width: 50%; padding: 12rpx; box-sizing: border-box;
}
.g-pic {
  width: 100%; aspect-ratio: 1; background: #f7f8fa; border-radius: 12rpx;
  overflow: hidden; margin-bottom: 12rpx;
}
.g-img { width: 100%; height: 100%; display: block; }
.g-title {
  font-size: 26rpx; color: #1a1a1a; line-height: 1.35;
  height: 70rpx; overflow: hidden;
  display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
  margin-bottom: 6rpx;
}
.price { font-size: 26rpx; color: #c2410c; font-weight: 600; display: block; }

.list-row {
  display: flex; gap: 20rpx; padding: 22rpx 24rpx;
  border-bottom: 1rpx solid #f1f4f7; align-items: center;
}
.list-thumb {
  width: 160rpx; height: 160rpx; background: #f7f8fa; border-radius: 12rpx; flex-shrink: 0;
}
.list-body { flex: 1; min-width: 0; }
.list-title {
  font-size: 28rpx; color: #1a1a1a; display: block; margin-bottom: 10rpx;
  overflow: hidden; text-overflow: ellipsis;
  display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
}
.news-row {
  display: flex; align-items: center; justify-content: space-between; gap: 12rpx;
  padding: 26rpx 24rpx; border-bottom: 1rpx solid #f1f4f7;
}
.news-mini {
  display: flex; align-items: center; gap: 16rpx;
  padding: 18rpx 24rpx; border-bottom: 1rpx solid #f6f8fb;
}
.news-mini-t { flex: 1; font-size: 26rpx; color: #334155; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.news-mini-d { font-size: 22rpx; flex-shrink: 0; }
.news-date { display: block; font-size: 22rpx; margin: 8rpx 0 16rpx; }
.honor-row { padding: 20rpx 24rpx; border-bottom: 1rpx solid #f1f4f7; }
.honor-img { width: 100%; height: 280rpx; background: #f7f8fa; margin-bottom: 12rpx; border-radius: 8rpx; }
.kv { display: flex; gap: 16rpx; margin-bottom: 16rpx; font-size: 28rpx; }
.k { width: 140rpx; color: #666; flex-shrink: 0; }
.contact-act { margin-top: 24rpx; }
.page-title { display: block; font-size: 32rpx; font-weight: 700; margin-bottom: 16rpx; }
.shop-links { padding-bottom: 8rpx; }
.link-wrap { display: flex; flex-wrap: wrap; gap: 12rpx 24rpx; padding: 0 24rpx 20rpx; }
.shop-link { font-size: 24rpx; color: #2769dc; }
.empty, .center { padding: 48rpx; text-align: center; }
.err-box { padding-top: 120rpx; }
.err-txt { display: block; color: #64748b; font-size: 28rpx; margin-bottom: 32rpx; }
.err-act { padding: 0 80rpx; }
.muted { color: #94a3b8; }
</style>
