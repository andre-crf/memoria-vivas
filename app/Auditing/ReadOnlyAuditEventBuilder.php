<?php

namespace App\Auditing;

use Illuminate\Database\Eloquent\Builder;
use LogicException;

final class ReadOnlyAuditEventBuilder extends Builder
{
    public function update(array $values)
    {
        $this->denyMutation();
    }

    public function upsert(array $values, $uniqueBy, $update = null)
    {
        $this->denyMutation();
    }

    public function delete()
    {
        $this->denyMutation();
    }

    public function increment($column, $amount = 1, array $extra = [])
    {
        $this->denyMutation();
    }

    public function decrement($column, $amount = 1, array $extra = [])
    {
        $this->denyMutation();
    }

    public function insert(array $values): never
    {
        $this->denyMutation();
    }

    public function insertOrIgnore(array $values): never
    {
        $this->denyMutation();
    }

    public function insertGetId(array $values, $sequence = null): never
    {
        $this->denyMutation();
    }

    private function denyMutation(): never
    {
        throw new LogicException('Eventos de auditoria somente podem ser gravados pelo AuditRecorder.');
    }
}
