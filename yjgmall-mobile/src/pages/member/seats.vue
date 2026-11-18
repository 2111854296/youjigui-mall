<script setup lang="ts">
import { reactive, ref } from "vue";
import { onLoad, onShow } from "@dcloudio/uni-app";
import { api } from "@/utils/request";
import { ensureLogin, errMsg } from "@/utils/auth";

const seats = ref<any[]>([]);
const seatLimit = ref(1);
const loading = ref(false);
const inviting = ref(false);
const form = reactive({ mobile: "", email: "" });
const pendingToken = ref("");

const statusLabel: Record<string, string> = {
  pending: "待接受",
  active: "已加入",
  revoked: "已回收",
};

function inviteLink(token: string) {
  if (!token) return "";
  // #ifdef H5
  if (typeof location !== "undefined") {
    const base = location.href.split("#")[0].replace(/\/?$/, "/");
    return `${base}#/pages/member/seats?token=${encodeURIComponent(token)}`;
  }
  // #endif
  return `/pages/member/seats?token=${encodeURIComponent(token)}`;
}

function copyText(text: string) {
  // #ifdef H5
  if (typeof navigator !== "undefined" && navigator.clipboard?.writeText) {
    navigator.clipboard.writeText(text).then(
      () => uni.showToast({ title: "已复制", icon: "success" }),
      () => uni.setClipboardData({ data: text }),
    );
    return;
  }
  // #endif
  uni.setClipboardData({ data: text });
}

async function load() {
  if (!ensureLogin()) return;
  loading.value = true;
  try {
    const res = await api<any>("/vip/seats");
    const d = res?.data || res;
    seats.value = d.items || [];
    seatLimit.value = d.seat_limit || 1;
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "加载失败（席位成员无需管理）"), icon: "none" });
  } finally {
    loading.value = false;
  }
}

async function invite() {
  inviting.value = true;
  try {
    const res = await api<any>("/vip/seats/invite", {
      method: "POST",
      body: { ...form },
    });
    const token = res?.data?.invite_token || res?.invite_token;
    uni.showToast({ title: token ? "链接已复制，请发给对方" : (res?.message || "已生成"), icon: "success" });
    if (token) copyText(inviteLink(token));
    form.mobile = "";
    form.email = "";
    await load();
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "邀请失败"), icon: "none" });
  } finally {
    inviting.value = false;
  }
}

async function revoke(id: number) {
  const ok = await new Promise<boolean>((resolve) => {
    uni.showModal({
      title: "确认回收",
      content: "确认回收该席位？",
      success: (r) => resolve(!!r.confirm),
      fail: () => resolve(false),
    });
  });
  if (!ok) return;
  try {
    await api(`/vip/seats/${id}/revoke`, { method: "POST" });
    uni.showToast({ title: "已回收", icon: "success" });
    await load();
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "操作失败"), icon: "none" });
  }
}

async function acceptToken(token: string) {
  try {
    const res = await api<any>("/vip/seats/accept", {
      method: "POST",
      body: { token },
    });
    uni.showToast({
      title: res?.data?.message || res?.message || "已加入席位",
      icon: "success",
    });
    await load();
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "接受失败"), icon: "none" });
  }
}

onLoad((q) => {
  if (q?.token) pendingToken.value = String(q.token);
});

onShow(async () => {
  await load();
  if (pendingToken.value) {
    const t = pendingToken.value;
    pendingToken.value = "";
    await acceptToken(t);
  }
});
</script>

<template>
  <view class="page">
    <view class="card">
      <view class="section">企业授权席位</view>
      <text class="lead muted">席位上限 {{ seatLimit }}（含企业主账号）。黄金 2 / 星钻 3。额度全企业共享，不重复扣次。</text>
      <text class="lead muted">生成链接后请自行发给对方，不会自动发短信或邮件。</text>
      <view class="gap" />
      <u-input v-model="form.mobile" placeholder="成员手机（选填，仅备注）" border="surround" />
      <view class="gap" />
      <u-input v-model="form.email" placeholder="或邮箱（选填，仅备注）" border="surround" />
      <view class="gap" />
      <u-button type="primary" color="#2769dc" text="生成邀请链接" :loading="inviting" @click="invite" />
    </view>

    <view v-for="s in seats" :key="s.id" class="row card">
      <view class="body">
        <text class="title">{{ statusLabel[s.status] || s.status }} · {{ s.member_name || s.member_user_id || "—" }}</text>
        <text class="meta muted">{{ s.mobile || s.email || "—" }}</text>
      </view>
      <view class="ops">
        <text
          v-if="s.status === 'pending' && s.invite_token"
          class="op"
          @click="copyText(inviteLink(s.invite_token))"
        >复制链接</text>
        <text class="op danger" @click="revoke(s.id)">回收</text>
      </view>
    </view>
    <view class="footer-tip muted">
      <text v-if="loading">加载中...</text>
      <text v-else-if="!seats.length">暂无额外席位邀请</text>
    </view>
  </view>
</template>

<style lang="scss" scoped>
.card { margin: 20rpx; padding: 28rpx; background: #fff; border-radius: 16rpx; }
.section {
  font-size: 28rpx; font-weight: 700; padding-left: 14rpx;
  border-left: 6rpx solid #2769dc; margin-bottom: 12rpx;
}
.lead { display: block; font-size: 24rpx; line-height: 1.45; }
.gap { height: 16rpx; }
.row { display: flex; align-items: center; gap: 12rpx; padding: 22rpx 20rpx; }
.body { flex: 1; min-width: 0; }
.title { font-size: 26rpx; font-weight: 600; display: block; margin-bottom: 6rpx; }
.meta { font-size: 22rpx; display: block; }
.ops { display: flex; flex-direction: column; gap: 8rpx; align-items: flex-end; }
.op { font-size: 24rpx; color: #2769dc; font-weight: 600; }
.op.danger { color: #dc2626; }
.footer-tip { text-align: center; padding: 32rpx; }
.muted { color: #6b7280; }
</style>
