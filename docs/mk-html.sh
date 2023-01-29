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
pagetitle="dbTables";
/usr/bin/pandoc -f gfm -t html5 -Vpagetitle="$pagetitle" -Vmath="$css" -s dbTables.md -o dbTables.html
pagetitle="SiteClass Methods";
/usr/bin/pandoc -f gfm -t html5 -Vpagetitle="$pagetitle" -Vmath="$css" -s siteclass.md -o siteclass.html
pagetitle="Additional Files";
/usr/bin/pandoc -f gfm -t html5 -Vpagetitle="$pagetitle" -Vmath="$css" -s files.md -o files.html
pagetitle="Analysis";
/usr/bin/pandoc -f gfm -t html5 -Vpagetitle="$pagetitle" -Vmath="$css" -s analysis.md -o analysis.html
pagetitle="examplereadme";
/usr/bin/pandoc -f gfm -t html5 -Vpagetitle="$pagetitle" -Vmath="$css" -s examplereadme.md -o examplereadme.html

