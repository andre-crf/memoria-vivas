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
    'storage_disks' => [
        'local' => 'local',
    ],
    'uploads' => [
        'original' => [
            'max_kb' => (int) env('ACERVO_ORIGINAL_UPLOAD_MAX_KB', 51200),
            'mime_types' => array_values(array_filter(array_map('trim', explode(',', $originalUploadMimeTypes)))),
            'extensions' => array_values(array_filter(array_map('trim', explode(',', $originalUploadExtensions)))),
        ],
    ],
    // Formato e qualidade das versões derivadas (o original é sempre preservado como foi enviado).
    // Formatos aceitos: webp, jpeg.
    'optimized_output' => [
        'format' => env('ACERVO_DERIVADOS_FORMATO', 'webp'),
        'quality' => (int) env('ACERVO_DERIVADOS_QUALIDADE', 80),
    ],
    // Motor de processamento: auto (Imagick quando disponível, senão GD), imagick ou gd.
    // Perfil sRGB usado para converter originais com perfil de cor embutido; sem ele, a conversão é aproximada.
    'image_processing' => [
        'engine' => env('ACERVO_IMAGEM_MOTOR', 'auto'),
        // Instalado pelo pacote icc-profiles-free no Dockerfile.
        'srgb_profile' => env('ACERVO_PERFIL_SRGB', '/usr/share/color/icc/sRGB.icc'),
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
