<?php

$originalUploadMimeTypes = env(
    'ACERVO_ORIGINAL_UPLOAD_MIME_TYPES',
    'image/jpeg,image/png,image/webp,image/tiff,application/pdf',
);

$originalUploadExtensions = env(
    'ACERVO_ORIGINAL_UPLOAD_EXTENSIONS',
    'jpg,jpeg,png,webp,tif,tiff,pdf',
);

return [
    'uploads' => [
        'original' => [
            'max_kb' => (int) env('ACERVO_ORIGINAL_UPLOAD_MAX_KB', 51200),
            'mime_types' => array_values(array_filter(array_map('trim', explode(',', $originalUploadMimeTypes)))),
            'extensions' => array_values(array_filter(array_map('trim', explode(',', $originalUploadExtensions)))),
        ],
    ],
    'optimized_versions' => [
        'thumbnail' => [
            'max_dimension' => (int) env('ACERVO_THUMBNAIL_MAX_DIMENSION', 320),
        ],
        'medium' => [
            'max_dimension' => (int) env('ACERVO_MEDIUM_MAX_DIMENSION', 1024),
        ],
        'large' => [
            'max_dimension' => (int) env('ACERVO_LARGE_MAX_DIMENSION', 1920),
        ],
    ],
];
