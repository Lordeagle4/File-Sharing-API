<?php

namespace App\Core;

class Response
{
    /**
     * Sends a JSON response with the given data and status code.
     *
     * @param array $data
     * @param int $status
     * @return void
     */
    public static function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    /**
     * Sends a plain text response.
     *
     * @param string $text
     * @param int $status
     * @return void
     */
    public static function text(string $text, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: text/plain');
        echo $text;
    }

    /**
     * Sends a file download response.
     *
     * @param string $filePath
     * @param string|null $downloadName
     * @return void
     */
    public static function download(string $filePath, ?string $downloadName = null): void
    {
        if (!file_exists($filePath)) {
            self::json(['error' => 'File not found'], 404);
            return;
        }

        $downloadName = $downloadName ?? basename($filePath);
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header("Content-Disposition: attachment; filename=\"$downloadName\"");
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }
}
