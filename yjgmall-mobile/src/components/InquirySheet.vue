<script setup lang="ts">
import { reactive, ref, watch } from "vue";
import { api } from "@/utils/request";
import { useAuth } from "@/composables/useAuth";
import { errMsg } from "@/utils/auth";

const props = withDefaults(
  defineProps<{
    show: boolean;
    type: "supply" | "purchase" | "product" | "company";
    id: string | number;
    defaultTitle?: string;
  }>(),
  { defaultTitle: "" },
);
const emit = defineEmits<{ (e: "update:show", v: boolean): void }>();

const { fetchMe, token, user } = useAuth();
const loading = ref(false);
const form = reactive({
  contact_name: "",
  contact_phone: "",
  contact_email: "",
  title: "",
  content: "",
});

watch(
  () => props.show,
  async (v) => {
    if (!v) return;
    form.title = props.defaultTitle ? `询价：${props.defaultTitle}` : "产品询价";
    if (token()) {
      const u = await fetchMe();
      if (u) {
        form.contact_name = u.name || u.username || form.contact_name;
        form.contact_phone = u.mobile || form.contact_phone;
        form.contact_email = (u as any).email || form.contact_email;
      }
    }
  },
);

function close() {
  emit("update:show", false);
}

async function submit() {
  if (!form.contact_name || !form.contact_phone || !form.content) {
    uni.showToast({ title: "请填写联系人、手机和内容", icon: "none" });
    return;
  }
  if (!props.id) {
    uni.showToast({ title: "缺少目标信息", icon: "none" });
    return;
  }
  loading.value = true;
  try {
    await api("/inquiries", {
      method: "POST",
      body: {
        type: props.type,
        id: Number(props.id),
        ...form,
      },
      auth: !!token(),
    });
    uni.showToast({ title: "已提交，平台审核后转达", icon: "success" });
    form.content = "";
    setTimeout(() => close(), 600);
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "发送失败"), icon: "none" });
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <view v-if="show" class="mask" @click="close">
    <view class="sheet" @click.stop>
      <view class="sheet-hd">
        <text class="sheet-title">{{ type === "company" ? "在线留言" : "发送询盘" }}</text>
        <text class="sheet-x" @click="close">×</text>
      </view>
      <u-input v-model="form.contact_name" placeholder="联系人 *" border="surround" />
      <view class="gap" />
      <u-input v-model="form.contact_phone" placeholder="手机 *" border="surround" />
      <view class="gap" />
      <u-input v-model="form.contact_email" placeholder="邮箱（选填）" border="surround" />
      <view class="gap" />
      <u-input v-model="form.title" placeholder="标题" border="surround" />
      <view class="gap" />
      <u-textarea v-model="form.content" placeholder="询盘内容 *" height="140" border="surround" />
      <view class="gap" />
      <u-button type="primary" color="#2769dc" text="提交询盘" :loading="loading" @click="submit" />
      <view v-if="!user()" class="tip muted">未登录也可提交；登录后可在「询盘管理」查看往来。</view>
    </view>
  </view>
</template>

<style lang="scss" scoped>
.mask {
  position: fixed;
  inset: 0;
  z-index: 1100;
  background: rgba(15, 28, 52, 0.48);
  display: flex;
  align-items: flex-end;
  justify-content: center;
}
.sheet {
  width: 100%;
  max-height: 86vh;
  overflow-y: auto;
  background: #fff;
  border-radius: 28rpx 28rpx 0 0;
  padding: 32rpx 28rpx calc(28rpx + env(safe-area-inset-bottom));
  box-sizing: border-box;
  box-shadow: 0 -12rpx 40rpx rgba(15, 28, 52, 0.12);
}
.sheet-hd {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 20rpx;
}
.sheet-title {
  font-size: 32rpx;
  font-weight: 700;
  padding-left: 16rpx;
  border-left: 6rpx solid #2769dc;
  color: #1f2937;
}
.sheet-x { font-size: 44rpx; color: #94a3b8; line-height: 1; padding: 0 8rpx; }
.gap { height: 18rpx; }
.tip { margin-top: 18rpx; font-size: 22rpx; line-height: 1.45; color: #64748b; }
</style>
