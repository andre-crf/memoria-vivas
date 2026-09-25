<?php

namespace App\Auditing\Enums;

enum AuditEntity: string
{
    case User = 'user';
    case ItemAcervo = 'item_acervo';
    case Arquivo = 'arquivo';
    case Categoria = 'categoria';
    case Assunto = 'assunto';
    case PalavraChave = 'palavra_chave';
    case Pessoa = 'pessoa';
    case Autor = 'autor';
    case Colecao = 'colecao';
    case ConjuntoContextual = 'conjunto_contextual';
}
