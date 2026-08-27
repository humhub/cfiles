import { defineConfig } from 'vitest/config';
import vue from '@vitejs/plugin-vue';
import { existsSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { resolve } from 'node:path';

/**
 * The module's Vue sources are tested against the core they run in, not against a copy of it:
 * `@humhub/vue` resolves to the core's own test shim, the platform stubs come from the core's
 * setup file, and the components the island nests (`DropdownMenu`, `UiModal`, the form suite,
 * `ContentControls`, `UserImage`) are imported from the core through the `@core` alias. That
 * keeps the tests honest — a change to a core component breaks them here rather than in a
 * browser.
 *
 * Where that core lives depends on the layout: inside a HumHub installation the module sits at
 * `protected/modules/<id>` or `modules/<id>`, and in development it is often a separate
 * checkout entirely. Set `HUMHUB_CORE_PATH` for anything the list below does not find.
 */
const here = (...parts) => resolve(fileURLToPath(new URL('.', import.meta.url)), ...parts);

const candidates = [
    process.env.HUMHUB_CORE_PATH,
    here('../../..'), // protected/modules/<id> — the CI and standard install layout
    here('../..'), // modules/<id>
    here('../../../..'),
    // Core and modules kept as sibling checkouts under one root, e.g.
    // <root>/core/develop next to <root>/modules/<line>/<id>.
    here('../../../core/develop'),
    here('../../core/develop'),
].filter(Boolean);

const isCore = (path) => existsSync(resolve(path, 'protected/humhub/vue/DropdownMenu.vue'));
const core = candidates.map((path) => resolve(here(), path)).find(isCore);

if (!core) {
    throw new Error(
        'Could not locate a HumHub core checkout to test against. Set HUMHUB_CORE_PATH to one, '
        + 'e.g. HUMHUB_CORE_PATH=../../../core/develop npm test\n'
        + 'Looked in:\n  ' + candidates.join('\n  '),
    );
}

const humhub = resolve(core, 'protected/humhub');

export default defineConfig({
    plugins: [vue()],
    resolve: {
        alias: {
            // Mirrors the production build, where `@humhub/vue` is external and mapped onto
            // the humhub.modules.vue global (see the core's vue.build.mjs).
            '@humhub/vue': resolve(humhub, 'tests/js/support/humhubVueShim.mjs'),
            // Lets a test import a core component by the path the core knows it under.
            '@core': humhub,
            // CSP forbids the template compiler in production (see the core's
            // docs/develop/ui-js-vuejs.md, "Constraints") — test against the same
            // runtime-only build, so a template sneaking into a test component fails here
            // instead of only in the browser.
            vue: 'vue/dist/vue.runtime.esm-bundler.js',
        },
    },
    server: {
        fs: {
            // The core lives outside this project, and vite refuses to serve from there
            // unless told: the shim, the setup file and every `@core/...` component are read
            // from that checkout.
            allow: [here(), core],
        },
    },
    define: {
        __VUE_OPTIONS_API__: true,
        __VUE_PROD_DEVTOOLS__: false,
        __VUE_PROD_HYDRATION_MISMATCH_DETAILS__: false,
    },
    test: {
        environment: 'jsdom',
        include: ['tests/js/**/*.test.js'],
        setupFiles: [
            resolve(humhub, 'tests/js/support/setup.mjs'),
            here('tests/js/support/setup.mjs'),
        ],
    },
});
