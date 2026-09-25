<?php

namespace App\Services\Arquivos;

use App\Auditing\AuditContext;
use App\Auditing\AuditEventCollector;
use App\Auditing\AuditTransaction;
use App\Models\Arquivo;
use App\Models\ItemAcervo;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Throwable;

final readonly class SubstituirArquivoOriginal
{
    public function __construct(
        private AuditTransaction $auditTransaction,
        private GerenciarArquivoOriginal $arquivos,
    ) {}

    public function execute(
        User $actor,
        ItemAcervo $item,
        UploadedFile $file,
        AuditContext $context,
    ): Arquivo {
        $context->assertActor($actor);
        $journal = $this->arquivos->journal();

        try {
            $original = $this->auditTransaction->run(
                $context,
                function (AuditEventCollector $audit) use ($actor, $item, $file, $journal): Arquivo {
                    $item = ItemAcervo::query()->lockForUpdate()->findOrFail($item->getKey());
                    Gate::forUser($actor)->authorize('update', $item);

                    return $this->arquivos->replace($actor, $item, $file, $audit, $journal);
                },
            );
        } catch (Throwable $exception) {
            $journal->rollbackCreated();

            throw $exception;
        }

        $journal->cleanupObsolete();

        return $original;
    }
}
