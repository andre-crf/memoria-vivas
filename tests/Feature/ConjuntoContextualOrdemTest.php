<?php

namespace Tests\Feature;

use App\Models\ConjuntoContextual;
use App\Models\ItemAcervo;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConjuntoContextualOrdemTest extends TestCase
{
    use RefreshDatabase;

    private function criarItem(string $titulo): ItemAcervo
    {
        return ItemAcervo::create([
            'titulo' => $titulo,
            'tipo_item' => 'fotografia',
            'tipo_data' => 'desconhecida',
            'status' => 'rascunho',
        ]);
    }

    public function test_ordens_distintas_e_multiplos_nulos_sao_aceitos(): void
    {
        $conjunto = ConjuntoContextual::create(['titulo' => 'Centro historico']);

        $conjunto->itensAcervo()->attach($this->criarItem('Foto A'), ['ordem' => 1]);
        $conjunto->itensAcervo()->attach($this->criarItem('Foto B'), ['ordem' => 2]);
        $conjunto->itensAcervo()->attach($this->criarItem('Foto C'), ['ordem' => null]);
        $conjunto->itensAcervo()->attach($this->criarItem('Foto D'), ['ordem' => null]);

        $this->assertCount(4, $conjunto->itensAcervo()->get());
    }

    public function test_ordem_repetida_no_mesmo_conjunto_e_rejeitada(): void
    {
        $conjunto = ConjuntoContextual::create(['titulo' => 'Centro historico']);

        $conjunto->itensAcervo()->attach($this->criarItem('Foto A'), ['ordem' => 1]);

        $this->expectException(QueryException::class);

        $conjunto->itensAcervo()->attach($this->criarItem('Foto B'), ['ordem' => 1]);
    }

    public function test_mesma_ordem_em_conjuntos_diferentes_e_aceita(): void
    {
        $primeiro = ConjuntoContextual::create(['titulo' => 'Centro historico']);
        $segundo = ConjuntoContextual::create(['titulo' => 'Zona rural']);

        $primeiro->itensAcervo()->attach($this->criarItem('Foto A'), ['ordem' => 1]);
        $segundo->itensAcervo()->attach($this->criarItem('Foto B'), ['ordem' => 1]);

        $this->assertSame(1, $primeiro->itensAcervo()->first()->pivot->ordem);
        $this->assertSame(1, $segundo->itensAcervo()->first()->pivot->ordem);
    }
}
