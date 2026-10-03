export type ExecutionStatus =
    'queued' | 'processing' | 'completed' | 'stopped' | 'paused' | 'failed';

export type Worker = {
    id: number;
    name: string;
    job_type: string | null;
    pivot?: { worker_id: number; library_id: number };
    concurrency: number;
    replace_original: boolean;
    enabled: boolean;
    created_at: string;
    updated_at: string;
    running_processes?: number | null;
    processing_count?: number;
    queued_count?: number;
};

export type Library = {
    original_handling?: string | null;
    replacement_start?: string | null;
    replacement_end?: string | null;
    id: number;
    base_path: string;
    status: string;
    scan_interval: number;
    last_scan: string | null;
    library_jobs: { id: number; job_id: string }[];
    workers: Worker[];
};

export type Execution = {
    replacement_status?: string | null;
    id: number;
    file_path: string;
    status: ExecutionStatus;
    progress: number | null;
    message: string | null;
    worker_id: number | null;
    library_job: {
        id: number;
        job_id: string;
        library?: { id: number; base_path: string };
    };
    started_at: string | null;
    heartbeat_at: string | null;
    finished_at: string | null;
    created_at: string;
};

export type ProcessingState = {
    paused: boolean;
    reasons: ('manual' | 'streams' | 'schedule')[];
    manual: boolean;
    active_streams: number;
    window: { start: string; end: string } | null;
};

export type HardwareCapabilities = {
    ffmpeg_version: string | null;
    ffprobe: boolean;
    encoders: Record<string, boolean>;
    devices: { nvidia: boolean; vaapi: boolean };
    vaapi_device: string;
    mode: string;
    selected_encoder: string | null;
    detected_at: string;
};

export const JobTypeLabels: Record<string, string> = {
    transcode_media: 'Transcode Media',
    extract_subs: 'Extract Subtitles',
    convert_sub: 'Convert Subtitles',
};

export const ACTIVE_STATUSES: ExecutionStatus[] = [
    'queued',
    'processing',
    'paused',
];

export const RETRYABLE_STATUSES: ExecutionStatus[] = ['failed', 'stopped'];
