#!/usr/bin/bash
./mk-html.sh
git add .
git commit
git log --all --graph -p --decorate > ~/www/bartonlp.com/gitlog
git log --all --graph -p --decorate -2 > ./limited-gitlog
echo "Run 'git push' when ready".