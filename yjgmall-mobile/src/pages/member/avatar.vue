<script setup lang="ts">
import { ref } from "vue";
import { onShow } from "@dcloudio/uni-app";
import { api, mediaUrl } from "@/utils/request";
import { ensureLogin, errMsg } from "@/utils/auth";
import ImageUpload from "@/components/ImageUpload/ImageUpload.vue";
import { useAuth } from "@/composables/useAuth";

const { user, fetchMe } = useAuth();
const avatar = ref("");
const saving = ref(false);

onShow(async () => {
  if (!ensureLogin()) return;
  const me = await fetchMe(true);
  avatar.value = me?.avatar || "";
});

async function save() {
  if (!avatar.value) {
    uni.showToast({ title: "请先上传头像", icon: "none" });
    return;
  }
  saving.value = true;
  try {
    await api("/member/profile-deep", { method: "PUT", body: { avatar: avatar.value } });
    await fetchMe(true);
    uni.showToast({ title: "头像已更新", icon: "success" });
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "保存失败"), icon: "none" });
  } finally {
    saving.value = false;
  }
}

function goProfile() {
  uni.navigateTo({ url: "/pages/member/profile" });
}
</script>

<template>
  <view class="page">
    <view class="card form">
      <view class="section">头像设置</view>
      <view class="preview">
        <image class="avatar" :src="mediaUrl(avatar || user()?.avatar, '')" mode="aspectFill" />
      </view>
      <view class="pic"><ImageUpload v-model="avatar" label="会员头像" /></view>
      <text class="hint muted">上传后点保存生效</text>
      <view class="gap" />
      <u-button type="primary" color="#2769dc" text="保存头像" :loading="saving" @click="save" />
      <view class="gap" />
      <u-button text="返回资料" plain color="#2769dc" @click="goProfile" />
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
.preview { display: flex; justify-content: center; margin-bottom: 24rpx; }
.avatar {
  width: 160rpx; height: 160rpx; border-radius: 50%;
  background: #e8edf3; border: 4rpx solid #dbe7f8;
}
.pic { width: 220rpx; margin: 0 auto; }
.hint { display: block; text-align: center; font-size: 22rpx; margin-top: 12rpx; }
</style>
