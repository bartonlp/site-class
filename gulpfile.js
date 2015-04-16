// Gulpfile
// Take the README.md and make a README.html
//

var gulp = require('gulp'),
exec = require('gulp-exec'),
header = require('gulp-header'),
footer = require('gulp-footer'),
rename = require('gulp-rename');

var readmeHeader = "<!DOCTYPE html>\n<html>\n<head>\n<style>\npre {\n"+
                   "background-color: #DBDBDB;\n"+
                   "overflow: auto;\n}\n</style>\n</head>\n<body>\n";

gulp.task('readmehtml', function() {
  return gulp.src("README.md")
      .pipe(exec('pandoc -f markdown_github <%= file.path %> -t html',
                 {pipeStdout: true}))
      .pipe(header(readmeHeader))
      .pipe(footer("\n</body>\n</html>\n"))
      .pipe(rename('README.html'))
      .pipe(gulp.dest('./'))
});

gulp.task('exampleshtml', function() {
  return gulp.src("examples/EXAMPLES.md")
      .pipe(exec('pandoc -f markdown_github <%= file.path %> -t html',
                 {pipeStdout: true}))
      .pipe(header(readmeHeader))
      .pipe(footer("\n</body>\n</html>\n"))
      .pipe(rename('EXAMPLES.html'))
      .pipe(gulp.dest('./examples/'))
});

gulp.task('default', ['readmehtml', 'exampleshtml']);

// Watch for changes to README.md

gulp.task('watch', function() {
  gulp.watch('README.md', ['readmehtml']);
  gulp.watch('examples/EXAMPLES.md', ['exampleshtml']);  
});
