import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import { realpathSync } from 'node:fs';
import { fileURLToPath } from 'node:url';

const root = realpathSync(fileURLToPath(new URL('.', import.meta.url)));

export default defineConfig({
    root,
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
