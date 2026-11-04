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

    public function label(): string
    {
        return match ($this) {
            self::User => 'Usuário',
            self::ItemAcervo => 'Item do acervo',
            self::Arquivo => 'Arquivo',
            self::Categoria => 'Categoria',
            self::Assunto => 'Assunto',
            self::PalavraChave => 'Palavra-chave',
            self::Pessoa => 'Pessoa',
            self::Autor => 'Autor',
            self::Colecao => 'Coleção',
            self::ConjuntoContextual => 'Conjunto contextual',
        };
    }
}
