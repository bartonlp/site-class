#!/bin/bash

git fetch origin --quiet

echo "Working version:"
if git diff --quiet && git diff --cached --quiet; then
    echo "  Same as committed version"
else
    echo "  Has uncommitted changes"
fi

echo
echo "Committed version:"
git log -1 --oneline HEAD

echo
echo "Pushed version:"
git log -1 --oneline origin/main

echo
if [ "$(git rev-parse HEAD)" = "$(git rev-parse origin/main)" ]; then
    echo "Committed and pushed versions are the same"
else
    echo "Committed and pushed versions are different"
    git status --short --branch
fi
