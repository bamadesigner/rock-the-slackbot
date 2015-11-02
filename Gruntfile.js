module.exports = function(grunt) {

    grunt.initConfig({
        sass: {
            totheslack: {
                options: {
                    sourcemap: 'none',
                    style: 'compressed',
                    noCache: true,
                    update: false
                },
                files: [{
                    expand: true,
                    src: '*.scss',
                    cwd: 'css',
                    dest: 'css',
                    ext: '.min.css'
                }]
            }
        },
        uglify: {
            options: {
                mangle: false,
                compress: false
            },
            totheslack: {
                files: [{
                    expand: true,
                    src: [ '**/*.js', '!*.min.js' ],
                    cwd: 'js',
                    dest: 'js',
                    ext: '.min.js'
                }]
            }
        },
        watch: {
            totheslacksass: {
                files: [ 'css/*.scss' ],
                tasks: [ 'sass:totheslack' ]
            },
            totheslackjs: {
                files: [ 'js/*.js', 'js/!*.min.js' ],
                tasks: [ 'uglify:totheslack' ]
            }
        }
    });

    // Load our dependencies
    grunt.loadNpmTasks( 'grunt-contrib-sass' );
    grunt.loadNpmTasks( 'grunt-contrib-uglify' );
    grunt.loadNpmTasks( 'grunt-contrib-watch' );
    grunt.loadNpmTasks( 'grunt-newer' );

    // Register our tasks
    grunt.registerTask( 'default', [ 'newer:sass', 'newer:uglify', 'watch' ] );

    // Register a watch function
    grunt.event.on( 'watch', function( action, filepath, target ) {
        grunt.log.writeln( target + ': ' + filepath + ' has ' + action );
    });

};