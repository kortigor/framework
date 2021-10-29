const gulp = require('gulp');
const sass = require('gulp-dart-sass');
const bulk = require('gulp-sass-bulk-importer');
const prefixer = require('gulp-autoprefixer');
const clean = require('gulp-clean-css');
const concat = require('gulp-concat');
const map = require('gulp-sourcemaps');
const bs = require('browser-sync');

module.exports = function sass_publications() {
    return gulp.src('static/src/scss/publications/**/*.scss')
        .pipe(map.init())
        .pipe(bulk())
        .pipe(sass({
            outputStyle: 'compressed'
        }).on('error', sass.logError))
        .pipe(prefixer({
            overrideBrowserslist: ['last 8 versions'],
            browsers: [
                'Android >= 4',
                'Chrome >= 20',
                'Firefox >= 24',
                'Explorer >= 11',
                'iOS >= 6',
                'Opera >= 12',
                'Safari >= 6',
            ],
        }))
        .pipe(clean({
            level: 2
        }))
        .pipe(concat('publications.min.css'))
        .pipe(map.write('./sourcemaps/'))
        .pipe(gulp.dest('frontend/web/css/'))
        .pipe(bs.stream());
}