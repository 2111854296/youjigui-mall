import { ref } from "vue";
import { api } from "@/utils/request";

export type CatNode = {
  id: number | string;
  name: string;
  children?: CatNode[];
};

export function useCategories(module = "sell") {
  const cats = ref<CatNode[]>([]);
  const loading = ref(false);

  async function load() {
    loading.value = true;
    try {
      const res = await api<any>("/categories", { query: { module } });
      cats.value = res?.data || [];
    } catch {
      cats.value = [];
    } finally {
      loading.value = false;
    }
  }

  /** 扁平选项：父分类下挂子类 */
  function flatOptions() {
    const out: { id: number | string; label: string }[] = [];
    cats.value.forEach((c) => {
      if (c.children?.length) {
        c.children.forEach((ch) => out.push({ id: ch.id, label: `${c.name} / ${ch.name}` }));
      } else {
        out.push({ id: c.id, label: c.name });
      }
    });
    return out;
  }

  return { cats, loading, load, flatOptions };
}
