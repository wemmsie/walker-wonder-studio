const gulp = require('gulp');
const sass = require('gulp-sass')(require('sass'));
const postcss = require('gulp-postcss');
const tailwindcss = require('tailwindcss');
const autoprefixer = require('autoprefixer');
const babel = require('gulp-babel');
const concat = require('gulp-concat');
const terser = require('gulp-terser');
const rename = require('gulp-rename');
const fs = require('fs');

// Paths
const paths = {
  scss: {
    src: './sass/base/**/*.scss',
    dest: './src/css/',
    adminSrc: './sass/admin/*.scss',
    adminDest: './src/css/',
  },
  js: {
    src: './assets/scripts/base/**/*.js',
    dest: './src/script/',

    adminJs: './assets/scripts/admin/**/*.js',
    adminJsDest: './src/script/',
  },
  php: './**/*.php',
};

// Compile SCSS to CSS with Tailwind
function compileSass() {
  console.log('Compiling Sass...');
  return gulp
    .src(paths.scss.src)
    .pipe(sass().on('error', sass.logError))
    .pipe(postcss([tailwindcss, autoprefixer]))
    .pipe(gulp.dest(paths.scss.dest));
}

// Compile Admin SCSS to CSS
function compileAdminSass() {
  return gulp
    .src(paths.scss.adminSrc)
    .pipe(sass().on('error', sass.logError))
    .pipe(postcss([tailwindcss, autoprefixer]))
    .pipe(gulp.dest(paths.scss.adminDest));
}

// Bundle and Minify JS
function bundleJS() {
  return gulp
    .src(paths.js.src)
    .pipe(
      babel({
        presets: ['@babel/preset-env'],
      })
    )
    .pipe(concat('main.js'))
    .pipe(terser()) // Replace uglify with terser
    .pipe(rename({ suffix: '.min' }))
    .pipe(gulp.dest(paths.js.dest));
}

// Bundle and Minify Admin JS
function bundleAdminJS() {
  return gulp
    .src(paths.js.adminJs)
    .pipe(
      babel({
        presets: ['@babel/preset-env'],
      })
    )
    .pipe(concat('admin.js'))
    .pipe(terser()) // Replace uglify with terser
    .pipe(rename({ suffix: '.min' }))
    .pipe(gulp.dest(paths.js.adminJsDest));
}

// Watch for changes and trigger actions
function watchFiles() {
  gulp.watch(paths.scss.src, compileSass);
  gulp.watch(paths.scss.adminSrc, compileAdminSass);
  gulp.watch(paths.js.src, bundleJS);
  gulp.watch(paths.js.adminJs, bundleAdminJS);
  gulp.watch(paths.php, compileSass);
  gulp.watch('./assets/tailwind-colors.json', compileSass); // Watch for color changes in the JSON file
}


// Define tasks
const build = gulp.series(gulp.parallel(compileSass, compileAdminSass, bundleJS, bundleAdminJS));
const watch = gulp.series(build, watchFiles);

// Define the default task
gulp.task('default', watch);

exports.build = build;
exports.watch = watch;

