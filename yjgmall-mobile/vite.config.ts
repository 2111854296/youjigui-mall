import { defineConfig } from "vite";
import uni from "@dcloudio/vite-plugin-uni";

// https://vitejs.dev/config/
export default defineConfig({
  // H5 挂载目录：https://g.aifeili.cn/h5/
  base: process.env.UNI_H5_BASE || "/h5/",
  plugins: [uni()],
  css: {
    preprocessorOptions: {
      scss: {
        silenceDeprecations: ["legacy-js-api", "import"],
      },
    },
  },
});
