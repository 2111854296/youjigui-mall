export default defineNuxtRouteMiddleware(async (to) => {
  const { user, token, fetchMe } = useAuth('front')
  // 已登录：不重复打 /auth/me，避免每次切换多等一轮
  if (token.value && user.value) return
  if (token.value) {
    // 超时则放行到登录，避免 /auth/me 卡住导致侧栏「点了没反应」
    try {
      await Promise.race([
        fetchMe(),
        new Promise((_, reject) => setTimeout(() => reject(new Error('auth-timeout')), 8000)),
      ])
    } catch {
      /* fetchMe 失败或超时：下面统一跳登录 */
    }
  }
  if (!token.value || !user.value) {
    return navigateTo({
      path: '/login',
      query: { redirect: to.fullPath },
    })
  }
})
