<?php

namespace App\Service;

class CronLog
{
    public function __construct(
        private readonly string $dirCronLog,
    ) {}

    public function create(string $name, string $description, string $message, array $context = []): string
    {
        if (!is_dir($this->dirCronLog)) {
            @mkdir($this->dirCronLog, 0775, true);
        }

        $file = rtrim($this->dirCronLog, '/').'/'.preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $name).'.log';

        $row = [
            'date'        => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            'name'        => $name,
            'description' => $description,
            'message'     => $message,
            'context'     => $context,
        ];

        file_put_contents($file, json_encode($row, JSON_UNESCAPED_UNICODE).PHP_EOL, FILE_APPEND);

        return $file;
    }
}
