<script setup lang="ts">
import { reactive, ref } from "vue";
import { onLoad } from "@dcloudio/uni-app";
import { api } from "@/utils/request";
import { ensureLogin, errMsg } from "@/utils/auth";
import ImageUpload from "@/components/ImageUpload/ImageUpload.vue";

const editId = ref<number | null>(null);
const loading = ref(false);
const saving = ref(false);
const form = reactive({
  title: "",
  price: "0",
  stock: 10,
  summary: "",
  content: "",
  thumb: "",
  detail_images: ["", "", "", "", ""] as string[],
});
const tiers = reactive([
  { min_qty: "", max_qty: "", price: "" },
  { min_qty: "", max_qty: "", price: "" },
  { min_qty: "", max_qty: "", price: "" },
]);

onLoad(async (q: any) => {
  if (!ensureLogin()) return;
  const id = q?.id ? Number(q.id) : null;
  if (!id) {
    uni.setNavigationBarTitle({ title: "发布商品" });
    return;
  }
  editId.value = id;
  uni.setNavigationBarTitle({ title: "编辑商品" });
  loading.value = true;
  try {
    const res = await api<any>(`/member/products/${id}`);
    const p = res?.data || {};
    Object.assign(form, {
      title: p.title || "",
      price: String(p.price ?? "0"),
      stock: Number(p.stock || 0),
      summary: p.summary || "",
      content: p.content || "",
      thumb: p.thumb || "",
    });
    const pics = Array.isArray(p.detail_images) ? p.detail_images : [];
    form.detail_images = [0, 1, 2, 3, 4].map((i) => String(pics[i] || ""));
    const saved = Array.isArray(p.price_tiers) ? p.price_tiers : [];
    for (let i = 0; i < 3; i++) {
      const row = saved[i] || {};
      tiers[i].min_qty = saved[i] && row.min_qty != null ? String(row.min_qty) : "";
      tiers[i].max_qty = saved[i] && row.max_qty != null ? String(row.max_qty) : "";
      tiers[i].price = saved[i] && row.price != null ? String(row.price) : "";
    }
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "加载失败"), icon: "none" });
  } finally {
    loading.value = false;
  }
});

async function submit() {
  if (!form.title.trim()) {
    uni.showToast({ title: "请填写标题", icon: "none" });
    return;
  }
  saving.value = true;
  try {
    const body = {
      ...form,
      price: Number(form.price),
      stock: Number(form.stock),
      price_tiers: tiers,
    };
    if (editId.value) {
      await api(`/member/products/${editId.value}`, { method: "PUT", body });
    } else {
      await api("/member/products", { method: "POST", body });
    }
    uni.showToast({ title: "已保存", icon: "success" });
    setTimeout(() => uni.redirectTo({ url: "/pages/member/products" }), 400);
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
      <u-input v-model="form.title" placeholder="标题 *" border="surround" />
      <view class="gap" />
      <u-input v-model="form.price" placeholder="价格 *" border="surround" type="digit" />
      <view class="gap" />
      <u-input v-model="form.stock" placeholder="库存" border="surround" type="number" />
      <view class="hint">区间价最多三档。不填则全数量都用上面的价格。最后一档截止留空表示该数量及以上。</view>
      <view v-for="(tier, i) in tiers" :key="i" class="tier">
        <text class="tier-k">第{{ i + 1 }}档</text>
        <u-input v-model="tier.min_qty" placeholder="起始数量" border="surround" type="number" />
        <u-input v-model="tier.max_qty" placeholder="截止数量" border="surround" type="number" />
        <u-input v-model="tier.price" placeholder="单价" border="surround" type="digit" />
      </view>
      <view class="section">商品图片</view>
      <view class="pic"><ImageUpload v-model="form.thumb" label="商品主图" /></view>
      <view class="section">详情页图片</view>
      <text class="hint">显示在商品评价上面。可不传，最多 5 张。竖图 3:4，建议 750×1000。</text>
      <view class="dpics">
        <view v-for="n in 5" :key="n" class="dpic">
          <ImageUpload v-model="form.detail_images[n - 1]" :label="`第 ${n} 张`" ratio="3 / 4" hint="竖图 3:4" />
        </view>
      </view>
      <view class="section">详细描述</view>
      <u-input v-model="form.summary" placeholder="摘要" border="surround" />
      <view class="gap" />
      <u-textarea v-model="form.content" placeholder="详情" height="180" border="surround" />
      <view class="gap" />
      <u-button type="primary" color="#2769dc" :text="editId ? '保存修改' : '提交审核'" :loading="saving" @click="submit" />
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
.section:first-child { margin-top: 0; }
.pic { width: 220rpx; }
.dpics { display: flex; flex-wrap: wrap; gap: 16rpx; }
.dpic { width: 200rpx; }
.hint { display: block; margin: 16rpx 0; font-size: 22rpx; color: #7b8ba3; line-height: 1.5; }
.tier { display: flex; gap: 10rpx; align-items: center; margin-bottom: 12rpx; }
.tier-k { width: 80rpx; font-size: 22rpx; color: #35577e; flex: none; }
.footer-tip { text-align: center; padding: 32rpx; }
</style>
