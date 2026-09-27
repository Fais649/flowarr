export function getTooltipText(
    jobType: string | null | undefined,
    setting: 'enabled' | 'concurrency' | 'replace_original',
): string {
    const tooltips = {
        transcode_media: {
            enabled: `Enable or disable video transcoding. When disabled, no videos will be transcoded.`,
            concurrency: `Number of videos to transcode simultaneously. Higher values process more videos in parallel but use more system resources.`,
            replace_original: `Replace original video files with transcoded versions. When enabled, the original is replaced by <name>.mkv after a successful transcode; otherwise <name>_hevc.mkv is written next to it.`,
        },
        extract_subs: {
            enabled: `Enable or disable subtitle extraction. When disabled, no subtitles will be extracted from videos.`,
            concurrency: `Number of subtitle extractions to run simultaneously. Higher values process more videos in parallel but use more system resources.`,
            replace_original: `Remove the extracted subtitle tracks from the video after extraction. Image-based subtitles (PGS, VobSub) are kept.`,
        },
        convert_sub: {
            enabled: `Enable or disable subtitle format conversion. When disabled, no subtitle files will be converted to SRT format.`,
            concurrency: `Number of subtitle conversions to run simultaneously. Higher values process more files in parallel but use more system resources.`,
            replace_original: `Delete original subtitle files after conversion to SRT. When enabled, only the converted SRT file is kept.`,
        },
    };

    const defaultTooltips = {
        enabled: `Enable or disable this worker. When disabled, no jobs of this type will be processed.`,
        concurrency: `Number of jobs to process simultaneously. Higher values process more items in parallel but use more system resources.`,
        replace_original: `Replace original files with processed versions. When enabled, original files are deleted after successful processing.`,
    };

    if (jobType && tooltips[jobType as keyof typeof tooltips]) {
        return tooltips[jobType as keyof typeof tooltips][setting];
    }

    return defaultTooltips[setting];
}
