<script setup lang="ts">
const { captchaOpen, onCaptchaVerified, onCaptchaCancel } = useQuoteApi()
</script>

<template>
  <SlideCaptchaModal
    v-model="captchaOpen"
    @verified="onCaptchaVerified"
    @cancel="onCaptchaCancel"
  />
</template>
