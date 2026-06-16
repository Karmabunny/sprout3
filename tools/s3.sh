#!/usr/bin/env bash
set -e

docker run --rm \
    --name s3 \
    -p 9000:9000 \
    -p 9080:9080 \
    -e BUCKETS=sprout3-test \
    ghcr.io/karmabunny/s3:latest
