module.exports = function(grunt) {

    grunt.initConfig({
        sass: {
            options: {
                sourcemap: 'none',
                noCache: true,
                update: false
            },
            rts: {
                options: {
                    style: 'expanded',
                },
                files: [{
                    expand: true,
                    src: '*.scss',
                    cwd: 'css',
                    dest: 'css',
                    ext: '.css'
                }]
            },
            rtsmin: {
                options: {
                    style: 'compressed',
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
            rts: {
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
            rtssass: {
                files: [ 'css/*.scss' ],
                tasks: [ 'sass:rts', 'sass:rtsmin' ]
            },
            rtsjs: {
                files: [ 'js/*.js', 'js/!*.min.js' ],
                tasks: [ 'uglify:rts' ]
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