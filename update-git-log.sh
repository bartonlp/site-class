#!/usr/bin/bash
# Should be run after every commit

git log --all --graph -p --decorate > ~/www/bartonlp.com/gitlog
git log --all --graph -p --decorate -2 > ./limited-gitlog