var gulp = require('gulp');
var minify = require('gulp-minify');
var phpcs = require('gulp-phpcs');
var rename = require('gulp-rename');
var sass = require('gulp-sass');
var sort = require('gulp-sort');
var watch = require('gulp-watch');
var wp_pot = require('gulp-wp-pot');

// Set the source for specific files.
var src = {
	sass: ['assets/scss/admin-tools.scss'],
	js: ['assets/js/admin-tools.js'],
	php: ['**/*.php','!vendor/**','!node_modules/**']
};

// Set the destination for specific files.
var dest = {
	sass: 'assets/css',
	js: 'assets/js'
};

// Compile the SASS.
gulp.task('sass',function() {
	gulp.src(src.sass)
		.pipe(sass({outputStyle:'compressed'}))
		.pipe(rename({
			suffix: '.min'
		}))
		.pipe(gulp.dest(dest.sass));
});

// Minify the JS.
gulp.task('js',function() {
	gulp.src(src.js)
		.pipe(minify({
			mangle: false,
			ext:{
				min:'.min.js'
			}
		}))
		.pipe(gulp.dest(dest.js))
});

// Check our PHP
gulp.task('php',function() {
	gulp.src(src.php)
		.pipe(phpcs({
			bin: 'vendor/bin/phpcs',
			standard: 'WordPress-Core'
		}))
		.pipe(phpcs.reporter('log'));
});

// Watch the files.
gulp.task('watch',function() {
    gulp.watch(src.sass,['sass']);
	gulp.watch(src.js,['js']);
	gulp.watch(src.php,['php']);
});

// Create the translation file.
gulp.task('translate', function() {
	gulp.src(src.php)
		.pipe(sort())
        .pipe(wp_pot({
        	domain: 'rock-the-slackbot',
            destFile:'rock-the-slackbot.pot',
            package: 'Rock_The_Slackbot',
            bugReport: 'https://github.com/bamadesigner/rock-the-slackbot/issues',
            lastTranslator: 'Rachel Carden <bamadesigner@gmail.com>',
            team: 'Rachel Carden <bamadesigner@gmail.com>',
            headers: false
        }))
        .pipe(gulp.dest('languages/rock-the-slackbot.pot'));
});

// Compile our assets.
gulp.task('compile',['sass','js']);

// Test our files.
gulp.task('test',['php']);

// Our default tasks
gulp.task('default',['compile','test','translate']);