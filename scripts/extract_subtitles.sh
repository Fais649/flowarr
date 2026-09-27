#!/bin/bash
# Extract text-based subtitle streams from a video into SRT sidecar files.
#
# Usage: extract_subtitles.sh <file> <target_video_path> <strip_embedded> <codec>...
#   target_video_path  sidecars are named after this path, e.g. <target>.en.srt, so they
#                      match the file name the video will have after transcoding
#   strip_embedded     true|false  remove the extracted streams from the source afterwards
#   codec              subtitle codecs (as reported by ffprobe) that should be extracted
set -euo pipefail

FILE_PATH="$1"
TARGET_VIDEO_PATH="${2:-$FILE_PATH}"
STRIP_EMBEDDED="${3:-false}"
shift 3
SUPPORTED_CODECS=("$@")

FFPROBE_BIN="${FFPROBE_BIN:-ffprobe}"
FFMPEG_BIN="${FFMPEG_BIN:-ffmpeg}"

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CONFIG_FILE="${CONFIG_FILE:-$SCRIPT_DIR/../config/languages.php}"

declare -A LANG_MAP
while IFS=',' read -r three_code two_code; do
    LANG_MAP["$three_code"]="$two_code"
done < <(php -r 'foreach (require $argv[1] as $k => $v) { echo "$k,$v\n"; }' "$CONFIG_FILE")

TARGET_BASENAME="${TARGET_VIDEO_PATH%.*}"
EXTENSION="${FILE_PATH##*.}"
TEMP_FILES=()
trap 'rm -f ${TEMP_FILES[@]+"${TEMP_FILES[@]}"}' EXIT
trap 'exit 143' TERM INT

is_supported() {
    local codec="$1" supported
    for supported in "${SUPPORTED_CODECS[@]}"; do
        [ "$codec" = "$supported" ] && return 0
    done
    return 1
}

declare -A USED_NAMES
EXTRACTED=()
FAILED=0

while IFS=',' read -r index codec forced language; do
    [ -z "$index" ] && continue
    is_supported "$codec" || continue

    code="${language:-und}"
    name="${TARGET_BASENAME}.${LANG_MAP[$code]:-$code}"
    [ "$forced" = "1" ] && name="${name}.forced"
    # Two tracks in the same language must not overwrite each other.
    [ -n "${USED_NAMES[$name]:-}" ] && name="${name}.${index}"
    USED_NAMES[$name]=1

    target="${name}.srt"
    temp_output="${FILE_PATH%.*}.${index}.tmp.srt"
    TEMP_FILES+=("$temp_output")

    echo "Extracting stream $index ($codec, ${code}) -> $target"
    if "$FFMPEG_BIN" -hide_banner -nostdin -y -i "$FILE_PATH" -map "0:$index" -c:s srt "$temp_output" &&
        mv "$temp_output" "$target"; then
        EXTRACTED+=("$index")
    else
        echo "Failed to extract subtitle stream $index" >&2
        FAILED=1
    fi
done < <("$FFPROBE_BIN" -v error -select_streams s \
    -show_entries stream=index,codec_name:stream_disposition=forced:stream_tags=language \
    -of csv=p=0 "$FILE_PATH")

if [ "$FAILED" -ne 0 ]; then
    exit 1
fi

if [ ${#EXTRACTED[@]} -eq 0 ]; then
    echo "No text-based subtitle streams found"
    exit 0
fi

if [ "$STRIP_EMBEDDED" = "true" ]; then
    stripped="${FILE_PATH%.*}.tmp.${EXTENSION}"
    TEMP_FILES+=("$stripped")
    map_args=(-map 0)
    for index in "${EXTRACTED[@]}"; do
        map_args+=(-map "-0:$index")
    done

    echo "Removing ${#EXTRACTED[@]} extracted subtitle stream(s) from $FILE_PATH"
    "$FFMPEG_BIN" -hide_banner -nostdin -y -i "$FILE_PATH" "${map_args[@]}" -c copy "$stripped"
    mv "$stripped" "$FILE_PATH"
fi

echo "Extracted ${#EXTRACTED[@]} subtitle stream(s)"
