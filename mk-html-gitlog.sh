#!/bin/bash
# !!! You need pandoc: sudo apt-get install pandoc


# Make .html files from .md files
pagetitle="Main Readme file";
/usr/bin/pandoc -Vpagetitle="$pagetitle" --css=pandoc.css --standalone -f gfm -t html5 README.md -o README.html -o index.html

# Create 'git log >~/www/bartonlp.com/gitlog
git log --all --graph -p --decorate > ~/www/bartonlp.com/gitlog

# now move into the docs directory and do those html files

cd docs

# Make .html files from .md files
echo "index";
pagetitle="index";
/usr/bin/pandoc -f gfm -t html5 -Vpagetitle="$pagetitle" --css=./stylesheets/styles.css --standalone index.md -o index.html
echo "dbTables";
pagetitle="dbTables";
/usr/bin/pandoc -f gfm -t html5 -Vpagetitle="$pagetitle" --css=pandoc.css --standalone dbTables.md -o dbTables.html
echo "siteclass";
pagetitle="SiteClass Methods";
/usr/bin/pandoc -f gfm -t html5 -Vpagetitle="$pagetitle" --css=pandoc.css --standalone siteclass.md -o siteclass.html
pagetitle="Additional Files";
echo "files";
/usr/bin/pandoc -f gfm -t html5 -Vpagetitle="$pagetitle" --css=pandoc.css --standalone files.md -o files.html
#pagetitle="Analysis";
#echo "analysis";
#/usr/bin/pandoc -f gfm -t html5 -Vpagetitle="$pagetitle" --css=pandoc.css --standalone analysis.md -o analysis.html
#pagetitle="examplereadme";
#echo "examplereadme";
#/usr/bin/pandoc -f gfm -t html5 -Vpagetitle="$pagetitle" --css=pandoc.css --standalone examplereadme.md -o examplereadme.html

pagetitle="README";
echo "README";
/usr/bin/pandoc -f gfm -t html5 -Vpagetitle="$pagetitle" --css=pandoc.css --standalone ../examples/README.md -o ../examples/README.html
