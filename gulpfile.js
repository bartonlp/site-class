// Gulpfile
// Take the README.md and make a README.html
//

var gulp = require('gulp'),
exec = require('gulp-exec'),
header = require('gulp-header'),
footer = require('gulp-footer'),
rename = require('gulp-rename');

var readmeHeader = "<!DOCTYPE html>\n<html>\n<head>\n<style>\ncode {\n"+
                   "background-color: #DBDBDB;}\n</style>\n</head>\n<body>\n";

gulp.task('default', function() {
  return gulp.src("README.md")
      .pipe(exec('pandoc -f markdown_github <%= file.path %> -t html',
                 {pipeStdout: true}))
      .pipe(header(readmeHeader))
      .pipe(footer("\n</body>\n</html>\n"))
      .pipe(rename('readme.html'))
      .pipe(gulp.dest('./'))
});

// Watch for changes to README.md

gulp.task('watch', function() {
  gulp.watch('README.md', ['default']);
});
