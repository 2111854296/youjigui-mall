<script setup lang="ts">
import { computed, reactive, ref } from "vue";
import { onShow, onPullDownRefresh } from "@dcloudio/uni-app";
import { api, mediaUrl } from "@/utils/request";
import { ensureLogin, errMsg } from "@/utils/auth";

const items = ref<any[]>([]);
const addresses = ref<any[]>([]);
const addressId = ref<number | null>(null);
const loading = ref(false);
const checking = ref(false);
const form = reactive({
  receiver_name: "",
  receiver_phone: "",
  receiver_address: "",
  remark: "",
});

const total = computed(() =>
  items.value.reduce((s, i) => s + Number(i.subtotal || (Number(i.price || i.product?.price || 0) * Number(i.quantity || 1))), 0),
);

function pickAddress(a: any) {
  if (!a) return;
  addressId.value = a.id;
  form.receiver_name = a.name || a.receiver_name || "";
  form.receiver_phone = a.phone || a.mobile || "";
  form.receiver_address =
    [a.area, a.address].filter(Boolean).join(" ") ||
    a.full_address ||
    a.full ||
    a.address ||
    "";
}

async function load() {
  if (!ensureLogin()) return;
  loading.value = true;
  try {
    const [cartRes, addrRes] = await Promise.all([
      api<any>("/member/cart"),
      api<any>("/member/addresses"),
    ]);
    items.value = cartRes?.data || [];
    addresses.value = addrRes?.data || [];
    if (addresses.value.length && !addressId.value) {
      const def = addresses.value.find((a) => a.is_default) || addresses.value[0];
      pickAddress(def);
    }
  } catch (e: any) {
    uni.showToast({ title: errMsg(e), icon: "none" });
  } finally {
    loading.value = false;
    uni.stopPullDownRefresh();
  }
}

async function changeQty(item: any, delta: number) {
  const next = Math.max(1, Number(item.quantity || 1) + delta);
  try {
    await api(`/member/cart/${item.id}`, { method: "PUT", body: { quantity: next } });
    await load();
  } catch (e: any) {
    uni.showToast({ title: errMsg(e), icon: "none" });
  }
}

async function remove(id: number) {
  try {
    await api(`/member/cart/${id}`, { method: "DELETE" });
    await load();
  } catch (e: any) {
    uni.showToast({ title: errMsg(e), icon: "none" });
  }
}

function onPickAddr(e: any) {
  const idx = Number(e.detail?.value ?? 0);
  pickAddress(addresses.value[idx]);
}

async function checkout() {
  if (!form.receiver_name.trim() || !form.receiver_phone.trim() || !form.receiver_address.trim()) {
    uni.showToast({ title: "请填写收货信息", icon: "none" });
    return;
  }
  checking.value = true;
  try {
    const res: any = await api("/member/checkout", { method: "POST", body: { ...form } });
    const oid = res?.data?.id;
    if (oid) {
      uni.navigateTo({ url: `/pages/member/fund-pay-detail?id=${oid}` });
      return;
    }
    uni.showToast({ title: res?.message || "请上传付款凭证", icon: "none" });
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "结算失败"), icon: "none" });
  } finally {
    checking.value = false;
  }
}

function goAddresses() {
  uni.navigateTo({ url: "/pages/member/addresses" });
}

onShow(load);
onPullDownRefresh(load);
</script>

<template>
  <view class="page">
    <view v-for="item in items" :key="item.id" class="row card">
      <image class="pic" :src="mediaUrl(item.thumb || item.product?.thumb)" mode="aspectFit" />
      <view class="body">
        <text class="title">{{ item.title || item.product?.title }}</text>
        <text class="price">¥{{ item.price || item.product?.price || "—" }}</text>
        <view class="qty">
          <text class="qty-btn" @click="changeQty(item, -1)">−</text>
          <text class="qty-num">{{ item.quantity || 1 }}</text>
          <text class="qty-btn" @click="changeQty(item, 1)">+</text>
        </view>
        <text class="sub muted">小计 ¥{{ item.subtotal ?? ((Number(item.price || item.product?.price || 0) * Number(item.quantity || 1)).toFixed(2)) }}</text>
      </view>
      <text class="danger" @click="remove(item.id)">删除</text>
    </view>

    <view class="footer-tip muted" v-if="loading || !items.length">
      <text v-if="loading">加载中...</text>
      <text v-else>购物车为空</text>
    </view>

    <view v-if="items.length" class="card checkout">
      <view class="total-row">
        <text>合计</text>
        <text class="price">¥{{ total.toFixed(2) }}</text>
      </view>
      <view class="section">收货信息</view>
      <picker v-if="addresses.length" :range="addresses" range-key="name" @change="onPickAddr">
        <view class="picker-row">
          <text class="muted">选择地址</text>
          <text class="val">{{ form.receiver_name || "请选择" }} · {{ form.receiver_phone }}</text>
          <u-icon name="arrow-right" color="#ccc" size="14" />
        </view>
      </picker>
      <view class="gap" />
      <u-input v-model="form.receiver_name" placeholder="收货人 *" border="surround" />
      <view class="gap" />
      <u-input v-model="form.receiver_phone" placeholder="手机 *" border="surround" />
      <view class="gap" />
      <u-input v-model="form.receiver_address" placeholder="地址 *" border="surround" />
      <view class="gap" />
      <u-input v-model="form.remark" placeholder="备注" border="surround" />
      <view class="gap" />
      <u-button type="primary" color="#2769dc" text="对公付款" :loading="checking" @click="checkout" />
      <view class="gap" />
      <u-button plain color="#2769dc" text="管理地址" @click="goAddresses" />
    </view>
  </view>
</template>

<style lang="scss" scoped>
.row { display: flex; gap: 16rpx; padding: 20rpx; align-items: flex-start; }
.pic { width: 120rpx; height: 120rpx; background: #f7f8fa; border-radius: 8rpx; flex-shrink: 0; }
.body { flex: 1; min-width: 0; }
.title { font-size: 28rpx; display: block; margin-bottom: 8rpx; }
.price { font-size: 26rpx; color: #c2410c; font-weight: 600; }
.qty { display: flex; align-items: center; gap: 16rpx; margin: 12rpx 0 8rpx; }
.qty-btn {
  width: 48rpx; height: 48rpx; border: 1rpx solid #dde3ea; text-align: center; line-height: 48rpx;
  border-radius: 8rpx; font-size: 28rpx; color: #333;
}
.qty-num { min-width: 40rpx; text-align: center; font-size: 28rpx; }
.sub { font-size: 22rpx; display: block; }
.danger { color: #e11d48; font-size: 24rpx; }
.footer-tip { text-align: center; padding: 32rpx; }
.checkout { padding: 28rpx; margin-bottom: 32rpx; }
.total-row {
  display: flex; justify-content: space-between; align-items: center;
  margin-bottom: 20rpx; font-size: 30rpx; font-weight: 600;
}
.section {
  font-size: 28rpx; font-weight: 700; padding-left: 14rpx;
  border-left: 6rpx solid #2769dc; margin-bottom: 16rpx;
}
.picker-row {
  display: flex; align-items: center; gap: 12rpx;
  padding: 20rpx 16rpx; border: 1rpx solid #dadbde; border-radius: 8rpx;
}
.val { flex: 1; font-size: 26rpx; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.gap { height: 18rpx; }
</style>
