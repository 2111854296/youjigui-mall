<template>
  <!-- 暂时仅有机硅商城单 logo -->
  <footer class="footer site-footer">
    <div class="container cols">
      <div class="brand">
        <div class="t">
          <img class="fmark" src="/img/logo-yjg.png" alt="有机硅商城">
        </div>
        <p>领先的有机硅电商综合服务平台，专注有机硅产业链的价格行情、供应采购与行业资讯。</p>
      </div>
      <div>
        <h4>找产品</h4>
        <ul>
          <li><NuxtLink to="/sell">供应大厅</NuxtLink></li>
          <li><NuxtLink to="/mall">商城精选</NuxtLink></li>
          <li><NuxtLink to="/companies">企业库</NuxtLink></li>
          <li><NuxtLink to="/#products">好物商城</NuxtLink></li>
        </ul>
      </div>
      <div>
        <h4>看行情</h4>
        <ul>
          <li><NuxtLink to="/quotes">价格行情</NuxtLink></li>
          <li><NuxtLink to="/news">行业资讯</NuxtLink></li>
          <li><NuxtLink to="/jobs">招聘专区</NuxtLink></li>
          <li><NuxtLink to="/member/grade">VIP 会员</NuxtLink></li>
        </ul>
      </div>
      <div>
        <h4>做生意</h4>
        <ul>
          <li><NuxtLink to="/member/supplies/create">发布供应</NuxtLink></li>
          <li><NuxtLink to="/buy">发布求购</NuxtLink></li>
          <li><NuxtLink to="/register">商家入驻</NuxtLink></li>
          <li><NuxtLink to="/member/grade">VIP 会员</NuxtLink></li>
        </ul>
      </div>
      <div>
        <h4>帮助中心</h4>
        <ul>
          <li><NuxtLink to="/pages/help">使用指南</NuxtLink></li>
          <li><NuxtLink to="/ask">在线客服</NuxtLink></li>
          <li><NuxtLink to="/guestbook">网站留言</NuxtLink></li>
          <li><NuxtLink to="/pages/about">关于我们</NuxtLink></li>
        </ul>
      </div>
    </div>
    <div class="container bottom">
      <div>
        <NuxtLink to="/">网站首页</NuxtLink>
        · <NuxtLink to="/sitemap">网站地图</NuxtLink>
        · <NuxtLink to="/guestbook">网站留言</NuxtLink>
        · <NuxtLink to="/ads">广告服务</NuxtLink>
        · <NuxtLink to="/pages/about">关于我们</NuxtLink>
        · <NuxtLink to="/pages/contact">联系方式</NuxtLink>
        · <a href="https://beian.miit.gov.cn/" target="_blank" rel="noopener noreferrer">粤ICP备17065327号</a>
      </div>
      <div>© 2015-{{ new Date().getFullYear() }} 有机硅商城</div>
    </div>
  </footer>
</template>

<style scoped>
.site-footer a { text-decoration: none; }
/* 压过 UA / Element Plus 列表缩进，保证标题与链接左缘对齐 */
.site-footer :deep(ul) {
  list-style: none;
  margin: 0;
  padding: 0;
}
.site-footer :deep(h4) {
  margin: 0 0 12px;
  padding: 0;
}
.site-footer :deep(li) {
  margin: 0 0 8px;
  padding: 0;
}
</style>
