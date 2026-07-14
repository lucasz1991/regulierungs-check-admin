import { defineConfig } from 'vite';
import laravel, { refreshPaths } from 'laravel-vite-plugin';
import { viteStaticCopy } from 'vite-plugin-static-copy'

import fs from 'fs-extra';
import path from 'path';

const folder = {
    src: "resources/", // source files
    src_assets: "resources/", // source assets files
    dist: "public/", // build files
    dist_assets: "public/build/" //build assets files
};

export default defineConfig({
    build: {
        manifest: true,
        rtl: true,
        outDir: 'public/build/',
        cssCodeSplit: true,
        buildDirectory: '',
        rollupOptions: {
            output: {
                assetFileNames: (css) => {
                    if (css.name.split('.').pop() == 'css') {
                        return 'css/' + `[name]` + '.min.' + 'css';
                    } else {
                        return 'icons/' + css.name;
                    }
                },
                entryFileNames: 'js/' + `[name]` + `-[hash].js`,
            },
        },
    },
    plugins: [
        laravel({
            input: [
                'resources/scss/tailwind.scss',
                'resources/scss/icons.scss',
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: [
                ...refreshPaths,
                'app/Livewire/**',
            ],
        }),
        viteStaticCopy({
            targets: [
                {
                    src: 'resources/fonts',
                    dest: ''
                },
                {
                    src: 'resources/images',
                    dest: ''
                },
                {
                    // Only legacy page initializers are served without bundling.
                    // Copying all of resources/js collides with Vite's app entry and
                    // previously produced an unhashed app2.js with stale chunk URLs.
                    src: 'resources/js/pages',
                    dest: 'js'
                },
            ]
        }),
        {
            name: 'copy-specific-packages',
            async writeBundle() {
                const outputPath = path.resolve(__dirname, folder.dist_assets); // Adjust the destination path
                const configPath = path.resolve(__dirname, 'package-copy-config.json');

                try {
                    const configContent = await fs.readFile(configPath, 'utf-8');
                    const { packagesToCopy } = JSON.parse(configContent);

                    for (const packageName of packagesToCopy) {
                        const destPackagePath = path.join(outputPath, 'libs', packageName);

                        const sourcePath = (fs.existsSync(path.join(__dirname, 'node_modules', packageName + "/dist"))) ?
                            path.join(__dirname, 'node_modules', packageName + "/dist")
                            : path.join(__dirname, 'node_modules', packageName);

                        try {
                            await fs.access(sourcePath, fs.constants.F_OK);
                            await fs.copy(sourcePath, destPackagePath);
                        } catch (error) {
                            console.error(`Package ${packageName} does not exist.`);
                        }
                    }
                } catch (error) {
                    console.error('Error copying and renaming packages:', error);
                }
            },
        },
        {
            name: 'sync-news-pagebuilder-tailwind-to-base',
            async closeBundle() {
                const sourcePath = path.resolve(
                    __dirname,
                    'public/build/css/tailwind.min.css'
                );
                const basePublicPath = path.resolve(
                    __dirname,
                    '../regulierungs-check-base/public/adminresources/css'
                );

                // Admin and Base are deployed independently. Keep the committed
                // Base asset in sync during local combined builds, but do not make
                // an Admin-only deployment depend on the sibling repository.
                if (
                    ! await fs.pathExists(sourcePath)
                    || ! await fs.pathExists(basePublicPath)
                ) {
                    return;
                }

                const targetPath = path.join(
                    basePublicPath,
                    'pagebuilder-tailwind.min.css'
                );

                await fs.copy(sourcePath, targetPath, { overwrite: true });
                console.log(`News PageBuilder Tailwind synced to ${targetPath}`);
            },
        },
    ],
    resolve: {
        alias: {
            '@fonts': '/resources/fonts',
        },
    },
});
