<script setup lang="ts">
import { ref } from "vue";
import { onShow } from "@dcloudio/uni-app";
import { api } from "@/utils/request";
import { ensureLogin, errMsg } from "@/utils/auth";

const packs = ref<any[]>([]);
const ent = ref<any>(null);
const loading = ref(false);
const buying = ref<number | null>(null);

async function load() {
  if (!ensureLogin()) return;
  loading.value = true;
  try {
    const [p, m] = await Promise.all([
      api<any>("/vip/contact-packs"),
      api<any>("/vip/me"),
    ]);
    packs.value = (p?.data || p) as any[];
    ent.value = m?.data || m;
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "加载失败"), icon: "none" });
  } finally {
    loading.value = false;
  }
}

async function buy(id: number) {
  const pack = packs.value.find((p) => p.id === id);
  if (!pack) return;
  const ok = await new Promise<boolean>((resolve) => {
    uni.showModal({
      title: "确认购买",
      content: `将创建对公付款订单「${pack.name}」¥${pack.pay_price}，审核通过后次数到账。`,
      success: (r) => resolve(!!r.confirm),
      fail: () => resolve(false),
    });
  });
  if (!ok) return;
  buying.value = id;
  try {
    const res = await api<any>("/vip/contact-packs/buy", {
      method: "POST",
      body: { pack_id: id },
    });
    const oid = res?.data?.id;
    if (oid) {
      uni.navigateTo({ url: `/pages/member/fund-pay-detail?id=${oid}` });
      return;
    }
    uni.showToast({ title: res?.message || "请上传付款凭证", icon: "none" });
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "购买失败"), icon: "none" });
  } finally {
    buying.value = null;
  }
}

function goFinance() {
  uni.navigateTo({ url: "/pages/member/finance" });
}

onShow(load);
</script>

<template>
  <view class="page">
    <view class="card">
      <view class="section">联系方式次数包</view>
      <text class="lead muted">全价 ¥100/2次、¥500/10次。会员折扣：白银 9 折 / 黄金 8 折 / 星钻 7 折。对公转账上传凭证，审核通过后到账。</text>
      <text class="finance-link" @click="goFinance">去充值 ›</text>
      <text v-if="ent" class="meta">
        当前剩余可查看次数：{{ ent.contact_remain_total ?? 0 }}
        （月额度剩余 {{ ent.contact_remain_month ?? 0 }} + 赠送/次数包 {{ ent.contact_bonus_left ?? 0 }}）
      </text>
      <view v-if="loading" class="muted tip">加载中...</view>
    </view>

    <view v-for="p in packs" :key="p.id" class="pack card">
      <text class="pname">{{ p.name }}</text>
      <text class="ptimes muted">{{ p.times }} 次联系方式查看</text>
      <view class="price-row">
        <text v-if="p.discount < 1" class="old">¥{{ p.list_price }}</text>
        <text class="price">¥{{ p.pay_price }}</text>
        <text v-if="p.discount < 1" class="disc">（{{ Math.round(p.discount * 10) }}折）</text>
      </view>
      <u-button
        type="primary"
        color="#2769dc"
        :text="buying === p.id ? '提交中…' : '对公付款'"
        :loading="buying === p.id"
        @click="buy(p.id)"
      />
    </view>
    <view v-if="!loading && !packs.length" class="footer-tip muted">暂无次数包</view>
  </view>
</template>

<style lang="scss" scoped>
.card { margin: 20rpx; padding: 28rpx; background: #fff; border-radius: 16rpx; }
.section {
  font-size: 28rpx; font-weight: 700; padding-left: 14rpx;
  border-left: 6rpx solid #2769dc; margin-bottom: 12rpx;
}
.lead { display: block; font-size: 24rpx; line-height: 1.45; }
.finance-link {
  display: inline-block; margin-top: 10rpx; font-size: 26rpx;
  color: #2769dc; font-weight: 600;
}
.meta { display: block; margin-top: 14rpx; font-size: 24rpx; color: #374151; line-height: 1.45; }
.tip { padding: 12rpx 0; }
.pack .pname { font-size: 30rpx; font-weight: 700; display: block; }
.ptimes { display: block; margin-top: 8rpx; font-size: 24rpx; }
.price-row { display: flex; align-items: baseline; gap: 10rpx; margin: 16rpx 0; }
.old { font-size: 24rpx; color: #9ca3af; text-decoration: line-through; }
.price { font-size: 40rpx; font-weight: 800; color: #c2410c; }
.disc { font-size: 22rpx; color: #2769dc; font-weight: 600; }
.footer-tip { text-align: center; padding: 32rpx; }
.muted { color: #6b7280; }
</style>
