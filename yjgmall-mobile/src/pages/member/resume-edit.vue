<script setup lang="ts">
import { reactive, ref } from "vue";
import { onLoad } from "@dcloudio/uni-app";
import { api } from "@/utils/request";
import { ensureLogin, errMsg } from "@/utils/auth";
import { FIELD_CATS, JOB_ROLE_CATS } from "@/utils/jobCats";

const cats = [...FIELD_CATS];
const roles = [...JOB_ROLE_CATS];
const editId = ref<number | null>(null);
const loading = ref(false);
const saving = ref(false);
const form = reactive({
  title: "",
  truename: "",
  gender: "",
  education: "",
  experience: "",
  minsalary: "",
  maxsalary: "",
  mobile: "",
  email: "",
  area: "",
  job_role: "",
  job_category: "",
  summary: "",
  content: "",
  open: "public",
  is_default: false,
});

onLoad(async (q: any) => {
  if (!ensureLogin()) return;
  const id = q?.id ? Number(q.id) : null;
  if (!id) {
    uni.setNavigationBarTitle({ title: "新建简历" });
    return;
  }
  editId.value = id;
  uni.setNavigationBarTitle({ title: "编辑简历" });
  loading.value = true;
  try {
    const res = await api<any>(`/member/resumes/${id}`);
    const a = res?.data || {};
    Object.assign(form, {
      title: a.title || "",
      truename: a.truename || "",
      gender: a.gender || "",
      education: a.education || "",
      experience: a.experience || "",
      minsalary: a.minsalary != null ? String(a.minsalary) : "",
      maxsalary: a.maxsalary != null ? String(a.maxsalary) : "",
      mobile: a.mobile || "",
      email: a.email || "",
      area: a.area || "",
      job_role: a.job_role || "",
      job_category: a.job_category || "",
      summary: a.summary || "",
      content: a.content || "",
      open: a.open === "private" ? "private" : "public",
      is_default: Boolean(Number(a.is_default)),
    });
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "加载失败"), icon: "none" });
  } finally {
    loading.value = false;
  }
});

function pickRole(e: any) {
  form.job_role = roles[Number(e.detail.value)] || "";
}
function pickCat(e: any) {
  form.job_category = cats[Number(e.detail.value)] || "";
}

async function submit() {
  if (!form.title.trim()) {
    uni.showToast({ title: "请填写简历标题", icon: "none" });
    return;
  }
  if (!form.job_role) {
    uni.showToast({ title: "请选择职位类别", icon: "none" });
    return;
  }
  if (!form.job_category) {
    uni.showToast({ title: "请选择所属行业", icon: "none" });
    return;
  }
  saving.value = true;
  try {
    const body = {
      ...form,
      minsalary: form.minsalary ? Number(form.minsalary) : null,
      maxsalary: form.maxsalary ? Number(form.maxsalary) : null,
      is_default: form.is_default ? 1 : 0,
    };
    if (editId.value) {
      await api(`/member/resumes/${editId.value}`, { method: "PUT", body });
    } else {
      await api("/member/resumes", { method: "POST", body });
    }
    uni.showToast({ title: "已提交审核", icon: "success" });
    setTimeout(() => uni.redirectTo({ url: "/pages/member/resumes" }), 400);
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
      <view class="section">基本信息</view>
      <u-input v-model="form.title" placeholder="简历标题 *" border="surround" />
      <view class="gap" />
      <u-input v-model="form.truename" placeholder="姓名" border="surround" />
      <view class="gap" />
      <u-input v-model="form.gender" placeholder="性别（男/女）" border="surround" />
      <view class="gap" />
      <u-input v-model="form.education" placeholder="学历" border="surround" />
      <view class="gap" />
      <u-input v-model="form.experience" placeholder="经验" border="surround" />
      <view class="gap" />
      <u-input v-model="form.area" placeholder="地区" border="surround" />
      <view class="gap" />
      <picker :range="roles" @change="pickRole">
        <view class="picker">职位类别：{{ form.job_role || "请选择 *" }}</view>
      </picker>
      <view class="gap" />
      <picker :range="cats" @change="pickCat">
        <view class="picker">所属行业：{{ form.job_category || "请选择 *" }}</view>
      </picker>
      <view class="gap" />
      <u-input v-model="form.minsalary" placeholder="期望薪资下限（K）" border="surround" type="number" />
      <view class="gap" />
      <u-input v-model="form.maxsalary" placeholder="期望薪资上限（K）" border="surround" type="number" />
      <view class="gap" />
      <u-input v-model="form.mobile" placeholder="手机" border="surround" />
      <view class="gap" />
      <u-input v-model="form.email" placeholder="邮箱" border="surround" />
      <view class="section">公开与内容</view>
      <view class="switch-row" @click="form.open = form.open === 'public' ? 'private' : 'public'">
        <text class="muted">公开到人才库</text>
        <text class="val">{{ form.open === "public" ? "公开" : "不公开" }}</text>
      </view>
      <view class="gap" />
      <view class="switch-row" @click="form.is_default = !form.is_default">
        <text class="muted">设为默认简历</text>
        <text class="val">{{ form.is_default ? "是" : "否" }}</text>
      </view>
      <view class="gap" />
      <u-textarea v-model="form.summary" placeholder="一句话简介" height="100" border="surround" />
      <view class="gap" />
      <u-textarea v-model="form.content" placeholder="详细经历 / 技能" height="200" border="surround" />
      <view class="gap" />
      <u-button type="primary" color="#2769dc" :text="editId ? '保存并重新审核' : '提交审核'" :loading="saving" @click="submit" />
    </view>
  </view>
</template>

<style lang="scss" scoped>
.form { margin: 20rpx; padding: 28rpx; }
.gap { height: 18rpx; }
.section {
  margin: 28rpx 0 16rpx; font-size: 28rpx; font-weight: 700;
  padding-left: 14rpx; border-left: 6rpx solid #2769dc;
}
.section:first-of-type { margin-top: 0; }
.picker {
  padding: 20rpx 16rpx; border: 1rpx solid #dadbde; border-radius: 8rpx; font-size: 26rpx; color: #333;
}
.switch-row {
  display: flex; align-items: center; gap: 12rpx;
  padding: 20rpx 16rpx; border: 1rpx solid #dadbde; border-radius: 8rpx;
}
.val { flex: 1; text-align: right; color: #2769dc; }
.footer-tip { text-align: center; padding: 32rpx; }
</style>
