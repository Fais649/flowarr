#!/bin/bash
# Convert a text subtitle file (ASS/SSA/WebVTT) to SRT.
#
# Usage: convert_subtitle.sh <file> [replace_original]
#   replace_original  true|false  delete the source subtitle after a successful conversion
set -euo pipefail

FILE_PATH="$1"
REPLACE_ORIGINAL="${2:-false}"
FFMPEG_BIN="${FFMPEG_BIN:-ffmpeg}"

TARGET_FILENAME="${FILE_PATH%.*}.srt"
TEMP_OUTPUT="${FILE_PATH%.*}.tmp.srt"

trap 'rm -f "$TEMP_OUTPUT"' EXIT
trap 'exit 143' TERM INT

if [ -e "$TARGET_FILENAME" ]; then
    echo "Refusing to overwrite existing subtitle: $TARGET_FILENAME" >&2
    exit 1
fi

"$FFMPEG_BIN" -hide_banner -nostdin -y -i "$FILE_PATH" -c:s srt "$TEMP_OUTPUT"

if [ ! -s "$TEMP_OUTPUT" ]; then
    echo "ffmpeg produced an empty subtitle file" >&2
    exit 1
fi

mv "$TEMP_OUTPUT" "$TARGET_FILENAME"

if [ "$REPLACE_ORIGINAL" = "true" ]; then
    rm -f "$FILE_PATH"
fi

echo "Wrote $TARGET_FILENAME"
