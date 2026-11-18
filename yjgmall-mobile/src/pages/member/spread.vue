<script setup lang="ts">
import { reactive, ref } from "vue";
import { onShow, onPullDownRefresh } from "@dcloudio/uni-app";
import { api } from "@/utils/request";
import { ensureLogin, errMsg } from "@/utils/auth";
import { statusLabel } from "@/utils/status";

const items = ref<any[]>([]);
const loading = ref(false);
const saving = ref(false);
const form = reactive({ module: "sell", keyword: "", bid: "100", days: "30" });

const moduleOpts = [
  { value: "sell", label: "供应" },
  { value: "buy", label: "采购" },
  { value: "company", label: "企业库" },
  { value: "mall", label: "商城" },
];

const moduleLabel: Record<string, string> = {
  sell: "供应",
  buy: "采购",
  company: "企业库",
  mall: "商城",
};

function pickModule(e: any) {
  form.module = moduleOpts[Number(e.detail.value)]?.value || "sell";
}

function moduleName() {
  return moduleOpts.find((o) => o.value === form.module)?.label || form.module;
}

async function load() {
  if (!ensureLogin()) return;
  loading.value = true;
  try {
    const res = await api<any>("/member/spreads");
    items.value = res?.data || [];
  } catch (e: any) {
    uni.showToast({ title: errMsg(e), icon: "none" });
  } finally {
    loading.value = false;
    uni.stopPullDownRefresh();
  }
}

async function submit() {
  if (!form.keyword.trim()) {
    uni.showToast({ title: "请填写关键词", icon: "none" });
    return;
  }
  saving.value = true;
  try {
    await api("/member/spreads", {
      method: "POST",
      body: { ...form, bid: Number(form.bid), days: Number(form.days) },
    });
    uni.showToast({ title: "推广申请已提交", icon: "success" });
    form.keyword = "";
    await load();
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "提交失败"), icon: "none" });
  } finally {
    saving.value = false;
  }
}

onShow(load);
onPullDownRefresh(load);
</script>

<template>
  <view class="page">
    <view class="card form">
      <view class="section">提交推广</view>
      <picker :range="moduleOpts" range-key="label" @change="pickModule">
        <view class="picker-row"><text class="muted">模块</text><text class="val">{{ moduleName() }}</text></view>
      </picker>
      <view class="gap" />
      <u-input v-model="form.keyword" placeholder="关键词 * 如：DMC" border="surround" />
      <view class="gap" />
      <u-input v-model="form.bid" placeholder="出价" border="surround" type="number" />
      <view class="gap" />
      <u-input v-model="form.days" placeholder="天数" border="surround" type="number" />
      <view class="gap" />
      <u-button type="primary" color="#2769dc" text="提交推广" :loading="saving" @click="submit" />
    </view>

    <view v-for="item in items" :key="item.id" class="row card">
      <view class="body">
        <text class="title">{{ item.keyword }}</text>
        <text class="meta muted">
          {{ moduleLabel[item.module] || item.module }} · 出价 {{ item.bid }} · {{ item.days }}天
        </text>
        <text class="meta muted">{{ statusLabel(item.status) }}</text>
      </view>
    </view>
    <view class="footer-tip muted">
      <text v-if="loading">加载中...</text>
      <text v-else-if="!items.length">暂无推广</text>
    </view>
  </view>
</template>

<style lang="scss" scoped>
.form { margin: 20rpx; padding: 24rpx; }
.gap { height: 18rpx; }
.section {
  margin: 0 0 16rpx; font-size: 28rpx; font-weight: 700;
  padding-left: 14rpx; border-left: 6rpx solid #2769dc;
}
.picker-row {
  display: flex; align-items: center; gap: 12rpx;
  padding: 20rpx 16rpx; border: 1rpx solid #dadbde; border-radius: 8rpx;
}
.val { flex: 1; }
.row { display: flex; gap: 12rpx; padding: 22rpx 20rpx; align-items: center; }
.body { flex: 1; min-width: 0; }
.title { font-size: 28rpx; font-weight: 600; display: block; margin-bottom: 8rpx; }
.meta { font-size: 22rpx; display: block; }
.footer-tip { text-align: center; padding: 32rpx; }
</style>
