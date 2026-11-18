<script setup lang="ts">
import { ref } from "vue";
import { onShow, onPullDownRefresh } from "@dcloudio/uni-app";
import { api } from "@/utils/request";
import { ensureLogin, errMsg } from "@/utils/auth";
import { statusLabel } from "@/utils/status";

const items = ref<any[]>([]);
const loading = ref(false);

async function load() {
  if (!ensureLogin()) return;
  loading.value = true;
  try {
    const res = await api<any>("/member/articles");
    items.value = res?.data || [];
  } catch (e: any) {
    uni.showToast({ title: errMsg(e), icon: "none" });
  } finally {
    loading.value = false;
    uni.stopPullDownRefresh();
  }
}

function create() {
  uni.navigateTo({ url: "/pages/member/article-edit" });
}

function edit(id: number) {
  uni.navigateTo({ url: `/pages/member/article-edit?id=${id}` });
}

async function remove(id: number) {
  uni.showModal({
    title: "确认删除？",
    success: async (r) => {
      if (!r.confirm) return;
      try {
        await api(`/member/articles/${id}`, { method: "DELETE" });
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
    <view class="top">
      <u-button type="primary" color="#2769dc" size="small" text="发布分享" @click="create" />
    </view>
    <view class="tip muted">审核通过后展示在信息资讯·技术分享</view>
    <view v-for="a in items" :key="a.id" class="row card">
      <view class="body">
        <text class="title">{{ a.title }}</text>
        <text class="meta muted">{{ statusLabel(a.status) }}</text>
      </view>
      <view class="ops">
        <text class="link" @click="edit(a.id)">编辑</text>
        <text class="link danger" @click="remove(a.id)">删除</text>
      </view>
    </view>
    <view v-if="!loading && !items.length" class="empty muted">暂无技术分享</view>
  </view>
</template>

<style scoped>
.page { padding: 12px; }
.top { display: flex; justify-content: flex-end; margin-bottom: 8px; }
.tip { font-size: 12px; margin-bottom: 10px; }
.row { display: flex; gap: 10px; align-items: center; padding: 12px; margin-bottom: 10px; }
.body { flex: 1; min-width: 0; }
.title { display: block; font-size: 15px; color: #1e293b; }
.meta { display: block; margin-top: 4px; font-size: 12px; }
.ops { display: flex; gap: 12px; flex-shrink: 0; }
.link { color: #2769dc; font-size: 13px; }
.link.danger { color: #dc2626; }
.empty { text-align: center; padding: 40px 0; }
.muted { color: #64748b; }
</style>
