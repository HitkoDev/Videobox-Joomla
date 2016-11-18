var gulp = require('gulp');
var typescript = require('gulp-typescript');
var uglify = require('gulp-uglify');
var rename = require('gulp-rename');
var cssnano = require('gulp-cssnano');
var zip = require('gulp-zip');
var merge = require('merge2');
var template = require('gulp-template');
var folders = require('gulp-recursive-folder');
var fs = require('fs');
var sourcemaps = require('gulp-sourcemaps');
var bourbon = require('bourbon');
var sass = require('gulp-sass');
var ftp = require('vinyl-ftp')

var package = require('./package.json');
var xmlescape = require('xml-escape');

const config = require('./config.json')
let ftpConnection = null

var manifestData = {
    joomlaVersion: '3.0',
    license: 'GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html',
    author: 'HitkoDev',
    copyright: 'Copyright (C) 2016 HtikoDev',
    mail: 'development@hitko.si',
    url: package['homepage'],
    version: package['version']
};

gulp.task('default', function () {

});

gulp.task('deploy', [
    'build'
], () => {
    if (!ftpConnection)
        ftpConnection = ftp.create({
            host: config.ftp.host,
            user: config.ftp.user,
            password: config.ftp.pass,
            port: config.ftp.port,
            secure: true,
            secureOptions: {
                rejectUnauthorized: false
            }
        })

    return merge([

        // install library
        gulp.src('./build/libraries/videobox/**', { buffer: false })
            .pipe(ftpConnection.newer(config.ftp.dir + '/libraries/videobox'))
            .pipe(ftpConnection.dest(config.ftp.dir + '/libraries/videobox')),


        // install system plugin
        gulp.src('./build/plugins/videobox/language/**', { buffer: false })
            .pipe(ftpConnection.newer(config.ftp.dir + '/administrator/language'))
            .pipe(ftpConnection.dest(config.ftp.dir + '/administrator/language')),

        gulp.src(['./build/plugins/videobox/**', '!./build/plugins/videobox/language/**'], { buffer: false })
            .pipe(ftpConnection.newer(config.ftp.dir + '/plugins/system/videobox'))
            .pipe(ftpConnection.dest(config.ftp.dir + '/plugins/system/videobox')),


        // install YouTube plugin
        gulp.src('./build/plugins/youtube/**', { buffer: false })
            .pipe(ftpConnection.newer(config.ftp.dir + '/plugins/videobox/youtube'))
            .pipe(ftpConnection.dest(config.ftp.dir + '/plugins/videobox/youtube')),


        // install Vimeo plugin
        gulp.src('./build/plugins/vimeo/**', { buffer: false })
            .pipe(ftpConnection.newer(config.ftp.dir + '/plugins/videobox/vimeo'))
            .pipe(ftpConnection.dest(config.ftp.dir + '/plugins/videobox/vimeo')),


        // install SoundCLoud plugin
        gulp.src('./build/plugins/soundcloud/**', { buffer: false })
            .pipe(ftpConnection.newer(config.ftp.dir + '/plugins/videobox/soundcloud'))
            .pipe(ftpConnection.dest(config.ftp.dir + '/plugins/videobox/soundcloud')),


        // install Twitch plugin
        gulp.src('./build/plugins/twitch/**', { buffer: false })
            .pipe(ftpConnection.newer(config.ftp.dir + '/plugins/videobox/twitch'))
            .pipe(ftpConnection.dest(config.ftp.dir + '/plugins/videobox/twitch')),


        // install HTML5 plugin
        gulp.src('./build/plugins/html5/**', { buffer: false })
            .pipe(ftpConnection.newer(config.ftp.dir + '/plugins/videobox/html5'))
            .pipe(ftpConnection.dest(config.ftp.dir + '/plugins/videobox/html5'))

    ]);
})

gulp.task('build', [
    'lib',
    'videobox',
    'youtube',
    'vimeo',
    'soundcloud',
    'twitch',
    'html5'
], function () {

    let streams = merge([

        // compress JS and CSS
        gulp.src(['./build/**/*.js', '!./build/**/*.min.js', '!./build/**/*.bundle.js'])
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
        }, function (folder) {
            return gulp.src('./src/index.html')
                .pipe(gulp.dest('./build/' + folder.pathTarget));
        })()

    ]);

    let tplDir = './src/templates'
    fs.readdir(tplDir, (err, files) => {
        files.forEach(file => {
            if (file.endsWith('.html')) {
                let fn = file.substr(0, file.length - 5)
                file = tplDir + '/' + file
                let content = fs.readFileSync(file, {
                    encoding: 'utf-8'
                })
                manifestData[fn] = xmlescape(content).replace(/[\n\r]+/igm, '&#10;').replace(/[\t]+/igm, '&#9;')
            }
        });
    })

    return merge([
        streams,

        // put data into the manifest files
        /*    folders({
                base: './build',
                exclude: [
                    'definitions'
                ]
            }, function (folder) {
                return gulp.src('./build/' + folder.pathTarget + '/' + folder.name + '.xml')
                    .pipe(template(manifestData))
                    .pipe(gulp.dest('./build/' + folder.pathTarget));
            })(),*/
        gulp.src('./build/**/*.xml')
            .pipe(template(manifestData))
            .pipe(gulp.dest('./build'))
    ])

});

gulp.task('install', [
    'build'
], function () {

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
            .pipe(gulp.dest('../plugins/videobox/twitch')),


        // install HTML5 plugin
        gulp.src('./build/plugins/html5/**')
            .pipe(gulp.dest('../plugins/videobox/html5'))

    ]);

});

gulp.task('pack-parts', [
    'build'
], function () {

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


        // pack HTML5 plugin
        gulp.src('./build/plugins/html5/**')
            .pipe(zip('plg_videobox_html5.zip'))
            .pipe(gulp.dest('./dist/packages')),


        // copy package manifest
        gulp.src('./src/pkg_videobox.xml')
            .pipe(template(manifestData))
            .pipe(gulp.dest('./dist/packages')),

        gulp.src('./src/scripts.php')
            .pipe(gulp.dest('./dist/packages')),

    ]);

});

gulp.task('pack-all', [
    'pack-parts'
], function () {

    var pkg = 'pkg_videobox-' + manifestData.version.replace(/\s+/, '_') + '.zip';
    manifestData['package'] = pkg;

    return merge([
        gulp.src('./dist/packages/**')
            .pipe(zip(pkg))
            .pipe(gulp.dest('./dist')),


        // copy updates file
        gulp.src('./src/updates.xml')
            .pipe(template(manifestData))
            .pipe(rename('pkg_videobox.xml'))
            .pipe(gulp.dest('./dist')),

    ]);

});

gulp.task('lib', function () {
    return merge([

        gulp.src([
            './src/libraries/videobox/**/*.php',
            './src/libraries/videobox/**/*.xml',
            './src/libraries/videobox/**/*.sh'
        ])
            .pipe(gulp.dest('./build/libraries/videobox')),

        gulp.src(['./node_modules/videobox/dist/*.min.css', './node_modules/videobox/dist/*.css.map'])
            .pipe(gulp.dest('./build/libraries/videobox/css')),

        gulp.src([
            './node_modules/videobox/dist/videobox.bundle.js',
            './node_modules/videobox/dist/videobox.bundle.map',
        ])
            .pipe(gulp.dest('./build/libraries/videobox/js')),

        gulp.src('./node_modules/video.js/dist/video.min.js')
            .pipe(gulp.dest('./build/libraries/videobox/video-js'))

    ]);

});

gulp.task('videobox', function () {

    return gulp.src('./src/plugins/videobox/**')
        .pipe(gulp.dest('./build/plugins/videobox'));

});

gulp.task('youtube', function () {

    return gulp.src('./src/plugins/youtube/**')
        .pipe(gulp.dest('./build/plugins/youtube'));
});

gulp.task('vimeo', function () {

    return gulp.src('./src/plugins/vimeo/**')
        .pipe(gulp.dest('./build/plugins/vimeo'));
});

gulp.task('soundcloud', function () {

    return gulp.src('./src/plugins/soundcloud/**')
        .pipe(gulp.dest('./build/plugins/soundcloud'));
});

gulp.task('twitch', function () {

    return gulp.src('./src/plugins/twitch/**')
        .pipe(gulp.dest('./build/plugins/twitch'));
});

gulp.task('html5', function () {

    return gulp.src('./src/plugins/html5/**')
        .pipe(gulp.dest('./build/plugins/html5'));
});