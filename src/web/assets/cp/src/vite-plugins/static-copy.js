import path from 'path';
import fs from 'fs-extra';
import chalk from 'chalk';
import { normalizePath } from 'vite';

let inputPath;
let outputPath;
let config;

export function readAllFile(root, reg) {
    let resultArr = [];

    try {
        if (fs.existsSync(root)) {
            const stat = fs.lstatSync(root);

            if (stat.isDirectory()) {
                // dir
                const files = fs.readdirSync(root);

                files.forEach(function (file) {
                    const t = readAllFile(path.join(root, '/', file), reg);
                    
                    resultArr = resultArr.concat(t);
                });
            } else {
                if (reg !== undefined) {
                    if (isFunction(reg.test) && reg.test(root)) {
                        resultArr.push(root);
                    }
                } else {
                    resultArr.push(root);
                }
            }
        }
    } catch (error) {}

    return resultArr;
}

function handleOutputLogger(config, recordMap) {
    config.logger.info(
        `\n${chalk.cyan('✨ [vite-plugin-static-copy]')}` + ' - copied static files: '
    );

    const outDir = path.resolve(config.root, config.build.outDir) + '/';

    recordMap.forEach((value, name) => {
        const rName = name.replace(outDir, '');

        config.logger.info(
            chalk.dim(config.build.outDir + '/') +
            chalk.blueBright(rName)
        );
    });
}

export default {
    name: 'static-copy',
    enforce: 'post',
    apply: 'build',

    configResolved(resolvedConfig) {
        config = resolvedConfig;
        inputPath = path.join(config.root, 'img/favicons');
        outputPath = path.join(path.resolve(config.root, config.build.outDir), 'img/favicons');
    },

    async writeBundle(options, bundle) {
        // Special-case for legacy, ignore
        // https://github.com/anncwb/vite-plugin-imagemin/issues/2
        if (options.chunkFileNames.includes('-legacy')) {
            return;
        }

        let files = readAllFile(inputPath) || [];

        if (!files.length) {
            return;
        }

        const filter = /\.(xml|ico|webmanifest)$/i;

        files = files.filter((file) => {
            return filter.test(file);
        });

        const tinyMap = new Map();

        const handles = files.map(async (filePath) => {
            let distFilePath = filePath.replace(inputPath, outputPath);

            let content = await fs.readFile(filePath);

            tinyMap.set(distFilePath);

            await fs.outputFile(distFilePath, content);
        });

        Promise.all(handles).then(() => {
            handleOutputLogger(config, tinyMap);
        });
    },
}