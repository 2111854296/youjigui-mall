export default defineNuxtConfig({
  compatibilityDate: '2024-11-01',
  devtools: { enabled: false },
  modules: ['@element-plus/nuxt'],
  elementPlus: {
    importStyle: 'css',
    defaultLocale: 'zh-cn',
  },
  css: [
    '~/assets/css/fonts.css',
    '~/assets/css/tokens.css',
    '~/assets/css/portal-design.css',
    '~/assets/css/news-portal.css',
    '~/assets/css/channel-portal.css',
    '~/assets/css/auth-portal.css',
  ],
  runtimeConfig: {
    public: {
      apiBase: process.env.NUXT_PUBLIC_API_BASE || 'http://127.0.0.1:8080/api/v1',
      siteName: '有机硅商城',
    },
  },
  // 宝塔静态托管：NUXT_SPA=1 时整站 SPA（无需服务器装 Node）
  ssr: process.env.NUXT_SPA === '1' ? false : true,
  nitro: process.env.NUXT_SPA === '1' ? { preset: 'static' } : undefined,
  // 开发态：会员/后台走 SPA
  routeRules: {
    '/member/**': { ssr: false },
    '/admin/**': { ssr: false },
    '/login': { ssr: false },
    '/register': { ssr: false },
  },
  // SPA 空白页提示（避免“一分钟白屏”无反馈）
  spaLoadingTemplate: true,
  vite: {
    // 关键：开发态首次进入后台时再发现 Element Plus，会触发 optimize → 整页 reload → 空白很久
    optimizeDeps: {
      include: [
        'element-plus',
        'element-plus/es',
        '@element-plus/icons-vue',
        'dayjs',
        'dayjs/plugin/localeData',
        'dayjs/plugin/localizedFormat',
        'dayjs/plugin/customParseFormat',
        'dayjs/plugin/advancedFormat',
        'lodash-unified',
        '@tiptap/vue-3',
        '@tiptap/starter-kit',
        '@tiptap/extension-link',
        '@tiptap/extension-placeholder',
        'vue',
        'vue-router',
      ],
    },
    server: {
      warmup: {
        clientFiles: [
          './layouts/admin.vue',
          './pages/admin/**/*.vue',
          './components/Admin*.vue',
          './components/AdminResourceForm.vue',
          './components/ImageUpload.vue',
          './components/RichTextEditor.vue',
        ],
      },
    },
  },
  app: {
    head: {
      htmlAttrs: { lang: 'zh-CN' },
      title: '有机硅商城',
      titleTemplate: '%s - 领先的有机硅电商综合服务平台',
      meta: [
        { charset: 'utf-8' },
        { name: 'viewport', content: 'width=device-width, initial-scale=1' },
        {
          name: 'description',
          content: '有机硅商城-供应采购企业库资讯一体的B2B平台',
        },
        { property: 'og:site_name', content: '有机硅商城' },
        { property: 'og:type', content: 'website' },
        { property: 'og:title', content: '有机硅商城' },
        {
          property: 'og:description',
          content: '有机硅商城-供应采购企业库资讯一体的B2B平台',
        },
        { property: 'og:url', content: 'https://g.aifeili.cn/' },
        { name: 'twitter:card', content: 'summary' },
      ],
      link: [
        { rel: 'icon', type: 'image/svg+xml', href: '/favicon.svg' },
        { rel: 'icon', href: '/favicon.ico' },
        { rel: 'apple-touch-icon', href: '/apple-touch-icon.png' },
        { rel: 'canonical', href: 'https://g.aifeili.cn/' },
      ],
      script: [{ src: '/article-batch.js?v=4', defer: true }],
    },
  },
})

