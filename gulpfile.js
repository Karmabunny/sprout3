const path = require('path');
const gulp = require('gulp');
const sourcemaps = require('gulp-sourcemaps');

const postcss = require('gulp-postcss');
const sass = require('gulp-dart-sass');
const cssnano = require('cssnano');
const autoprefixer = require('autoprefixer');

const _webpack = require('webpack');
const webpack = require('webpack-stream');
const named = require('vinyl-named');

const through = require('through2');
const Vinyl = require('vinyl');

const r = path.resolve.bind(null, __dirname);

const config = Object.assign(
    {
        styles: {},
        assets: {},
        scripts: {},
    },
    require(__dirname + '/gulp.config.js')
);


/**
 * Build all sets.
 */
function runAll(config, fn) {
    const all = [];

    for (let [key, configSet] of Object.entries(config)) {
        // Prep a function and give it a name.
        const task = fn.bind(null, configSet);
        task.displayName = fn.name + ':' + key;

        // Ready to go.
        all.push(task);
    }

    if (!all.length) {
        const task = function(done) {
            done();
        }
        task.displayName = fn.name + ':skip';
        all.push(task);
    }

    // All at once.
    return gulp.parallel(...all);
}


/**
 * Watch all sets.
 *
 * Make sure you've got your inotify watch count maxed out.
 */
 function watch(config, fn) {
     const all = [];

     for (let [key, configSet] of Object.entries(config)) {
        const globs = [];

        if (Array.isArray(configSet.watch)) {
            globs.push(...configSet.watch);
        }

        if (!globs.length) continue;

        const task = function() {
            const subtask = fn.bind(null, configSet);
            subtask.displayName = fn.name + ':' + key;

            return gulp.watch(globs, {
                ignoreInitial: false,
                events: 'all',
                delay: 300,
            }, subtask);
        }

        task.displayName = 'watch:' + fn.name + ':' + key;

        // Ready to go.
        all.push(task);
    }

    if (!all.length) {
        const task = function(done) {
            done();
        }
        task.displayName = 'watch:skip';
        all.push(task);
    }

    return gulp.parallel(...all);
}


/**
 * Build a single skin set.
 */
function styles(configSet) {
    const { src, target } = configSet;

    return gulp.src(src.map(v => r(v)))
    .pipe(sourcemaps.init())
    .pipe(
        sass.sync({
            sourceMap: true,
        })
        .on('error', sass.logError)
    )
    .pipe(
        postcss([
            autoprefixer(),
            cssnano({
                preset: 'default',
            }),
        ])
    )
    .pipe(sourcemaps.write('.'))
    .pipe(gulp.dest(r(target)))
}


/**
 * Build a script set.
 */
function scripts(configSet) {
    const { src, target } = configSet;

    const isProduction = process.env.NODE_ENV == 'production';

    return gulp.src(src.map(v => r(v)))
    .pipe(named())
    .pipe(webpack({
        mode: isProduction ? 'production' : 'development',
        devtool: isProduction ? 'eval-cheap-source-map' : false
    }, _webpack))
    .pipe(gulp.dest(r(target)));
}


/**
 * Copy in 3rd-party assets from wherever.
 */
 function assets(configSet) {
    const { src, target } = configSet;

    let written = false;
    const ignores = src.map(v => path.basename(v));

    return gulp.src(src.map(v => r(v)))
    .pipe(through.obj(function(chunk, enc, next) {
        // Just once, add the gitignore of all our asset.
        if (!written) {
            let contents = '';
            contents += "# Auto-generated (by gulpfile.js) \n";
            contents += "# Yes, please commit this.\n";
            contents += ignores.join("\n");

            this.push(new Vinyl({
                path: '.gitignore',
                contents: Buffer.from(contents),
            }));

            written = true;
        }

        next(null, chunk);
    }))
    .pipe(gulp.dest(r(target)));
}


const allStyles = runAll(config.styles, styles);
const allAssets = runAll(config.assets, assets);
const allScripts = runAll(config.scripts, scripts);

const watchStyles = watch(config.styles, styles);
const watchScripts = watch(config.scripts, scripts);

const watchAll = gulp.parallel(watchStyles, watchScripts);
const buildAll = gulp.parallel(allStyles, allAssets, allScripts);

exports.styles = allStyles;
exports.assets = allAssets;
exports.scripts = allScripts;
exports.build = buildAll;
exports.watch = watchAll;
exports.default = buildAll;
