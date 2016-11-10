var gulp = require('gulp');
var minify = require('gulp-minify');
var sass = require('gulp-sass');
var watch = require('gulp-watch');

gulp.task('sass', function() {
	gulp.src('css/admin-tools.scss')
		.pipe(sass({outputStyle:'compressed'}))
		.pipe(gulp.dest('css'));
});

gulp.task('compress', function() {
  gulp.src('js/admin-tools.js')
	  .pipe(minify())
	  .pipe(gulp.dest('js'))
});

gulp.task('default', ['sass','compress'], function() {
	gulp.watch(['css/admin-tools.scss'],['sass']);
	gulp.watch(['js/admin-tools.js'],['compress']);
});