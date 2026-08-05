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
            ],
            refresh: true,
        }),
    ],
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
