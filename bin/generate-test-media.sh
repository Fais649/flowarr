#!/usr/bin/env bash
set -euo pipefail

# Regenerates the gitignored test fixtures under test-media/ with ffmpeg.
# test-media/ is not tracked by git; after a fresh clone run this script to
# recreate the sample media files that compose.yaml mounts into the dev
# container at /media (see the './test-media:/media' volume entry).
#
# Files are generated to match the characteristics of the original fixtures:
#   - video.mkv:      h264, 320x240, 25 fps, ~1s
#   - long-test.mkv:  h264, 1920x1080, 60 fps, ~58s

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
MEDIA_DIR="$DIR/test-media"

mkdir -p "$MEDIA_DIR"

ffmpeg -y -f lavfi -i testsrc2=duration=1:size=320x240:rate=25 \
    -c:v libx264 -pix_fmt yuv420p "$MEDIA_DIR/video.mkv"

ffmpeg -y -f lavfi -i testsrc2=duration=58:size=1920x1080:rate=60 \
    -c:v libx264 -pix_fmt yuv420p "$MEDIA_DIR/long-test.mkv"

echo "Generated test media: $MEDIA_DIR/video.mkv, $MEDIA_DIR/long-test.mkv"
