/** 主流有机硅行情品种（固定展示顺序） */
export const QUOTE_CATALOG = [
  "二甲基环硅氧烷混合物（DMC）",
  "八甲基环四硅氧烷（D4）",
  "甲基乙烯基硅橡胶（生胶）",
  "107室温硫化硅橡胶（107胶）",
  "二甲基硅油",
  "外资品牌二甲基硅油",
  "硅橡胶混炼胶",
  "气相法二氧化硅（气相白炭黑）",
  "沉淀二氧化硅（沉淀白炭黑）",
  "421#金属硅",
  "工业硅期货",
  "一氯甲烷",
  "有机硅助剂",
  "裂解料二甲基硅油",
  "废硅胶毛边",
] as const;

export function normQuoteName(raw: string): string {
  const s = String(raw || "").trim();
  const map: Record<string, string> = {
    DMC: "二甲基环硅氧烷混合物（DMC）",
    dmc: "二甲基环硅氧烷混合物（DMC）",
    D4: "八甲基环四硅氧烷（D4）",
    d4: "八甲基环四硅氧烷（D4）",
    生胶: "甲基乙烯基硅橡胶（生胶）",
    "基乙烯基硅橡胶（生胶）": "甲基乙烯基硅橡胶（生胶）",
    基乙烯基硅橡胶: "甲基乙烯基硅橡胶（生胶）",
    "107胶": "107室温硫化硅橡胶（107胶）",
  };
  if (map[s]) return map[s];
  return s
    .replace(/^DMC$/i, "二甲基环硅氧烷混合物（DMC）")
    .replace(/^D4$/i, "八甲基环四硅氧烷（D4）")
    .replace(/^生胶$/, "甲基乙烯基硅橡胶（生胶）")
    .replace(/^107胶$/, "107室温硫化硅橡胶（107胶）");
}

export function sortQuotesByCatalog<T extends { name?: string }>(list: T[]): T[] {
  const rank = new Map(QUOTE_CATALOG.map((n, i) => [n, i]));
  return [...list].sort((a, b) => {
    const na = normQuoteName(String(a?.name || ""));
    const nb = normQuoteName(String(b?.name || ""));
    const ra = rank.has(na) ? (rank.get(na) as number) : 999;
    const rb = rank.has(nb) ? (rank.get(nb) as number) : 999;
    if (ra !== rb) return ra - rb;
    return na.localeCompare(nb, "zh");
  });
}
