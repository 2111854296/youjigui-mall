export default defineNuxtPlugin(() => {
  const { token, fetchMe, loaded } = useAuth('front')
  if (!loaded.value && token.value) {
    // 不阻塞首屏；会员中间件在缺 user 时再等
    void fetchMe()
  } else if (!token.value) {
    loaded.value = true
  }
})
