<?php

namespace App\Auditing;

use Closure;
use Illuminate\Support\Facades\DB;

final readonly class AuditTransaction
{
    public function __construct(
        private AuditRecorder $recorder,
    ) {}

    /**
     * @template TResult
     *
     * @param  Closure(AuditEventCollector): TResult  $operation
     * @return TResult
     */
    public function run(AuditContext $context, Closure $operation): mixed
    {
        return DB::transaction(function () use ($context, $operation): mixed {
            $collector = new AuditEventCollector;
            $result = $operation($collector);

            foreach ($collector->events() as $event) {
                $this->recorder->record($context, $event);
            }

            return $result;
        });
    }
}
