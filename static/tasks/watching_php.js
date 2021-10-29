const gulp = require('gulp');

const bs = require('browser-sync');

module.exports = function watching_php() {
    gulp.watch(['**/*.php'], gulp.parallel('php'));
}