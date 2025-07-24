# 📁 File Sharing API

A lightweight, extensible file upload and sharing API built in PHP with support for direct and queued uploads, CDN integration, metadata tracking, and flexible storage backends (local, Redis, or DB).

---

## 🚀 Features

- ✅ Direct & Queued File Uploads
- ☁️ Optional CDN integration for file delivery
- 📊 File metadata: views, downloads, progress tracking
- 🧱 Strategy-based storage drivers (Local, Redis, DB, etc.)
- 🧪 PHPUnit test suite for core services
- 🧩 Extensible architecture (OOP & Dependency Injection)

---

## 📦 Installation

git clone https://github.com/Lordeagle4/file-sharing-api.git

cd file-sharing-api

composer install

## ⚙️ Configuration
Create your .env file based on the provided .env.example

## Set writable permissions for storage directories:
chmod -R 775 storage/

## 🛠️ Usage

## 🔼 Upload File

- POST /upload
- Form-data with key: file

- Optional fields:

- queue — (bool) true to enqueue

- user — (string)

- type — (string)

## 📥 Fetch Metadata
- POST /fetch
{
  "file_id": "abc123"
}

## 📊 Track Stats
- POST /addViewCount

- POST /addDownloadCount

- GET /fetchStats

- GET /upload/progress

All require file_id as parameter.

## 🧪 Testing

-Run unit tests:

./vendor/bin/phpunit
Run the full API test:


php tests/test_direct_upload.php
php tests/test_queued_upload.php

## 🧱 Architecture
- FileService: Handles upload logic

- StorageDriverInterface: Abstracts metadata backend

- CdnDriverInterface: Abstracts CDN uploads

- Router: Handles custom route dispatching

- QueueManager: Manages queued uploads

## 📁 Project Structure

├── app/
│   ├── Contracts/
│   ├── Services/
│   ├── Drivers/
│   └── Routes/
├── public/
├── storage/
├── tests/
├── .env.example
├── composer.json
└── index.php

## 🧩 Customization

🔄 Add new storage backends by implementing StorageDriverInterface

☁️ Integrate your CDN provider by extending CdnDriverInterface

⚙️ Extend FileService to modify behavior

📝 License
MIT License © 2025 Lordeagle


