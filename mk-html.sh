#!/bin/bash
# !!! You need pandoc: sudo apt-get install pandoc

# Make .html files from .md files

pandoc -fmarkdown_github README.md > README.html
pandoc -fmarkdown_github examples/EXAMPLES.md > examples/EXAMPLES.html

