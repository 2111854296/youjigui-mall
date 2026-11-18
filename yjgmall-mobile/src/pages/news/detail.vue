<script setup lang="ts">
import { computed, ref } from "vue";
import { onLoad } from "@dcloudio/uni-app";
import { api } from "@/utils/request";
import { contentNodes } from "@/utils/content";
import { ensureLogin, errMsg } from "@/utils/auth";

const item = ref<any>(null);
const loading = ref(true);
const buying = ref(false);
const articleId = ref("");
const latestNews = ref<any[]>([]);
const hotNews = ref<any[]>([]);

const reportLocked = computed(() => !!item.value?.is_report && !!item.value?.report_locked);
const reportAccess = computed(() => String(item.value?.report_access || ""));
const reportPrice = computed(() => {
  const n = Number(item.value?.report_price);
  return Number.isFinite(n) && n > 0 ? n : 19.8;
});

function excludeSelf(list: any[]) {
  return (list || []).filter((n) => String(n.id) !== articleId.value);
}

async function loadSide() {
  try {
    const [latest, hot] = await Promise.all([
      api<any>("/news", { query: { per_page: 12, sort: "new" }, auth: false }),
      api<any>("/news", { query: { per_page: 8, sort: "hot" }, auth: false }),
    ]);
    latestNews.value = excludeSelf(latest?.data || []).slice(0, 10);
    const used = new Set(latestNews.value.map((n) => String(n.id)));
    hotNews.value = excludeSelf(hot?.data || [])
      .filter((n) => !used.has(String(n.id)))
      .slice(0, 8);
  } catch {
    latestNews.value = [];
    hotNews.value = [];
  }
}

async function load(id: string) {
  loading.value = true;
  try {
    const res = await api<any>(`/news/${id}`);
    item.value = res?.data || res;
    await loadSide();
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "加载失败"), icon: "none" });
  } finally {
    loading.value = false;
  }
}

onLoad((q) => {
  if (!q?.id) return;
  articleId.value = String(q.id);
  load(articleId.value);
});

function goNews(nid: number | string) {
  uni.redirectTo({ url: `/pages/news/detail?id=${nid}` });
}

function goNewsList() {
  uni.navigateTo({ url: "/pages/news/index" });
}

function goGrade() {
  uni.navigateTo({ url: "/pages/member/grade" });
}

function goLogin() {
  ensureLogin();
}

async function buyReport() {
  if (!ensureLogin()) return;
  buying.value = true;
  try {
    const res: any = await api("/vip/buy-report", {
      method: "POST",
      body: { article_id: Number(articleId.value) },
    });
    const oid = res?.data?.id;
    if (oid) {
      uni.navigateTo({ url: `/pages/member/fund-pay-detail?id=${oid}` });
      return;
    }
    uni.showToast({ title: res?.message || "您已可阅读本报告", icon: "none" });
    await load(articleId.value);
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "购买失败"), icon: "none" });
  } finally {
    buying.value = false;
  }
}
</script>

<template>
  <view class="page">
    <view v-if="loading" class="center"><u-loading-icon color="#2769dc" /></view>
    <view v-else-if="item" class="card article">
      <text class="title">{{ item.title }}</text>
      <text class="meta muted">{{ item.addtime || item.created_at || "" }}</text>
      <rich-text class="body" :nodes="contentNodes(item.content || item.introduce || '')" />
      <view v-if="reportLocked" class="gate">
        <text class="gate-title">后续内容已锁定</text>
        <text v-if="reportAccess === 'guest'" class="gate-txt">非会员仅可查看导读以上内容，其余需升级会员后观看。</text>
        <text v-else class="gate-txt">普通会员和白银会员需购买单个报告后才能阅读全文。黄金、星钻会员可直接阅读。</text>
        <view v-if="reportAccess === 'guest'" class="gate-acts">
          <u-button type="primary" color="#2769dc" text="升级会员" @click="goGrade" />
          <u-button text="登录" @click="goLogin" />
        </view>
        <view v-else class="gate-acts">
          <u-button type="primary" color="#2769dc" :loading="buying" :text="buying ? '提交中…' : `对公付款 ¥${reportPrice}`" @click="buyReport" />
          <u-button text="升级黄金/星钻" @click="goGrade" />
        </view>
      </view>
    </view>

    <view v-if="!loading && latestNews.length" class="card side">
      <view class="side-hd">
        <text class="side-title">最新资讯</text>
        <text class="side-more" @click="goNewsList">更多 ›</text>
      </view>
      <view v-for="n in latestNews" :key="'n' + n.id" class="side-row" @click="goNews(n.id)">
        <text class="side-t">{{ n.title }}</text>
      </view>
    </view>

    <view v-if="!loading && hotNews.length" class="card side">
      <view class="side-hd">
        <text class="side-title">热门阅读</text>
      </view>
      <view v-for="n in hotNews" :key="'h' + n.id" class="side-row" @click="goNews(n.id)">
        <text class="side-t">{{ n.title }}</text>
      </view>
    </view>
  </view>
</template>

<style lang="scss" scoped>
.center { padding: 80rpx; text-align: center; }
.article { padding: 28rpx 24rpx; }
.title { font-size: 36rpx; font-weight: 700; display: block; margin-bottom: 12rpx; }
.meta { font-size: 22rpx; display: block; margin-bottom: 24rpx; }
.body { font-size: 28rpx; line-height: 1.7; }
.side { margin: 20rpx; padding: 24rpx; }
.side-hd { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8rpx; }
.side-title {
  font-size: 28rpx; font-weight: 700; padding-left: 14rpx;
  border-left: 6rpx solid #2769dc;
}
.side-more { font-size: 24rpx; color: #2769dc; }
.side-row { padding: 16rpx 0; border-bottom: 1px dashed #e8edf4; }
.side-row:last-child { border-bottom: 0; }
.side-t { font-size: 26rpx; color: #1f2937; line-height: 1.45; }
.gate {
  margin-top: 24rpx;
  padding: 28rpx 20rpx;
  border: 1rpx solid #d7e3f5;
  border-radius: 16rpx;
  background: #f7faff;
  text-align: center;
}
.gate-title { display: block; font-size: 30rpx; font-weight: 700; margin-bottom: 12rpx; }
.gate-txt { display: block; font-size: 24rpx; color: #475569; line-height: 1.6; margin-bottom: 20rpx; }
.gate-acts { display: flex; flex-direction: column; gap: 16rpx; }
</style>
