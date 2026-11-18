<script setup lang="ts">
import { ref, computed } from "vue";
import { onLoad } from "@dcloudio/uni-app";
import { api, mediaUrl } from "@/utils/request";
import { useAuth } from "@/composables/useAuth";
import { ensureLogin, errMsg } from "@/utils/auth";
import { contentNodes } from "@/utils/content";
import { openChat } from "@/utils/chat";
import InquirySheet from "@/components/InquirySheet.vue";

const { addFavorite } = useAuth();
const item = ref<any>(null);
const loading = ref(true);
const id = ref("");
const swiperIndex = ref(0);
const showInquiry = ref(false);
const reviews = ref<any[]>([]);
const reviewsMeta = ref<any>({});
const reviewForm = ref({ rating: 5, content: "" });
const reviewSaving = ref(false);
const qty = ref(1);
const priceTiers = computed(() => {
  const rows = item.value?.price_tiers;
  return Array.isArray(rows) ? rows.filter((t) => t && t.price != null && t.min_qty != null) : [];
});
function tierMatches(tier: any, n: number) {
  const min = Number(tier.min_qty);
  const max = tier.max_qty == null || tier.max_qty === "" ? null : Number(tier.max_qty);
  return n >= min && (max == null || n <= max);
}
const activeTier = computed(() => priceTiers.value.find((t) => tierMatches(t, qty.value)) || null);
const detailImages = computed(() => {
  const rows = item.value?.detail_images;
  if (!Array.isArray(rows)) return [];
  return rows.map((src: any) => String(src || "").trim()).filter(Boolean).slice(0, 5);
});
/** 一屏两张，可左右滑 */
const detailPicPages = computed(() => {
  const list = detailImages.value;
  const pages: string[][] = [];
  for (let i = 0; i < list.length; i += 2) pages.push(list.slice(i, i + 2));
  return pages;
});
const detailPicPage = ref(0);
function onDetailPicsChange(e: any) {
  detailPicPage.value = Number(e?.detail?.current || 0);
}
const shopProducts = computed(() => {
  const rows = item.value?.shop_products;
  return Array.isArray(rows) ? rows.filter((p) => p && p.id) : [];
});
function openShopProduct(pid: number) {
  uni.navigateTo({ url: `/pages/mall/detail?id=${pid}` });
}
const priceText = computed(() => {
  const n = Number(activeTier.value ? activeTier.value.price : item.value?.price);
  return Number.isFinite(n) && n > 0 ? `¥${n.toFixed(2)}` : "面议";
});
function tierLabel(tier: any) {
  const unit = item.value?.unit ? String(item.value.unit) : "";
  const min = Number(tier.min_qty);
  const max = tier.max_qty == null || tier.max_qty === "" ? null : Number(tier.max_qty);
  return max == null ? `≥${min}${unit}` : `${min}~${max}${unit}`;
}
function setQty(delta: number) {
  const stock = Number(item.value?.stock || 0);
  let n = Math.max(1, qty.value + delta);
  if (stock > 0) n = Math.min(n, stock);
  qty.value = n;
}

const gallery = computed(() => {
  const list = [item.value?.thumb, item.value?.thumb1, item.value?.thumb2, ...(item.value?.images || [])]
    .map((s) => String(s || "").trim())
    .filter(Boolean);
  const uniq = [...new Set(list)];
  return uniq.length ? uniq : [""];
});

function onSwiperChange(e: any) {
  swiperIndex.value = e?.detail?.current || 0;
}

onLoad(async (q) => {
  if (!q?.id) return;
  id.value = String(q.id);
  try {
    const res = await api<any>(`/mall/${id.value}`);
    item.value = res?.data || res;
    reviewsMeta.value = res?.reviews_meta || {};
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "加载失败"), icon: "none" });
  } finally {
    loading.value = false;
  }
  try {
    const rev = await api<any>(`/mall/${id.value}/reviews`);
    reviews.value = rev?.data || [];
    if (rev?.meta) reviewsMeta.value = { ...reviewsMeta.value, ...rev.meta };
  } catch {
    /* optional */
  }
});

function inquiry() {
  showInquiry.value = true;
}

function chat() {
  openChat(item.value?.user?.id);
}

async function fav() {
  if (!ensureLogin()) return;
  try {
    await addFavorite("product", id.value);
    uni.showToast({ title: "已收藏", icon: "success" });
  } catch (e: any) {
    uni.showToast({ title: errMsg(e), icon: "none" });
  }
}

async function addCart() {
  if (!ensureLogin()) return;
  try {
    await api(`/member/cart/${id.value}`, { method: "POST", body: { quantity: qty.value } });
    uni.showToast({ title: "已加入购物车", icon: "success" });
  } catch (e: any) {
    uni.showToast({ title: errMsg(e), icon: "none" });
  }
}

function goCart() {
  if (!ensureLogin()) return;
  uni.navigateTo({ url: "/pages/member/cart" });
}

async function submitReview() {
  if (!ensureLogin()) return;
  if (!reviewForm.value.content.trim()) {
    uni.showToast({ title: "请填写评价内容", icon: "none" });
    return;
  }
  reviewSaving.value = true;
  try {
    await api(`/mall/${id.value}/reviews`, {
      method: "POST",
      body: { ...reviewForm.value },
    });
    uni.showToast({ title: "评价已提交", icon: "success" });
    reviewForm.value.content = "";
    const rev = await api<any>(`/mall/${id.value}/reviews`);
    reviews.value = rev?.data || [];
    if (rev?.meta) reviewsMeta.value = { ...reviewsMeta.value, ...rev.meta };
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "评价失败"), icon: "none" });
  } finally {
    reviewSaving.value = false;
  }
}
</script>

<template>
  <view class="page">
    <view v-if="loading" class="center"><u-loading-icon color="#2769dc" /></view>
    <template v-else-if="item">
      <view class="gallery">
        <swiper
          class="gallery-swiper"
          circular
          :indicator-dots="gallery.length > 1"
          indicator-active-color="#2769dc"
          @change="onSwiperChange"
        >
          <swiper-item v-for="(g, i) in gallery" :key="i">
            <image class="thumb-contain" :src="mediaUrl(g)" mode="aspectFit" />
          </swiper-item>
        </swiper>
        <view v-if="gallery.length > 1" class="gallery-count">{{ swiperIndex + 1 }}/{{ gallery.length }}</view>
      </view>
      <view class="card main">
        <text class="title">{{ item.title }}</text>
        <text class="price">{{ priceText }}</text>
        <view v-if="priceTiers.length" class="tiers">
          <view v-for="tier in priceTiers" :key="`${tier.min_qty}-${tier.max_qty}`" class="tier" :class="{ on: activeTier === tier }">
            <text class="tier-price">¥{{ Number(tier.price).toFixed(2) }}</text>
            <text class="tier-qty">{{ tierLabel(tier) }}</text>
          </view>
        </view>
        <view class="qty-row">
          <text class="qty-btn" @click="setQty(-1)">−</text>
          <text class="qty-num">{{ qty }}</text>
          <text class="qty-btn" @click="setQty(1)">+</text>
        </view>
        <view class="meta muted">
          <text v-if="item.category?.name">分类 {{ item.category.name }}</text>
          <text>销量 {{ item.sales || item.sold || 0 }}</text>
          <text>浏览 {{ item.hits || item.views || 0 }}</text>
          <text v-if="reviewsMeta.avg_rating || reviewsMeta.count || reviewsMeta.total">
            评分 {{ reviewsMeta.avg_rating || "-" }}（{{ reviewsMeta.count || reviewsMeta.total || 0 }}条）
          </text>
        </view>
      </view>
      <view v-if="shopProducts.length" class="card shop-more">
        <text class="block-title">本店商品</text>
        <view v-for="p in shopProducts" :key="p.id" class="sp" @click="openShopProduct(p.id)">
          <image class="sp-img" :src="mediaUrl(p.thumb)" mode="aspectFill" />
          <view class="sp-txt">
            <text class="sp-name">{{ p.title }}</text>
            <text class="sp-price">¥{{ Number(p.price || 0).toFixed(2) }}</text>
          </view>
        </view>
      </view>

      <view class="card">
        <view class="section-hd" style="padding-left:0;padding-right:0">
          <text class="title">商品详情</text>
        </view>
        <rich-text :nodes="contentNodes(item.content || item.introduce)" />
      </view>

      <view v-if="detailImages.length" class="card">
        <view class="detail-pics-hd">
          <text class="block-title">详情图片</text>
          <text v-if="detailPicPages.length > 1" class="detail-pics-meta">{{ detailPicPage + 1 }} / {{ detailPicPages.length }}</text>
        </view>
        <swiper
          class="detail-pics-swiper"
          :current="detailPicPage"
          :indicator-dots="detailPicPages.length > 1"
          indicator-color="rgba(201,213,234,0.9)"
          indicator-active-color="#2769dc"
          :duration="280"
          @change="onDetailPicsChange"
        >
          <swiper-item v-for="(page, pi) in detailPicPages" :key="'dp-' + pi">
            <view class="detail-pics-page">
              <image
                v-for="(src, i) in page"
                :key="src + i"
                class="detail-pic"
                :src="mediaUrl(src)"
                mode="aspectFill"
              />
            </view>
          </swiper-item>
        </swiper>
      </view>

      <view class="card reviews">
        <view class="section-hd" style="padding-left:0;padding-right:0">
          <text class="title">商品评价</text>
        </view>
        <view v-for="r in reviews" :key="r.id" class="review-row">
          <text class="review-user">{{ r.user?.name || r.user?.username || "会员" }}</text>
          <text class="review-meta muted">{{ r.rating }}星 · {{ String(r.created_at || "").slice(0, 16) }}</text>
          <text class="review-body">{{ r.content }}</text>
        </view>
        <text v-if="!reviews.length" class="muted empty-tip">暂无评价</text>
        <view class="review-form">
          <view class="rate-row">
            <text
              v-for="n in 5"
              :key="n"
              class="star"
              :class="{ on: reviewForm.rating >= n }"
              @click="reviewForm.rating = n"
            >★</text>
          </view>
          <u-textarea v-model="reviewForm.content" placeholder="写下你的评价" height="100" border="surround" />
          <view class="gap" />
          <u-button
            type="primary"
            color="#2769dc"
            size="small"
            text="发表评价"
            :loading="reviewSaving"
            @click="submitReview"
          />
        </view>
      </view>

      <view class="bottom-bar">
        <view class="bar-icon" @click="fav">
          <u-icon name="star" color="#2769dc" size="22" />
          <text>收藏</text>
        </view>
        <view class="bar-icon" @click="goCart">
          <u-icon name="shopping-cart" color="#2769dc" size="22" />
          <text>购物车</text>
        </view>
        <u-button text="询盘" plain color="#2769dc" customStyle="flex:1;height:76rpx;margin-right:12rpx" @click="inquiry" />
        <u-button type="primary" color="#2769dc" text="加购" customStyle="flex:1.2;height:76rpx" @click="addCart" />
      </view>

      <InquirySheet
        v-model:show="showInquiry"
        type="product"
        :id="id"
        :default-title="item.title"
      />
    </template>
  </view>
</template>

<style lang="scss" scoped>
.center { padding: 80rpx; text-align: center; }
.gallery {
  position: relative; height: 560rpx;
  background: linear-gradient(180deg, #fff, #f8fafc);
}
.gallery-swiper { height: 560rpx; }
.gallery-count {
  position: absolute; right: 20rpx; bottom: 20rpx;
  background: rgba(15, 28, 52, 0.55); color: #fff; font-size: 22rpx;
  padding: 6rpx 16rpx; border-radius: 999rpx;
}
.main { padding: 28rpx 24rpx; }
.title { font-size: 34rpx; font-weight: 700; display: block; margin-bottom: 14rpx; line-height: 1.4; color: #1f2937; }
.price { font-size: 42rpx; font-weight: 800; display: block; letter-spacing: -1rpx; }
.tiers { display: flex; flex-wrap: wrap; gap: 12rpx; margin-top: 16rpx; }
.tier { min-width: 180rpx; padding: 12rpx 16rpx; border: 1rpx solid #f0d2c4; background: #fff8f4; border-radius: 8rpx; }
.tier.on { border-color: #e11d48; background: #fff1f2; }
.tier-price { display: block; color: #e11d48; font-size: 30rpx; font-weight: 800; }
.tier-qty { display: block; color: #9a3412; font-size: 22rpx; margin-top: 4rpx; }
.qty-row { display: flex; align-items: center; gap: 16rpx; margin-top: 16rpx; }
.qty-btn { width: 56rpx; height: 56rpx; line-height: 56rpx; text-align: center; border: 1rpx solid #d5e0ee; border-radius: 8rpx; }
.qty-num { min-width: 48rpx; text-align: center; font-size: 28rpx; }
.meta { display: flex; flex-wrap: wrap; gap: 20rpx; margin-top: 14rpx; font-size: 24rpx; }
.card { padding: 24rpx; }
.block-title { font-size: 30rpx; font-weight: 700; display: block; margin-bottom: 8rpx; }
.detail-pics-hd {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16rpx;
  margin-bottom: 8rpx;
}
.detail-pics-hd .block-title { margin-bottom: 0; }
.detail-pics-meta { font-size: 22rpx; color: #64748b; font-weight: 600; }
.detail-pics-swiper {
  width: 100%;
  /* 内容区约 702rpx，两列间隙 16rpx → 单列 343rpx × 4/3 ≈ 457 */
  height: 456rpx;
}
.detail-pics-page {
  height: 100%;
  display: flex;
  gap: 16rpx;
  box-sizing: border-box;
}
.detail-pic {
  flex: 1 1 0;
  width: 0;
  height: 100%;
  border-radius: 8rpx;
  background: #f4f7fb;
}
.sp { display: flex; gap: 16rpx; align-items: center; padding: 16rpx 0; border-top: 1rpx solid #f1f5f9; }
.sp-img { width: 120rpx; height: 120rpx; border-radius: 8rpx; background: #f4f7fb; flex: 0 0 120rpx; }
.sp-txt { flex: 1; min-width: 0; }
.sp-name { display: block; font-size: 26rpx; font-weight: 600; line-height: 1.4; }
.sp-price { display: block; margin-top: 8rpx; color: #e11d48; font-size: 26rpx; font-weight: 800; }
.reviews .empty-tip { display: block; padding: 12rpx 0; font-size: 24rpx; }
.review-row { padding: 18rpx 0; border-bottom: 1rpx solid #f1f5f9; }
.review-user { font-size: 26rpx; font-weight: 600; display: block; color: #1f2937; }
.review-meta { font-size: 22rpx; display: block; margin-top: 4rpx; }
.review-body { font-size: 26rpx; display: block; margin-top: 8rpx; line-height: 1.5; color: #334155; }
.review-form { margin-top: 20rpx; }
.rate-row { display: flex; gap: 8rpx; margin-bottom: 12rpx; }
.star { font-size: 40rpx; color: #ddd; }
.star.on { color: #f59e0b; }
.gap { height: 16rpx; }
.page { padding-bottom: 160rpx; }
</style>
