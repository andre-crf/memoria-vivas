<?php

namespace Tests\Feature;

use App\Models\Contribuicao;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicContributionTest extends TestCase
{
    use RefreshDatabase;

    private function validData(): array
    {
        return [
            'nome' => 'Maria Silva',
            'email' => 'maria@example.com',
            'titulo' => 'Praça antiga',
            'historia' => 'Fotografias de uma festa na praça.',
            'local' => 'Centro',
            'periodo' => 'Década de 1980',
            'autorizacao' => '1',
            'fotos' => [UploadedFile::fake()->image('praca.jpg')],
        ];
    }

    public function test_public_form_saves_private_photos_without_publishing_them(): void
    {
        Storage::fake('local');

        $this->get(route('public.colaborar'))->assertOk()->assertSee('Enviar fotografias');

        $this->post(route('public.colaborar.store'), $this->validData())
            ->assertRedirect(route('public.colaborar'))
            ->assertSessionHas('contribuicao_enviada', true);

        $contribuicao = Contribuicao::with('arquivos')->firstOrFail();
        $this->assertSame('Maria Silva', $contribuicao->nome);
        $this->assertCount(1, $contribuicao->arquivos);
        Storage::disk('local')->assertExists($contribuicao->arquivos->first()->caminho);
        $this->assertDatabaseCount('item_acervos', 0);

        $this->get(route('public.colaborar'))->assertOk()->assertSee('Fotografias recebidas.');
    }

    public function test_invalid_or_unauthorized_submissions_are_rejected(): void
    {
        Storage::fake('local');

        $this->post(route('public.colaborar.store'), [
            ...$this->validData(),
            'autorizacao' => null,
            'fotos' => [UploadedFile::fake()->create('arquivo.pdf', 20, 'application/pdf')],
        ])->assertSessionHasErrors(['autorizacao', 'fotos.0']);

        $this->assertDatabaseCount('contribuicoes', 0);
        $this->assertSame([], Storage::disk('local')->allFiles('contribuicoes'));
    }

    public function test_form_rejects_more_than_five_photographs(): void
    {
        Storage::fake('local');

        $fotos = array_map(
            fn (int $number) => UploadedFile::fake()->image("foto-{$number}.jpg"),
            range(1, 6),
        );

        $this->post(route('public.colaborar.store'), [
            ...$this->validData(),
            'fotos' => $fotos,
        ])->assertSessionHasErrors('fotos');

        $this->assertDatabaseCount('contribuicoes', 0);
    }

    public function test_admin_can_review_and_download_but_guests_cannot(): void
    {
        Storage::fake('local');
        $this->post(route('public.colaborar.store'), $this->validData())->assertRedirect();

        $contribuicao = Contribuicao::with('arquivos')->firstOrFail();
        $arquivo = $contribuicao->arquivos->first();
        $download = route('admin.contribuicoes.arquivos.download', [$contribuicao, $arquivo]);

        $this->get(route('admin.contribuicoes.index'))->assertRedirect(route('login'));
        $this->get($download)->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create(['role' => 'operador', 'status' => 'ativo']))
            ->get(route('admin.contribuicoes.index'))->assertOk()->assertSee('Praça antiga');
        $this->get(route('admin.contribuicoes.show', $contribuicao))
            ->assertOk()->assertSee('maria@example.com')->assertSee('praca.jpg');
        $this->get($download)->assertOk()->assertDownload('praca.jpg');

        $outra = Contribuicao::create([
            'nome' => 'Outra pessoa',
            'email' => 'outra@example.com',
            'titulo' => 'Outro envio',
            'autorizacao' => true,
        ]);
        $this->get(route('admin.contribuicoes.arquivos.download', [$outra, $arquivo]))->assertNotFound();
    }

    public function test_admin_displays_contribution_time_in_the_display_timezone(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-20 13:50:00', 'UTC'));

        $contribuicao = Contribuicao::create([
            'nome' => 'Andre',
            'email' => 'andre@example.com',
            'titulo' => 'Fotografia de teste',
            'autorizacao' => true,
        ]);

        $this->actingAs(User::factory()->create(['role' => 'operador', 'status' => 'ativo']));

        foreach ([route('admin.contribuicoes.index'), route('admin.contribuicoes.show', $contribuicao)] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('20/10/2026 10:50')
                ->assertSee('datetime="2026-10-20T13:50:00+00:00"', false)
                ->assertDontSee('20/10/2026 13:50');
        }
    }
}
