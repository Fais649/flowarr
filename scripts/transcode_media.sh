#!/bin/bash
# Transcode a video to HEVC in a Matroska container, preferring GPU encoders.
#
# Usage: transcode_media.sh <file> [replace_original] [mode] [video_filter]
#   replace_original  true|false  replace the source with <name>.mkv instead of writing <name>_hevc.mkv
#   mode              auto|nvidia|amd|software  encoder selection (auto detects available hardware)
#   video_filter      optional ffmpeg -vf chain (e.g. HDR -> SDR tonemapping)
#
# All audio, subtitle and attachment streams are kept. Output is written to a
# temporary file first and only moved into place after ffmpeg succeeds.
set -euo pipefail

FILE_PATH="$1"
REPLACE_ORIGINAL="${2:-false}"
MODE="${3:-auto}"
VIDEO_FILTER="${4:-}"
MAX_BITRATE="${5:-0}"
FFPROBE_BIN="${FFPROBE_BIN:-ffprobe}"

FFMPEG_BIN="${FFMPEG_BIN:-ffmpeg}"
VAAPI_DEVICE="${VAAPI_DEVICE:-/dev/dri/renderD128}"

BASE_PATH="${FILE_PATH%.*}"
EXTENSION="${FILE_PATH##*.}"
TEMP_OUTPUT="${BASE_PATH}.tmp.mkv"

if [ "$REPLACE_ORIGINAL" = "true" ]; then
    FINAL_OUTPUT="${BASE_PATH}.mkv"
else
    FINAL_OUTPUT="${BASE_PATH}_hevc.mkv"
fi

if [ "$FINAL_OUTPUT" != "$FILE_PATH" ] && [ -e "$FINAL_OUTPUT" ]; then
    echo "Refusing to overwrite existing file: $FINAL_OUTPUT" >&2
    exit 1
fi

SOURCE_FINGERPRINT=$(stat -c "%s:%Y:%i" "$FILE_PATH")

trap 'rm -f "$TEMP_OUTPUT" "$TEMP_OUTPUT.source.json" "$TEMP_OUTPUT.output.json"' EXIT
trap 'exit 143' TERM INT

has_encoder() {
    "$FFMPEG_BIN" -hide_banner -encoders 2>/dev/null | grep -w "$1" >/dev/null
}

detect_gpu() {
    case "$MODE" in
        nvidia) echo "nvidia"; return ;;
        amd) echo "amd"; return ;;
        software) echo "none"; return ;;
        auto) ;;
        *) echo "Unknown mode '$MODE', using auto detection" >&2 ;;
    esac

    if { command -v nvidia-smi >/dev/null 2>&1 && nvidia-smi -L >/dev/null 2>&1; } ||
        [ -e /dev/nvidiactl ] || [ -e /dev/nvidia0 ]; then
        echo "nvidia"
    elif [ -e "$VAAPI_DEVICE" ]; then
        echo "amd"
    else
        echo "none"
    fi
}

GPU="$(detect_gpu)"
HW_ARGS=()
ENCODE_ARGS=()
FILTER="$VIDEO_FILTER"

case "$GPU" in
    nvidia)
        if has_encoder hevc_nvenc; then
            ENCODE_ARGS=(-c:v hevc_nvenc -preset p4 -rc vbr -cq 28 -b:v 0)
        fi
        ;;
    amd)
        if has_encoder hevc_vaapi && [ -e "$VAAPI_DEVICE" ]; then
            HW_ARGS=(-vaapi_device "$VAAPI_DEVICE")
            ENCODE_ARGS=(-c:v hevc_vaapi -qp 28)
            # Frames are decoded in software, so upload them to the GPU before encoding.
            FILTER="${VIDEO_FILTER:+$VIDEO_FILTER,}format=nv12,hwupload"
        fi
        ;;
esac

SOFTWARE_ARGS=(-c:v libx265 -preset medium -crf 28)
RATE_ARGS=()
if [ "$MAX_BITRATE" -gt 0 ]; then
    AUDIO_BITRATE=$("$FFPROBE_BIN" -v error -show_streams -of json "$FILE_PATH" | php -r '
        $data = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
        $total = 0;
        foreach ($data["streams"] as $stream) {
            if ($stream["codec_type"] !== "audio") { continue; }
            $rate = $stream["bit_rate"] ?? $stream["tags"]["BPS"] ?? $stream["tags"]["BPS-eng"] ?? null;
            if (!is_numeric($rate) || $rate <= 0) { fwrite(STDERR, "Unknown audio bitrate; refusing capped encode\n"); exit(1); }
            $total += (int) $rate;
        }
        echo $total;
    ')
    VIDEO_MAX=$((MAX_BITRATE - AUDIO_BITRATE - 500000))
    if [ "$VIDEO_MAX" -lt 1000000 ]; then
        echo "Audio tracks leave insufficient video bitrate budget" >&2
        exit 1
    fi
    VIDEO_TARGET=$((VIDEO_MAX * 3 / 4))
    RATE_ARGS=(-b:v "$VIDEO_TARGET" -maxrate "$VIDEO_MAX" -bufsize "$((VIDEO_MAX * 2))")
    if [ "$GPU" = "amd" ] && [ ${#ENCODE_ARGS[@]} -gt 0 ]; then
        ENCODE_ARGS=(-c:v hevc_vaapi -rc_mode VBR)
    elif [ "$GPU" = "nvidia" ] && [ ${#ENCODE_ARGS[@]} -gt 0 ]; then
        ENCODE_ARGS=(-c:v hevc_nvenc -preset p4 -rc vbr)
    fi
fi

if [ ${#ENCODE_ARGS[@]} -eq 0 ]; then
    GPU="none"
    HW_ARGS=()
    ENCODE_ARGS=("${SOFTWARE_ARGS[@]}")
    FILTER="$VIDEO_FILTER"
fi

# MP4/MOV text subtitles (mov_text) cannot be stored in Matroska as-is.
SUBTITLE_CODEC="copy"
case "${EXTENSION,,}" in
    mp4|m4v|mov) SUBTITLE_CODEC="srt" ;;
esac

# encode <with_streams:true|false> <filter> <hw args...> -- <encoder args...>
encode() {
    local with_streams="$1" filter="$2"
    shift 2

    local hw=()
    while [ "$#" -gt 0 ] && [ "$1" != "--" ]; do hw+=("$1"); shift; done
    shift

    local stream_args=(-map 0:v:0 -map '0:a?')
    if [ "$with_streams" = "true" ]; then
        stream_args+=(-map '0:s?' -map '0:t?' -c:s "$SUBTITLE_CODEC" -c:t copy)
    fi

    local filter_args=()
    if [ -n "$filter" ]; then
        filter_args=(-vf "$filter")
    fi

    rm -f "$TEMP_OUTPUT"
    "$FFMPEG_BIN" -hide_banner -nostdin -y \
        ${hw[@]+"${hw[@]}"} \
        -i "$FILE_PATH" \
        "${stream_args[@]}" \
        ${filter_args[@]+"${filter_args[@]}"} \
        "$@" \
        ${RATE_ARGS[@]+"${RATE_ARGS[@]}"} \
        -map_metadata 0 -map_chapters 0 \
        -c:a copy \
        -max_muxing_queue_size 4096 \
        "$TEMP_OUTPUT"
}

echo "Transcoding $FILE_PATH -> $FINAL_OUTPUT using ${ENCODE_ARGS[1]} (GPU: $GPU)"

if ! encode true "$FILTER" ${HW_ARGS[@]+"${HW_ARGS[@]}"} -- "${ENCODE_ARGS[@]}"; then
    if [ "$GPU" != "none" ]; then
        echo "Encoding with ${ENCODE_ARGS[1]} failed, retrying with libx265" >&2
    fi

    if [ "$GPU" = "none" ] || ! encode true "$VIDEO_FILTER" -- "${SOFTWARE_ARGS[@]}"; then
        if [ "$MAX_BITRATE" -gt 0 ]; then
            echo "Capped encode failed; refusing to drop tracks or filters" >&2
            exit 1
        fi
        echo "Encoding with all streams failed, retrying without subtitles and attachments" >&2

        if ! encode false "$VIDEO_FILTER" -- "${SOFTWARE_ARGS[@]}"; then
            if [ -z "$VIDEO_FILTER" ]; then
                exit 1
            fi

            echo "Encoding with video filter '$VIDEO_FILTER' failed, retrying without it" >&2
            encode false "" -- "${SOFTWARE_ARGS[@]}"
        fi
    fi
fi

if [ ! -s "$TEMP_OUTPUT" ]; then
    echo "ffmpeg produced no output" >&2
    exit 1
fi

# Validate duration, track counts and total bitrate before committing the output.
"$FFPROBE_BIN" -v error -show_format -show_streams -of json "$FILE_PATH" > "$TEMP_OUTPUT.source.json"
"$FFPROBE_BIN" -v error -show_format -show_streams -of json "$TEMP_OUTPUT" > "$TEMP_OUTPUT.output.json"
php "$(dirname "$0")/validate_transcode.php" "$TEMP_OUTPUT.source.json" "$TEMP_OUTPUT.output.json" "$MAX_BITRATE"
rm -f "$TEMP_OUTPUT.source.json" "$TEMP_OUTPUT.output.json"
if [ "$REPLACE_ORIGINAL" = "true" ]; then
    "$FFMPEG_BIN" -v error -xerror -nostdin -i "$TEMP_OUTPUT" -map 0:v -map '0:a?' -f null -
fi
if [ "$(stat -c "%s:%Y:%i" "$FILE_PATH")" != "$SOURCE_FINGERPRINT" ]; then
    echo "Source changed during encoding; refusing replacement" >&2
    exit 1
fi
mv "$TEMP_OUTPUT" "$FINAL_OUTPUT"

if [ "$REPLACE_ORIGINAL" = "true" ] && [ "$FINAL_OUTPUT" != "$FILE_PATH" ]; then
    rm -f "$FILE_PATH"
fi

echo "Wrote $FINAL_OUTPUT"
