export type AdSpot = {
  page: string
  kind: string
  hint: string
  where: string
  slot?: number
  cat?: string
}

/** 根据广告位 code/名称 推断页面位置说明（与前台广告中心一致） */
export function spotOf(p: { code?: string; name?: string; size?: string; position?: string; title?: string }): AdSpot {
  const code = String(p.code || p.position || '')
  const name = String(p.name || p.title || '')
  const size = String(p.size || '')
  const dims = /^\s*(\d+)\s*[x×]\s*(\d+)/i.exec(size)
  const isTall = !!(dims && Number(dims[2]) > Number(dims[1]) + 40)

  if (/^home_top/.test(code) || /头图|头牌/.test(name)) {
    return { page: '首页', kind: 'top', hint: '页头区域的头牌位', where: '首页最上方页头' }
  }
  if (code === 'home' || code === 'home_banner' || /首页轮播|首页横幅/.test(name)) {
    return { page: '首页', kind: 'banner', hint: '分类栏与右侧栏中间的轮播大图', where: '首页正中间：左边是分类，右边是「您好，欢迎回来」' }
  }
  if (code === 'home_right' || /首页右侧/.test(name)) {
    return { page: '首页', kind: 'right', hint: '首页轮播右侧的竖向展位', where: '首页轮播大图的右侧' }
  }
  if (code === 'home_portal' || /分类展位/.test(name)) {
    return { page: '首页', kind: 'portal', hint: '首页轮播下面那三个小图', where: '首页正中间，轮播大图下面、品牌推荐下面的三个小图' }
  }
  if (code.startsWith('sell_elite') || code.startsWith('elite_')) {
    const parts = name.split('·').map((s) => s.trim()).filter(Boolean)
    const slot = Number((/第(\d+)/.exec(parts[parts.length - 1] || '') || [])[1] || (/_(\d)$/.exec(code) || [])[1] || 0)
    const cat = parts.length >= 3 ? parts[1] : ''
    const where = cat
      ? `产品分类「${cat}」推荐供应，从左往右第 ${slot} 张广告图`
      : (slot ? `产品分类页「推荐供应」这一排，从左往右第 ${slot} 张广告图` : '产品分类页「推荐供应」这一排的广告图')
    return { page: '产品分类', kind: 'elite', hint: '推荐供应里可购买的广告图', where, slot }
  }
  if (code.startsWith('floor_')) {
    const parts = name.split('·').map((s) => s.trim()).filter(Boolean)
    const slot = Number((/第(\d+)/.exec(parts[parts.length - 1] || '') || [])[1] || (/_(\d)$/.exec(code) || [])[1] || 0)
    const parent = parts.length >= 2 ? parts[1].replace(/第\d+位/, '').trim() : '该品类'
    const child = parts.length >= 4 ? parts[2] : ''
    const where = child
      ? `首页材料产品「${parent}」的「${child}」，从左往右第 ${slot} 个商品`
      : (slot ? `首页材料产品「${parent}」这一排，从左往右第 ${slot} 个商品` : `首页材料产品「${parent}」这一排的 5 个商品位`)
    return { page: '首页', kind: 'floor', hint: '材料产品板块里可购买的推广商品位', where, slot }
  }
  if (/排名|首条/.test(name) || code.startsWith('rank_')) {
    const pageNo = code === 'rank_sell' ? '1' : ((/^rank_[a-z]+_(\d)$/.exec(code) || [])[1] || '')
    const page = /求购|buy/.test(code + name) ? '求购列表' : /公司|企业|company/.test(code + name) ? '名企推荐' : '供应列表'
    const where = pageNo && page === '供应列表' ? `供应列表第 ${pageNo} 页，从上往下第一条` : '列表靠前的推广位'
    return { page, kind: 'rank', hint: `${where}，标成推广，可付费购买`, where }
  }
  if (code.startsWith('quote_') || /^行情详情·/.test(name)) {
    const parts = name.split('·').map((s) => s.trim()).filter(Boolean)
    const slot = Number((/第(\d+)/.exec(parts[parts.length - 1] || '') || [])[1] || (/_(\d)$/.exec(code) || [])[1] || 0)
    const cat = parts.length >= 2 ? parts[1] : '该品种'
    const where = slot
      ? `价格行情「${cat}」详情页右侧，其它品种下面从上往下第 ${slot} 个广告位`
      : `价格行情「${cat}」详情页右侧，其它品种下面的三个广告位`
    return { page: '价格行情', kind: 'quote-ad', hint: '行情详情页右侧对应品种的产品广告位', where, slot, cat }
  }
  if (code.startsWith('sample_floor_') || /^样品专区·/.test(name)) {
    const parts = name.split('·').map((s) => s.trim()).filter(Boolean)
    const slot = Number((/第(\d+)/.exec(parts[parts.length - 1] || '') || [])[1] || (/_(\d)$/.exec(code) || [])[1] || 0)
    const cat = parts.length >= 2 ? parts[1] : '该分类'
    const where = slot
      ? `样品专区「${cat}」这一排，从左往右第 ${slot} 个商品。这一排在顶部横幅下面`
      : `样品专区「${cat}」这一排的 5 个商品位，在顶部横幅下面`
    return { page: '样品专区', kind: 'sample-floor', hint: '样品分类楼层里可购买的推广商品位', where, slot, cat }
  }
  if (code === 'sample_top' || /样品/.test(name)) {
    return { page: '样品专区', kind: 'sample', hint: '样品专区页标题下面的通栏横幅', where: '样品专区页标题下面的通栏横幅' }
  }
  if (code === 'mall_top' || /商城/.test(name)) {
    return { page: '优质产品', kind: 'channel', hint: '商城页顶部横幅轮播', where: '优质产品页，标题下面、商品列表上面的横幅' }
  }
  if (code === 'company_top' || /企业库/.test(name)) {
    return { page: '名企推荐', kind: 'channel', hint: '企业库页顶部横幅轮播', where: '名企推荐页，标题下面、企业卡片上面的横幅' }
  }
  if (code === 'news_top' || /资讯/.test(name)) {
    return { page: '资讯', kind: 'channel', hint: '资讯页顶部横幅轮播', where: '资讯页左上角，右边挨着行情卡片' }
  }
  if (code === 'sell_right' || code === 'buy_right' || /右侧/.test(name) || isTall) {
    const page = /求购|buy/.test(code + name) ? '求购' : '供应'
    return { page, kind: 'side', hint: `${page}详情页右侧竖向展位`, where: `打开任意一条${page}，页面右侧：卖家信息下面、温馨提示上面` }
  }
  if (/供应/.test(name) || code === 'sell_top') {
    return { page: '供应', kind: 'channel', hint: '供应页顶部横幅轮播', where: '供应页标题下面的横幅' }
  }
  if (code === 'footer' || /页脚|底部/.test(name)) {
    return { page: '全站', kind: 'footer', hint: '页面底部通栏', where: '每个页面最底部' }
  }
  return { page: '对应页面', kind: 'channel', hint: '该广告位在页面中的大致区域', where: '红框圈出的区域' }
}
