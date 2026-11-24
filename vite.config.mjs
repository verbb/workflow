import { resolve } from 'node:path';

import { defineConfig } from 'vite';

const root = resolve(import.meta.dirname, 'src/web/assets/cp');

// `css` and `js` build Workflow's own assets for every page. `setup` builds the Plugin Kit components for the
// first-run checklist separately, so only the Submissions page loads them, and only until the first submission.
const inputs = {
    css: 'src/workflow.css',
    js: 'src/workflow.js',
    setup: 'src/setup.js',
};

export default defineConfig(({ mode }) => {
    if (!inputs[mode]) {
        throw new Error(`Unknown asset build mode: ${mode}`);
    }

    const isCssBuild = mode === 'css';

    let output;

    if (isCssBuild) {
        output = {
            assetFileNames: '[name][extname]',
        };
    } else {
        output = {
            assetFileNames: '[name][extname]',
            codeSplitting: false,
            entryFileNames: '[name].js',
            format: 'iife',
        };
    }

    return {
        root,
        input: resolve(root, inputs[mode]),
        build: {
            outDir: resolve(root, 'dist'),
            emptyOutDir: isCssBuild,
            assetsDir: '',
            cssMinify: 'esbuild',
            // Plugin Kit targets the browsers Craft 5's control panel supports, so its bundle keeps modern syntax.
            cssTarget: mode === 'setup' ? undefined : ['chrome61', 'safari10'],
            minify: 'oxc',
            sourcemap: !isCssBuild,
            target: mode === 'setup' ? 'es2022' : 'es2015',
            rolldownOptions: {
                output,
            },
        },
    };
});
