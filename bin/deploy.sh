#!/usr/bin/env bash

set -e

if [[ "false" != "$TRAVIS_PULL_REQUEST" ]]; then
  echo "Not deploying pull requests."
  exit
fi

if [[ "develop" != "$TRAVIS_BRANCH" ]]; then
  echo "Not on the 'develop' branch."
  exit
fi

cd master

git add -A
git commit -m "[ci skip] master branch update from travis $TRAVIS_COMMIT"
git push --quiet "https://${GH_TOKEN}@github.com/${TRAVIS_REPO_SLUG}.git" master 2> /dev/null
