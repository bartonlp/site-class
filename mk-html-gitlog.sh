#!/bin/bash
# !!! You need pandoc: sudo apt-get install pandoc

css='
<style>
div.sourceCode {
  background-color: #EEF3E2;
  border-left: 10px solid gray;
  padding-left: 5px;
}
code {
  background-color: #EEF3E2;
}
</style>
';

# Make .html files from .md files
pagetitle="Main Readme file";
/usr/bin/pandoc -Vpagetitle="$pagetitle" -Vmath="$css" -s -f gfm -t html5 README.md -o README.html

# Create 'git log >~/www/bartonlp.com/gitlog
git log > ~/www/bartonlp.com/gitlog

# now move into the docs directory and do those html files

cd docs

pagetitle="dbTables";
/usr/bin/pandoc -Vpagetitle="$pagetitle" -Vmath="$css" -s -f gfm -t html5 dbTables.md -o dbTables.html
pagetitle="SiteClass Methods";
/usr/bin/pandoc -f gfm -t html5 -Vpagetitle="$pagetitle" -Vmath="$css" -s siteclass.md -o siteclass.html
pagetitle="Additional Files";
/usr/bin/pandoc -f gfm -t html5 -Vpagetitle="$pagetitle" -Vmath="$css" -s files.md -o files.html
pagetitle="Analysis";
/usr/bin/pandoc -f gfm -t html5 -Vpagetitle="$pagetitle" -Vmath="$css" -s analysis.md -o analysis.html
pagetitle="examplereadme";
/usr/bin/pandoc -f gfm -t html5 -Vpagetitle="$pagetitle" -Vmath="$css" -s examplereadme.md -o examplereadme.html


