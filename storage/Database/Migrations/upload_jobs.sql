CREATE TABLE upload_jobs (
    id VARCHAR(64) PRIMARY KEY,
    temp_path TEXT NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'queued',
    progress TINYINT UNSIGNED DEFAULT 0, -- percentage 0 to 100
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE INDEX idx_upload_jobs_status ON upload_jobs (status);
