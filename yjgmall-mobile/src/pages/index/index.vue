<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { api, mediaUrl } from "@/utils/request";
import { quoteApi } from "@/utils/quoteApi";
import { openAdLink } from "@/utils/adLink";
import PriceTrendChart from "@/components/PriceTrendChart/PriceTrendChart.vue";
import { normQuoteName, sortQuotesByCatalog } from "@/utils/quoteCatalog";

const loading = ref(true);
const banners = ref<any[]>([]);
const sells = ref<any[]>([]);
const buys = ref<any[]>([]);
const news = ref<any[]>([]);
const quotes = ref<any[]>([]);
const floors = ref<any[]>([]);
const announcements = ref<any[]>([]);
const friendLinks = ref<any[]>([]);
const keyword = ref("");
const eliteTab = ref(0);

const SAFE_THUMB = "/img/logo-yjg.png";

/** 与 PC 名企推荐左侧分类别名一致 */
const ELITE_TAB_ALIAS: Record<string, string> = {
  "有机硅单体/中间体": "原料区",
  硅油: "硅油区",
  硅橡胶: "制品区",
  "有机硅助剂/填料": "助剂区",
  机械设备: "设备展示",
  硅烷: "硅烷区",
  胶粘剂: "胶粘剂",
  硅树脂: "树脂区",
};

const newsBoards = [
  { id: "review", label: "网评" },
  { id: "report", label: "研究报告" },
  { id: "company", label: "企业动态" },
  { id: "trade", label: "进出口" },
  { id: "tech", label: "技术分享" },
  { id: "expo", label: "展会动态" },
];

const shortcuts = [
  { name: "优质厂家", path: "/pages/companies/index", tab: true, icon: "account", color: "#0f766e" },
  { name: "优质采购", path: "/pages/buy/index", tab: true, icon: "order", color: "#ea580c" },
  { name: "供应", path: "/pages/sell/index", tab: true, icon: "home", color: "#2769dc" },
  { name: "信息资讯", path: "/pages/news/index", tab: false, icon: "file-text", color: "#1d4ed8" },
  { name: "行情", path: "/pages/quotes/index", tab: false, icon: "rmb-circle", color: "#c2410c" },
  { name: "分类", path: "/pages/category/index?module=sell&target=sell", tab: false, icon: "grid", color: "#2563eb" },
  { name: "样品专区", path: "/pages/sample/index", tab: false, icon: "bag", color: "#4b86e8" },
  { name: "发布", path: "/pages/publish/index", tab: false, icon: "plus-circle", color: "#2769dc" },
];

function go(path: string, tab = false) {
  if (tab) uni.switchTab({ url: path });
  else uni.navigateTo({ url: path });
}

function goPc() {
  // #ifdef H5
  location.href = `${location.origin}/?pc=1`;
  // #endif
  // #ifndef H5
  uni.showToast({ title: "请在浏览器中打开电脑版", icon: "none" });
  // #endif
}

function onSearch() {
  const q = keyword.value.trim();
  uni.navigateTo({
    url: `/pages/search/index${q ? `?q=${encodeURIComponent(q)}` : ""}`,
  });
}

function floorTitle(f: any) {
  return f.title || f.category?.name || f.name || "热门推荐";
}

function floorCatId(f: any) {
  return f.category?.id || f.category_id || "";
}

/** 只展示真实供应；无数据不补分类名/演示图假卡 */
function floorCards(floor: any) {
  const supplies = Array.isArray(floor?.supplies) ? floor.supplies.slice(0, 6) : [];
  return supplies.map((s: any) => ({
    ...s,
    thumb: mediaUrl(s.thumb, SAFE_THUMB),
    _kw: false,
  }));
}

function tipFor(s: any) {
  if (s?.price) return `¥${s.price}`;
  return "面议";
}

function openFloorCard(item: any) {
  if (item?.id != null) {
    go(`/pages/sell/detail?id=${item.id}`);
    return;
  }
  uni.switchTab({ url: "/pages/sell/index" });
}

function goFloorMore(f: any) {
  const id = floorCatId(f);
  if (id) {
    uni.setStorageSync(
      "pick_category",
      JSON.stringify({ target: "sell", id, name: floorTitle(f) }),
    );
  }
  uni.switchTab({ url: "/pages/sell/index" });
}

const eliteTabs = computed(() =>
  (floors.value || [])
    .map((f: any) => {
      const full = String(f?.category?.name || "");
      return {
        id: f?.category?.id,
        name: ELITE_TAB_ALIAS[full] || full,
        fullName: full,
      };
    })
    .filter((t: any) => t.id && t.name),
);

function eliteTypeOf(c: any, catName: string) {
  const typed = String(c?.company_type || c?.business_mode || "").trim();
  if (typed) return typed;
  const biz = String(c?.business || "");
  if (/贸易|经销|代理/.test(biz)) return "贸易商";
  if (/服务|物流|检测/.test(biz)) return "服务商";
  if (catName) return catName;
  return "生产厂家";
}

function eliteScaleOf(c: any) {
  return String(c?.scale || "").trim();
}

function eliteBizOf(c: any) {
  const biz = String(c?.business || "").trim().replace(/\s+/g, " ");
  if (!biz) return "";
  return biz.length > 28 ? `${biz.slice(0, 28)}…` : biz;
}

function companyShortName(c: any) {
  const raw = String(c?.name || "").trim();
  if (!raw) return "";
  let name = raw
    .replace(/有限责任公司|股份有限公司|有限公司|集团有限公司/g, "")
    .replace(/[（(][^）)]*[）)]/g, "")
    .replace(/[（(][^）)]*$/g, "")
    .replace(/(厂|店)$/g, "")
    .trim();
  if (!name) name = raw;
  if (name.length <= 12) return name;
  return `${name.slice(0, 11)}…`;
}

/** 对齐 PC：只显示当前分类楼层下的企业，不跨类凑数 */
const eliteCompanies = computed(() => {
  const tabs = eliteTabs.value;
  if (!tabs.length) return [];
  const fi = Math.min(eliteTab.value, Math.max(tabs.length - 1, 0));
  const floor = floors.value[fi];
  const catName = String(floor?.category?.name || tabs[fi]?.fullName || "");
  return (floor?.companies || []).slice(0, 6).map((c: any) => ({
    id: c.id,
    slug: c.slug,
    name: String(c.name || companyShortName(c)),
    logo: mediaUrl(c.logo || c.thumb, SAFE_THUMB),
    vip: !!(c.is_certified || c.is_featured),
    type: eliteTypeOf(c, catName),
    scale: eliteScaleOf(c),
    business: eliteBizOf(c),
    address: String(c.area || c.address || "").trim(),
  }));
});

function setEliteTab(i: number) {
  eliteTab.value = i;
}

function openElite(c: any) {
  go(`/pages/companies/shop?slug=${encodeURIComponent(c.slug || c.id)}`);
}

function onBannerTap(b: any) {
  openAdLink(b?.link || b?.url);
}

function openFriend(url: string) {
  openAdLink(url);
}

function openAnnounce(a: any) {
  if (a?.link) {
    openAdLink(a.link);
    return;
  }
  go("/pages/announce/index");
}

async function load() {
  loading.value = true;
  try {
    const home = await api<any>("/home").catch(() => null);
    const data = home?.data || home || {};
    banners.value = (data.banners || data.ads || []).slice(0, 5);
    if (!banners.value.length) {
      banners.value = [{ id: 1, image: "/img/ads/banner1.jpg", title: "有机硅中间体" }];
    }
    sells.value = (data.sells || data.supplies || data.latest_sell || []).slice(0, 8);
    buys.value = (data.buys || data.purchases || data.latest_buy || []).slice(0, 6);
    news.value = (data.news || data.articles || []).slice(0, 6);
    quotes.value = sortQuotesByCatalog(
      (data.quote_products || data.quotes || []).map((p: any) => ({
        ...p,
        name: normQuoteName(String(p.name || "")),
      })),
    ).slice(0, 15);
    floors.value = (data.floors || []).slice(0, 8);
    announcements.value = (data.announcements || []).slice(0, 4);
    friendLinks.value = (data.links || []).slice(0, 12);
    eliteTab.value = 0;

    if (!sells.value.length) {
      const s = await api<any>("/sell", { query: { page: 1 } }).catch(() => null);
      sells.value = (s?.data || []).slice(0, 8);
    }
    if (!buys.value.length) {
      const b = await api<any>("/buy", { query: { page: 1 } }).catch(() => null);
      buys.value = (b?.data || []).slice(0, 6);
    }
    if (!news.value.length) {
      const n = await api<any>("/news", { query: { page: 1 } }).catch(() => null);
      news.value = (n?.data || []).slice(0, 6);
    }
    if (!quotes.value.length) {
      const q = await quoteApi<any>("/quotes").catch(() => null);
      quotes.value = sortQuotesByCatalog(
        (q?.data || []).map((p: any) => ({
          ...p,
          name: normQuoteName(String(p.name || "")),
        })),
      ).slice(0, 15);
    }
  } finally {
    loading.value = false;
  }
}

onMounted(load);
</script>

<template>
  <view class="page home">
    <view class="search-bar">
      <view class="search-inner" @click="onSearch">
        <u-icon name="search" color="#999" size="18" />
        <input
          v-model="keyword"
          class="search-input"
          type="text"
          confirm-type="search"
          placeholder="搜索供应 / 企业 / 采购 / 行情"
          @confirm="onSearch"
          @click.stop
        />
      </view>
    </view>

    <swiper
      v-if="banners.length"
      class="banner"
      circular
      autoplay
      indicator-dots
      indicator-active-color="#2769dc"
    >
      <swiper-item v-for="(b, i) in banners" :key="b.id || i">
        <image
          class="banner-img"
          :src="mediaUrl(b.image || b.thumb || b.image_src)"
          mode="aspectFill"
          @click="onBannerTap(b)"
        />
      </swiper-item>
    </swiper>

    <view v-if="announcements.length" class="notice-strip card">
      <text class="notice-label" @click="go('/pages/announce/index')">公告</text>
      <scroll-view scroll-x class="notice-x">
        <text
          v-for="(a, i) in announcements"
          :key="a.id || i"
          class="notice-item"
          @click="openAnnounce(a)"
        >{{ a.title }}</text>
      </scroll-view>
      <text class="notice-more" @click="go('/pages/announce/index')">更多</text>
    </view>

    <view class="quick card">
      <view
        v-for="(s, i) in shortcuts"
        :key="i"
        class="quick-item"
        @click="go(s.path, s.tab)"
      >
        <view class="quick-icon" :style="{ background: s.color }">
          <u-icon :name="s.icon" color="#fff" size="22" />
        </view>
        <text class="quick-name">{{ s.name }}</text>
      </view>
    </view>

    <PriceTrendChart :products="quotes" />

    <view v-if="loading" class="loading-wrap">
      <u-loading-icon mode="circle" color="#2769dc" />
    </view>

    <template v-else>
      <!-- 名企推荐 -->
      <view v-if="eliteTabs.length" class="elite-block">
        <view class="section-hd">
          <text class="title">名企推荐</text>
          <text class="more" @click="go('/pages/companies/index', true)">更多</text>
        </view>
        <scroll-view scroll-x class="elite-tabs" enable-flex>
          <text
            v-for="(t, i) in eliteTabs"
            :key="t.id"
            class="elite-tab"
            :class="{ on: eliteTab === i }"
            @click="setEliteTab(i)"
          >{{ t.name }}</text>
        </scroll-view>
        <view class="elite-grid">
          <view
            v-for="c in eliteCompanies"
            :key="c.id"
            class="elite-card card"
            @click="openElite(c)"
          >
            <image class="elite-logo" :src="c.logo" mode="aspectFit" />
            <view class="elite-info">
              <view class="elite-name-row">
                <text class="elite-name">{{ c.name }}</text>
                <text v-if="c.vip" class="vip-badge">尊享会员</text>
              </view>
              <text class="elite-line"><text class="em">公司类型</text>{{ c.type }}</text>
              <text class="elite-line"><text class="em">公司规模</text>{{ c.scale }}</text>
              <text class="elite-line"><text class="em">主营产品</text>{{ c.business }}</text>
              <text class="elite-line"><text class="em">公司地址</text>{{ c.address }}</text>
            </view>
          </view>
          <view v-if="!eliteCompanies.length" class="empty muted">该分类暂无名企，切换其他分类看看</view>
        </view>
      </view>

      <view class="section-hd">
        <text class="title">最新供应</text>
        <text class="more" @click="go('/pages/sell/index', true)">更多</text>
      </view>
      <view class="sell-grid card">
        <view
          v-for="(item, i) in sells"
          :key="item.id || i"
          class="sell-item"
          @click="go(`/pages/sell/detail?id=${item.id}`)"
        >
          <view class="sell-thumb">
            <image class="thumb-contain" :src="mediaUrl(item.thumb)" mode="aspectFit" />
            <text v-if="item.is_elite || item.is_vip" class="badge">荐</text>
          </view>
          <text class="sell-title">{{ item.title }}</text>
          <text class="price">{{ item.price ? `¥${item.price}` : "面议" }}</text>
        </view>
        <view v-if="!sells.length" class="empty muted">暂无供应</view>
      </view>

      <view
        v-for="(f, fi) in floors"
        :key="floorCatId(f) || fi"
        class="floor-block"
      >
        <view class="section-hd">
          <text class="title">{{ floorTitle(f) }}</text>
          <text class="more" @click="goFloorMore(f)">更多</text>
        </view>
        <view class="sell-grid card">
          <view
            v-for="(item, i) in floorCards(f)"
            :key="item.id || i"
            class="sell-item"
            @click="openFloorCard(item)"
          >
            <view class="sell-thumb">
              <image class="thumb-contain" :src="item.thumb || mediaUrl(item.thumb, SAFE_THUMB)" mode="aspectFit" />
            </view>
            <text class="sell-title">{{ item.title || item.name }}</text>
            <text class="price">{{ tipFor(item) }}</text>
          </view>
          <view v-if="!floorCards(f).length" class="empty muted">暂无供应</view>
        </view>
      </view>

      <view class="section-hd">
        <text class="title">采购信息</text>
        <text class="more" @click="go('/pages/buy/index', true)">更多</text>
      </view>
      <view class="list-card card">
        <view
          v-for="(item, i) in buys"
          :key="item.id || i"
          class="list-row"
          @click="go(`/pages/buy/detail?id=${item.id}`)"
        >
          <view class="list-main">
            <text class="list-title">{{ item.title }}</text>
            <text class="list-sub muted">
              {{ item.quantity || item.amount || "数量面议" }}
              <template v-if="item.area || item.region"> · {{ item.area || item.region }}</template>
            </text>
          </view>
          <text v-if="item.is_urgent" class="tag-urg">急</text>
        </view>
        <view v-if="!buys.length" class="empty muted">暂无采购</view>
      </view>

      <view class="section-hd">
        <text class="title">信息资讯</text>
        <text class="more" @click="go('/pages/news/index')">更多</text>
      </view>
      <view class="news-chips">
        <text
          v-for="b in newsBoards"
          :key="b.id"
          class="news-chip"
          @click="go(`/pages/news/index?section=${b.id}`)"
        >{{ b.label }}</text>
      </view>
      <view class="list-card card">
        <view
          v-for="(item, i) in news"
          :key="item.id || i"
          class="news-row"
          @click="go(`/pages/news/detail?id=${item.id}`)"
        >
          <view class="news-body">
            <text class="list-title multi">{{ item.title }}</text>
            <text class="list-sub muted">{{ String(item.published_at || item.created_at || "").slice(0, 10) }}</text>
          </view>
          <image
            v-if="item.thumb"
            class="news-thumb"
            :src="mediaUrl(item.thumb)"
            mode="aspectFill"
          />
        </view>
        <view v-if="!news.length" class="empty muted">暂无资讯</view>
      </view>

      <view v-if="friendLinks.length" class="friend-bar card">
        <view class="friend-hd">
          <text class="friend-title">友情链接</text>
          <text class="friend-more" @click="go('/pages/link/index')">更多 ›</text>
        </view>
        <view class="friend-links">
          <text
            v-for="(l, i) in friendLinks"
            :key="l.id || i"
            class="friend-link"
            @click="openFriend(l.url)"
          >{{ l.name }}</text>
        </view>
      </view>

      <view class="pc-link muted" @click="goPc">电脑版 ›</view>
    </template>
  </view>
</template>

<style lang="scss" scoped>
.home { padding-bottom: 48rpx; }
.pc-link {
  text-align: center;
  padding: 32rpx 24rpx 56rpx;
  font-size: 24rpx;
  color: #64748b;
}
.search-bar {
  background: linear-gradient(165deg, #4b86e8 0%, #2769dc 55%, #1e5fc4 100%);
  padding: 16rpx 24rpx 32rpx;
}
.search-inner {
  display: flex;
  align-items: center;
  gap: 12rpx;
  background: #fff;
  border-radius: 999rpx;
  padding: 16rpx 28rpx;
  box-shadow: 0 10rpx 28rpx rgba(15, 40, 90, 0.16);
}
.search-input { flex: 1; font-size: 28rpx; height: 40rpx; color: #1f2937; }
.banner {
  height: 300rpx;
  margin: 20rpx 20rpx 0;
  border-radius: 20rpx;
  overflow: hidden;
  background: #fff;
  box-shadow: 0 8rpx 24rpx rgba(31, 56, 100, 0.08);
}
.banner-img { width: 100%; height: 300rpx; background: #eef2f7; }
.notice-strip {
  display: flex;
  align-items: center;
  gap: 12rpx;
  margin: 16rpx 20rpx 0;
  padding: 18rpx 22rpx;
}
.notice-label {
  flex-shrink: 0;
  font-size: 22rpx;
  font-weight: 700;
  color: #c2410c;
  background: #fff7ed;
  padding: 6rpx 14rpx;
  border-radius: 8rpx;
}
.notice-x { flex: 1; white-space: nowrap; min-width: 0; }
.notice-item {
  display: inline-block;
  font-size: 24rpx;
  color: #334155;
  margin-right: 28rpx;
}
.notice-more { flex-shrink: 0; font-size: 22rpx; color: #2769dc; font-weight: 500; }
.friend-bar { margin: 16rpx 20rpx 0; padding: 22rpx 24rpx; }
.friend-hd {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 14rpx;
}
.friend-title { font-size: 28rpx; font-weight: 700; }
.friend-more { font-size: 22rpx; color: #2769dc; }
.friend-links { display: flex; flex-wrap: wrap; gap: 12rpx 24rpx; }
.friend-link { font-size: 24rpx; color: #2769dc; }
.quick {
  display: flex;
  flex-wrap: wrap;
  padding: 28rpx 8rpx 16rpx;
  margin-top: 16rpx;
}
.quick-item {
  width: 25%;
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-bottom: 20rpx;
}
.quick-icon {
  width: 84rpx;
  height: 84rpx;
  border-radius: 22rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 12rpx;
  box-shadow: 0 10rpx 20rpx rgba(39, 105, 220, 0.16);
}
.quick-name { font-size: 22rpx; color: #334155; font-weight: 500; }
.elite-block { margin-top: 4rpx; overflow: hidden; max-width: 100%; }
.elite-tabs {
  width: 100%;
  max-width: 100%;
  box-sizing: border-box;
  white-space: nowrap;
  padding: 0 20rpx 14rpx;
  overflow-x: auto;
}
.elite-tab {
  display: inline-block;
  margin-right: 12rpx;
  padding: 12rpx 24rpx;
  font-size: 24rpx;
  color: #64748b;
  background: #fff;
  border-radius: 999rpx;
  border: 1rpx solid #e8edf3;
  box-shadow: 0 2rpx 8rpx rgba(31, 56, 100, 0.04);
}
.elite-tab.on {
  color: #fff;
  background: linear-gradient(135deg, #4b86e8, #2769dc);
  border-color: transparent;
  font-weight: 700;
  box-shadow: 0 6rpx 16rpx rgba(39, 105, 220, 0.28);
}
.elite-grid {
  display: flex;
  flex-direction: column;
  gap: 14rpx;
  padding: 0 20rpx 8rpx;
}
.elite-card {
  display: flex;
  gap: 18rpx;
  padding: 22rpx;
  margin: 0;
}
.elite-logo {
  width: 112rpx;
  height: 112rpx;
  flex-shrink: 0;
  background: #f7f9fc;
  border-radius: 14rpx;
  border: 1rpx solid #eef2f7;
}
.elite-info { flex: 1; min-width: 0; }
.elite-name-row {
  display: flex;
  align-items: center;
  gap: 10rpx;
  margin-bottom: 10rpx;
}
.elite-name {
  flex: 1;
  min-width: 0;
  font-size: 28rpx;
  font-weight: 700;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}
.vip-badge {
  flex-shrink: 0;
  font-size: 18rpx;
  color: #c2410c;
  background: #fff7ed;
  padding: 4rpx 12rpx;
  border-radius: 8rpx;
  font-weight: 600;
}
.elite-line {
  display: block;
  font-size: 22rpx;
  color: #475569;
  line-height: 1.55;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}
.elite-line .em {
  color: #94a3b8;
  margin-right: 10rpx;
}
.sell-grid {
  display: flex;
  flex-wrap: wrap;
  padding: 8rpx 12rpx 20rpx;
}
.sell-item {
  width: 50%;
  padding: 12rpx;
  box-sizing: border-box;
}
.sell-thumb {
  position: relative;
  height: 220rpx;
  background: linear-gradient(180deg, #f8fafc, #f1f5f9);
  border-radius: 14rpx;
  overflow: hidden;
  margin-bottom: 12rpx;
  border: 1rpx solid #eef2f7;
}
.badge {
  position: absolute;
  left: 8rpx;
  top: 8rpx;
  background: linear-gradient(135deg, #ea580c, #c2410c);
  color: #fff;
  font-size: 18rpx;
  padding: 4rpx 10rpx;
  border-radius: 8rpx;
  font-weight: 600;
}
.sell-title {
  font-size: 26rpx;
  line-height: 1.4;
  height: 72rpx;
  color: #1f2937;
  overflow: hidden;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}
.list-card { padding: 4rpx 8rpx; }
.list-row {
  padding: 24rpx 20rpx;
  border-bottom: 1rpx solid #f1f5f9;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16rpx;
}
.list-row:last-child { border-bottom: none; }
.list-main { flex: 1; min-width: 0; }
.list-title {
  display: block;
  font-size: 28rpx;
  font-weight: 600;
  color: #1f2937;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}
.list-title.multi {
  white-space: normal;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}
.list-sub { display: block; margin-top: 8rpx; font-size: 22rpx; }
.tag-urg {
  flex-shrink: 0;
  background: #fee2e2;
  color: #dc2626;
  font-size: 20rpx;
  font-weight: 600;
  padding: 6rpx 12rpx;
  border-radius: 8rpx;
}
.news-chips {
  display: flex; flex-wrap: wrap; gap: 12rpx;
  padding: 0 24rpx 8rpx;
}
.news-chip {
  font-size: 24rpx; color: #1d4ed8; background: #eef4ff;
  padding: 8rpx 16rpx; border-radius: 999rpx;
}
.news-row {
  display: flex;
  gap: 18rpx;
  padding: 22rpx 20rpx;
  border-bottom: 1rpx solid #f1f5f9;
}
.news-row:last-child { border-bottom: none; }
.news-body { flex: 1; min-width: 0; }
.news-thumb {
  width: 148rpx;
  height: 108rpx;
  border-radius: 12rpx;
  background: #f3f5f9;
  flex-shrink: 0;
}
.loading-wrap, .empty { padding: 40rpx; text-align: center; width: 100%; box-sizing: border-box; }
.floor-block { margin-top: 4rpx; }
.price { display: block; margin-top: 8rpx; color: #c2410c; font-size: 26rpx; font-weight: 800; }
.thumb-contain { width: 100%; height: 100%; }
</style>
