import { defineConfig } from "vite";
import { nodePolyfills } from "vite-plugin-node-polyfills";
import { resolve } from 'path'

export default defineConfig({
    build: {
        target: "es2022",
        chunkSizeWarningLimit: 10000,
        lib: {
            entry: resolve(__dirname, 'lib/main.js'),
            name: 'MyLib',
            fileName: 'my-lib',
        },
        rollupOptions: {
            output: {
                manualChunks: id => {
                    if (id.includes("node_modules"))
                        return "vendor";
                }
            }
        }
    },
    plugins: [
        nodePolyfills({
            include: ["buffer"]
        })
    ]
})
