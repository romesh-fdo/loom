import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

const projectRoot = path.dirname(fileURLToPath(import.meta.url));

export default defineConfig({
    resolve: {
        dedupe: [
            '@codemirror/state',
            '@codemirror/view',
            '@codemirror/language',
            '@codemirror/commands',
            '@codemirror/autocomplete',
            '@codemirror/search',
            '@codemirror/lint',
            'codemirror',
        ],
        alias: {
            '@codemirror/state': path.resolve(projectRoot, 'node_modules/@codemirror/state'),
            '@codemirror/view': path.resolve(projectRoot, 'node_modules/@codemirror/view'),
            '@codemirror/language': path.resolve(projectRoot, 'node_modules/@codemirror/language'),
            '@codemirror/commands': path.resolve(projectRoot, 'node_modules/@codemirror/commands'),
            codemirror: path.resolve(projectRoot, 'node_modules/codemirror'),
        },
    },
    build: {
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (
                        id.includes('node_modules/@codemirror/')
                        || id.includes('node_modules/codemirror/')
                        || /resources[\\/]js[\\/](code-editor|dynamic-code-editor)\.js/.test(id)
                    ) {
                        return 'codemirror';
                    }
                },
            },
        },
    },
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/admin.css',
                'resources/css/theme-settings.css',
                'resources/css/assets-file-manager.css',
                'resources/css/segments-tree.css',
                'resources/css/layout-form.css',
                'resources/js/admin.js',
                'resources/js/file-manager-page.js',
                'resources/js/segments-tree.js',
                'resources/js/layout-form.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
