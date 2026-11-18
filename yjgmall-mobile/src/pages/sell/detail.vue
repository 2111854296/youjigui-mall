<script setup lang="ts">
import { ref, computed } from "vue";
import { onLoad } from "@dcloudio/uni-app";
import { api, mediaUrl } from "@/utils/request";
import { memberBadgeUrl } from "@/utils/memberBadge";
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

const badgeSrc = computed(() => memberBadgeUrl(item.value?.user?.member_grade));
const companyLogo = computed(() =>
  mediaUrl(item.value?.user?.company_logo || item.value?.user?.logo || item.value?.user?.avatar),
);

const gallery = computed(() => {
  const list = [item.value?.thumb, item.value?.thumb1, item.value?.thumb2, ...(item.value?.images || [])]
    .map((s) => String(s || "").trim())
    .filter(Boolean);
  const uniq = [...new Set(list)];
  return uniq.length ? uniq : [""];
});

const attrs = computed(() => {
  const rows: { label: string; value: string }[] = [];
  const it = item.value || {};
  const push = (label: string, value: any) => {
    if (value === undefined || value === null || value === "") return;
    rows.push({ label, value: String(value) });
  };
  push("分类", it.category?.name || it.catname);
  push("价格", it.price ? `¥${it.price}/${it.unit || ""}` : "面议");
  push("起订量", it.min_amount || it.min_order || it.moq);
  push("供货量", it.amount || it.quantity || it.stock);
  push("发货期", it.days ? `${it.days}天` : (it.delivery || it.lead_time || ""));
  push("品牌", it.brand);
  push("地区", it.area || it.region);
  push("公司", it.user?.company_name || it.company || it.user?.name);
  push("联系人", it.user?.name);
  push("手机", it.user?.mobile || "登录后可见/询盘联系");
  for (const a of it.attrs || []) {
    if (a?.name || a?.label) push(a.name || a.label, a.value);
  }
  // Destoon 风格 attr1/2/3
  if (it.attr1_name && it.attr1_value) push(it.attr1_name, it.attr1_value);
  if (it.attr2_name && it.attr2_value) push(it.attr2_name, it.attr2_value);
  if (it.attr3_name && it.attr3_value) push(it.attr3_name, it.attr3_value);
  return rows;
});

async function load(sid: string) {
  loading.value = true;
  try {
    const res = await api<any>(`/sell/${sid}`);
    item.value = res?.data || res;
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "加载失败"), icon: "none" });
  } finally {
    loading.value = false;
  }
}

function goCompany() {
  const u = item.value?.user || {};
  const slug = u.company_slug || u.company_id || u.id;
  if (!slug) {
    uni.showToast({ title: "暂无企业旺铺", icon: "none" });
    return;
  }
  uni.navigateTo({ url: `/pages/companies/shop?slug=${encodeURIComponent(String(slug))}` });
}

function contact() {
  showInquiry.value = true;
}

function chat() {
  openChat(item.value?.user?.id);
}

async function fav() {
  if (!ensureLogin()) return;
  try {
    await addFavorite("supply", id.value);
    uni.showToast({ title: "已收藏", icon: "success" });
  } catch (e: any) {
    uni.showToast({ title: errMsg(e), icon: "none" });
  }
}

function onSwiperChange(e: any) {
  swiperIndex.value = e?.detail?.current || 0;
}

onLoad((q) => {
  if (q?.id) {
    id.value = String(q.id);
    load(id.value);
  }
});
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
        <view class="tags" v-if="item.is_elite || item.is_urgent">
          <text v-if="item.is_elite" class="tag">荐</text>
          <text v-if="item.is_urgent" class="tag urg">急</text>
        </view>
        <text class="title">{{ item.title }}</text>
        <text class="price">{{ item.price ? `¥${item.price}` : "面议" }}</text>
        <view class="meta muted">
          <text>浏览 {{ item.hits || item.views || 0 }}</text>
          <text v-if="item.refreshed_at || item.updated_at">更新 {{ String(item.refreshed_at || item.updated_at).slice(0, 10) }}</text>
        </view>
      </view>

      <view v-if="attrs.length" class="card">
        <view class="section-hd" style="padding-left:0;padding-right:0">
          <text class="title">产品参数</text>
        </view>
        <view v-for="(a, i) in attrs" :key="i" class="attr-row">
          <text class="attr-k muted">{{ a.label }}</text>
          <text class="attr-v">{{ a.value }}</text>
        </view>
      </view>

      <view class="card company" @click="goCompany">
        <view class="co-left">
          <view class="co-mark">
            <image v-if="badgeSrc" class="co-badge" :src="badgeSrc" mode="heightFix" />
            <image class="co-logo" :src="companyLogo" mode="aspectFit" />
          </view>
          <view>
            <text class="co-name">{{ item.user?.company_name || item.company || item.username || "—" }}</text>
            <text class="co-sub muted">点击进入企业主页</text>
          </view>
        </view>
        <u-icon name="arrow-right" color="#ccc" size="14" />
      </view>

      <view v-if="item.summary" class="card">
        <text class="summary">{{ item.summary }}</text>
      </view>

      <view class="card">
        <view class="section-hd" style="padding-left:0;padding-right:0">
          <text class="title">详情介绍</text>
        </view>
        <rich-text class="content" :nodes="contentNodes(item.content || item.introduce)" />
      </view>

      <view class="bottom-bar">
        <view class="bar-icon" @click="fav">
          <u-icon name="star" color="#2769dc" size="22" />
          <text>收藏</text>
        </view>
        <view class="bar-icon" @click="goCompany">
          <u-icon name="home" color="#2769dc" size="22" />
          <text>旺铺</text>
        </view>
        <u-button type="primary" color="#2769dc" text="立即询盘" customStyle="flex:2;height:76rpx" @click="contact" />
      </view>

      <InquirySheet
        v-model:show="showInquiry"
        type="supply"
        :id="id"
        :default-title="item.title"
      />
    </template>
  </view>
</template>

<style lang="scss" scoped>
.center { padding: 80rpx; text-align: center; }
.gallery {
  position: relative;
  height: 560rpx;
  background: linear-gradient(180deg, #fff, #f8fafc);
  margin-bottom: 8rpx;
}
.gallery-swiper { height: 560rpx; }
.gallery-count {
  position: absolute;
  right: 20rpx;
  bottom: 20rpx;
  background: rgba(15, 28, 52, 0.55);
  color: #fff;
  font-size: 22rpx;
  padding: 6rpx 16rpx;
  border-radius: 999rpx;
  backdrop-filter: blur(6px);
}
.main { padding: 28rpx 24rpx; }
.tags { display: flex; gap: 10rpx; margin-bottom: 14rpx; }
.tag {
  font-size: 20rpx;
  background: #eef4ff;
  color: #2769dc;
  padding: 4rpx 12rpx;
  border-radius: 8rpx;
  font-weight: 600;
}
.tag.vip { background: #fff7ed; color: #c2410c; }
.tag.urg { background: #fee2e2; color: #dc2626; }
.title { font-size: 34rpx; font-weight: 700; display: block; margin-bottom: 14rpx; line-height: 1.4; color: #1f2937; }
.price { font-size: 42rpx; display: block; margin-bottom: 16rpx; font-weight: 800; letter-spacing: -1rpx; }
.meta { display: flex; gap: 24rpx; font-size: 24rpx; }
.attr-row {
  display: flex;
  padding: 16rpx 0;
  border-bottom: 1rpx solid #f1f5f9;
  font-size: 26rpx;
}
.attr-row:last-child { border-bottom: none; }
.attr-k { width: 160rpx; flex-shrink: 0; color: #64748b; }
.attr-v { flex: 1; color: #1f2937; }
.company {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 24rpx;
}
.co-left { display: flex; align-items: center; gap: 16rpx; }
.co-mark { display: flex; flex-direction: column; align-items: center; gap: 8rpx; flex-shrink: 0; }
.co-badge { height: 36rpx; width: 116rpx; }
.co-logo {
  width: 84rpx;
  height: 84rpx;
  border-radius: 14rpx;
  background: #f7f9fc;
  border: 1rpx solid #eef2f7;
}
.co-name { display: block; font-size: 28rpx; font-weight: 700; color: #1f2937; }
.co-sub { display: block; font-size: 22rpx; margin-top: 6rpx; color: #64748b; }
.summary { font-size: 28rpx; color: #475569; line-height: 1.65; display: block; }
.content { font-size: 28rpx; line-height: 1.7; color: #334155; word-break: break-word; }
.card { padding: 24rpx; }
.page { padding-bottom: 160rpx; }
</style>
