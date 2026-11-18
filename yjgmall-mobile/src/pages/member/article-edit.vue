<script setup lang="ts">
import { reactive, ref } from "vue";
import { onLoad } from "@dcloudio/uni-app";
import { api } from "@/utils/request";
import { ensureLogin, errMsg } from "@/utils/auth";

const editId = ref<number | null>(null);
const loading = ref(false);
const busy = ref(false);
const form = reactive({ title: "", thumb: "", summary: "", content: "" });

async function load(id: number) {
  loading.value = true;
  try {
    const res = await api<any>(`/member/articles/${id}`);
    const t = res?.data || {};
    Object.assign(form, {
      title: t.title || "",
      thumb: t.thumb || "",
      summary: t.summary || "",
      content: t.content || "",
    });
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "加载失败"), icon: "none" });
  } finally {
    loading.value = false;
  }
}

async function save() {
  if (!form.title.trim()) {
    uni.showToast({ title: "请填写标题", icon: "none" });
    return;
  }
  busy.value = true;
  try {
    if (editId.value) {
      await api(`/member/articles/${editId.value}`, { method: "PUT", body: { ...form } });
    } else {
      await api("/member/articles", { method: "POST", body: { ...form } });
    }
    uni.showToast({ title: "已提交审核", icon: "success" });
    setTimeout(() => uni.redirectTo({ url: "/pages/member/articles" }), 400);
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "保存失败"), icon: "none" });
  } finally {
    busy.value = false;
  }
}

onLoad(async (q: any) => {
  if (!ensureLogin()) return;
  const id = q?.id ? Number(q.id) : null;
  if (id) {
    editId.value = id;
    uni.setNavigationBarTitle({ title: "编辑技术分享" });
    await load(id);
  } else {
    uni.setNavigationBarTitle({ title: "发布技术分享" });
  }
});
</script>

<template>
  <view class="page">
    <view class="tip muted">发布后归入前台「信息资讯 · 技术分享」，需审核通过后展示。</view>
    <view class="field">
      <text class="label">标题 *</text>
      <input v-model="form.title" class="input" placeholder="请输入标题" />
    </view>
    <view class="field">
      <text class="label">摘要</text>
      <input v-model="form.summary" class="input" placeholder="可选" />
    </view>
    <view class="field">
      <text class="label">封面</text>
      <ImageUpload v-model="form.thumb" label="封面图" />
    </view>
    <view class="field">
      <text class="label">正文</text>
      <textarea v-model="form.content" class="textarea" placeholder="请输入内容" />
    </view>
    <u-button type="primary" color="#2769dc" :loading="busy" text="提交审核" @click="save" />
  </view>
</template>

<style scoped>
.page { padding: 14px; }
.tip { font-size: 12px; margin-bottom: 12px; }
.field { margin-bottom: 14px; }
.label { display: block; font-size: 13px; color: #334155; margin-bottom: 6px; }
.input, .textarea {
  width: 100%; background: #fff; border: 1px solid #e2e8f0; border-radius: 8px;
  padding: 10px 12px; font-size: 14px; box-sizing: border-box;
}
.textarea { min-height: 160px; }
.muted { color: #64748b; }
</style>
