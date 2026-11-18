<script setup lang="ts">
import { reactive, ref } from "vue";
import { onShow, onPullDownRefresh } from "@dcloudio/uni-app";
import { api } from "@/utils/request";
import { ensureLogin, errMsg } from "@/utils/auth";

const items = ref<any[]>([]);
const loading = ref(false);
const saving = ref(false);
const form = reactive({
  name: "",
  phone: "",
  province: "",
  city: "",
  district: "",
  address: "",
  is_default: true,
});

async function load() {
  if (!ensureLogin()) return;
  loading.value = true;
  try {
    const res = await api<any>("/member/addresses");
    items.value = res?.data || [];
  } catch (e: any) {
    uni.showToast({ title: errMsg(e), icon: "none" });
  } finally {
    loading.value = false;
    uni.stopPullDownRefresh();
  }
}

async function submit() {
  if (!form.name.trim() || !form.phone.trim() || !form.address.trim()) {
    uni.showToast({ title: "请填写收货人、手机和详细地址", icon: "none" });
    return;
  }
  saving.value = true;
  try {
    await api("/member/addresses", { method: "POST", body: { ...form } });
    uni.showToast({ title: "地址已保存", icon: "success" });
    form.address = "";
    await load();
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "保存失败"), icon: "none" });
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
        await api(`/member/addresses/${id}`, { method: "DELETE" });
        uni.showToast({ title: "已删除", icon: "success" });
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
    <view v-for="item in items" :key="item.id" class="row card">
      <view class="body">
        <text class="title">{{ item.name }} · {{ item.phone }}</text>
        <text class="meta muted">{{ item.full || [item.province, item.city, item.district, item.address].filter(Boolean).join(" ") }}{{ item.is_default ? " · 默认" : "" }}</text>
      </view>
      <text class="danger" @click="remove(item.id)">删除</text>
    </view>
    <view class="footer-tip muted" v-if="loading || !items.length">
      <text v-if="loading">加载中...</text>
      <text v-else>暂无地址</text>
    </view>

    <view class="card form">
      <view class="section">新增地址</view>
      <u-input v-model="form.name" placeholder="收货人 *" border="surround" />
      <view class="gap" />
      <u-input v-model="form.phone" placeholder="手机 *" border="surround" type="number" />
      <view class="gap" />
      <u-input v-model="form.province" placeholder="省" border="surround" />
      <view class="gap" />
      <u-input v-model="form.city" placeholder="市" border="surround" />
      <view class="gap" />
      <u-input v-model="form.district" placeholder="区" border="surround" />
      <view class="gap" />
      <u-input v-model="form.address" placeholder="详细地址 *" border="surround" />
      <view class="gap" />
      <view class="toggle" @click="form.is_default = !form.is_default">
        <view class="box" :class="{ on: form.is_default }" />
        <text>设为默认</text>
      </view>
      <view class="gap" />
      <u-button type="primary" color="#2769dc" text="保存地址" :loading="saving" @click="submit" />
    </view>
  </view>
</template>

<style lang="scss" scoped>
.row { display: flex; gap: 12rpx; padding: 22rpx 20rpx; align-items: center; }
.body { flex: 1; min-width: 0; }
.title { font-size: 28rpx; font-weight: 600; display: block; margin-bottom: 8rpx; }
.meta { font-size: 22rpx; }
.danger { color: #e11d48; font-size: 24rpx; }
.footer-tip { text-align: center; padding: 24rpx; }
.form { margin: 16rpx 20rpx 32rpx; padding: 28rpx; }
.section {
  font-size: 28rpx; font-weight: 700; color: #1a1a1a;
  padding-left: 14rpx; border-left: 6rpx solid #2769dc; margin-bottom: 20rpx;
}
.gap { height: 18rpx; }
.toggle { display: flex; align-items: center; gap: 12rpx; font-size: 28rpx; }
.box {
  width: 36rpx; height: 36rpx; border: 2rpx solid #c8c9cc; border-radius: 6rpx;
}
.box.on { background: #2769dc; border-color: #2769dc; }
</style>
