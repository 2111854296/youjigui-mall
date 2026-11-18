<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { onShow } from "@dcloudio/uni-app";
import { useAuth, type AuthUser, type MemberCapabilities } from "@/composables/useAuth";
import { api, mediaUrl } from "@/utils/request";
import { memberBadgeUrl } from "@/utils/memberBadge";
import { ensureLogin, errMsg } from "@/utils/auth";

const { fetchMe, logout, token, caps } = useAuth();
const user = ref<AuthUser | null>(null);
const dash = ref<Record<string, any>>({});
const note = ref("");
const noteMsg = ref("");
const badgeSrc = computed(() => memberBadgeUrl(String(user.value?.vip_grade_code || "")));
const pending = computed(
  () => user.value?.status === "pending" || user.value?.group_code === "pending",
);

type MenuItem = {
  name: string;
  tip: string;
  path: string;
  icon: string;
  color: string;
  auth: boolean;
  enterprise?: boolean;
  need?: keyof MemberCapabilities;
};

const groupsAll: { title: string; enterprise?: boolean; items: MenuItem[] }[] = [
  {
    title: "会员服务",
    items: [
      { name: "站内通知", tip: "询盘可回复需审核", path: "/pages/member/messages", icon: "bell", color: "#64748b", auth: true },
      { name: "我的商友", tip: "添加好友", path: "/pages/member/friends", icon: "account", color: "#2563eb", auth: true },
      { name: "商机收藏", tip: "收藏管理", path: "/pages/member/favorites", icon: "star", color: "#ca8a04", auth: true },
      { name: "贸易提醒", tip: "关键词提醒", path: "/pages/member/alerts", icon: "bell-fill", color: "#ea580c", auth: true },
      { name: "排名推广", tip: "关键词推广", path: "/pages/member/spread", icon: "thumb-up", color: "#c2410c", auth: true, need: "spread" },
      { name: "广告预定", tip: "广告位预定", path: "/pages/ads/index", icon: "photo", color: "#db2777", auth: true, need: "ads" },
      { name: "修改资料", tip: "个人资料", path: "/pages/member/profile", icon: "setting", color: "#475569", auth: true },
      { name: "头像设置", tip: "更换头像", path: "/pages/member/avatar", icon: "camera", color: "#64748b", auth: true },
      { name: "密码管理", tip: "登录/支付", path: "/pages/member/password", icon: "lock", color: "#64748b", auth: true },
      { name: "会员认证", tip: "手机/实名", path: "/pages/member/validate", icon: "checkmark-circle", color: "#16a34a", auth: true },
      { name: "会员升级", tip: "VIP申请", path: "/pages/member/grade", icon: "level", color: "#ff7a00", auth: true },
      { name: "企业席位", tip: "授权成员", path: "/pages/member/seats", icon: "account", color: "#2563eb", auth: true },
      { name: "拉新邀请", tip: "邀请奖励", path: "/pages/member/invite", icon: "share", color: "#0f766e", auth: true },
      { name: "次数包", tip: "联系方式", path: "/pages/member/contact-packs", icon: "integral", color: "#ca8a04", auth: true },
      { name: "客服中心", tip: "提问客服", path: "/pages/member/ask", icon: "server-man", color: "#1e60d3", auth: true },
    ],
  },
  {
    title: "信息管理",
    items: [
      { name: "供应管理", tip: "发布供应", path: "/pages/member/supplies", icon: "home", color: "#2563eb", auth: true, need: "supply" },
      { name: "采购管理", tip: "采购管理", path: "/pages/member/purchases", icon: "order", color: "#ea580c", auth: true },
      { name: "商品管理", tip: "商城商品", path: "/pages/member/products", icon: "bag", color: "#2769dc", auth: true, need: "product" },
      { name: "技术分享", tip: "发布到资讯栏目", path: "/pages/member/articles", icon: "file-text", color: "#1e60d3", auth: true, need: "article" },
      { name: "招聘管理", tip: "发布招聘", path: "/pages/member/jobs", icon: "man-add", color: "#0f766e", auth: true },
      { name: "简历管理", tip: "新建简历", path: "/pages/member/resumes", icon: "file-text", color: "#0f766e", auth: true },
      { name: "我的投递", tip: "投递记录", path: "/pages/member/job-applications", icon: "order", color: "#2563eb", auth: true },
      { name: "收到的投递", tip: "处理投递", path: "/pages/member/job-received", icon: "man-add", color: "#c2410c", auth: true },
      { name: "询盘管理", tip: "往来询盘", path: "/pages/member/inquiries", icon: "chat", color: "#0f766e", auth: true },
      { name: "收到报价", tip: "采购报价", path: "/pages/member/quotes", icon: "rmb-circle", color: "#c2410c", auth: true },
      { name: "评价管理", tip: "我的评价", path: "/pages/member/reviews", icon: "star-fill", color: "#ca8a04", auth: true },
    ],
  },
  {
    title: "交易管理",
    items: [
      { name: "我的订单", tip: "买家订单", path: "/pages/member/orders", icon: "coupon", color: "#1d4ed8", auth: true },
      { name: "售出订单", tip: "卖家订单", path: "/pages/member/seller-orders", icon: "shopping-cart", color: "#2563eb", auth: true, need: "seller_orders" },
      { name: "购物车", tip: "商城购物车", path: "/pages/member/cart", icon: "bag", color: "#4b86e8", auth: true },
      { name: "收货地址", tip: "地址管理", path: "/pages/member/addresses", icon: "map", color: "#0f766e", auth: true },
      { name: "资金流水", tip: "充值记账", path: "/pages/member/finance", icon: "red-packet", color: "#c2410c", auth: true },
      { name: "对公付款", tip: "上传凭证", path: "/pages/member/fund-pay", icon: "order", color: "#0f766e", auth: true },
      { name: "保证金", tip: "转入保证金", path: "/pages/member/deposit", icon: "integral", color: "#ca8a04", auth: true },
      { name: "申请提现", tip: "余额提现", path: "/pages/member/cash", icon: "rmb", color: "#ea580c", auth: true },
      { name: "购买积分", tip: "积分兑换", path: "/pages/member/credit", icon: "gift", color: "#db2777", auth: true },
    ],
  },
  {
    title: "商铺管理",
    enterprise: true,
    items: [
      { name: "旺铺设置", tip: "店铺资料", path: "/pages/member/shop", icon: "home", color: "#2769dc", auth: true, enterprise: true },
      { name: "公司新闻", tip: "旺铺新闻", path: "/pages/member/shop-news", icon: "file-text", color: "#1e60d3", auth: true, enterprise: true },
      { name: "荣誉资质", tip: "证书荣誉", path: "/pages/member/shop-honors", icon: "coupon", color: "#ca8a04", auth: true, enterprise: true },
      { name: "公司单页", tip: "自定义页", path: "/pages/member/shop-pages", icon: "list", color: "#64748b", auth: true, enterprise: true },
      { name: "友情链接", tip: "外链管理", path: "/pages/member/shop-links", icon: "share", color: "#0f766e", auth: true, enterprise: true },
    ],
  },
];

const groups = computed(() => {
  const isEnt = !!user.value?.is_enterprise;
  const c = caps();
  return groupsAll
    .filter((g) => !g.enterprise || isEnt)
    .map((g) => ({
      ...g,
      items: g.items.filter((m) => {
        if (m.enterprise && !isEnt) return false;
        if (m.need && !c[m.need]) return false;
        return true;
      }),
    }));
});

const quicks = computed(() => {
  const isEnt = !!user.value?.is_enterprise;
  return [
    { path: "/pages/member/cart", value: dash.value.cart ?? 0, label: "购物车" },
    { path: "/pages/member/messages", value: dash.value.messages ?? 0, label: "新消息" },
    { path: "/pages/member/deposit", value: dash.value.deposit ?? "0.00", label: "保证金" },
    { path: "/pages/member/credit", value: dash.value.points ?? user.value?.points ?? 0, label: "积分" },
    {
      path: isEnt ? "/pages/publish/supply" : "/pages/publish/purchase",
      value: "+",
      label: isEnt ? "发布供应" : "发布采购",
      action: true,
    },
    { path: "/pages/member/orders", value: "☰", label: "我的订单", action: true },
    {
      path: isEnt ? "/pages/member/shop" : "/pages/member/grade",
      value: isEnt ? "⌂" : "↑",
      label: isEnt ? "商铺管理" : "升级企业",
      action: true,
    },
  ];
});

async function refresh() {
  if (!token()) {
    user.value = null;
    dash.value = {};
    return;
  }
  user.value = await fetchMe(true);
  try {
    const res = await api<any>("/member/dashboard");
    const data = res?.data || {};
    dash.value = data.stats || {};
    if (data.user) user.value = { ...user.value, ...data.user };
  } catch (e: any) {
    // ignore soft fail; profile still usable
  }
  note.value = uni.getStorageSync("yjgmall_biz_note") || "";
}

function saveNote() {
  uni.setStorageSync("yjgmall_biz_note", note.value);
  noteMsg.value = "便笺已保存";
  setTimeout(() => { noteMsg.value = ""; }, 1500);
}

function goLogin() {
  uni.navigateTo({ url: "/pages/login/index" });
}

function goPc() {
  // #ifdef H5
  location.href = `${location.origin}/?pc=1`;
  // #endif
  // #ifndef H5
  uni.showToast({ title: "请在浏览器中打开电脑版", icon: "none" });
  // #endif
}

function goProfile() {
  if (!ensureLogin()) return;
  uni.navigateTo({ url: "/pages/member/profile" });
}

function go(m: MenuItem) {
  if (m.auth && !ensureLogin()) return;
  uni.navigateTo({ url: m.path });
}

function goPath(path: string) {
  if (!ensureLogin()) return;
  uni.navigateTo({ url: path });
}

async function onLogout() {
  await logout();
  user.value = null;
  dash.value = {};
  uni.showToast({ title: "已退出", icon: "none" });
}

onMounted(refresh);
onShow(refresh);
</script>

<template>
  <view class="page member">
    <view class="hero">
      <view v-if="user" class="profile" @click="goProfile">
        <image class="avatar" :src="mediaUrl(user.avatar, '')" mode="aspectFit" />
        <view class="info">
          <text class="name">欢迎，{{ user.name || user.username }}</text>
          <view class="uid-row">
            <text class="uid">用户ID {{ user.id }}</text>
            <image v-if="badgeSrc" class="mbadge" :src="badgeSrc" mode="heightFix" />
          </view>
          <text class="sub">
            {{ user.is_enterprise ? "企业会员" : "个人会员" }}
            · {{ user.group_label || "会员" }}
            （{{ user.username }}）
          </text>
          <view class="verify">
            <text class="v" :class="{ on: user.mobile_verified }">手机</text>
            <text class="v" :class="{ on: user.bank_verified }">银行</text>
            <text class="v" :class="{ on: user.truename_verified }">实名</text>
            <text class="up" @click.stop="goPath('/pages/member/grade')">会员升级</text>
          </view>
        </view>
        <u-icon name="arrow-right" color="rgba(255,255,255,.8)" size="16" />
      </view>
      <view v-else class="profile" @click="goLogin">
        <view class="avatar placeholder">
          <u-icon name="account" color="#fff" size="36" />
        </view>
        <view class="info">
          <text class="name">点击登录</text>
          <text class="sub">管理供应 / 采购 / 订单 / 旺铺</text>
        </view>
        <u-icon name="arrow-right" color="rgba(255,255,255,.8)" size="16" />
      </view>
    </view>

    <view v-if="pending" class="warn">帐号审核中，部分功能可能受限。</view>

    <view v-if="user" class="card dash">
      <view class="money">
        <text class="ml">账户余额</text>
        <text class="mv">¥{{ dash.balance ?? user.balance ?? "0.00" }}</text>
        <text class="ma" @click="goPath('/pages/member/finance')">流水</text>
        <text class="ma" @click="goPath('/pages/member/cash')">提现</text>
        <text class="charge" @click="goPath('/pages/member/finance')">充值</text>
      </view>
      <view class="quick">
        <view
          v-for="(q, i) in quicks"
          :key="i"
          class="q"
          :class="{ action: q.action }"
          @click="goPath(q.path)"
        >
          <text class="qb">{{ q.value }}</text>
          <text class="ql">{{ q.label }}</text>
        </view>
      </view>
    </view>

    <view v-if="user" class="card sys-card">
      <text class="group-title">系统消息</text>
      <text class="muted sys-empty">暂无消息</text>
    </view>

    <view v-if="user" class="card note-card">
      <text class="group-title">商务便笺</text>
      <u-textarea v-model="note" placeholder="个人记事本，仅自己可见" maxlength="5000" height="140" border="surround" />
      <view class="note-ft">
        <text class="muted">{{ note.length }}/5000</text>
        <view class="note-acts">
          <text v-if="noteMsg" class="muted">{{ noteMsg }}</text>
          <u-button size="small" type="primary" color="#2769dc" text="更新" @click="saveNote" />
        </view>
      </view>
    </view>

    <view v-for="(g, gi) in groups" :key="gi" class="card group">
      <text class="group-title">{{ g.title }}</text>
      <view class="grid">
        <view v-for="(m, i) in g.items" :key="i" class="grid-item" @click="go(m)">
          <view class="icon" :style="{ background: m.color }">
            <u-icon :name="m.icon" color="#fff" size="20" />
          </view>
          <text class="m-name">{{ m.name }}</text>
          <text class="m-tip muted">{{ m.tip }}</text>
        </view>
      </view>
    </view>

    <view v-if="user" class="logout-wrap">
      <u-button text="退出登录" color="#e11d48" plain @click="onLogout" />
      <text class="pc-link" @click="goPc">电脑版 ›</text>
    </view>
    <view v-else class="logout-wrap">
      <u-button type="primary" color="#2769dc" text="登录 / 注册" @click="goLogin" />
      <text class="pc-link" @click="goPc">电脑版 ›</text>
    </view>
  </view>
</template>

<style lang="scss" scoped>
.hero {
  background: linear-gradient(155deg, #5b93f0 0%, #2769dc 48%, #1a56b8 100%);
  padding: 52rpx 28rpx 44rpx;
  color: #fff;
  position: relative;
  overflow: hidden;
}
.hero::after {
  content: "";
  position: absolute;
  right: -40rpx;
  top: -40rpx;
  width: 220rpx;
  height: 220rpx;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.08);
  pointer-events: none;
}
.profile { display: flex; align-items: center; gap: 24rpx; position: relative; z-index: 1; }
.avatar {
  width: 116rpx; height: 116rpx; border-radius: 50%;
  background: rgba(255,255,255,.2); overflow: hidden;
  border: 4rpx solid rgba(255,255,255,.4);
  box-shadow: 0 8rpx 20rpx rgba(0,0,0,.12);
}
.avatar.placeholder { display: flex; align-items: center; justify-content: center; }
.info { flex: 1; min-width: 0; }
.uid-row { display: flex; align-items: center; gap: 10rpx; margin-top: 6rpx; }
.uid { font-size: 22rpx; color: rgba(255,255,255,.92); }
.mbadge { height: 32rpx; width: 108rpx; }
.name { font-size: 36rpx; font-weight: 700; display: block; letter-spacing: 0.5rpx; }
.sub { font-size: 22rpx; opacity: .88; margin-top: 8rpx; display: block; }
.verify { display: flex; flex-wrap: wrap; gap: 8rpx; margin-top: 14rpx; align-items: center; }
.v {
  font-size: 20rpx; padding: 4rpx 12rpx; border: 1rpx solid rgba(255,255,255,.4);
  border-radius: 999rpx; opacity: .7;
}
.v.on { opacity: 1; background: rgba(255,255,255,.2); }
.up {
  background: linear-gradient(135deg, #ff9a3c, #ff7a00);
  color: #fff; font-size: 20rpx; padding: 6rpx 14rpx; border-radius: 999rpx;
  font-weight: 600; box-shadow: 0 4rpx 12rpx rgba(255, 122, 0, 0.35);
}
.warn {
  margin: 16rpx 20rpx 0; padding: 18rpx 22rpx; background: #fffbeb;
  border: 1rpx solid #fde68a; color: #92400e; font-size: 24rpx; border-radius: 14rpx;
}
.dash { margin: 16rpx 20rpx 0; padding: 24rpx 18rpx 12rpx; }
.money {
  display: flex; flex-wrap: wrap; align-items: center; gap: 14rpx; padding: 0 8rpx 18rpx;
  border-bottom: 1rpx solid #f1f5f9; font-size: 24rpx;
}
.ml { color: #64748b; }
.mv { color: #c2410c; font-size: 40rpx; font-weight: 800; letter-spacing: -1rpx; }
.ma { color: #2769dc; font-weight: 500; }
.charge {
  margin-left: auto; background: linear-gradient(135deg, #ff9a3c, #ff7a00);
  color: #fff; padding: 8rpx 20rpx; border-radius: 999rpx; font-weight: 600;
  box-shadow: 0 4rpx 12rpx rgba(255, 122, 0, 0.28);
}
.quick { display: flex; flex-wrap: wrap; padding-top: 10rpx; }
.q {
  width: 25%; box-sizing: border-box; padding: 18rpx 4rpx; display: flex; flex-direction: column;
  align-items: center; gap: 8rpx;
}
.qb { font-size: 30rpx; color: #2769dc; font-weight: 700; line-height: 1.2; }
.q.action .qb { color: #ea580c; font-size: 34rpx; }
.ql { font-size: 22rpx; color: #64748b; }
.sys-card, .note-card { margin: 16rpx 20rpx 0; padding: 22rpx; }
.sys-empty { display: block; padding: 8rpx 12rpx 8rpx; font-size: 24rpx; }
.note-ft {
  display: flex; justify-content: space-between; align-items: center; margin-top: 14rpx;
}
.note-acts { display: flex; align-items: center; gap: 12rpx; }
.group { margin-top: 16rpx; padding: 26rpx 12rpx 10rpx; }
.group-title {
  display: block; font-size: 26rpx; font-weight: 700; color: #334155;
  padding: 0 16rpx 18rpx; position: relative;
}
.grid { display: flex; flex-wrap: wrap; }
.grid-item {
  width: 25%; display: flex; flex-direction: column; align-items: center;
  padding: 12rpx 8rpx 26rpx; box-sizing: border-box;
}
.icon {
  width: 78rpx; height: 78rpx; border-radius: 22rpx; display: flex;
  align-items: center; justify-content: center; margin-bottom: 12rpx;
  box-shadow: 0 8rpx 18rpx rgba(39, 105, 220, 0.14);
}
.m-name { font-size: 24rpx; color: #1f2937; font-weight: 500; }
.m-tip { font-size: 18rpx; margin-top: 4rpx; color: #94a3b8; }
.logout-wrap { padding: 28rpx 28rpx 56rpx; }
.pc-link {
  display: block;
  text-align: center;
  margin-top: 28rpx;
  font-size: 24rpx;
  color: #64748b;
}
</style>
