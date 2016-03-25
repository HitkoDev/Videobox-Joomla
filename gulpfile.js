var gulp = require('gulp');
var typescript = require('gulp-typescript');
var uglify = require('gulp-uglify');
var rename = require('gulp-rename');
var compass = require('gulp-compass');
var cssnano = require('gulp-cssnano');
var zip = require('gulp-zip');
var merge = require('merge2');
var template = require('gulp-template');

var manifestData = {
    joomlaVersion: '3.0',
    license: 'GNU General Public License version 3 or later',
    author: 'HitkoDev',
    copyright: 'Copyright (C) 2016 HtikoDev',
    mail: 'development@hitko.si',
    url: 'https://hitko.eu/videobox/',
    version: '5.0.0 beta-1'
};

gulp.task('default', function() {

});

gulp.task('build', [
    'lib',
    'plg',
    'plg_yt',
    'plg_vi',
    'plg_sc',
    'plg_tw'
], function() {

    return merge([

        // compress JS and CSS
        gulp.src(['./build/**/*.js', '!./build/**/*.min.js'])
            .pipe(uglify({
                preserveComments: 'license'
            }))
            .pipe(rename({
                suffix: '.min'
            }))
            .pipe(gulp.dest('./build')),

        gulp.src(['./build/**/*.css', '!./build/**/*.min.css'])
            .pipe(cssnano())
            .pipe(rename({
                suffix: '.min'
            }))
            .pipe(gulp.dest('./build'))

    ]);

});

gulp.task('install', [
    'build'
], function() {

    return merge([

        // install library
        gulp.src('./build/lib/**')
            .pipe(gulp.dest('../libraries/videobox')),


        // install system plugin
        gulp.src('./build/plg/language/**')
            .pipe(gulp.dest('../administrator/language')),

        gulp.src(['./build/plg/**', '!./build/plg/language/**'])
            .pipe(gulp.dest('../plugins/system/videobox')),


        // install YouTube plugin
        gulp.src('./build/plg_yt/**')
            .pipe(gulp.dest('../plugins/videobox/youtube')),


        // install Vimeo plugin
        gulp.src('./build/plg_vi/**')
            .pipe(gulp.dest('../plugins/videobox/vimeo')),


        // install SoundCLoud plugin
        gulp.src('./build/plg_sc/**')
            .pipe(gulp.dest('../plugins/videobox/soundcloud')),


        // install Twitch plugin
        gulp.src('./build/plg_tw/**')
            .pipe(gulp.dest('../plugins/videobox/twitch'))

    ]);

});

gulp.task('pack-parts', [
    'build'
], function() {

    return merge([

        // pack library
        gulp.src('./build/lib/**')
            .pipe(zip('lib_videobox.zip'))
            .pipe(gulp.dest('./dist/packages')),

        // pack system plugin
        gulp.src('./build/plg/**')
            .pipe(zip('plg_system_videobox.zip'))
            .pipe(gulp.dest('./dist/packages')),


        // pack YouTube plugin
        gulp.src('./build/plg_yt/**')
            .pipe(zip('plg_videobox_youtube.zip'))
            .pipe(gulp.dest('./dist/packages')),


        // pack Vimeo plugin
        gulp.src('./build/plg_vi/**')
            .pipe(zip('plg_videobox_vimeo.zip'))
            .pipe(gulp.dest('./dist/packages')),


        // pack SoundCloud plugin
        gulp.src('./build/plg_sc/**')
            .pipe(zip('plg_videobox_soundcloud.zip'))
            .pipe(gulp.dest('./dist/packages')),


        // pack Twitch plugin
        gulp.src('./build/plg_tw/**')
            .pipe(zip('plg_videobox_twitch.zip'))
            .pipe(gulp.dest('./dist/packages')),
        
        
        // copy package manifest
        gulp.src('./src/pkg_videobox.xml')
            .pipe(template(manifestData))
            .pipe(gulp.dest('./dist/packages')),

    ]);

});

gulp.task('pack-all', [
    'pack-parts'
], function() {

    return merge([

        gulp.src('./dist/packages/**')
            .pipe(zip('pkg_videobox-' + manifestData.version.replace(/\s+/, '_') + '.zip'))
            .pipe(gulp.dest('./dist')),

    ]);

});

gulp.task('lib', function() {
    var tsResult = gulp.src('./src/lib/**/*.ts')
        .pipe(typescript({
            declaration: true,
            noExternalResolve: true,
            target: 'ES5',
            sourcemap: true
        }));

    return merge([

        tsResult.dts.pipe(gulp.dest('./build/definitions')),
        tsResult.js.pipe(gulp.dest('./build/lib')),

        gulp.src('./src/lib/sass/*.scss')
            .pipe(compass({
                css: 'src/lib/css',
                sass: 'src/lib/sass'
            }))
            .pipe(gulp.dest('./build/lib/css')),

        gulp.src('./src/lib/**/*.php')
            .pipe(gulp.dest('./build/lib')),

        gulp.src('./src/lib/**/*.xml')
            .pipe(template(manifestData))
            .pipe(gulp.dest('./build/lib')),

        gulp.src(['./node_modules/videobox/dist/*.css'])
            .pipe(gulp.dest('./build/lib/css')),

        gulp.src(['./node_modules/videobox/dist/*.js'])
            .pipe(gulp.dest('./build/lib/js')),

        gulp.src(['./node_modules/web-animations-js/web-animations.min.js'])
            .pipe(gulp.dest('./build/lib/js')),

        gulp.src(['./node_modules/videobox/dist/video-js/**'])
            .pipe(gulp.dest('./build/lib/video-js'))

    ]);

});

gulp.task('plg', function() {

    return merge([

        gulp.src(['./src/plg/**', '!./src/plg/**/*.xml'])
            .pipe(gulp.dest('./build/plg')),

        gulp.src('./src/plg/**/*.xml')
            .pipe(template(manifestData))
            .pipe(gulp.dest('./build/plg')),

    ]);

});

gulp.task('plg_yt', function() {

    return merge([

        gulp.src(['./src/plg_yt/**', '!./src/plg_yt/**/*.xml'])
            .pipe(gulp.dest('./build/plg_yt')),

        gulp.src('./src/plg_yt/**/*.xml')
            .pipe(template(manifestData))
            .pipe(gulp.dest('./build/plg_yt')),

    ]);
});

gulp.task('plg_vi', function() {

    return merge([

        gulp.src(['./src/plg_vi/**', '!./src/plg_vi/**/*.xml'])
            .pipe(gulp.dest('./build/plg_vi')),

        gulp.src('./src/plg_vi/**/*.xml')
            .pipe(template(manifestData))
            .pipe(gulp.dest('./build/plg_vi')),

    ]);
});

gulp.task('plg_sc', function() {

    return merge([

        gulp.src(['./src/plg_sc/**', '!./src/plg_sc/**/*.xml'])
            .pipe(gulp.dest('./build/plg_sc')),

        gulp.src('./src/plg_sc/**/*.xml')
            .pipe(template(manifestData))
            .pipe(gulp.dest('./build/plg_sc')),

    ]);
});

gulp.task('plg_tw', function() {

    return merge([

        gulp.src(['./src/plg_tw/**', '!./src/plg_tw/**/*.xml'])
            .pipe(gulp.dest('./build/plg_tw')),

        gulp.src('./src/plg_tw/**/*.xml')
            .pipe(template(manifestData))
            .pipe(gulp.dest('./build/plg_tw')),

    ]);
});