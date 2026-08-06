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
                'resources/js/f/loader.js',
                'resources/js/r/loader.js',
                'resources/js/b/loader.js',
                'resources/js/sp/loader.js',
            ],
            refresh: true,
        }),
    ],
    build: {
        sourcemap: false,
        rollupOptions: {
            output: {
                entryFileNames: (chunk) => {
                    if (chunk.facadeModuleId?.includes('/sp/loader')) {
                        return 'assets/sp-[hash].js';
                    }
                    if (chunk.facadeModuleId?.includes('/b/loader')) {
                        return 'assets/b-[hash].js';
                    }
                    if (chunk.facadeModuleId?.includes('/r/loader')) {
                        return 'assets/r-[hash].js';
                    }
                    if (chunk.facadeModuleId?.includes('/f/loader')) {
                        return 'assets/f-[hash].js';
                    }
                    if (chunk.name === 'loader' || chunk.facadeModuleId?.includes('/x/loader')) {
                        return 'assets/x-[hash].js';
                    }
                    return 'assets/[name]-[hash].js';
                },
                chunkFileNames: 'assets/[name]-[hash].js',
                assetFileNames: (asset) => {
                    const name = asset.name || '';
                    if (name.startsWith('sp-') || /[/\\]sp[/\\]/.test(String(asset.originalFileName || ''))) {
                        return 'assets/sp-[hash][extname]';
                    }
                    if (name.startsWith('b-') || /[/\\]b[/\\]/.test(String(asset.originalFileName || ''))) {
                        return 'assets/b-[hash][extname]';
                    }
                    if (name.startsWith('r-') || /[/\\]r[/\\]/.test(String(asset.originalFileName || ''))) {
                        return 'assets/r-[hash][extname]';
                    }
                    if (name === 'public.css' || name.startsWith('x-') || /[/\\]x[/\\]/.test(String(asset.originalFileName || ''))) {
                        return 'assets/x-[hash][extname]';
                    }
                    if (name.startsWith('f-') || /[/\\]f[/\\]/.test(String(asset.originalFileName || ''))) {
                        return 'assets/f-[hash][extname]';
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
