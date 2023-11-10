#!/bin/bash
# !!! You need pandoc: sudo apt-get install pandoc

# Make .html files from .md files
echo "index";
pagetitle="index";
# --include-in-header=addscript. The file addscript just has two
# <script src=...></script> statements. This lets me add the two needed
# javascript file: 1) jQuery, 2) javascripts/main.js
/usr/bin/pandoc -f gfm -t html5 -Vpagetitle="$pagetitle" --css=stylesheets/styles.css --include-in-header=addscript --standalone index.md -o index.html
#
echo "dbTables";
pagetitle="dbTables";
/usr/bin/pandoc -f gfm -t html5 -Vpagetitle="$pagetitle" --css=pandoc.css --standalone dbTables.md -o dbTables.html
echo "siteclass";
pagetitle="SiteClass Methods";
/usr/bin/pandoc -f gfm -t html5 -Vpagetitle="$pagetitle" --css=pandoc.css --standalone siteclass.md -o siteclass.html
pagetitle="Additional Files";
echo "files";
/usr/bin/pandoc -f gfm -t html5 -Vpagetitle="$pagetitle" --css=pandoc.css --standalone files.md -o files.html
pagetitle="Analysis";
echo "analysis";
/usr/bin/pandoc -f gfm -t html5 -Vpagetitle="$pagetitle" --css=pandoc.css --standalone analysis.md -o analysis.html
pagetitle="examplereadme";
echo "examplereadme";
/usr/bin/pandoc -f gfm -t html5 -Vpagetitle="$pagetitle" --css=pandoc.css --standalone examplereadme.md -o examplereadme.html

