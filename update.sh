#!/usr/bin/bash
echo "TEST";

./mk-html.sh
git add .
git commit
./update-git-log.sh
