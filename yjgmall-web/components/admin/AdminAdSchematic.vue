<script setup lang="ts">
import { spotOf, type AdSpot } from '~/utils/adPlaceSpot'

const props = defineProps<{
  code?: string
  name?: string
  title?: string
  size?: string
}>()

const place = computed(() => ({
  code: props.code,
  name: props.name || props.title,
  title: props.title,
  size: props.size,
}))

const spot = computed<AdSpot>(() => spotOf(place.value))
const label = computed(() => props.name || props.title || props.code || '广告位')
</script>

<template>
  <div class="schematic">
    <div class="hd">广告位置示意图</div>
    <div class="name">{{ label }}<span v-if="size"> · {{ size }}</span></div>
    <div class="sk" aria-hidden="true">
      <!-- 首页轮播 / 分类展位 -->
      <div v-if="spot.kind === 'banner' || spot.kind === 'portal' || spot.kind === 'right'" class="mock home">
        <aside class="cats"><b>分类</b><i /><i /><i /></aside>
        <div class="mid">
          <div class="banner" :class="{ on: spot.kind === 'banner' }">轮播大图</div>
          <div class="portal">
            <span v-for="n in 3" :key="n" :class="{ on: spot.kind === 'portal' }">小图{{ n }}</span>
          </div>
        </div>
        <aside class="rail">
          <div class="hello">欢迎回来</div>
          <div class="side" :class="{ on: spot.kind === 'right' }">右侧位</div>
        </aside>
      </div>
      <!-- 频道顶栏 -->
      <div v-else-if="spot.kind === 'channel' || spot.kind === 'sample' || spot.kind === 'top'" class="mock channel">
        <div class="tabs"><i /><i /><i /></div>
        <div class="title">{{ spot.page }}</div>
        <div class="banner on">{{ label }}</div>
        <div class="grid"><i /><i /><i /><i /></div>
      </div>
      <!-- 详情右侧 -->
      <div v-else-if="spot.kind === 'side'" class="mock sidepage">
        <div class="main"><i class="photo" /><div class="info"><b>商品标题</b><em /><em /></div></div>
        <aside>
          <div class="box">卖家信息</div>
          <div class="banner on tall">广告位</div>
          <div class="box">温馨提示</div>
        </aside>
      </div>
      <!-- 行情详情 -->
      <div v-else-if="spot.kind === 'quote-ad'" class="mock quote">
        <div class="main"><b>{{ spot.cat || '品种' }}</b><div class="chart">走势</div></div>
        <aside>
          <div class="box">其它品种</div>
          <div v-for="n in 3" :key="n" class="slot" :class="{ on: spot.slot === n }">第{{ n }}位</div>
        </aside>
      </div>
      <!-- 楼层 / 名企 / 样品楼层 -->
      <div v-else-if="spot.kind === 'floor' || spot.kind === 'elite' || spot.kind === 'sample-floor'" class="mock floor">
        <aside>{{ spot.cat || spot.page }}</aside>
        <div class="cards">
          <span v-for="n in 5" :key="n" :class="{ on: !spot.slot || spot.slot === n }">第{{ n }}位</span>
        </div>
      </div>
      <!-- 列表排名 -->
      <div v-else-if="spot.kind === 'rank'" class="mock rank">
        <aside><b>分类</b><i /><i /></aside>
        <div class="list">
          <div class="row on">第1条 · 推广</div>
          <div class="row">普通信息</div>
          <div class="row">普通信息</div>
        </div>
      </div>
      <div v-else class="mock channel">
        <div class="title">{{ spot.page }}</div>
        <div class="banner on">{{ label }}</div>
      </div>
    </div>
    <p class="where">位置：{{ spot.where }}</p>
    <p class="hint">{{ spot.hint }}</p>
  </div>
</template>

<style scoped>
.schematic {
  background: #fff;
  border: 1px solid #e8ecf0;
  border-radius: 14px;
  padding: 14px;
}
.hd { font-size: 14px; font-weight: 700; color: #1443b8; }
.name { margin-top: 4px; font-size: 12px; color: #64748b; }
.sk { margin-top: 10px; }
.mock {
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #f8fafc;
  padding: 8px;
  font-size: 11px;
  color: #64748b;
}
.home { display: grid; grid-template-columns: 56px 1fr 64px; gap: 6px; min-height: 120px; }
.cats, .rail { display: flex; flex-direction: column; gap: 4px; }
.cats b, .hello { font-size: 10px; color: #334155; }
.cats i, .grid i { display: block; height: 10px; background: #e2e8f0; border-radius: 3px; }
.mid { display: flex; flex-direction: column; gap: 6px; }
.banner {
  height: 48px;
  border-radius: 6px;
  background: #e2e8f0;
  display: grid;
  place-items: center;
  font-weight: 700;
}
.banner.tall { height: 72px; }
.banner.on, .portal span.on, .side.on, .slot.on, .cards span.on, .row.on {
  background: #fff1e0;
  color: #1443b8;
  outline: 2px solid #2468f2;
  outline-offset: -1px;
}
.portal { display: grid; grid-template-columns: repeat(3, 1fr); gap: 4px; }
.portal span, .side, .slot, .box, .cards span, .row {
  background: #e2e8f0;
  border-radius: 4px;
  padding: 6px 4px;
  text-align: center;
}
.channel .tabs { display: flex; gap: 4px; margin-bottom: 6px; }
.channel .tabs i { flex: 1; height: 8px; background: #e2e8f0; border-radius: 3px; }
.channel .title { font-weight: 700; color: #334155; margin-bottom: 6px; }
.channel .grid { margin-top: 6px; display: grid; grid-template-columns: repeat(4, 1fr); gap: 4px; }
.channel .grid i { height: 28px; }
.sidepage { display: grid; grid-template-columns: 1fr 72px; gap: 6px; }
.sidepage .main { display: flex; gap: 6px; }
.sidepage .photo { width: 48px; height: 64px; background: #e2e8f0; border-radius: 4px; }
.sidepage .info { flex: 1; display: flex; flex-direction: column; gap: 4px; }
.sidepage .info em { height: 8px; background: #e2e8f0; border-radius: 3px; }
.sidepage aside { display: flex; flex-direction: column; gap: 4px; }
.quote { display: grid; grid-template-columns: 1fr 70px; gap: 6px; }
.quote .chart { margin-top: 6px; height: 48px; background: #e2e8f0; border-radius: 4px; display: grid; place-items: center; }
.quote aside { display: flex; flex-direction: column; gap: 4px; }
.floor { display: grid; grid-template-columns: 52px 1fr; gap: 6px; align-items: center; }
.floor .cards { display: grid; grid-template-columns: repeat(5, 1fr); gap: 4px; }
.rank { display: grid; grid-template-columns: 48px 1fr; gap: 6px; }
.rank .list { display: flex; flex-direction: column; gap: 4px; }
.where { margin: 10px 0 0; font-size: 12px; font-weight: 700; color: #1443b8; line-height: 1.5; }
.hint { margin: 4px 0 0; font-size: 11px; color: #94a3b8; line-height: 1.45; }
</style>
