import { defineConfig } from 'vite'
import uni from '@dcloudio/vite-plugin-uni'

export default defineConfig({
  plugins: [uni()],
  define: {
    __UNI_PLATFORM__: JSON.stringify('app'),
  },
})
