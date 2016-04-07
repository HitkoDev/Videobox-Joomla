var gulp = require('gulp');
var typescript = require('gulp-typescript');
var uglify = require('gulp-uglify');
var rename = require('gulp-rename');
var compass = require('gulp-compass');
var cssnano = require('gulp-cssnano');
var zip = require('gulp-zip');
var merge = require('merge2');
var template = require('gulp-template');
var folders = require('gulp-recursive-folder');
var fs = require('fs');

var package = JSON.parse(fs.readFileSync('./package.json'));

var manifestData = {
    joomlaVersion: '3.0',
    license: 'GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html',
    author: 'HitkoDev',
    copyright: 'Copyright (C) 2016 HtikoDev',
    mail: 'development@hitko.si',
    url: package['homepage'],
    version: package['version']
};

gulp.task('default', function() {

});

gulp.task('build', [
    'lib',
    'videobox',
    'youtube',
    'vimeo',
    'soundcloud',
    'twitch'
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
            .pipe(gulp.dest('./build')),

        // put index.html inside the folders
        folders({
            base: './build',
            exclude: [
                'definitions',
                'language'
            ]
        }, function(folder) {
            return gulp.src('./src/index.html')
                .pipe(gulp.dest('./build/' + folder.pathTarget));
        })(),

        // put data into the manifest files
        folders({
            base: './build',
            exclude: [
                'definitions'
            ]
        }, function(folder) {
            return gulp.src('./build/' + folder.pathTarget + '/' + folder.name + '.xml')
                .pipe(template(manifestData))
                .pipe(gulp.dest('./build/' + folder.pathTarget));
        })()

    ]);

});

gulp.task('install', [
    'build'
], function() {

    return merge([

        // install library
        gulp.src('./build/libraries/videobox/**')
            .pipe(gulp.dest('../libraries/videobox')),


        // install system plugin
        gulp.src('./build/plugins/videobox/language/**')
            .pipe(gulp.dest('../administrator/language')),

        gulp.src(['./build/plugins/videobox/**', '!./build/plugins/videobox/language/**'])
            .pipe(gulp.dest('../plugins/system/videobox')),


        // install YouTube plugin
        gulp.src('./build/plugins/youtube/**')
            .pipe(gulp.dest('../plugins/videobox/youtube')),


        // install Vimeo plugin
        gulp.src('./build/plugins/vimeo/**')
            .pipe(gulp.dest('../plugins/videobox/vimeo')),


        // install SoundCLoud plugin
        gulp.src('./build/plugins/soundcloud/**')
            .pipe(gulp.dest('../plugins/videobox/soundcloud')),


        // install Twitch plugin
        gulp.src('./build/plugins/twitch/**')
            .pipe(gulp.dest('../plugins/videobox/twitch'))

    ]);

});

gulp.task('pack-parts', [
    'build'
], function() {

    return merge([

        // pack library
        gulp.src('./build/libraries/videobox/**')
            .pipe(zip('lib_videobox.zip'))
            .pipe(gulp.dest('./dist/packages')),

        // pack system plugin
        gulp.src('./build/plugins/videobox/**')
            .pipe(zip('plg_system_videobox.zip'))
            .pipe(gulp.dest('./dist/packages')),


        // pack YouTube plugin
        gulp.src('./build/plugins/youtube/**')
            .pipe(zip('plg_videobox_youtube.zip'))
            .pipe(gulp.dest('./dist/packages')),


        // pack Vimeo plugin
        gulp.src('./build/plugins/vimeo/**')
            .pipe(zip('plg_videobox_vimeo.zip'))
            .pipe(gulp.dest('./dist/packages')),


        // pack SoundCloud plugin
        gulp.src('./build/plugins/soundcloud/**')
            .pipe(zip('plg_videobox_soundcloud.zip'))
            .pipe(gulp.dest('./dist/packages')),


        // pack Twitch plugin
        gulp.src('./build/plugins/twitch/**')
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

    return gulp.src('./dist/packages/**')
        .pipe(zip('pkg_videobox-' + manifestData.version.replace(/\s+/, '_') + '.zip'))
        .pipe(gulp.dest('./dist'));

});

gulp.task('lib', function() {
    var tsResult = gulp.src('./src/libraries/videobox/**/*.ts')
        .pipe(typescript({
            declaration: true,
            noExternalResolve: true,
            target: 'ES5',
            sourcemap: true
        }));

    return merge([

        tsResult.dts.pipe(gulp.dest('./build/definitions')),
        tsResult.js.pipe(gulp.dest('./build/libraries/videobox')),

        gulp.src('./src/libraries/videobox/sass/*.scss')
            .pipe(compass({
                css: 'src/libraries/videobox/css',
                sass: 'src/libraries/videobox/sass'
            }))
            .pipe(gulp.dest('./build/libraries/videobox/css')),

        gulp.src([
            './src/libraries/videobox/**/*.php',
            './src/libraries/videobox/**/*.xml'
        ])
            .pipe(gulp.dest('./build/libraries/videobox')),

        gulp.src(['./node_modules/videobox/dist/*.css'])
            .pipe(gulp.dest('./build/libraries/videobox/css')),

        gulp.src([
            './node_modules/videobox/dist/*.js',
            './node_modules/web-animations-js/web-animations.min.js'
        ])
            .pipe(gulp.dest('./build/libraries/videobox/js')),

        gulp.src([
            './node_modules/video.js/dist/*.js',
            './node_modules/video.js/dist/*.swf'
        ])
            .pipe(gulp.dest('./build/libraries/videobox/video-js'))

    ]);

});

gulp.task('videobox', function() {

    return gulp.src('./src/plugins/videobox/**')
        .pipe(gulp.dest('./build/plugins/videobox'));

});

gulp.task('youtube', function() {

    return gulp.src('./src/plugins/youtube/**')
        .pipe(gulp.dest('./build/plugins/youtube'));
});

gulp.task('vimeo', function() {

    return gulp.src('./src/plugins/vimeo/**')
        .pipe(gulp.dest('./build/plugins/vimeo'));
});

gulp.task('soundcloud', function() {

    return gulp.src('./src/plugins/soundcloud/**')
        .pipe(gulp.dest('./build/plugins/soundcloud'));
});

gulp.task('twitch', function() {

    return gulp.src('./src/plugins/twitch/**')
        .pipe(gulp.dest('./build/plugins/twitch'));
});