<script setup lang="ts">
const props = defineProps<{
  place: string
  name?: string
  where?: string
}>()

const homeCats = [
  { name: "有机硅单体/中间体", on: true },
  { name: "硅油" },
  { name: "硅橡胶" },
  { name: "有机硅助剂/填料" },
  { name: "机械设备" },
  { name: "硅烷" },
  { name: "胶粘剂" },
  { name: "硅树脂" },
]
const hotWords = ["DMC", "生胶", "二甲基硅油", "甲基硅油", "混炼胶", "气相白炭黑"]
const subCats = ["甲基硅油", "乙烯基硅油", "含氢硅油", "羟基硅油"]
const friendNames = ["中国硅业", "中国化工信息", "有机硅在线", "硅橡胶网", "化工仪器网"]
const navLinks = ["首页", "价格行情", "好物商城", "样品专区", "名企推荐", "供需对接", "招聘专区", "硅业智讯"]
const notices = [
  "如何让您发布的信息排名更靠前",
  "忘记了登录名或密码该如何找回",
  "联系方式变更怎么办？",
  "VIP会员可享首页推荐位优先展示",
]
</script>

<template>
  <div class="ad-preview">
    <div class="ad-preview-hd">功能位置示意图</div>
    <div class="ad-preview-name">{{ name || "网站功能" }}</div>
    <div class="page-sk" aria-hidden="true">
      <div class="site-top">
        <span>请登录</span>
        <span>免费注册</span>
        <i class="grow" />
        <span>用户中心</span>
        <span class="vip">★ VIP会员</span>
        <span>客服中心</span>
        <span class="help" :class="{ mark: props.place === 'pages' }">帮助中心</span>
      </div>

      <div class="site-head">
        <b class="logo">有机硅商城</b>
        <div class="search">
          <div class="bar">
            <em>产品</em>
            <span>请输入产品名、型号、牌号，如 DMC、107胶</span>
            <b>搜全站</b>
          </div>
          <div class="hots" :class="{ mark: props.place === 'hotwords' }">
            <span>热门搜索：</span>
            <em v-for="(word, i) in hotWords" :key="word" :class="{ red: i === 0 }">{{ word }}</em>
          </div>
        </div>
        <div class="acts">
          <span class="pri">免费发布</span>
          <span>企业入驻</span>
        </div>
      </div>

      <div class="site-nav">
        <b v-for="(link, i) in navLinks" :key="link" :class="{ on: i === 0 }">{{ link }}</b>
      </div>

      <div class="mock-home">
        <aside class="mh-cats" :class="{ mark: props.place === 'categories' }">
          <b>全部产品分类</b>
          <span v-for="cat in homeCats" :key="cat.name" :class="{ on: cat.on }">{{ cat.name }}</span>
        </aside>
        <div class="mh-mid">
          <div class="mh-zone banner">
            <span class="arr">‹</span>
            <b>首页轮播广告位</b>
            <span class="arr">›</span>
            <span class="dots"><i class="on" /><i /><i /><i /><i /></span>
          </div>
          <div class="mh-detail">
            <div class="mh-title">有机硅单体/中间体</div>
            <div class="mh-cards">
              <div v-for="n in 3" :key="n" class="mh-card" />
            </div>
            <div class="mh-line subs" :class="{ mark: props.place === 'categories' }">
              <em>子分类：</em>
              <span v-for="name in subCats" :key="name">{{ name }}</span>
            </div>
            <div class="mh-line"><em>热门供应</em><i /></div>
          </div>
        </div>
        <aside class="mh-rail">
          <div class="mh-hello">
            <b>您好，欢迎回来</b>
            <p>进入商务中心管理供应、采购与企业信息</p>
            <div class="mh-btns"><span>商务中心</span><span class="ghost">发供应</span></div>
            <div class="mh-pub"><b>免费发布供应 / 求购</b><small>直达数万行业买家</small></div>
          </div>
          <div class="mh-notice" :class="{ mark: props.place === 'announcements' }">
            <b>网站公告 <small>更多</small></b>
            <span v-for="title in notices" :key="title">{{ title }}</span>
          </div>
          <div class="mh-stats">
            <div><b>281+</b><small>入驻企业</small></div>
            <div><b>864+</b><small>硅橡胶产品</small></div>
            <div><b>8</b><small>大产品品类</small></div>
          </div>
        </aside>
      </div>

      <div class="friend" :class="{ mark: props.place === 'friend-links' }">
        <b>友情链接</b>
        <span v-for="name in friendNames" :key="name">{{ name }}</span>
        <em>申请链接 ›</em>
      </div>

      <div class="site-foot">
        <div>
          <b>找产品</b>
          <span>供应大厅</span>
          <span>商城精选</span>
          <span>企业库</span>
        </div>
        <div>
          <b>看行情</b>
          <span>价格行情</span>
          <span>行业资讯</span>
        </div>
        <div>
          <b>做生意</b>
          <span>发布供应</span>
          <span>商家入驻</span>
          <span>VIP 会员</span>
        </div>
        <div :class="{ mark: props.place === 'pages' }">
          <b>帮助中心</b>
          <span>使用指南</span>
          <span>在线客服</span>
          <span>关于我们</span>
          <span>联系方式</span>
        </div>
      </div>
    </div>
    <p v-if="where" class="ad-preview-where">位置：{{ where }}</p>
  </div>
</template>

<style scoped>
.ad-preview {
  margin: 0;
  padding: 12px;
  border: 1px solid #d7e4fb;
  border-radius: 10px;
  background: #f7faff;
}
.ad-preview-hd { font-size: 13px; font-weight: 700; color: #123c8f; }
.ad-preview-name { margin-top: 2px; font-size: 12px; color: #5a6b85; }
.ad-preview-where { margin: 8px 0 0; font-size: 13px; line-height: 1.5; font-weight: 700; color: #123c8f; }
.page-sk {
  margin-top: 10px;
  padding: 8px;
  border-radius: 8px;
  background: #eef3fb;
  border: 1px solid #e3ebf6;
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.mark {
  border: 3px solid #e10600;
  border-radius: 8px;
  background: #fff;
}
.site-top {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 4px 8px;
  background: #f7f9fc;
  border-radius: 6px;
  font-size: 10px;
  color: #5a6b85;
}
.site-top .grow { flex: 1; }
.site-top .vip { color: #1443b8; font-weight: 700; }
.site-top .help { padding: 2px 6px; }
.site-head {
  display: grid;
  grid-template-columns: 92px 1fr auto;
  gap: 8px;
  align-items: center;
  background: #fff;
  border-radius: 8px;
  padding: 8px;
}
.logo { font-size: 13px; color: #2468f2; line-height: 1.2; }
.search { min-width: 0; }
.bar {
  display: flex;
  align-items: center;
  height: 28px;
  border: 2px solid #2f7bff;
  border-radius: 6px;
  overflow: hidden;
  background: #fff;
}
.bar em {
  flex: none;
  font-style: normal;
  font-size: 11px;
  font-weight: 700;
  color: #13294b;
  padding: 0 8px;
  border-right: 1px solid #d7e4fb;
}
.bar span { flex: 1; font-size: 10px; color: #98a5bc; padding: 0 8px; white-space: nowrap; overflow: hidden; }
.bar b {
  flex: none;
  height: 100%;
  display: flex;
  align-items: center;
  padding: 0 10px;
  background: #2f7bff;
  color: #fff;
  font-size: 11px;
}
.hots { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; margin-top: 4px; padding: 2px 4px; }
.hots span, .hots em { font-size: 10px; font-style: normal; color: #5a6b85; }
.hots em.red { color: #e10600; font-weight: 700; }
.acts { display: flex; flex-direction: column; gap: 4px; }
.acts span {
  font-size: 10px;
  font-weight: 700;
  text-align: center;
  border-radius: 100px;
  padding: 4px 8px;
  color: #1443b8;
  background: #e8f0ff;
}
.acts .pri { color: #fff; background: #2f7bff; }
.site-nav {
  display: flex;
  gap: 10px;
  padding: 4px 8px;
  background: #1746c0;
  border-radius: 6px;
  overflow: hidden;
}
.site-nav b { font-size: 11px; color: rgba(255,255,255,.88); font-weight: 600; white-space: nowrap; }
.site-nav b.on { color: #fff; }
.mock-home {
  display: grid;
  grid-template-columns: 108px 1fr 132px;
  gap: 6px;
}
.mh-cats {
  background: linear-gradient(180deg, #1746c0, #12379a);
  color: #fff;
  border-radius: 8px;
  padding: 8px 0;
  display: flex;
  flex-direction: column;
}
.mh-cats b { font-size: 11px; padding: 0 8px 6px; }
.mh-cats span { font-size: 10px; line-height: 1.7; padding: 1px 8px; color: rgba(255,255,255,.9); }
.mh-cats span.on { background: rgba(255,255,255,.16); }
.mh-cats.mark span { color: #13294b; }
.mh-cats.mark span.on { background: #e8f0ff; color: #1443b8; }
.mh-cats.mark b { color: #123c8f; }
.mh-mid { display: flex; flex-direction: column; gap: 6px; min-width: 0; }
.mh-zone.banner {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 78px;
  border-radius: 8px;
  background: #2f7bff;
  color: #fff;
  font-size: 13px;
  font-weight: 800;
}
.mh-zone .arr {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  width: 16px;
  height: 16px;
  border-radius: 50%;
  background: rgba(255,255,255,.22);
  font-size: 12px;
  line-height: 16px;
  text-align: center;
}
.mh-zone .arr:first-child { left: 6px; }
.mh-zone .arr:nth-of-type(2) { right: 6px; }
.mh-zone .dots { position: absolute; right: 10px; bottom: 6px; display: flex; gap: 3px; }
.mh-zone .dots i { width: 4px; height: 4px; border-radius: 50%; background: rgba(255,255,255,.45); }
.mh-zone .dots i.on { width: 10px; border-radius: 4px; background: #fff; }
.mh-detail { background: #fff; border-radius: 8px; padding: 6px; }
.mh-title { font-size: 11px; font-weight: 800; color: #1d4fd7; }
.mh-line { display: flex; align-items: center; gap: 4px; margin-top: 4px; }
.mh-line em { font-style: normal; font-size: 9px; color: #5a6b85; flex: none; }
.mh-line i { flex: 1; height: 4px; border-radius: 2px; background: repeating-linear-gradient(90deg, #d5deea 0 4px, transparent 4px 7px); }
.mh-line.subs span { font-size: 10px; color: #1443b8; }
.mh-cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 4px; margin-top: 6px; }
.mh-card { height: 32px; border-radius: 4px; background: #2f7bff; }
.mh-rail { display: flex; flex-direction: column; gap: 4px; min-width: 0; }
.mh-hello {
  background: linear-gradient(135deg, #1d4fd7, #2f6fe8 55%, #3f86f9);
  color: #fff;
  border-radius: 8px;
  padding: 8px;
}
.mh-hello b { display: block; font-size: 11px; }
.mh-hello p { margin: 3px 0 0; font-size: 9px; color: #d6e4ff; }
.mh-btns { display: flex; gap: 4px; margin-top: 6px; }
.mh-btns span { flex: 1; text-align: center; font-size: 10px; font-weight: 700; background: #fff; color: #1443b8; border-radius: 100px; padding: 3px 0; }
.mh-btns .ghost { background: transparent; color: #fff; border: 1px solid rgba(255,255,255,.7); }
.mh-pub { margin-top: 4px; background: rgba(255,255,255,.16); border-radius: 6px; padding: 4px 6px; text-align: center; }
.mh-pub b { font-size: 10px; }
.mh-pub small { display: block; font-size: 8px; color: #d6e4ff; }
.mh-notice { background: #fff; border-radius: 8px; padding: 6px; }
.mh-notice b { display: flex; justify-content: space-between; font-size: 11px; color: #13294b; margin-bottom: 2px; }
.mh-notice small { font-weight: 500; color: #2468f2; }
.mh-notice span { display: block; font-size: 9px; color: #5a6b85; line-height: 1.55; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.mh-notice span::before { content: "• "; color: #2468f2; font-weight: 800; }
.mh-stats { display: flex; background: #fff; border-radius: 8px; padding: 6px; }
.mh-stats div { flex: 1; text-align: center; }
.mh-stats b { display: block; font-size: 12px; color: #123c8f; }
.mh-stats small { font-size: 8px; color: #98a5bc; }
.friend {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
  background: #fff;
  border-radius: 8px;
  padding: 6px 8px;
  font-size: 11px;
  color: #5a6b85;
}
.friend b { color: #13294b; }
.friend em { margin-left: auto; font-style: normal; color: #2468f2; }
.site-foot {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 6px;
}
.site-foot > div { background: #fff; border-radius: 8px; padding: 6px 8px; }
.site-foot b { display: block; font-size: 11px; color: #13294b; margin-bottom: 2px; }
.site-foot span { display: block; font-size: 10px; color: #5a6b85; line-height: 1.6; }
</style>
