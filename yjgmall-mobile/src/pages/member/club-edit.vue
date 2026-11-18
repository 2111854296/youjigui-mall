<script setup lang="ts">
import { reactive, ref } from "vue";
import { onLoad } from "@dcloudio/uni-app";
import { api } from "@/utils/request";
import { ensureLogin, errMsg } from "@/utils/auth";

const editId = ref<number | null>(null);
const loading = ref(false);
const saving = ref(false);
const form = reactive({ title: "", circle: "行情圈", content: "" });

const circleOpts = ["行情圈", "技术圈", "综合"];

function pickCircle(e: any) {
  form.circle = circleOpts[Number(e.detail.value)] || "行情圈";
}

onLoad(async (q: any) => {
  if (!ensureLogin()) return;
  const id = q?.id ? Number(q.id) : null;
  if (!id) {
    uni.setNavigationBarTitle({ title: "商圈发帖" });
    return;
  }
  editId.value = id;
  uni.setNavigationBarTitle({ title: "编辑帖子" });
  loading.value = true;
  try {
    const res = await api<any>(`/member/club/${id}`);
    const a = res?.data || {};
    form.title = a.title || "";
    form.circle = a.circle || "行情圈";
    form.content = a.content || "";
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "加载失败"), icon: "none" });
  } finally {
    loading.value = false;
  }
});

async function submit() {
  if (!form.title.trim()) {
    uni.showToast({ title: "请填写标题", icon: "none" });
    return;
  }
  saving.value = true;
  try {
    if (editId.value) {
      await api(`/member/club/${editId.value}`, { method: "PUT", body: { ...form } });
    } else {
      await api("/member/club", { method: "POST", body: { ...form } });
    }
    uni.showToast({ title: "已保存", icon: "success" });
    setTimeout(() => uni.redirectTo({ url: "/pages/member/club" }), 400);
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "保存失败"), icon: "none" });
  } finally {
    saving.value = false;
  }
}
</script>

<template>
  <view class="page">
    <view v-if="loading" class="footer-tip muted">加载中...</view>
    <view v-else class="card form">
      <view class="section">帖子</view>
      <u-input v-model="form.title" placeholder="标题 *" border="surround" />
      <view class="gap" />
      <picker :range="circleOpts" @change="pickCircle">
        <view class="picker-row"><text class="muted">圈子</text><text class="val">{{ form.circle }}</text></view>
      </picker>
      <view class="gap" />
      <u-textarea v-model="form.content" placeholder="分享行情、技术或行业见闻…" height="220" border="surround" />
      <view class="gap" />
      <u-button
        type="primary"
        color="#2769dc"
        :text="editId ? '保存并重新审核' : '提交审核'"
        :loading="saving"
        @click="submit"
      />
    </view>
  </view>
</template>

<style lang="scss" scoped>
.form { margin: 20rpx; padding: 28rpx; }
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
.footer-tip { text-align: center; padding: 32rpx; }
</style>
