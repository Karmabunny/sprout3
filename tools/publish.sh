#!/usr/bin/env bash
set -e
cd "$(dirname $0)/.."

LAST="$( git tag | sort -V | tail -n 1 )"
BRANCH=$( git rev-parse --abbrev-ref HEAD )
UNPUSHED=$( git rev-list --count origin/master..master )

if [[ $BRANCH != "master" ]]; then
	echo "You must be on the master branch to publish"
	echo "Aborting publish"
	exit 1
fi

if [ $UNPUSHED -gt 0 ]; then
	echo "You have $UNPUSHED commit(s) on master which have not been pushed to origin"
	echo "Aborting publish"
	exit 1
fi

if [[ -z $1 ]]; then
	echo "Usage: $0 <version> <message>"
	echo
	echo "Last version: $LAST"
	exit 1
fi

if ! [[ $1 =~ ^v[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
	echo "Version must be in the format 'v1.2.3'"
	echo "Aborting publish"
	exit 1
fi

if [ $1 == $LAST ]; then
	echo "Version $1 has already been published"
	echo "Aborting publish"
	exit 1
fi

if ! echo -en "$LAST\n$1\n" | sort -C -V; then
	echo "Version must be greater than the last tag ($LAST)"
	echo "Aborting publish"
	exit 1
fi

echo "Publishing version: $1"
git tag "$1" -m "$2"
git push origin "$1"

echo "Done"
