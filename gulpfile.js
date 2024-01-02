const gulp = require('gulp'),
    autoprefixer = require('autoprefixer'),
    composer = require('gulp-uglify/composer'),
    concat = require('gulp-concat'),
    cssnano = require('cssnano'),
    footer = require('gulp-footer'),
    format = require('date-format'),
    header = require('gulp-header'),
    postcss = require('gulp-postcss'),
    rename = require('gulp-rename'),
    replace = require('gulp-replace'),
    sass = require('gulp-sass')(require('sass')),
    uglifyjs = require('uglify-js'),
    uglify = composer(uglifyjs, console),
    pkg = require('./_build/config.json');

const banner = '/*!\n' +
    ' * <%= pkg.name %> - <%= pkg.description %>\n' +
    ' * Version: <%= pkg.version %>\n' +
    ' * Build date: ' + format("yyyy-MM-dd", new Date()) + '\n' +
    ' */';
const year = new Date().getFullYear();

const mgrScripts = [
    'source/js/mgr/twofactorx.js',
];

const scriptsMgr = function () {
    return gulp.src([
        'source/js/mgr/twofactorx.js',
    ])
        .pipe(concat('twofactorx.min.js'))
        .pipe(uglify())
        .pipe(header(banner + '\n', {pkg: pkg}))
        .pipe(gulp.dest('assets/components/twofactorx/js/mgr/'))
};
const scriptsUserTab = function () {
    return gulp.src([
        'source/js/mgr/usertab.js',
    ])
        .pipe(concat('usertab.min.js'))
        .pipe(uglify())
        .pipe(header(banner + '\n', {pkg: pkg}))
        .pipe(gulp.dest('assets/components/twofactorx/js/mgr/'))
};
const scriptsUserProfile = function () {
    return gulp.src([
        'source/js/mgr/userprofile.js',
    ])
        .pipe(concat('userprofile.min.js'))
        .pipe(uglify())
        .pipe(header(banner + '\n', {pkg: pkg}))
        .pipe(gulp.dest('assets/components/twofactorx/js/mgr/'))
};
const scriptsUserOnetime = function () {
    return gulp.src([
        'source/js/mgr/useronetime.js',
    ])
        .pipe(concat('useronetime.min.js'))
        .pipe(uglify())
        .pipe(header(banner + '\n', {pkg: pkg}))
        .pipe(gulp.dest('assets/components/twofactorx/js/mgr/'))
};
gulp.task('scripts', gulp.series(scriptsMgr, scriptsUserTab, scriptsUserProfile, scriptsUserOnetime));

const sassMgr = function () {
    return gulp.src([
        'source/sass/mgr/twofactorx.scss',
    ])
        .pipe(sass().on('error', sass.logError))
        .pipe(postcss([
            autoprefixer()
        ]))
        .pipe(gulp.dest('source/css/mgr/'))
        .pipe(concat('twofactorx.css'))
        .pipe(postcss([
            cssnano({
                preset: ['default', {
                    discardComments: {
                        removeAll: true
                    }
                }]
            })
        ]))
        .pipe(rename({
            suffix: '.min'
        }))
        .pipe(footer('\n' + banner, {pkg: pkg}))
        .pipe(gulp.dest('assets/components/twofactorx/css/mgr/'))
};
gulp.task('sass', gulp.series(sassMgr));

const bumpCopyright = function () {
    return gulp.src([
        'core/components/twofactorx/model/twofactorx/twofactorx.class.php',
        'core/components/twofactorx/src/TwoFactorX.php',
    ], {base: './'})
        .pipe(replace(/Copyright 2023(-\d{4})? by/g, 'Copyright ' + (year > 2023 ? '2023-' : '') + year + ' by'))
        .pipe(gulp.dest('.'));
};
const bumpVersion = function () {
    return gulp.src([
        'core/components/twofactorx/src/TwoFactorX.php',
    ], {base: './'})
        .pipe(replace(/version = '\d+\.\d+\.\d+[-a-z0-9]*'/ig, 'version = \'' + pkg.version + '\''))
        .pipe(gulp.dest('.'));
};
const bumpDocs = function () {
    return gulp.src([
        'mkdocs.yml',
    ], {base: './'})
        .pipe(replace(/&copy; 2023(-\d{4})?/g, '&copy; ' + (year > 2023 ? '2023-' : '') + year))
        .pipe(gulp.dest('.'));
};
gulp.task('bump', gulp.series(bumpCopyright, bumpVersion, bumpDocs));

gulp.task('watch', function () {
    // Watch .js files
    gulp.watch(['./source/js/**/*.js'], gulp.series('scripts'));
    // Watch .scss files
    gulp.watch(['./source/sass/**/*.scss'], gulp.series('sass'));
});

// Default Task
gulp.task('default', gulp.series('bump', 'scripts', 'sass'));
