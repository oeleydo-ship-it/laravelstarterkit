import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/sass/app.scss',
                'resources/js/app.js',
                'resources/js/chat/inbox.js',
                'resources/js/chat/list.js',
                'resources/js/chat/presence.js',
                'resources/js/chat/widget.js',
                'resources/js/x/loader.js',
            ],
            refresh: true,
        }),
    ],
    build: {
        sourcemap: false,
        rollupOptions: {
            output: {
                entryFileNames: (chunk) => {
                    if (chunk.name === 'loader' || chunk.facadeModuleId?.includes('/x/loader')) {
                        return 'assets/x-[hash].js';
                    }
                    return 'assets/[name]-[hash].js';
                },
                chunkFileNames: 'assets/[name]-[hash].js',
                assetFileNames: (asset) => {
                    const name = asset.name || '';
                    if (name === 'public.css' || name.startsWith('x-') || /[/\\]x[/\\]/.test(String(asset.originalFileName || ''))) {
                        return 'assets/x-[hash][extname]';
                    }
                    return 'assets/[name]-[hash][extname]';
                },
            },
        },
    },
    css: {
        preprocessorOptions: {
            scss: {
                silenceDeprecations: [
                    'import',
                    'global-builtin',
                    'color-functions',
                    'mixed-decls',
                    'if-function',
                ],
                quietDeps: true,
            },
        },
    },
});
