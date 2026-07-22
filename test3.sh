#!/bin/bash

branch=$(git branch --show-current)
remote="origin/$branch"

show_files()
{
    while IFS=$'\t' read -r status file; do
        [ -z "$file" ] && continue

        case "$status" in
            M)  printf "  modified:   %s\n" "$file" ;;
            A)  printf "  new file:   %s\n" "$file" ;;
            D)  printf "  deleted:    %s\n" "$file" ;;
            R*) printf "  renamed:    %s\n" "$file" ;;
            C*) printf "  copied:     %s\n" "$file" ;;
            *)  printf "  %-11s %s\n" "$status:" "$file" ;;
        esac
    done
}

echo
echo "WORKING"
{
    git diff --name-status
    git ls-files --others --exclude-standard |
        while IFS= read -r file; do
            printf 'A\t%s\n' "$file"
        done
} | show_files

echo
echo "COMMIT"
git diff --cached --name-status | show_files

echo
echo "LOCAL"
if git show-ref --verify --quiet "refs/remotes/$remote"; then
    git diff --name-status "$remote..HEAD" | show_files
else
    echo "  Remote information is unavailable"
fi

echo
echo "PUSH"
if git show-ref --verify --quiet "refs/remotes/$remote"; then
    git diff-tree \
        --root \
        --no-commit-id \
        --name-status \
        -r "$remote" |
        show_files
else
    echo "  Remote information is unavailable"
fi