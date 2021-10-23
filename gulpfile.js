const gulp = require('gulp');
const postcss = require('gulp-postcss');
const sourcemaps = require('gulp-sourcemaps');
const sass = require('gulp-dart-sass');
const cssnano = require('cssnano');
const autoprefixer = require('autoprefixer');

// Settings, per skin.
const config = {
    "default": {
        "src": ["src/skin/default/_patterns/global.scss"],
        "target": "src/skin/default/css",
    },
};


// A lovely little hack to 'discover' all the watched files.
// This could also be smarter to map entry files to included files, so we can
// rebuild only the necessary files from the watch() method.
const watched = new Set();
(function() {
    const _render = sass.compiler.renderSync;
    sass.compiler.renderSync = function(opts) {
        const result = _render(opts);
        for (let file of result.stats.includedFiles) {
            watched.add(file);
        }
        return result;
    };
})();


/**
 * Build a single skin.
 *
 * @param {string[]} src files
 * @param {string} target folder
 */
function build(src, target) {
    return gulp.src(src)
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
    .pipe(gulp.dest(target))
}


/**
 * All of them.
 */
const buildAll = gulp.parallel(...(
    Object.entries(config).map(([name, skin]) => {
        function wrapper() {
            return build(skin.src, skin.target);
        }
        wrapper.displayName = name;
        return wrapper;
    })
));


/**
 * Watch all files.
 *
 * Make sure you've got your inotify watch count maxed out.
 */
function watch() {
    return gulp.watch([...watched], {
        ignoreInitial: true,
        events: 'all',
        delay: 300,
    }, buildAll);
}


exports.default = build;
exports.build = buildAll;
exports.watch = gulp.series(buildAll, watch);
