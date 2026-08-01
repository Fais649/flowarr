#!/bin/bash
set -euo pipefail

FILE_PATH="$1"
REPLACE_ORIGINAL="${2:-false}"
MODE="${3:-auto}"

TEMP_OUTPUT="${FILE_PATH%.*}.tmp.mkv"
FFMPEG_BIN="${FFMPEG_BIN:-ffmpeg}"
VAAPI_DEVICE="${VAAPI_DEVICE:-/dev/dri/renderD128}"

has_encoder() {
    "$FFMPEG_BIN" -hide_banner -encoders 2>/dev/null | grep -qw "$1"
}

detect_gpu() {
    case "$MODE" in
        auto|nvidia|amd|software) ;;
        *) echo "Unknown mode '$MODE', defaulting to auto" >&2 ;;
    esac

    if [ "$MODE" = "nvidia" ] || { [ "$MODE" = "auto" ] &&
        { { command -v nvidia-smi >/dev/null 2>&1 && nvidia-smi -L >/dev/null 2>&1; } ||
          [ -e /dev/nvidiactl ] || [ -e /dev/nvidia0 ]; }; }; then
        echo "nvidia"
    elif [ "$MODE" = "amd" ] || { [ "$MODE" = "auto" ] && [ -e "$VAAPI_DEVICE" ]; }; then
        echo "amd"
    elif [ "$MODE" = "software" ]; then
        echo "none"
    else
        echo "none"
    fi
}

GPU="$(detect_gpu)"
HW_ARGS=()
ENCODE_ARGS=()

case "$GPU" in
    nvidia)
        if has_encoder hevc_nvenc; then
            ENCODE_ARGS=(-c:v hevc_nvenc -preset p4 -cq 28)
        else
            GPU="none"
        fi
        ;;
    amd)
        if has_encoder hevc_vaapi && [ -e "$VAAPI_DEVICE" ]; then
            HW_ARGS=(-vaapi_device "$VAAPI_DEVICE")
            ENCODE_ARGS=(-c:v hevc_vaapi -global_quality 28)
        else
            GPU="none"
        fi
        ;;
esac

if [ "$GPU" = "none" ] || [ ${#ENCODE_ARGS[@]} -eq 0 ]; then
    ENCODE_ARGS=(-c:v libx265 -preset medium -crf 28)
fi

echo "Transcoding $FILE_PATH using ${ENCODE_ARGS[1]} (GPU: $GPU)"

if ! "$FFMPEG_BIN" -y "${HW_ARGS[@]}" -i "$FILE_PATH" "${ENCODE_ARGS[@]}" -c:a copy "$TEMP_OUTPUT"; then
    echo "Hardware encode with ${ENCODE_ARGS[1]} failed, falling back to libx265" >&2
    rm -f "$TEMP_OUTPUT"
    "$FFMPEG_BIN" -y -i "$FILE_PATH" -c:v libx265 -preset medium -crf 28 -c:a copy "$TEMP_OUTPUT"
fi

if [ "$REPLACE_ORIGINAL" = "true" ]; then
    mv "$TEMP_OUTPUT" "$FILE_PATH"
else
    mv "$TEMP_OUTPUT" "${FILE_PATH%.*}_hevc.mkv"
fi
