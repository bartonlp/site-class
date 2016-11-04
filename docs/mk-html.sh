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
/usr/bin/pandoc -f markdown_github -Vpagetitle="$pagetitle" -Vmath="$css" -s dbTables.md -o dbTables.html
pagetitle="Examples";
/usr/bin/pandoc -f markdown_github -Vpagetitle="$pagetitle" -Vmath="$css" -s examples.md -o examples.html

