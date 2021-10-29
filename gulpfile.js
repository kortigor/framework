'use strict';

const gulp = require('gulp');
const requireDir = require('require-dir');
const tasks = requireDir('./static/tasks');

exports.bs_files = tasks.bs_files;

// SASS
exports.sass_frontend = tasks.sass_frontend;
exports.sass_backend = tasks.sass_backend;
exports.sass_common = tasks.sass_common;
exports.sass_publications = tasks.sass_publications;

// PHP
exports.php = tasks.php;

// Watch
exports.watching = tasks.watching;
exports.watching_sass = tasks.watching_sass;
exports.watching_php = tasks.watching_php;

exports.sass_build = gulp.parallel(
    exports.sass_common,
    exports.sass_backend,
    exports.sass_frontend,
    exports.sass_publications
);

exports.work = gulp.parallel(
    exports.bs_files,
    exports.watching
);

exports.work_sass = gulp.parallel(
    exports.bs_files,
    exports.watching_sass
);