export type Worker = {
    id: number;
    name: string;
    job_type: string | null;
    pivot: { worker_id: number; library_id: number };
};

export type Library = {
    id: number;
    base_path: string;
    status: string;
    scan_interval: number;
    last_scan: string | null;
    library_jobs: { id: number; job_id: string }[];
    workers: Worker[];
};

export type Execution = {
    id: number;
    file_path: string;
    status: string;
    library_job: { id: number; job_id: string };
    created_at: string;
};

