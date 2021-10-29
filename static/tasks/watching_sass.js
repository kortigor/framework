const gulp = require('gulp');
// const bs = require('browser-sync');

module.exports = function watching_sass() {
    gulp.watch('static/src/scss/*.scss', gulp.parallel('sass_common', 'sass_backend', 'sass_frontend', 'sass_publications'));
    gulp.watch('static/src/scss/frontend/**/*.scss', gulp.parallel('sass_frontend'));
    gulp.watch('static/src/scss/backend/**/*.scss', gulp.parallel('sass_backend'));
    gulp.watch('static/src/scss/common/**/*.scss', gulp.parallel('sass_common'));
    gulp.watch('static/src/scss/publications/**/*.scss', gulp.parallel('sass_publications'));
}