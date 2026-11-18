<script setup lang="ts">
import { nextTick, ref, watch } from "vue";
import SlideCaptcha from "@/components/SlideCaptcha/SlideCaptcha.vue";

const open = defineModel<boolean>({ default: false });

const emit = defineEmits<{
  (e: "verified", token: string): void;
  (e: "cancel"): void;
}>();

const captchaRef = ref<{ reload: () => void } | null>(null);

watch(open, (v) => {
  if (v) nextTick(() => captchaRef.value?.reload?.());
});

function onVerified(token: string) {
  open.value = false;
  emit("verified", token);
}

function close() {
  if (!open.value) return;
  open.value = false;
  emit("cancel");
}
</script>

<template>
  <view v-if="open" class="scm-mask" @click.self="close">
    <view class="scm-box" @click.stop>
      <text class="scm-x" @click="close">×</text>
      <SlideCaptcha ref="captchaRef" :auto-load="false" @verified="onVerified" />
    </view>
  </view>
</template>

<style lang="scss" scoped>
.scm-mask {
  position: fixed;
  inset: 0;
  z-index: 10050;
  background: rgba(15, 23, 42, 0.48);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 40rpx;
}
.scm-box {
  position: relative;
  width: 100%;
  max-width: 680rpx;
  background: #fff;
  border-radius: 24rpx;
  padding: 44rpx 32rpx 32rpx;
  box-shadow: 0 20rpx 50rpx rgba(15, 23, 42, 0.22);
}
.scm-x {
  position: absolute;
  top: 12rpx;
  right: 20rpx;
  width: 56rpx;
  height: 56rpx;
  line-height: 56rpx;
  text-align: center;
  color: #94a3b8;
  font-size: 40rpx;
}
</style>
