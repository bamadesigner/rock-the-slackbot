var gulp = require('gulp');
var minify = require('gulp-minify');
var phpcs = require('gulp-phpcs');
var sass = require('gulp-sass');
var watch = require('gulp-watch');

var to_watch = {
	sass: ['css/admin-tools.scss'],
	js: ['js/admin-tools.js'],
	php: ['**/*.php','!vendor/**','!node_modules/**']
};

// Compile the SASS
gulp.task('sass',function() {
	gulp.src(to_watch.sass)
		.pipe(sass({outputStyle:'compressed'}))
		.pipe(gulp.dest('css'));
});

// Minify the JS
gulp.task('js',function() {
  gulp.src(to_watch.js)
	  .pipe(minify())
	  .pipe(gulp.dest('js'))
});

// Compile our assets
gulp.task('compile',['sass','js']);

// Check our PHP
gulp.task('php',function() {
	gulp.src(to_watch.php)
		.pipe(phpcs({
			bin: 'vendor/bin/phpcs',
			standard: 'WordPress-Core'
		}))
		.pipe(phpcs.reporter('log'));
});

// Watch the files
gulp.task('watch',function() {
    gulp.watch(to_watch.sass,['sass']);
	gulp.watch(to_watch.js,['js']);
	gulp.watch(to_watch.php,['php']);
});

// Our default tasks
gulp.task('default',['compile','php']);