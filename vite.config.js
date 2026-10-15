import { copyFileSync, mkdirSync } from 'node:fs';
import { resolve } from 'node:path';
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

/**
 * flowbite-vue ships a compiled stylesheet that Tailwind v3's PostCSS pipeline
 * cannot process (it uses v4 syntax such as `@layer base` without `@tailwind`).
 * The file is therefore copied to `public/` and linked from the layout instead
 * of being imported through the bundler.
 */
const copyFlowbiteVueStylesheet = () => {
    const target = resolve('public/vendor/flowbite-vue.css');

    mkdirSync(resolve('public/vendor'), { recursive: true });
    copyFileSync(resolve('node_modules/flowbite-vue/dist/index.css'), target);
};

copyFlowbiteVueStylesheet();

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
});
