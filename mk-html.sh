#!/bin/bash
# !!! You need pandoc: sudo apt-get install pandoc

# Make .html files from .md files

pandoc -fmarkdown_github README.md -o README.html
pandoc -fmarkdown_github README.md -o README.pdf 
pandoc -fmarkdown_github examples/EXAMPLES.md -o examples/EXAMPLES.html
pandoc -fmarkdown_github examples/EXAMPLES.md -o examples/EXAMPLES.pdf

