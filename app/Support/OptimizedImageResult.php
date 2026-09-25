<?php

namespace App\Support;

use App\Models\Arquivo;

final readonly class OptimizedImageResult
{
    /**
     * @param  list<Arquivo>  $generated
     * @param  list<string>  $failedVersions
     */
    public function __construct(
        public bool $applicable,
        public array $generated = [],
        public array $failedVersions = [],
    ) {}
}
