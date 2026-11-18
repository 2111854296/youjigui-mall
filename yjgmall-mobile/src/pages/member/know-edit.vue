<script setup lang="ts">
import { reactive, ref } from "vue";
import { onLoad } from "@dcloudio/uni-app";
import { api } from "@/utils/request";
import { ensureLogin, errMsg } from "@/utils/auth";

const editId = ref<number | null>(null);
const loading = ref(false);
const saving = ref(false);
const form = reactive({ title: "", content: "", reward: 0 });

onLoad(async (q: any) => {
  if (!ensureLogin()) return;
  const id = q?.id ? Number(q.id) : null;
  if (!id) {
    uni.setNavigationBarTitle({ title: "我要提问" });
    return;
  }
  editId.value = id;
  uni.setNavigationBarTitle({ title: "编辑提问" });
  loading.value = true;
  try {
    const res = await api<any>(`/member/know/${id}`);
    const a = res?.data || {};
    form.title = a.title || "";
    form.content = a.content || "";
    form.reward = a.reward || 0;
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
    const body = { ...form, reward: Number(form.reward) || 0 };
    if (editId.value) {
      await api(`/member/know/${editId.value}`, { method: "PUT", body });
    } else {
      await api("/member/know", { method: "POST", body });
    }
    uni.showToast({ title: "已保存", icon: "success" });
    setTimeout(() => uni.redirectTo({ url: "/pages/member/know" }), 400);
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
      <view class="section">问题</view>
      <u-input v-model="form.title" placeholder="标题 *" border="surround" />
      <view class="gap" />
      <u-input v-model="form.reward" placeholder="悬赏积分" border="surround" type="number" />
      <view class="gap" />
      <u-textarea v-model="form.content" placeholder="补充问题背景、已尝试方法…" height="200" border="surround" />
      <view class="gap" />
      <u-button
        type="primary"
        color="#2769dc"
        :text="editId ? '保存' : '发布问题'"
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
.footer-tip { text-align: center; padding: 32rpx; }
</style>
