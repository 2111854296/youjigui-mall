<script setup lang="ts">
import { computed, ref } from "vue";
import { onLoad, onShow, onHide, onUnload, onPullDownRefresh } from "@dcloudio/uni-app";
import { api, mediaUrl } from "@/utils/request";
import { ensureLogin, errMsg } from "@/utils/auth";

type PayOrder = {
  id: number;
  order_no: string;
  title?: string;
  pay_type?: string;
  pay_type_label?: string;
  amount_yuan: string;
  status: string;
  status_label: string;
  remain_seconds: number;
  expire_at?: string;
  bank_name?: string;
  bank_account?: string;
  bank_account_name?: string;
  transfer_note?: string;
  voucher_url?: string | null;
  admin_remark?: string | null;
  can_upload_voucher?: boolean;
  can_cancel?: boolean;
  effect_hint?: string;
  done_label?: string;
  return_path?: string;
  pay_hint?: string;
};

const orderId = ref("");
const order = ref<PayOrder | null>(null);
const voucher = ref("");
const err = ref("");
const msg = ref("");
const loading = ref(true);
const busy = ref(false);
const remain = ref(0);
let tickTimer: ReturnType<typeof setInterval> | null = null;
let pollTimer: ReturnType<typeof setInterval> | null = null;

const countdown = computed(() => {
  const s = Math.max(0, remain.value);
  const m = Math.floor(s / 60);
  const sec = s % 60;
  return `${String(m).padStart(2, "0")}:${String(sec).padStart(2, "0")}`;
});
const isUrgent = computed(() => remain.value > 0 && remain.value <= 300);
const isPendingPay = computed(() => order.value?.status === "pending_pay");
const isPendingReview = computed(() => order.value?.status === "pending_review");
const isApproved = computed(() => order.value?.status === "approved");
const isRejected = computed(() => order.value?.status === "rejected");
const isDead = computed(() => ["expired", "cancelled"].includes(String(order.value?.status || "")));
const voucherSrc = computed(() => (order.value?.voucher_url ? mediaUrl(order.value.voucher_url, "") : ""));

function stopTimers() {
  if (tickTimer) clearInterval(tickTimer);
  if (pollTimer) clearInterval(pollTimer);
  tickTimer = null;
  pollTimer = null;
}

function startTick() {
  if (tickTimer) clearInterval(tickTimer);
  tickTimer = setInterval(() => {
    if (!isPendingPay.value) return;
    if (remain.value <= 0) {
      void load();
      return;
    }
    remain.value -= 1;
    if (remain.value <= 0) void load();
  }, 1000);
}

function startPoll() {
  if (pollTimer) clearInterval(pollTimer);
  pollTimer = setInterval(() => {
    if (isPendingReview.value) void loadQuiet();
  }, 20000);
}

function apply(data: PayOrder) {
  order.value = data;
  voucher.value = data.voucher_url || voucher.value;
  remain.value = Number(data.remain_seconds || 0);
  startTick();
  startPoll();
}

async function load() {
  if (!orderId.value) return;
  if (!ensureLogin()) return;
  loading.value = true;
  err.value = "";
  try {
    const res = await api<{ data: PayOrder }>(`/member/fund/orders/${orderId.value}`);
    apply(res.data);
  } catch (e: any) {
    err.value = errMsg(e, "订单加载失败");
    order.value = null;
  } finally {
    loading.value = false;
    uni.stopPullDownRefresh();
  }
}

async function loadQuiet() {
  try {
    const res = await api<{ data: PayOrder }>(`/member/fund/orders/${orderId.value}`);
    apply(res.data);
    if (!isPendingReview.value && pollTimer) {
      clearInterval(pollTimer);
      pollTimer = null;
    }
  } catch {
    /* ignore */
  }
}

function copyText(text: string, label: string) {
  const t = String(text || "").trim();
  if (!t || t === "—") return;
  uni.setClipboardData({
    data: t,
    success: () => uni.showToast({ title: `已复制${label}`, icon: "none" }),
  });
}

function previewVoucher() {
  if (!voucherSrc.value) return;
  uni.previewImage({ urls: [voucherSrc.value] });
}

function mobileReturn(path?: string) {
  const p = String(path || "");
  const news = p.match(/^\/news\/(\d+)/);
  if (news) return `/pages/news/detail?id=${news[1]}`;
  const map: Record<string, string> = {
    "/member/contact-packs": "/pages/member/contact-packs",
    "/member/orders": "/pages/member/orders",
    "/member/credit": "/pages/member/credit",
    "/member/finance": "/pages/member/finance",
    "/member/fund-pay": "/pages/member/fund-pay",
  };
  return map[p] || "/pages/member/fund-pay";
}

function goReturn() {
  uni.redirectTo({ url: mobileReturn(order.value?.return_path) });
}

function goList() {
  uni.redirectTo({ url: "/pages/member/fund-pay" });
}

function goDeadBack() {
  uni.redirectTo({
    url: order.value?.pay_type === "charge" ? "/pages/member/finance" : "/pages/member/fund-pay",
  });
}

async function submitVoucher() {
  if (!order.value || busy.value) return;
  if (!voucher.value) {
    err.value = "请先上传付款凭证截图";
    return;
  }
  busy.value = true;
  err.value = "";
  msg.value = "";
  try {
    const res = await api<{ data: PayOrder; message?: string }>(`/member/fund/orders/${order.value.id}/voucher`, {
      method: "POST",
      body: { voucher_url: voucher.value },
    });
    apply(res.data);
    msg.value = res.message || "凭证已提交，等待财务审核";
  } catch (e: any) {
    err.value = errMsg(e, "提交失败");
  } finally {
    busy.value = false;
  }
}

async function cancelOrder() {
  if (!order.value || busy.value) return;
  const ok = await new Promise<boolean>((resolve) => {
    uni.showModal({
      title: "取消订单",
      content: "确认取消该付款订单？",
      success: (r) => resolve(!!r.confirm),
      fail: () => resolve(false),
    });
  });
  if (!ok) return;
  busy.value = true;
  try {
    const res = await api<{ data: PayOrder }>(`/member/fund/orders/${order.value.id}/cancel`, { method: "POST" });
    apply(res.data);
    msg.value = "订单已取消";
  } catch (e: any) {
    err.value = errMsg(e, "取消失败");
  } finally {
    busy.value = false;
  }
}

onLoad((q) => {
  orderId.value = String(q?.id || "");
});
onShow(() => {
  if (orderId.value) void load();
});
onPullDownRefresh(() => {
  void load();
});
onHide(stopTimers);
onUnload(stopTimers);
</script>

<template>
  <view class="page">
    <view v-if="loading && !order" class="footer-tip muted">订单加载中...</view>
    <view v-else-if="err && !order" class="card err">{{ err }}</view>

    <template v-else-if="order">
      <view class="hero" :class="{ urgent: isUrgent && isPendingPay, dead: isDead }">
        <text class="pill">{{ order.status_label }}</text>
        <text class="hero-k">应付金额</text>
        <text class="amount">¥{{ order.amount_yuan }}</text>
        <text class="grade">{{ order.pay_type_label || order.title || "账户充值" }}</text>
        <view v-if="isPendingPay" class="hero-sub">
          <text class="cd" :class="{ blink: isUrgent }">{{ countdown }}</text>
          <text class="cd-hint">超时未上传凭证订单将自动关闭</text>
        </view>
        <view v-else-if="isPendingReview" class="hero-sub">
          <text class="cd-hint">{{ order.effect_hint || "财务核对到账后生效" }}</text>
        </view>
        <view v-else-if="isApproved" class="hero-sub">
          <text class="cd-hint">{{ order.done_label || "已通过" }}</text>
          <text class="back" @click="goReturn">返回查看</text>
        </view>
      </view>

      <view v-if="msg" class="card ok">{{ msg }}</view>
      <view v-if="err" class="card err">{{ err }}</view>
      <view v-if="isRejected && order.admin_remark" class="card err">驳回原因：{{ order.admin_remark }}</view>

      <view class="card block">
        <view class="hd">
          <text class="hd-t">订单信息</text>
          <text class="mini" @click="copyText(order.order_no, '订单号')">复制订单号</text>
        </view>
        <view class="kv"><text class="k">订单号</text><text class="v mono">{{ order.order_no }}</text></view>
        <view class="kv"><text class="k">类型</text><text class="v">{{ order.pay_type_label || order.title || "账户充值" }}</text></view>
        <view class="kv"><text class="k">应付金额</text><text class="v price">¥{{ order.amount_yuan }}</text></view>
        <view class="kv"><text class="k">支付截止</text><text class="v">{{ String(order.expire_at || "").replace("T", " ").slice(0, 19) }}</text></view>
      </view>

      <view v-if="!isDead && !isApproved" class="card block">
        <text class="hd-t">收款账户（对公转账）</text>
        <text class="tip">请按下列信息转账，金额须与应付金额一致；备注请填写订单号以便核对。</text>
        <text v-if="order.pay_hint" class="hint-box">{{ order.pay_hint }}</text>
        <view class="bank">
          <text class="k">户名</text>
          <text class="v">{{ order.bank_account_name || "—" }}</text>
          <text class="mini" @click="copyText(order.bank_account_name || '', '户名')">复制</text>
        </view>
        <view class="bank">
          <text class="k">开户行</text>
          <text class="v">{{ order.bank_name || "—" }}</text>
          <text class="mini" @click="copyText(order.bank_name || '', '开户行')">复制</text>
        </view>
        <view class="bank hi">
          <text class="k">账号</text>
          <text class="v mono">{{ order.bank_account || "—" }}</text>
          <text class="mini on" @click="copyText(order.bank_account || '', '账号')">复制</text>
        </view>
        <view class="bank">
          <text class="k">备注</text>
          <text class="v mono">{{ order.transfer_note || order.order_no }}</text>
          <text class="mini" @click="copyText(order.transfer_note || order.order_no, '转账备注')">复制</text>
        </view>
        <view class="bank">
          <text class="k">金额</text>
          <text class="v price">¥{{ order.amount_yuan }}</text>
          <text class="mini" @click="copyText(order.amount_yuan, '金额')">复制</text>
        </view>
      </view>

      <view v-if="isPendingReview" class="card block review">
        <text class="hd-t">审核进度</text>
        <text class="tip">凭证已提交，财务核对到账后生效。本页会定时刷新，也可在对公付款列表查看。</text>
        <text v-if="voucherSrc" class="link" @click="previewVoucher">查看已提交凭证</text>
        <view class="actions">
          <text class="btn ghost" @click="load">刷新状态</text>
          <text v-if="order.can_cancel" class="btn ghost" @click="cancelOrder">取消订单</text>
        </view>
      </view>

      <view v-if="order.can_upload_voucher" class="card block">
        <text class="hd-t">{{ isPendingReview ? "更换付款凭证" : "上传付款凭证" }}</text>
        <text class="tip">
          {{ isPendingReview ? "如截图不清晰或金额有误，可重新上传后继续等待审核。" : isRejected ? "请按驳回原因修正后重新上传银行回单。" : "请上传银行回单或网银转账成功截图（含金额、对方账户、时间）。" }}
        </text>
        <ImageUpload v-model="voucher" label="付款凭证" hint="建议上传清晰完整的转账回单" />
        <view class="actions">
          <text class="btn primary" @click="submitVoucher">
            {{ busy ? "提交中…" : (isRejected ? "重新提交凭证" : isPendingReview ? "更换凭证并提交" : "确认已转账并提交") }}
          </text>
          <text v-if="order.can_cancel && !isPendingReview" class="btn ghost" @click="cancelOrder">取消订单</text>
        </view>
      </view>

      <view v-else-if="voucherSrc" class="card block">
        <text class="hd-t">已提交凭证</text>
        <text class="link" @click="previewVoucher">查看凭证图片</text>
      </view>

      <view v-if="isDead" class="card block">
        <text class="tip">该订单已结束。如需继续，请重新发起付款。</text>
        <text class="btn primary" @click="goDeadBack">返回</text>
      </view>

      <view class="steps">
        <text class="step on">1 创建对公付款订单</text>
        <text class="step on">2 在时限内对公转账（备注订单号）</text>
        <text class="step" :class="{ on: isPendingReview || isApproved || !!order.voucher_url }">3 上传付款凭证</text>
        <text class="step" :class="{ on: isApproved }">4 {{ order.effect_hint || "财务审核通过后生效" }}</text>
      </view>
      <text class="list-link" @click="goList">全部付款订单</text>
    </template>
  </view>
</template>

<style lang="scss" scoped>
.err { margin: 16rpx 20rpx; padding: 20rpx 24rpx; color: #b91c1c; font-size: 26rpx; }
.ok { margin: 16rpx 20rpx; padding: 20rpx 24rpx; color: #047857; background: #ecfdf5; font-size: 26rpx; }
.hero {
  margin: 16rpx 20rpx; padding: 28rpx 28rpx 24rpx; border-radius: 20rpx; color: #fff;
  background: linear-gradient(135deg, #0f766e, #14b8a6);
}
.hero.urgent { background: linear-gradient(135deg, #c2410c, #ea580c); }
.hero.dead { background: linear-gradient(135deg, #64748b, #94a3b8); }
.pill {
  display: inline-block; font-size: 22rpx; font-weight: 700; padding: 4rpx 16rpx;
  border-radius: 999rpx; background: rgba(255,255,255,.22); margin-bottom: 12rpx;
}
.hero-k { display: block; font-size: 24rpx; opacity: .9; }
.amount { display: block; font-size: 64rpx; font-weight: 800; line-height: 1.2; margin-top: 4rpx; }
.grade { display: block; margin-top: 8rpx; font-size: 24rpx; opacity: .9; }
.hero-sub { margin-top: 16rpx; }
.cd { display: block; font-size: 56rpx; font-weight: 800; letter-spacing: 2rpx; }
.cd.blink { opacity: .7; }
.cd-hint { display: block; margin-top: 6rpx; font-size: 22rpx; opacity: .9; }
.back {
  display: inline-block; margin-top: 16rpx; padding: 10rpx 24rpx; border-radius: 999rpx;
  background: #fff; color: #0f766e; font-size: 26rpx; font-weight: 700;
}
.block { padding: 24rpx 28rpx 28rpx; }
.review { background: #f0fdfa; }
.hd { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8rpx; }
.hd-t { display: block; font-size: 30rpx; font-weight: 700; margin-bottom: 12rpx; }
.tip { display: block; font-size: 24rpx; color: #64748b; line-height: 1.5; margin-bottom: 16rpx; }
.hint-box {
  display: block; margin-bottom: 16rpx; padding: 16rpx 18rpx; border-radius: 12rpx;
  background: #fffbeb; color: #92400e; font-size: 24rpx; line-height: 1.5;
}
.kv { display: flex; justify-content: space-between; gap: 16rpx; padding: 10rpx 0; }
.kv .k { color: #94a3b8; font-size: 24rpx; flex: none; }
.kv .v { font-size: 26rpx; font-weight: 600; text-align: right; word-break: break-all; }
.bank {
  display: flex; align-items: center; gap: 12rpx; padding: 16rpx 18rpx; margin-bottom: 12rpx;
  border-radius: 12rpx; background: #f6f9fe;
}
.bank.hi { background: #ecfdf5; }
.bank .k { width: 88rpx; flex: none; font-size: 24rpx; color: #64748b; }
.bank .v { flex: 1; min-width: 0; font-size: 28rpx; font-weight: 700; word-break: break-all; }
.mono { font-family: ui-monospace, Menlo, Consolas, monospace; }
.price { color: #c2410c; font-weight: 800; }
.mini {
  flex: none; font-size: 22rpx; font-weight: 700; color: #2769dc;
  padding: 6rpx 16rpx; border-radius: 999rpx; background: #fff; border: 1rpx solid #dbe4f0;
}
.mini.on { background: #0f766e; color: #fff; border-color: #0f766e; }
.link { display: inline-block; color: #2769dc; font-size: 26rpx; font-weight: 700; margin-bottom: 12rpx; }
.actions { display: flex; flex-wrap: wrap; gap: 16rpx; margin-top: 20rpx; }
.btn {
  display: inline-flex; align-items: center; justify-content: center;
  min-height: 72rpx; padding: 0 28rpx; border-radius: 12rpx; font-size: 28rpx; font-weight: 700;
}
.btn.primary { background: #0f766e; color: #fff; }
.btn.ghost { background: #f1f5f9; color: #475569; }
.steps { margin: 8rpx 28rpx 0; display: flex; flex-direction: column; gap: 10rpx; }
.step { font-size: 24rpx; color: #94a3b8; }
.step.on { color: #0f172a; font-weight: 600; }
.list-link { display: block; text-align: center; margin: 24rpx 0 8rpx; color: #2769dc; font-size: 26rpx; font-weight: 700; }
</style>
