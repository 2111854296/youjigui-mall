<script setup lang="ts">
import { computed, ref } from "vue";
import { API_BASE } from "@/config/env";
import { getToken, mediaUrl } from "@/utils/request";
import { errMsg } from "@/utils/auth";

const props = withDefaults(
  defineProps<{ modelValue?: string; label?: string; hint?: string; ratio?: string }>(),
  { modelValue: "", label: "图片", hint: "点击上传，jpg/png，最大 5MB", ratio: "1 / 1" },
);
const framePad = computed(() => {
  const m = String(props.ratio || "1 / 1").match(/(\d+(?:\.\d+)?)\s*\/\s*(\d+(?:\.\d+)?)/);
  if (!m) return "100%";
  const w = Number(m[1]);
  const h = Number(m[2]);
  if (!w || !h) return "100%";
  return `${(h / w) * 100}%`;
});
const emit = defineEmits<{ "update:modelValue": [string] }>();

const uploading = ref(false);
const error = ref("");

const preview = computed(() => (props.modelValue ? mediaUrl(props.modelValue, "") : ""));

function choose() {
  if (uploading.value) return;
  error.value = "";
  uni.chooseImage({
    count: 1,
    sizeType: ["compressed"],
    sourceType: ["album", "camera"],
    success: (res) => {
      const path = res.tempFilePaths?.[0];
      if (path) void upload(path);
    },
  });
}

function upload(filePath: string) {
  uploading.value = true;
  error.value = "";
  const token = getToken();
  return new Promise<void>((resolve, reject) => {
    uni.uploadFile({
      url: `${API_BASE}/member/upload`,
      filePath,
      name: "file",
      header: token ? { Authorization: `Bearer ${token}`, Accept: "application/json" } : { Accept: "application/json" },
      success: (res) => {
        try {
          const body = typeof res.data === "string" ? JSON.parse(res.data) : res.data;
          if ((res.statusCode || 0) >= 200 && (res.statusCode || 0) < 300 && body?.data?.url) {
            emit("update:modelValue", String(body.data.url));
            resolve();
            return;
          }
          const msg = body?.message || body?.errors?.file?.[0] || `上传失败(${res.statusCode})`;
          error.value = msg;
          reject(new Error(msg));
        } catch (e: any) {
          error.value = errMsg(e, "上传失败");
          reject(e);
        }
      },
      fail: (e) => {
        error.value = errMsg(e, "上传失败");
        reject(e);
      },
      complete: () => {
        uploading.value = false;
      },
    });
  });
}

function clear() {
  emit("update:modelValue", "");
  error.value = "";
}
</script>

<template>
  <view class="iu">
    <text class="iu-label">{{ props.label }}</text>
    <view class="iu-box" :class="{ on: !!preview, busy: uploading }" :style="{ paddingBottom: framePad }" @click="choose">
      <image v-if="preview" class="iu-img" :src="preview" mode="aspectFill" />
      <view v-else class="iu-empty">
        <text class="plus">+</text>
        <text class="tip">{{ uploading ? "上传中…" : "点击上传" }}</text>
      </view>
      <view v-if="preview" class="iu-mask" @click.stop>
        <text class="btn" @click="choose">更换</text>
        <text class="btn" @click="clear">删除</text>
      </view>
    </view>
    <text v-if="error" class="iu-err">{{ error }}</text>
    <text v-else class="iu-hint">{{ props.hint }}</text>
    <text class="iu-rule">图片内容严禁带有联系方式及违反法律法规内容！</text>
  </view>
</template>

<style lang="scss" scoped>
.iu { width: 100%; }
.iu-label {
  display: block;
  font-size: 26rpx;
  font-weight: 600;
  color: #334155;
  margin-bottom: 12rpx;
}
.iu-box {
  position: relative;
  width: 100%;
  height: 0;
  padding-bottom: 100%;
  border: 2rpx dashed #cbd5e1;
  background: #f8fafc;
  border-radius: 12rpx;
  overflow: hidden;
  box-sizing: border-box;
}
.iu-box.on { border-style: solid; border-color: #e2e8f0; background: #fff; }
.iu-box.busy { opacity: 0.7; }
.iu-empty {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8rpx;
  color: #94a3b8;
}
.plus {
  width: 56rpx;
  height: 56rpx;
  border-radius: 50%;
  border: 2rpx solid #cbd5e1;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 36rpx;
  line-height: 1;
  color: #64748b;
}
.tip { font-size: 22rpx; }
.iu-img {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
}
.iu-mask {
  position: absolute;
  left: 0; right: 0; bottom: 0;
  padding: 12rpx;
  background: linear-gradient(transparent, rgba(15, 23, 42, 0.72));
  display: flex;
  justify-content: center;
  gap: 16rpx;
}
.btn {
  background: #fff;
  color: #0f172a;
  font-size: 22rpx;
  padding: 8rpx 18rpx;
  border-radius: 8rpx;
}
.iu-hint { display: block; margin-top: 8rpx; font-size: 20rpx; color: #94a3b8; }
.iu-rule {
  display: block;
  margin-top: 6rpx;
  font-size: 20rpx;
  line-height: 1.45;
  color: #c9372b;
  font-weight: 600;
}
.iu-err { display: block; margin-top: 8rpx; font-size: 20rpx; color: #dc2626; }
</style>
