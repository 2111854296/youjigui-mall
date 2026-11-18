<script setup lang="ts">
function go(url: string) {
  uni.navigateTo({ url });
}
</script>

<template>
  <view class="page">
    <view class="card box">
      <text class="title">忘记密码</text>
      <text class="desc">为保障账户安全，密码重置需人工核验会员身份。</text>
      <view class="steps">
        <text class="step">1. 准备好注册时的用户名 / 手机号 / 邮箱</text>
        <text class="step">2. 通过客服工单或网站留言提交重置申请</text>
        <text class="step">3. 核验通过后，客服将协助重置登录密码</text>
      </view>
      <u-button type="primary" color="#2769dc" text="提交客服工单" @click="go('/pages/ask/index')" />
      <view class="gap" />
      <u-button type="primary" color="#2769dc" plain text="网站留言" @click="go('/pages/guestbook/index')" />
      <view class="gap" />
      <u-button type="primary" color="#2769dc" plain text="返回登录" @click="go('/pages/login/index')" />
    </view>
  </view>
</template>

<style lang="scss" scoped>
.box { margin: 40rpx 28rpx; padding: 36rpx 28rpx; }
.title { font-size: 36rpx; font-weight: 800; display: block; margin-bottom: 16rpx; }
.desc { display: block; font-size: 26rpx; color: #555; line-height: 1.5; margin-bottom: 20rpx; }
.steps { margin-bottom: 28rpx; }
.step { display: block; font-size: 26rpx; color: #333; line-height: 1.7; margin-bottom: 8rpx; }
.gap { height: 16rpx; }
</style>
