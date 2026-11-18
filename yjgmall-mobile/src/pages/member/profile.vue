<script setup lang="ts">
import { reactive, ref } from "vue";
import { onShow } from "@dcloudio/uni-app";
import { api } from "@/utils/request";
import { ensureLogin, errMsg } from "@/utils/auth";
import ImageUpload from "@/components/ImageUpload/ImageUpload.vue";
import { useAuth } from "@/composables/useAuth";

const { fetchMe } = useAuth();
const loading = ref(false);
const groupLabel = ref("");
const form = reactive({
  name: "",
  gender: "male",
  email: "",
  mobile: "",
  qq: "",
  province: "",
  city: "",
  district: "",
  area: "",
  company_name: "",
  company_type: "",
  avatar: "",
  sound: 1,
  online_status: "online",
});

const genderOpts = [
  { value: "male", label: "先生" },
  { value: "female", label: "女士" },
];
const soundOpts = [
  { value: 0, label: "关闭" },
  { value: 1, label: "默认" },
  { value: 2, label: "提示音2" },
  { value: 3, label: "提示音3" },
];
const onlineOpts = [
  { value: "online", label: "在线" },
  { value: "invisible", label: "隐身" },
  { value: "offline", label: "离线" },
];

onShow(async () => {
  if (!ensureLogin()) return;
  try {
    const res = await api<any>("/member/profile-deep");
    const d = res?.data || {};
    groupLabel.value = res?.group_label || "";
    Object.assign(form, {
      name: d.name || "",
      gender: d.gender || "male",
      email: d.email || "",
      mobile: d.mobile || "",
      qq: d.qq || "",
      province: d.province || "",
      city: d.city || "",
      district: d.district || "",
      area: d.area || "",
      company_name: d.company_name || "",
      company_type: d.company_type || "",
      avatar: d.avatar || "",
      sound: d.sound ?? 1,
      online_status: d.online_status || "online",
    });
  } catch {
    try {
      const me = await api<any>("/auth/me");
      const d = me?.data || {};
      Object.assign(form, {
        name: d.name || "",
        mobile: d.mobile || "",
        email: d.email || "",
        company_name: d.company_name || "",
        avatar: d.avatar || "",
      });
    } catch { /* ignore */ }
  }
});

function pickGender(e: any) {
  form.gender = genderOpts[Number(e.detail.value)]?.value || "male";
}
function pickSound(e: any) {
  form.sound = soundOpts[Number(e.detail.value)]?.value ?? 1;
}
function pickOnline(e: any) {
  form.online_status = onlineOpts[Number(e.detail.value)]?.value || "online";
}

function labelOf(opts: { value: any; label: string }[], v: any) {
  return opts.find((o) => o.value === v)?.label || String(v);
}

async function save() {
  if (!form.name.trim()) {
    uni.showToast({ title: "请填写联系人", icon: "none" });
    return;
  }
  loading.value = true;
  try {
    await api("/member/profile-deep", { method: "PUT", body: { ...form } });
    await fetchMe(true);
    uni.showToast({ title: "资料已更新", icon: "success" });
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "保存失败"), icon: "none" });
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <view class="page">
    <view class="card form">
      <text v-if="groupLabel" class="meta muted">会员组 {{ groupLabel }}</text>
      <view class="section">头像</view>
      <view class="pic"><ImageUpload v-model="form.avatar" label="会员头像" /></view>

      <view class="section">联系信息</view>
      <u-input v-model="form.name" placeholder="联系人 *" border="surround" />
      <view class="gap" />
      <picker :range="genderOpts" range-key="label" @change="pickGender">
        <view class="picker-row"><text class="muted">性别</text><text class="val">{{ labelOf(genderOpts, form.gender) }}</text></view>
      </picker>
      <view class="gap" />
      <u-input v-model="form.mobile" placeholder="手机" border="surround" />
      <view class="gap" />
      <u-input v-model="form.email" placeholder="邮箱" border="surround" />
      <view class="gap" />
      <u-input v-model="form.qq" placeholder="QQ" border="surround" />

      <view class="section">地区与公司</view>
      <u-input v-model="form.province" placeholder="省" border="surround" />
      <view class="gap" />
      <u-input v-model="form.city" placeholder="市" border="surround" />
      <view class="gap" />
      <u-input v-model="form.district" placeholder="区/县" border="surround" />
      <view class="gap" />
      <u-input v-model="form.area" placeholder="地区备注" border="surround" />
      <view class="gap" />
      <u-input v-model="form.company_name" placeholder="公司" border="surround" />
      <view class="gap" />
      <u-input v-model="form.company_type" placeholder="公司类型" border="surround" />

      <view class="section">偏好设置</view>
      <picker :range="soundOpts" range-key="label" @change="pickSound">
        <view class="picker-row"><text class="muted">提示音</text><text class="val">{{ labelOf(soundOpts, form.sound) }}</text></view>
      </picker>
      <view class="gap" />
      <picker :range="onlineOpts" range-key="label" @change="pickOnline">
        <view class="picker-row"><text class="muted">在线状态</text><text class="val">{{ labelOf(onlineOpts, form.online_status) }}</text></view>
      </picker>
      <view class="gap" />
      <u-button type="primary" color="#2769dc" text="保存资料" :loading="loading" @click="save" />
    </view>
  </view>
</template>

<style lang="scss" scoped>
.form { margin: 20rpx; padding: 28rpx; }
.meta { display: block; margin-bottom: 8rpx; font-size: 22rpx; }
.gap { height: 18rpx; }
.section {
  margin: 28rpx 0 16rpx; font-size: 28rpx; font-weight: 700;
  padding-left: 14rpx; border-left: 6rpx solid #2769dc;
}
.section:first-of-type { margin-top: 12rpx; }
.pic { width: 220rpx; }
.picker-row {
  display: flex; align-items: center; gap: 12rpx;
  padding: 20rpx 16rpx; border: 1rpx solid #dadbde; border-radius: 8rpx;
}
.val { flex: 1; }
</style>
