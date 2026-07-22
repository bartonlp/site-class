#!/bin/bash
echo "=== WORKING FILES ==="
git status --short

echo "=== LOCAL COMMIT ==="
git log -1 --oneline

echo "=== GITHUB COMMIT ==="
git log -1 --oneline origin/main

some new
