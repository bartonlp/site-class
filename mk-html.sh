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
/usr/bin/pandoc -Vpagetitle="$pagetitle" -Vmath="$css" -s -f markdown_github README.md -o README.html
pagetitle="Examples Document";
/usr/bin/pandoc -s -Vpagetitle="$pagetitle" -Vmath="$css" -o examples/EXAMPLES.html -f markdown_github examples/EXAMPLES.md
pagetitle="Test Readme file"
/usr/bin/pandoc -s -Vpagetitle="$pagetitle" -Vmath="$css" -o tests/README.html -f markdown_github tests/README.md

