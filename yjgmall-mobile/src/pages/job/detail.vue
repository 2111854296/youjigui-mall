<script setup lang="ts">
import { ref } from "vue";
import { onLoad } from "@dcloudio/uni-app";
import { api } from "@/utils/request";
import { ensureLogin, errMsg } from "@/utils/auth";
import { contentNodes } from "@/utils/content";

const item = ref<any>(null);
const loading = ref(true);
const jobId = ref("");

const showApply = ref(false);
const resumes = ref<any[]>([]);
const resumeId = ref(0);
const applyHint = ref("");
const applying = ref(false);

onLoad(async (q) => {
  if (!q?.id) return;
  jobId.value = String(q.id);
  try {
    const res = await api<any>(`/jobs/${q.id}`);
    item.value = res?.data || res;
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "加载失败"), icon: "none" });
  } finally {
    loading.value = false;
  }
});

async function openApply() {
  if (!ensureLogin()) return;
  try {
    const res = await api<any>(`/jobs/${jobId.value}/apply`);
    resumes.value = res?.data?.resumes || [];
    resumeId.value = resumes.value[0]?.id || 0;
    applyHint.value = String(res?.data?.hint || "");
    showApply.value = true;
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "无法投递"), icon: "none" });
  }
}

function pickResume(e: any) {
  const idx = Number(e?.detail?.value ?? 0);
  resumeId.value = resumes.value[idx]?.id || 0;
}

async function submitApply() {
  if (!resumeId.value) {
    uni.showToast({ title: "请先创建并通过审核的简历", icon: "none" });
    return;
  }
  applying.value = true;
  try {
    await api(`/jobs/${jobId.value}/apply`, {
      method: "POST",
      body: { resume_id: resumeId.value },
    });
    uni.showToast({ title: "投递成功", icon: "success" });
    showApply.value = false;
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "投递失败"), icon: "none" });
  } finally {
    applying.value = false;
  }
}

function goResumes() {
  showApply.value = false;
  uni.navigateTo({ url: "/pages/member/resumes" });
}
</script>

<template>
  <view class="page">
    <view v-if="loading" class="center"><u-loading-icon color="#2769dc" /></view>
    <view v-else-if="item" class="card article">
      <text class="title">{{ item.title }}</text>
      <text class="meta muted">
        {{ item.company_name || "" }} · {{ item.area || "" }} · {{ item.salary || "薪资面议" }} · 浏览 {{ item.views || 0 }}
      </text>
      <text class="meta muted">
        学历 {{ item.education || "-" }} · 经验 {{ item.experience || "-" }} · 人数 {{ item.headcount || "-" }}
      </text>
      <text v-if="item.summary" class="summary">{{ item.summary }}</text>
      <rich-text class="body" :nodes="contentNodes(item.content)" />
      <text v-if="item.contact_name" class="contact">
        联系：{{ item.contact_name }} {{ item.contact_phone || "" }}
      </text>

      <view class="acts">
        <button class="btn-primary" type="button" @click="openApply">投递简历</button>
        <button class="btn-soft" type="button" @click="goResumes">我的简历</button>
      </view>
    </view>

    <view v-if="showApply" class="mask" @click="showApply = false">
      <view class="sheet" @click.stop>
        <text class="sheet-title">选择简历投递</text>
        <view v-if="!resumes.length" class="empty">
          <text>{{ applyHint || "暂无已通过审核的简历" }}</text>
          <button class="btn-primary" type="button" @click="goResumes">去管理简历</button>
        </view>
        <template v-else>
          <picker mode="selector" :range="resumes" range-key="title" @change="pickResume">
            <view class="picker">
              {{ resumes.find((r) => r.id === resumeId)?.title || "请选择简历" }}
            </view>
          </picker>
          <button class="btn-primary" type="button" :disabled="applying" @click="submitApply">
            {{ applying ? "提交中…" : "确认投递" }}
          </button>
        </template>
        <button class="btn-soft" type="button" @click="showApply = false">取消</button>
      </view>
    </view>
  </view>
</template>

<style lang="scss" scoped>
.center { padding: 80rpx; text-align: center; }
.article { padding: 28rpx 24rpx; }
.title { font-size: 36rpx; font-weight: 700; display: block; margin-bottom: 12rpx; }
.meta { font-size: 22rpx; display: block; margin-bottom: 10rpx; }
.summary { display: block; font-size: 26rpx; color: #555; margin: 16rpx 0; line-height: 1.5; }
.body { font-size: 28rpx; line-height: 1.7; margin-top: 16rpx; }
.contact { display: block; margin-top: 24rpx; font-size: 26rpx; color: #2769dc; }
.acts { display: flex; gap: 16rpx; margin-top: 32rpx; }
.btn-primary, .btn-soft {
  flex: 1; font-size: 28rpx; border-radius: 12rpx; line-height: 2.4;
}
.btn-primary { background: #2769dc; color: #fff; border: none; }
.btn-soft { background: #f3f4f6; color: #333; border: none; }
.mask {
  position: fixed; inset: 0; background: rgba(0,0,0,.45);
  display: flex; align-items: flex-end; z-index: 99;
}
.sheet {
  width: 100%; background: #fff; border-radius: 24rpx 24rpx 0 0;
  padding: 32rpx 28rpx calc(28rpx + env(safe-area-inset-bottom));
  display: flex; flex-direction: column; gap: 20rpx;
}
.sheet-title { font-size: 32rpx; font-weight: 700; }
.picker {
  padding: 22rpx 20rpx; background: #f5f7fa; border-radius: 12rpx; font-size: 28rpx;
}
.empty { display: flex; flex-direction: column; gap: 16rpx; font-size: 26rpx; color: #666; }
</style>
