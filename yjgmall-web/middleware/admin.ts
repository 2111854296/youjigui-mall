export default defineNuxtRouteMiddleware(async (to) => {
  const { user, token, fetchMe } = useAuth('admin')
  if (token.value && !user.value) {
    try {
      // 避免 API 卡住时中间件无限白屏
      await Promise.race([
        fetchMe(true),
        new Promise((_, reject) => setTimeout(() => reject(new Error('auth timeout')), 8000)),
      ])
    } catch {
      // 超时/失败：落到登录页，不要挂死
    }
  }
  if (!token.value || !user.value) {
    return navigateTo({ path: '/admin/login', query: { redirect: to.fullPath } })
  }
  if (!user.value.is_admin) {
    return navigateTo({ path: '/admin/login', query: { error: 'forbidden' } })
  }
})
