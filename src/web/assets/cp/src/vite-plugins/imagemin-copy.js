import path from 'path';
import fs from 'fs-extra';
import chalk from 'chalk';
import { normalizePath } from 'vite';
import imagemin from 'imagemin';
import imageminGif from 'imagemin-gifsicle';
import imageminPng from 'imagemin-pngquant';
import imageminOptPng from 'imagemin-optipng';
import imageminJpeg from 'imagemin-mozjpeg';
import imageminSvgo from 'imagemin-svgo';
import imageminWebp from 'imagemin-webp';
import imageminJpegTran from 'imagemin-jpegtran';
import { extendDefaultPlugins } from 'svgo';

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
        `\n${chalk.cyan('✨ [vite-plugin-imagemin]')}` + ' - compressed image resource successfully: '
    );

    const keyLengths = Array.from(recordMap.keys(), (name) => name.length);
    const valueLengths = Array.from(
        recordMap.values(),
        (value) => `${Math.floor(100 * value.ratio)}`.length
    );

    const maxKeyLength = Math.max(...keyLengths);
    const valueKeyLength = Math.max(...valueLengths);

    const outDir = path.resolve(config.root, config.build.outDir) + '/';

    recordMap.forEach((value, name) => {
        let { ratio, size, oldSize } = value;

        const rName = name.replace(outDir, '');

        ratio = Math.floor(100 * ratio);
        const fr = `${ratio}`;

        const denseRatio = ratio > 0 ? chalk.red(`+${fr}%`) : ratio <= 0 ? chalk.green(`${fr}%`) : '';

        const sizeStr = `${oldSize.toFixed(2)}kb / tiny: ${size.toFixed(2)}kb`;

        config.logger.info(
            chalk.dim(config.build.outDir + '/') +
            chalk.blueBright(rName) +
            ' '.repeat(2 + maxKeyLength - name.length) +
            chalk.gray(`${denseRatio} ${' '.repeat(valueKeyLength - fr.length)}`) +
            ' ' +
            chalk.dim(sizeStr)
        );
    });
}

export default {
    name: 'imagemin-copy',
    enforce: 'post',
    apply: 'build',

    configResolved(resolvedConfig) {
        config = resolvedConfig;
        inputPath = path.join(config.root, 'src/img');
        outputPath = path.join(path.resolve(config.root, config.build.outDir), 'img');
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

        const filter = /\.(png|jpeg|gif|jpg|bmp|svg)$/i;

        files = files.filter((file) => {
            return filter.test(file);
        });

        const imageminPlugins = [
            imageminGif({
                optimizationLevel: 7,
                interlaced: true,
            }),
            imageminOptPng({
                optimizationLevel: 7,
            }),
            imageminJpeg({
                quality: 20,
                progressive: true,
                arithmetic: false,
            }),
            imageminPng({
                quality: [0.8, 0.9],
                speed: 4,
            }),
            imageminSvgo({
                plugins: [{
                    name: 'preset-default',
                    overrides: {
                        removeDimensions: false,
                        removeViewBox: false,
                        cleanupIDs: false,
                        convertColors: {
                            currentColor: false,
                        },
                    },
                }],
            }),
        ];

        const tinyMap = new Map();
        const mtimeCache = new Map();

        const handles = files.map(async (filePath) => {
            let distFilePath = filePath.replace(inputPath, outputPath);

            let { mtimeMs, size: oldSize } = await fs.stat(filePath);

            if (mtimeMs <= (mtimeCache.get(filePath) || 0)) {
                return;
            }

            let content = await fs.readFile(filePath);

            try {
                content = await imagemin.buffer(content, {
                    plugins: imageminPlugins,
                });
            } catch (error) {
                config.logger.error('imagemin error:' + filePath);
                config.logger.error(error);
            }

            const size = content.byteLength;

            tinyMap.set(distFilePath, {
                size: size / 1024,
                oldSize: oldSize / 1024,
                ratio: size / oldSize - 1,
            });

            await fs.outputFile(distFilePath, content);
            mtimeCache.set(filePath, Date.now());
        });

        Promise.all(handles).then(() => {
            handleOutputLogger(config, tinyMap);
        });
    },
}