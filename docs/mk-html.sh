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
/usr/bin/pandoc -f markdown_github -t html5 -Vpagetitle="$pagetitle" -Vmath="$css" -s dbTables.md -o dbTables.html
pagetitle="Examples";
/usr/bin/pandoc -f markdown_github -t html5 -Vpagetitle="$pagetitle" -Vmath="$css" -s examples.md -o examples.html
pagetitle="SiteClass Methods";
/usr/bin/pandoc -f markdown_github -t html5 -Vpagetitle="$pagetitle" -Vmath="$css" -s siteclass.md -o siteclass.html
pagetitle="Additional Files";
/usr/bin/pandoc -f markdown_github -t html5 -Vpagetitle="$pagetitle" -Vmath="$css" -s files.md -o files.html
pagetitle="Analysis";
/usr/bin/pandoc -f markdown_github -t html5 -Vpagetitle="$pagetitle" -Vmath="$css" -s analysis.md -o analysis.html
pagetitle="Testing";
/usr/bin/pandoc -f markdown_github -t html5 -Vpagetitle="$pagetitle" -Vmath="$css" -s testing.md -o testing.html

