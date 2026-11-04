<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['codigo', 'nome', 'ativo'])]
class Pais extends Model
{
    protected $table = 'paises';

    public function registroDownloads(): HasMany
    {
        return $this->hasMany(RegistroDownload::class);
    }

    public function isBrasil(): bool
    {
        return $this->codigo === 'BR';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
        ];
    }
}
