<script setup lang="ts">
import { reactive, ref } from "vue";
import { onLoad } from "@dcloudio/uni-app";
import { api } from "@/utils/request";
import { ensureCertifiedEnterprise, errMsg } from "@/utils/auth";
import { FIELD_CATS, JOB_EDU_OPTS, JOB_EXP_OPTS, JOB_NATURE_OPTS, JOB_ROLE_CATS } from "@/utils/jobCats";

const editId = ref<number | null>(null);
const loading = ref(false);
const saving = ref(false);
const form = reactive({
  title: "",
  company_name: "",
  area: "东莞",
  salary: "",
  education: "不限",
  experience: "不限",
  headcount: 1,
  summary: "",
  content: "",
  contact_name: "",
  contact_phone: "",
  job_role: "销售",
  job_category: "硅橡胶",
  job_nature: "全职",
});

const roleIdx = ref(JOB_ROLE_CATS.indexOf("销售"));
const fieldIdx = ref(FIELD_CATS.indexOf("硅橡胶"));
const expIdx = ref(0);
const eduIdx = ref(0);
const natureIdx = ref(0);

onLoad(async (q: any) => {
  if (!(await ensureCertifiedEnterprise())) {
    setTimeout(() => uni.navigateBack({ fail: () => uni.switchTab({ url: "/pages/member/index" }) }), 300);
    return;
  }
  const id = q?.id ? Number(q.id) : null;
  if (!id) {
    uni.setNavigationBarTitle({ title: "发布招聘" });
    return;
  }
  editId.value = id;
  uni.setNavigationBarTitle({ title: "编辑招聘" });
  loading.value = true;
  try {
    const res = await api<any>(`/member/jobs/${id}`);
    const a = res?.data || {};
    Object.assign(form, {
      title: a.title || "",
      company_name: a.company_name || "",
      area: a.area || "东莞",
      salary: a.salary || "",
      education: a.education || "不限",
      experience: a.experience || "不限",
      headcount: a.headcount || 1,
      summary: a.summary || "",
      content: a.content || "",
      contact_name: a.contact_name || "",
      contact_phone: a.contact_phone || "",
      job_role: a.job_role || "其他",
      job_category: a.job_category || "其他",
      job_nature: a.job_nature || "全职",
    });
    roleIdx.value = Math.max(0, JOB_ROLE_CATS.indexOf(form.job_role as any));
    fieldIdx.value = Math.max(0, FIELD_CATS.indexOf(form.job_category as any));
    expIdx.value = Math.max(0, JOB_EXP_OPTS.indexOf(form.experience as any));
    eduIdx.value = Math.max(0, JOB_EDU_OPTS.indexOf(form.education as any));
    natureIdx.value = Math.max(0, JOB_NATURE_OPTS.indexOf(form.job_nature as any));
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "加载失败"), icon: "none" });
  } finally {
    loading.value = false;
  }
});

function onPickRole(e: any) {
  const i = Number(e.detail.value);
  roleIdx.value = i;
  form.job_role = JOB_ROLE_CATS[i] || "其他";
}
function onPickField(e: any) {
  const i = Number(e.detail.value);
  fieldIdx.value = i;
  form.job_category = FIELD_CATS[i] || "其他";
}
function onPickExp(e: any) {
  const i = Number(e.detail.value);
  expIdx.value = i;
  form.experience = JOB_EXP_OPTS[i] || "不限";
}
function onPickEdu(e: any) {
  const i = Number(e.detail.value);
  eduIdx.value = i;
  form.education = JOB_EDU_OPTS[i] || "不限";
}
function onPickNature(e: any) {
  const i = Number(e.detail.value);
  natureIdx.value = i;
  form.job_nature = JOB_NATURE_OPTS[i] || "全职";
}

async function submit() {
  if (!(await ensureCertifiedEnterprise())) return;
  if (!form.title.trim()) {
    uni.showToast({ title: "请填写职位", icon: "none" });
    return;
  }
  if (!form.job_role) {
    uni.showToast({ title: "请选择职位类别", icon: "none" });
    return;
  }
  saving.value = true;
  try {
    const body = { ...form, headcount: Number(form.headcount) || 1 };
    if (editId.value) {
      await api(`/member/jobs/${editId.value}`, { method: "PUT", body });
    } else {
      await api("/member/jobs", { method: "POST", body });
    }
    uni.showToast({ title: "已保存", icon: "success" });
    setTimeout(() => uni.redirectTo({ url: "/pages/member/jobs" }), 400);
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
      <view class="section">职位信息</view>
      <u-input v-model="form.title" placeholder="职位 *" border="surround" />
      <view class="gap" />
      <picker :range="JOB_ROLE_CATS" :value="roleIdx" @change="onPickRole">
        <view class="picker">职位类别：{{ form.job_role || "请选择 *" }}</view>
      </picker>
      <view class="gap" />
      <picker :range="FIELD_CATS" :value="fieldIdx" @change="onPickField">
        <view class="picker">领域分类：{{ form.job_category || "请选择 *" }}</view>
      </picker>
      <view class="gap" />
      <u-input v-model="form.company_name" placeholder="公司" border="surround" />
      <view class="gap" />
      <u-input v-model="form.area" placeholder="地区 如：广东东莞" border="surround" />
      <view class="gap" />
      <u-input v-model="form.salary" placeholder="薪资 如：8K-12K" border="surround" />
      <view class="gap" />
      <u-input v-model="form.headcount" placeholder="人数" border="surround" type="number" />
      <view class="gap" />
      <picker :range="JOB_EDU_OPTS" :value="eduIdx" @change="onPickEdu">
        <view class="picker">学历：{{ form.education }}</view>
      </picker>
      <view class="gap" />
      <picker :range="JOB_EXP_OPTS" :value="expIdx" @change="onPickExp">
        <view class="picker">经验：{{ form.experience }}</view>
      </picker>
      <view class="gap" />
      <picker :range="JOB_NATURE_OPTS" :value="natureIdx" @change="onPickNature">
        <view class="picker">性质：{{ form.job_nature }}</view>
      </picker>
      <view class="gap" />
      <u-textarea v-model="form.summary" placeholder="摘要 / 福利标签" height="80" border="surround" />

      <view class="section">岗位详情</view>
      <u-textarea v-model="form.content" placeholder="岗位职责、任职要求…" height="200" border="surround" />

      <view class="section">联系方式</view>
      <u-input v-model="form.contact_name" placeholder="联系人" border="surround" />
      <view class="gap" />
      <u-input v-model="form.contact_phone" placeholder="电话" border="surround" />
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
  margin: 28rpx 0 16rpx; font-size: 28rpx; font-weight: 700;
  padding-left: 14rpx; border-left: 6rpx solid #2769dc;
}
.section:first-of-type { margin-top: 0; }
.picker {
  padding: 22rpx 24rpx;
  border: 1px solid #e5e7eb;
  border-radius: 12rpx;
  font-size: 28rpx;
  color: #1f2937;
  background: #fff;
}
.footer-tip { text-align: center; padding: 32rpx; }
</style>
