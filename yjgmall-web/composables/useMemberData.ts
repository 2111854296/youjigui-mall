/**
 * 会员中心数据：不阻塞路由切换，先出壳再拉接口。
 * 老 Destoon 是同进程 PHP+SQL；我们多一跳 API，必须用懒加载才不会感觉卡。
 */
export function useMemberData<T = any>(
  key: string,
  fetcher: () => Promise<T>,
  opts: { watch?: any[] } = {},
) {
  return useAsyncData<T>(key, fetcher, {
    lazy: true,
    server: false,
    ...opts,
  })
}
