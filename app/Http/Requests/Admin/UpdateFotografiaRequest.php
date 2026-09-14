<?php

namespace App\Http\Requests\Admin;

use App\Models\ItemAcervo;

class UpdateFotografiaRequest extends StoreFotografiaRequest
{
    public function authorize(): bool
    {
        $fotografia = $this->route('fotografia');

        return $fotografia instanceof ItemAcervo
            && ($this->user()?->can('update', $fotografia) ?? false);
    }
}
