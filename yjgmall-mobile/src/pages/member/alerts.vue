<script setup lang="ts">
import { reactive, ref } from "vue";
import { onShow, onPullDownRefresh } from "@dcloudio/uni-app";
import { api } from "@/utils/request";
import { ensureLogin, errMsg } from "@/utils/auth";

const items = ref<any[]>([]);
const loading = ref(false);
const saving = ref(false);
const form = reactive({ module: "sell", keyword: "", area: "", price_min: "", price_max: "" });

const moduleOpts = [
  { value: "sell", label: "供应" },
  { value: "buy", label: "采购" },
  { value: "mall", label: "商城" },
  { value: "company", label: "企业" },
];

const moduleLabel: Record<string, string> = {
  sell: "供应",
  buy: "采购",
  mall: "商城",
  company: "企业",
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
    const res = await api<any>("/member/alerts");
    items.value = res?.data || [];
  } catch (e: any) {
    uni.showToast({ title: errMsg(e), icon: "none" });
  } finally {
    loading.value = false;
    uni.stopPullDownRefresh();
  }
}

async function add() {
  if (!form.keyword.trim()) {
    uni.showToast({ title: "请填写关键词", icon: "none" });
    return;
  }
  saving.value = true;
  try {
    await api("/member/alerts", {
      method: "POST",
      body: {
        module: form.module,
        keyword: form.keyword,
        area: form.area || null,
        price_min: form.price_min ? Number(form.price_min) : null,
        price_max: form.price_max ? Number(form.price_max) : null,
      },
    });
    form.keyword = "";
    form.area = "";
    form.price_min = "";
    form.price_max = "";
    uni.showToast({ title: "已添加", icon: "success" });
    await load();
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "添加失败"), icon: "none" });
  } finally {
    saving.value = false;
  }
}

async function remove(id: number) {
  uni.showModal({
    title: "确认删除？",
    success: async (r) => {
      if (!r.confirm) return;
      try {
        await api(`/member/alerts/${id}`, { method: "DELETE" });
        uni.showToast({ title: "已删除", icon: "none" });
        load();
      } catch (e: any) {
        uni.showToast({ title: errMsg(e), icon: "none" });
      }
    },
  });
}

onShow(load);
onPullDownRefresh(load);
</script>

<template>
  <view class="page">
    <view class="card form">
      <view class="section">添加提醒</view>
      <picker :range="moduleOpts" range-key="label" @change="pickModule">
        <view class="picker-row"><text class="muted">模块</text><text class="val">{{ moduleName() }}</text></view>
      </picker>
      <view class="gap" />
      <u-input v-model="form.keyword" placeholder="关键词 *" border="surround" />
      <view class="gap" />
      <u-input v-model="form.area" placeholder="地区" border="surround" />
      <view class="gap" />
      <view class="row2">
        <u-input v-model="form.price_min" placeholder="最低价" border="surround" type="digit" />
        <u-input v-model="form.price_max" placeholder="最高价" border="surround" type="digit" />
      </view>
      <view class="gap" />
      <u-button type="primary" color="#2769dc" text="添加提醒" :loading="saving" @click="add" />
    </view>

    <view v-for="a in items" :key="a.id" class="row card">
      <view class="body">
        <text class="title">{{ a.keyword }}</text>
        <text class="meta muted">
          {{ moduleLabel[a.module] || a.module }} · {{ a.area || "不限地区" }}
        </text>
        <text class="meta muted">{{ a.price_min || "-" }} ~ {{ a.price_max || "-" }}</text>
      </view>
      <text class="danger" @click="remove(a.id)">删除</text>
    </view>
    <view class="footer-tip muted">
      <text v-if="loading">加载中...</text>
      <text v-else-if="!items.length">暂无提醒</text>
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
.row2 { display: flex; gap: 16rpx; }
.row { display: flex; gap: 12rpx; padding: 22rpx 20rpx; align-items: center; }
.body { flex: 1; min-width: 0; }
.title { font-size: 28rpx; font-weight: 600; display: block; margin-bottom: 8rpx; }
.meta { font-size: 22rpx; display: block; }
.danger { color: #e11d48; font-size: 24rpx; }
.footer-tip { text-align: center; padding: 32rpx; }
</style>
