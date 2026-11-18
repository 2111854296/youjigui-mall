<script setup lang="ts">
definePageMeta({ layout: 'member', middleware: ['auth', 'enterprise'] })
useHead({ title: '排名推广' })
const { api } = useApi()
const msg = ref('')
const err = ref('')
const form = reactive({ module: 'sell', keyword: '', bid: '100', days: 30 })
const { data, refresh } = useMemberData('spreads', () => api('/member/spreads'))
const items = computed(() => (data.value as any)?.data || [])

const moduleLabel: Record<string, string> = {
  sell: '供应',
  buy: '采购',
  company: '企业库',
  mall: '商城',
}

const demoWord = computed(() => String(form.keyword || '').trim() || 'DMC')
const demoModule = computed(() => moduleLabel[form.module] || '供应')
const demoThing = computed(() => {
  if (form.module === 'buy') return '采购信息'
  if (form.module === 'company') return '企业'
  if (form.module === 'mall') return '商品'
  return '产品'
})
const searchKind = computed(() => {
  if (form.module === 'buy') return '采购'
  if (form.module === 'company') return '名企'
  return '产品'
})
const navWords = ['供应', '求购', '企业库', '商城', '资讯', '展会', '知道', '报价']
const demoRows = computed(() => {
  const w = demoWord.value
  const pics = ['/img/logo-yjg.png', '/img/logo-yjg.png', '/img/logo-yjg.png', '/img/logo-yjg.png']
  if (form.module === 'buy') {
    return [
      { title: `求购 ${w} 100吨`, sum: '广州某化工 · 起订 100吨', area: '广东', price: '预算面议', pic: pics[0], hot: true },
      { title: `求购 ${w} 现货`, sum: '江苏硅材 · 现货', area: '江苏', price: '预算¥12000/吨', pic: pics[1], hot: true },
      { title: `求购 ${w} 月需`, sum: '浙江新材 · 月采购', area: '浙江', price: '预算面议', pic: pics[2], hot: true },
      { title: `求购 ${w} 零散`, sum: '山东贸易 · 零散', area: '山东', price: '预算面议', pic: pics[3], hot: false },
    ]
  }
  if (form.module === 'company') {
    return [
      { title: `${w} 材料有限公司`, sum: '制造商 · 星钻会员', area: '广东', price: '', pic: pics[0], hot: true },
      { title: `德鑫${w}有机硅`, sum: '贸易商 · 黄金会员', area: '江苏', price: '', pic: pics[1], hot: true },
      { title: `鑫弘${w}科技`, sum: '制造商', area: '浙江', price: '', pic: pics[2], hot: true },
      { title: `硅众${w}网络`, sum: '服务商', area: '山东', price: '', pic: pics[3], hot: false },
    ]
  }
  if (form.module === 'mall') {
    return [
      { title: `${w} 二甲基环硅氧烷`, sum: '商城现货 · 起订 1吨', area: '广东', price: '¥11600/吨', pic: pics[0], hot: true },
      { title: `${w} 硅油`, sum: '商城现货 · 起订 200kg', area: '浙江', price: '¥9800/吨', pic: pics[1], hot: true },
      { title: `${w} 硅树脂`, sum: '商城 · 起订面议', area: '江苏', price: '面议', pic: pics[2], hot: true },
      { title: `${w} 硅橡胶`, sum: '商城 · 起订 1吨', area: '山东', price: '¥8600/吨', pic: pics[3], hot: false },
    ]
  }
  return [
    { title: `${w} 二甲基硅氧烷混合环体`, sum: '广州天赐高新材料 · 起订 1吨 · 供货 100吨', area: '广东', price: '¥12800/吨', pic: pics[0], hot: true },
    { title: `${w} 二甲基环硅氧烷`, sum: '浙江硅达材料 · 起订 1吨 · 供货 50吨', area: '浙江', price: '¥11600/吨', pic: pics[1], hot: true },
    { title: `${w} 羟基硅油`, sum: '江苏德鑫有机硅 · 起订 200kg', area: '江苏', price: '面议', pic: pics[2], hot: true },
    { title: `${w} 硅树脂`, sum: '山东普通供应 · 起订 1吨', area: '山东', price: '¥9800/吨', pic: pics[3], hot: false },
    { title: `${w} 硅橡胶`, sum: '普通供应 · 起订 1吨', area: '上海', price: '¥8600/吨', pic: pics[0], hot: false },
  ]
})

async function submit() {
  msg.value = ''
  err.value = ''
  try {
    await api('/member/spreads', { method: 'POST', body: { ...form, bid: Number(form.bid), days: Number(form.days) } })
    await appOk('推广申请已提交。', '提交成功')
    form.keyword = ''
    await refresh()
  } catch (e: any) {
    await appFail(apiErrorText(e, '提交失败'), '提交失败')
  }
}
</script>

<template>
  <div class="panel">
    <h3>排名推广</h3>
    <div class="body">
      <div v-if="msg" class="alert alert-success">{{ msg }}</div>
      <div v-if="err" class="alert alert-error">{{ err }}</div>
      <div class="spread-top">
        <form @submit.prevent="submit">
          <div class="form-row">
            <label>模块</label>
            <select v-model="form.module">
              <option value="sell">供应</option>
              <option value="buy">采购</option>
              <option value="company">企业库</option>
              <option value="mall">商城</option>
            </select>
          </div>
          <div class="form-row"><label>关键词</label><input v-model="form.keyword" required placeholder="如：DMC"></div>
          <div class="form-row"><label>出价</label><input v-model="form.bid" type="number" min="1" required></div>
          <div class="form-row"><label>天数</label><input v-model="form.days" type="number" min="1" required></div>
          <button class="btn" type="submit">提交推广</button>
        </form>

        <div class="guide">
        <aside class="intro">
          <h4>推广功能介绍：</h4>
          <p>
            例如在选择<strong>{{ demoModule }}</strong>模块中使用关键词<strong>{{ demoWord }}</strong>进行排名推广功能，使用后可使全网站用户在全站搜索<strong>{{ demoWord }}</strong>时，您的<strong>{{ demoThing }}</strong>会展现在搜索结果靠前位置。（具体位置需通过网站出价竞价系统综合运算后实时展现，推广出价越高展现排名越靠前，如果需要了解排名推广细节，可联系客服。）
          </p>
        </aside>
        <div class="shot" aria-hidden="true">
          <div class="pg-head">
            <img class="pg-logo" src="/img/logo-yjg.png" alt="">
            <div class="pg-find">
              <div class="pg-bar">
                <span class="pg-cat">{{ searchKind }}
                  <svg viewBox="0 0 12 12" width="9" height="9" aria-hidden="true"><path d="M2.5 4.2l3.5 3.6 3.5-3.6" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                </span>
                <span class="pg-q"><em>{{ demoWord }}</em></span>
                <span class="pg-go">
                  <svg viewBox="0 0 24 24" width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.2-3.2"/></svg>
                  搜全站
                </span>
              </div>
              <div class="pg-hot"><span>热门搜索：</span><b>{{ demoWord }}</b><i>生胶</i><i>二甲基硅油</i><i>甲基硅油</i><i>混炼胶</i><i>气相白炭黑</i></div>
            </div>
            <div class="pg-acts">
              <span class="pub">免费发布</span>
              <span class="join">企业入驻</span>
            </div>
          </div>
          <div class="pg-nav">
            <span v-for="word in navWords" :key="word">{{ word }}</span>
          </div>
          <div class="pg-list">
            <div v-for="row in demoRows" :key="row.title" class="pg-row" :class="{ hot: row.hot }">
              <span class="badge">{{ demoWord }}</span>
              <span class="tx">
                <b>{{ row.title }}</b>
                <i>{{ row.sum }}</i>
              </span>
            </div>
          </div>
        </div>
        </div>
      </div>

      <table class="table">
        <thead><tr><th>模块</th><th>关键词</th><th>出价</th><th>天数</th><th>状态</th></tr></thead>
        <tbody>
          <tr v-for="item in items" :key="item.id">
            <td>{{ moduleLabel[item.module] || item.module }}</td>
            <td>{{ item.keyword }}</td>
            <td>{{ item.bid }}</td>
            <td>{{ item.days }}</td>
            <td>{{ auditStatusLabel(item.status) }}</td>
          </tr>
          <tr v-if="!items.length"><td colspan="5" class="muted">暂无推广</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<style scoped>
.spread-top {
  display: grid;
  grid-template-columns: minmax(200px, 3fr) minmax(0, 7fr);
  gap: 18px;
  align-items: start;
}
.guide {
  display: grid;
  grid-template-columns: minmax(0, 9fr) minmax(0, 11fr);
  gap: 14px 18px;
  align-items: center;
  border: 2px solid #2f6fe8;
  border-radius: 22px;
  padding: 22px 16px 22px 26px;
}
.intro { min-width: 0; }
.intro h4 {
  margin: 0 0 10px;
  font-size: 18px;
  font-weight: 800;
  color: #1a1a1a;
}
.intro p {
  margin: 0;
  font-size: 15px;
  line-height: 1.85;
  color: #222;
}
.intro strong { color: #123c8f; font-weight: 800; }
.shot {
  min-width: 0;
  background: #fff;
  border: 1px solid #e5eaf2;
  border-radius: 4px;
  overflow: hidden;
}
.pg-head {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  padding: 8px 8px 4px;
}
.pg-logo { height: 32px; width: auto; flex: 0 0 auto; display: block; margin-top: 2px; }
.pg-find { flex: 1; min-width: 0; }
.pg-bar {
  display: flex;
  align-items: stretch;
  height: 32px;
  border: 2px solid #2468f2;
  border-radius: 8px;
  background: #fff;
}
.pg-cat {
  display: inline-flex;
  align-items: center;
  gap: 2px;
  padding: 0 8px;
  font-size: 12px;
  font-weight: 700;
  color: #13294b;
  border-right: 1px solid #e6edf6;
  white-space: nowrap;
}
.pg-q {
  flex: 1;
  min-width: 0;
  display: flex;
  align-items: center;
  padding: 0 8px;
}
.pg-q em {
  position: relative;
  font-style: normal;
  font-size: 12px;
  font-weight: 700;
  color: #13294b;
  padding: 2px 8px;
}
.pg-q em::after,
.pg-go::after {
  content: "";
  position: absolute;
  border: 2px solid #e10600;
  border-radius: 999px;
  pointer-events: none;
}
.pg-q em::after { inset: -4px -3px; }
.pg-go {
  position: relative;
  display: inline-flex;
  align-items: center;
  gap: 3px;
  margin: -2px -2px -2px 0;
  padding: 0 12px;
  border-radius: 0 6px 6px 0;
  background: #2468f2;
  color: #fff;
  font-size: 12px;
  font-weight: 800;
  white-space: nowrap;
}
.pg-go::after { inset: -6px -4px; }
.pg-hot {
  display: flex;
  gap: 7px;
  margin-top: 4px;
  font-size: 11px;
  color: #98a5bc;
  white-space: nowrap;
  overflow: hidden;
}
.pg-hot b { color: #f04b3e; font-weight: 700; }
.pg-hot i { font-style: normal; color: #5a6b85; }
.pg-acts {
  display: flex;
  flex-direction: column;
  gap: 4px;
  flex: 0 0 auto;
}
.pg-acts span {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  height: 22px;
  padding: 0 8px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 800;
  white-space: nowrap;
}
.pub { background: #2468f2; color: #fff; }
.join { background: #e8f1ff; color: #2468f2; }
.pg-nav {
  display: flex;
  gap: 10px;
  padding: 4px 10px 6px;
  border-bottom: 1px solid #eef2f8;
  font-size: 11px;
  color: #5a6b85;
  white-space: nowrap;
  overflow: hidden;
}
.pg-nav span:first-child { color: #2468f2; font-weight: 800; }
.pg-list { padding: 8px; background: #f5f7fb; }
.pg-row {
  display: flex;
  align-items: center;
  gap: 8px;
  margin: 0 0 6px;
  padding: 6px 8px;
  background: #fff;
  border: 2px solid transparent;
  border-radius: 10px;
}
.pg-row.hot { border-color: #e10600; }
.badge {
  flex: 0 0 auto;
  width: 40px;
  height: 36px;
  display: grid;
  place-items: center;
  border-radius: 4px;
  background: #2468f2;
  color: #fff;
  font-size: 10px;
  font-weight: 800;
  overflow: hidden;
  padding: 0 2px;
}
.tx { min-width: 0; display: flex; flex-direction: column; }
.tx b, .tx i {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.tx b { font-size: 12.5px; font-weight: 800; color: #13294b; }
.tx i { margin-top: 2px; font-style: normal; font-size: 11px; color: #8b98ad; }
.table { margin-top: 18px; }
@media (max-width: 900px) {
  .spread-top,
  .guide { grid-template-columns: 1fr; }
}
@media (max-width: 860px) {
  .spread-top { grid-template-columns: 1fr; }
}
</style>
