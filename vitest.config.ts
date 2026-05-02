import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import { fileURLToPath, URL } from 'node:url';
import { defineConfig } from 'vitest/config';

// Separate vitest config that excludes the wayfinder plugin (which requires
// php artisan to be available) so that unit tests can run independently.
export default defineConfig({
    plugins: [
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            '@spa': fileURLToPath(new URL('resources/spa', import.meta.url)),
            '@': fileURLToPath(new URL('resources/js', import.meta.url)),
        },
    },
    test: {
        environment: 'jsdom',
        include: ['resources/**/*.{spec,test}.{ts,js}'],
    },
});
