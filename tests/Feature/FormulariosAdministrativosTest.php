<?php

namespace Tests\Feature;

use App\Models\Assunto;
use App\Models\Autor;
use App\Models\Categoria;
use App\Models\PalavraChave;
use App\Models\Pessoa;
use App\Models\User;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Formulários administrativos simples migrados para os componentes do Design System (Etapa 4):
 * contrato com o backend (name, id, método, valores) e estados de validação acessíveis.
 */
class FormulariosAdministrativosTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{0: string, 1: class-string<Model>, 2: array<string, string>, 3: array<string, array{0: string, 1: array<string, string>}>, 4: array{0: string, 1: string}, 5: string}> */
    public static function catalogos(): array
    {
        return [
            'categorias' => ['categorias', Categoria::class, ['titulo' => 'Eventos', 'descricao' => 'Registros de eventos.'], [
                'titulo' => ['input', ['type' => 'text', 'required' => 'required', 'maxlength' => '255']],
                'descricao' => ['textarea', ['rows' => '5']],
            ], ['Nova categoria', 'Editar categoria'], 'Cadastrar categoria'],
            'assuntos' => ['assuntos', Assunto::class, ['titulo' => 'Arquitetura', 'descricao' => 'Prédios e praças.'], [
                'titulo' => ['input', ['type' => 'text', 'required' => 'required', 'maxlength' => '255']],
                'descricao' => ['textarea', ['rows' => '5']],
            ], ['Novo assunto', 'Editar assunto'], 'Cadastrar assunto'],
            'palavras-chave' => ['palavras-chave', PalavraChave::class, ['termo' => 'memória'], [
                'termo' => ['input', ['type' => 'text', 'required' => 'required', 'maxlength' => '255']],
            ], ['Nova palavra-chave', 'Editar palavra-chave'], 'Cadastrar palavra-chave'],
            'autores' => ['autores', Autor::class, ['nome' => 'Museu Municipal', 'tipo' => 'instituicao', 'observacao' => 'Acervo doado.'], [
                'nome' => ['input', ['type' => 'text', 'required' => 'required', 'maxlength' => '255']],
                'tipo' => ['select', ['required' => 'required']],
                'observacao' => ['textarea', ['rows' => '4']],
            ], ['Novo autor', 'Editar autor'], 'Cadastrar autor'],
            'pessoas' => ['pessoas', Pessoa::class, ['nome' => 'Maria Souza', 'observacao' => 'Moradora identificada.'], [
                'nome' => ['input', ['type' => 'text', 'required' => 'required', 'maxlength' => '255']],
                'observacao' => ['textarea', ['rows' => '5']],
            ], ['Nova pessoa', 'Editar pessoa'], 'Cadastrar pessoa'],
        ];
    }

    /**
     * @param  class-string<Model>  $modelo
     * @param  array<string, string>  $dados
     * @param  array<string, array{0: string, 1: array<string, string>}>  $campos
     * @param  array{0: string, 1: string}  $titulos
     */
    #[DataProvider('catalogos')]
    public function test_catalog_forms_keep_backend_contract(string $modulo, string $modelo, array $dados, array $campos, array $titulos, string $botaoCadastro): void
    {
        $this->actingAs($this->admin());
        $registro = $modelo::create($dados);

        // Cadastro
        $xpath = $this->dom($this->get(route("admin.{$modulo}.create"))->assertOk());
        $this->assertCabecalho($xpath, $titulos[0]);
        $form = $this->formulario($xpath, 'POST', route("admin.{$modulo}.store"));
        $this->assertSame(0, $xpath->query('.//input[@name="_method"]', $form)->length);
        $this->assertCampos($xpath, $form, $campos);
        $this->assertAcoes($xpath, $form, $botaoCadastro, route("admin.{$modulo}.index"));
        $this->assertSame(0, $xpath->query('//*[@aria-invalid]')->length);

        // Edição: os valores atuais preenchem os campos.
        $xpath = $this->dom($this->get(route("admin.{$modulo}.edit", $registro))->assertOk());
        $this->assertCabecalho($xpath, $titulos[1]);
        $form = $this->formulario($xpath, 'POST', route("admin.{$modulo}.update", $registro));
        $this->assertSame('PUT', $xpath->query('.//input[@name="_method"]', $form)->item(0)?->getAttribute('value'));
        $this->assertCampos($xpath, $form, $campos);
        $this->assertAcoes($xpath, $form, 'Salvar alterações', route("admin.{$modulo}.index"));

        foreach ($dados as $nome => $valor) {
            $this->assertSame($valor, $this->valor($this->campo($xpath, $nome)), $nome);
        }
    }

    /**
     * @param  class-string<Model>  $modelo
     * @param  array<string, string>  $dados
     * @param  array<string, array{0: string, 1: array<string, string>}>  $campos
     * @param  array{0: string, 1: string}  $titulos
     */
    #[DataProvider('catalogos')]
    public function test_catalog_forms_mark_invalid_fields_and_restore_old_values(string $modulo, string $modelo, array $dados, array $campos, array $titulos, string $botaoCadastro): void
    {
        $this->actingAs($this->admin());
        $registro = $modelo::create($dados);
        $obrigatorio = array_key_first($campos);
        // Campo obrigatório vazio; os demais com valores novos que precisam voltar preenchidos.
        $envio = [];
        foreach ($dados as $nome => $valor) {
            $envio[$nome] = match ($nome) {
                $obrigatorio => '',
                'tipo' => 'pessoa',
                default => "{$valor} (novo)",
            };
        }

        foreach ([
            'cadastro' => fn () => $this->from(route("admin.{$modulo}.create"))->followingRedirects()->post(route("admin.{$modulo}.store"), $envio),
            'edição' => fn () => $this->from(route("admin.{$modulo}.edit", $registro))->followingRedirects()->put(route("admin.{$modulo}.update", $registro), $envio),
        ] as $fluxo => $enviar) {
            $xpath = $this->dom($enviar()->assertOk());

            $this->assertCampoInvalido($xpath, $obrigatorio, $fluxo);
            $this->assertResumoDeErros($xpath, $fluxo);

            foreach (array_diff_key($envio, [$obrigatorio => true]) as $nome => $valor) {
                $campo = $this->campo($xpath, $nome);
                $this->assertSame($valor, $this->valor($campo), "{$fluxo}: valor antigo de {$nome}");
                $this->assertFalse($campo->hasAttribute('aria-invalid'), "{$fluxo}: {$nome} não deveria estar inválido");
            }
        }
    }

    public function test_duplicate_title_is_reported_next_to_the_field(): void
    {
        $this->actingAs($this->admin());
        Categoria::create(['titulo' => 'Eventos']);

        $xpath = $this->dom($this->from(route('admin.categorias.create'))->followingRedirects()
            ->post(route('admin.categorias.store'), ['titulo' => 'Eventos', 'descricao' => 'Outra descrição'])->assertOk());

        $this->assertCampoInvalido($xpath, 'titulo');
        $this->assertSame('Eventos', $this->valor($this->campo($xpath, 'titulo')));
        $this->assertSame('Outra descrição', $this->valor($this->campo($xpath, 'descricao')));
    }

    public function test_user_create_form_keeps_password_contract_and_does_not_restore_password(): void
    {
        $this->actingAs($this->admin());
        $existente = User::factory()->create(['email' => 'ja-existe@example.test']);

        $xpath = $this->dom($this->get(route('admin.usuarios.create'))->assertOk());
        $this->assertCabecalho($xpath, 'Novo usuário');
        $form = $this->formulario($xpath, 'POST', route('admin.usuarios.store'));
        $this->assertCampos($xpath, $form, [
            'nome' => ['input', ['type' => 'text', 'required' => 'required', 'maxlength' => '255']],
            'email' => ['input', ['type' => 'email', 'required' => 'required', 'maxlength' => '255']],
            'role' => ['select', ['required' => 'required']],
            'password' => ['input', ['type' => 'password', 'required' => 'required', 'minlength' => '8', 'autocomplete' => 'new-password', 'aria-describedby' => 'password-requisitos']],
            'password_confirmation' => ['input', ['type' => 'password', 'required' => 'required', 'minlength' => '8', 'autocomplete' => 'new-password']],
        ]);
        $this->assertSame('operador', $this->valor($this->campo($xpath, 'role')));
        $this->assertAcoes($xpath, $form, 'Cadastrar usuário', route('admin.usuarios.index'));

        $xpath = $this->dom($this->from(route('admin.usuarios.create'))->followingRedirects()->post(route('admin.usuarios.store'), [
            'nome' => 'Nova Pessoa',
            'email' => $existente->email,
            'role' => 'admin',
            'password' => 'Senha-Segura-1',
            'password_confirmation' => 'outra-senha',
        ])->assertOk());

        $this->assertCampoInvalido($xpath, 'email');
        $this->assertCampoInvalido($xpath, 'password', descricaoExtra: 'password-requisitos');
        $this->assertResumoDeErros($xpath);
        $this->assertSame('Nova Pessoa', $this->valor($this->campo($xpath, 'nome')));
        $this->assertSame('admin', $this->valor($this->campo($xpath, 'role')));
        $this->assertFalse($this->campo($xpath, 'password')->hasAttribute('value'));
        $this->assertFalse($this->campo($xpath, 'password_confirmation')->hasAttribute('value'));
    }

    public function test_user_edit_form_keeps_role_and_status_rules(): void
    {
        $admin = $this->admin();
        $outro = User::factory()->create(['role' => 'operador', 'status' => 'inativo']);
        $this->actingAs($admin);

        $xpath = $this->dom($this->get(route('admin.usuarios.edit', $outro))->assertOk());
        $this->assertCabecalho($xpath, 'Editar usuário');
        $form = $this->formulario($xpath, 'POST', route('admin.usuarios.update', $outro));
        $this->assertSame('PUT', $xpath->query('.//input[@name="_method"]', $form)->item(0)?->getAttribute('value'));
        $this->assertCampos($xpath, $form, [
            'nome' => ['input', ['type' => 'text', 'required' => 'required', 'maxlength' => '255']],
            'email' => ['input', ['type' => 'email', 'required' => 'required', 'maxlength' => '255']],
            'role' => ['select', ['required' => 'required']],
            'status' => ['select', ['required' => 'required']],
        ]);
        $this->assertSame('operador', $this->valor($this->campo($xpath, 'role')));
        $this->assertSame('inativo', $this->valor($this->campo($xpath, 'status')));
        $this->assertAcoes($xpath, $form, 'Salvar alterações', route('admin.usuarios.index'));

        // Na própria conta, perfil e situação são só leitura. Se a situação chegar alterada mesmo assim,
        // o serviço recusa com um erro em "status", que não tem campo aqui: ele aparece no resumo.
        $xpath = $this->dom($this->from(route('admin.usuarios.edit', $admin))->followingRedirects()->put(route('admin.usuarios.update', $admin), [
            'nome' => $admin->nome,
            'email' => $admin->email,
            'status' => 'inativo',
        ])->assertOk());
        $this->assertSame(0, $xpath->query('//*[@name="role" or @name="status"]')->length);
        $this->assertSame(['Perfil', 'Situação'], $this->textos($xpath, '//main//dl/div/dt'));
        $this->assertSame(['Administrador', 'Ativo'], $this->textos($xpath, '//main//dl/div/dd'));
        $this->assertSame(['Você não pode alterar sua própria situação.'], $this->textos($xpath, './/li', $this->resumoDeErros($xpath)));
        $this->assertSame('ativo', $admin->fresh()->status);
    }

    public function test_profile_forms_use_separate_error_bags(): void
    {
        $usuario = User::factory()->create(['role' => 'operador', 'status' => 'ativo', 'nome' => 'Ana', 'email' => 'ana@example.test']);
        $this->actingAs($usuario);

        $xpath = $this->dom($this->get(route('admin.perfil.edit'))->assertOk());
        $this->assertCabecalho($xpath, 'Meu perfil');
        $this->assertSame(['Dados pessoais', 'Segurança'], $this->textos($xpath, '//main//h2'));
        $dados = $this->formulario($xpath, 'POST', route('admin.perfil.update'));
        $senha = $this->formulario($xpath, 'POST', route('admin.perfil.password.update'));
        $this->assertCampos($xpath, $dados, [
            'nome' => ['input', ['type' => 'text', 'required' => 'required', 'maxlength' => '255', 'autocomplete' => 'name']],
            'email' => ['input', ['type' => 'email', 'required' => 'required', 'maxlength' => '255', 'autocomplete' => 'email']],
        ]);
        $this->assertCampos($xpath, $senha, [
            'current_password' => ['input', ['type' => 'password', 'required' => 'required', 'autocomplete' => 'current-password']],
            'password' => ['input', ['type' => 'password', 'required' => 'required', 'minlength' => '8', 'autocomplete' => 'new-password', 'aria-describedby' => 'password-requisitos']],
            'password_confirmation' => ['input', ['type' => 'password', 'required' => 'required', 'minlength' => '8', 'autocomplete' => 'new-password']],
        ]);
        $this->assertStringContainsString('Pelo menos 8 caracteres', $xpath->query('//*[@id="password-requisitos"]')->item(0)->textContent);
        $this->assertSame(['Salvar dados pessoais'], $this->textos($xpath, './/button[@type="submit"]', $dados));
        $this->assertSame(['Atualizar senha'], $this->textos($xpath, './/button[@type="submit"]', $senha));

        // Erro de senha: só o formulário de segurança é marcado; os requisitos continuam descritos antes da mensagem.
        $xpath = $this->dom($this->from(route('admin.perfil.edit'))->followingRedirects()->put(route('admin.perfil.password.update'), [
            'current_password' => 'errada',
            'password' => 'curta',
            'password_confirmation' => 'curta',
        ])->assertOk());
        $this->assertCampoInvalido($xpath, 'current_password');
        $this->assertCampoInvalido($xpath, 'password', descricaoExtra: 'password-requisitos');
        $this->assertFalse($this->campo($xpath, 'nome')->hasAttribute('aria-invalid'));
        $this->assertSame('A senha não foi atualizada.', trim($xpath->query('//main//*[@role="alert"]//p[1]')->item(0)->textContent));
        $this->assertSame(1, $xpath->query('//main//*[@role="alert"]')->length);

        // Erro nos dados pessoais, incluindo um campo que esta tela não oferece: ele aparece no resumo.
        $xpath = $this->dom($this->from(route('admin.perfil.edit'))->followingRedirects()->put(route('admin.perfil.update'), [
            'nome' => 'Ana Atualizada',
            'email' => 'invalido',
            'role' => 'admin',
        ])->assertOk());
        $this->assertCampoInvalido($xpath, 'email');
        $this->assertSame('Ana Atualizada', $this->valor($this->campo($xpath, 'nome')));
        $this->assertSame('invalido', $this->valor($this->campo($xpath, 'email')));
        $this->assertFalse($this->campo($xpath, 'current_password')->hasAttribute('aria-invalid'));
        $resumo = $this->resumoDeErros($xpath);
        $this->assertStringContainsString('Os dados pessoais não foram atualizados.', $resumo->textContent);
        $this->assertCount(1, $this->textos($xpath, './/li', $resumo));
        $this->assertSame('operador', $usuario->fresh()->role);
    }

    public function test_download_filters_keep_query_string_export_and_clear(): void
    {
        $this->actingAs($this->admin());
        $filtros = ['q' => 'Praça', 'de' => '2026-11-01', 'ate' => '2026-11-03'];

        $xpath = $this->dom($this->get(route('admin.downloads.index', $filtros))->assertOk());
        // O relatório mantém o cabeçalho próprio; só os filtros foram padronizados.
        $this->assertSame(['Relatório de downloads'], $this->textos($xpath, '//h1'));
        $form = $this->formulario($xpath, 'GET', route('admin.downloads.index'));
        $this->assertCampos($xpath, $form, [
            'q' => ['input', ['id' => 'download-search', 'type' => 'search', 'maxlength' => '100']],
            'de' => ['input', ['id' => 'download-from', 'type' => 'date']],
            'ate' => ['input', ['id' => 'download-to', 'type' => 'date']],
        ]);
        foreach ($filtros as $nome => $valor) {
            $this->assertSame($valor, $this->valor($this->campo($xpath, $nome)));
        }
        $this->assertSame(['Filtrar'], $this->textos($xpath, './/button[@type="submit"]', $form));
        $this->assertSame(0, $xpath->query('.//button[not(@type="submit")]', $form)->length);
        $this->assertSame(route('admin.downloads.index'), $xpath->query('.//a[normalize-space()="Limpar"]', $form)->item(0)?->getAttribute('href'));
        $this->assertSame(route('admin.downloads.export', $filtros), $xpath->query('//a[normalize-space()="Exportar CSV"]')->item(0)?->getAttribute('href'));
        $this->assertSame(0, $xpath->query('//*[@aria-invalid]')->length);

        // Período invertido: a mensagem fica no resumo dos filtros e é ligada ao campo "Até".
        $xpath = $this->dom($this->from(route('admin.downloads.index'))->followingRedirects()
            ->get(route('admin.downloads.index', ['de' => '2026-11-04', 'ate' => '2026-11-03']))->assertOk());
        $ate = $this->campo($xpath, 'ate');
        $this->assertSame('true', $ate->getAttribute('aria-invalid'));
        $this->assertSame('download-to-erro', $ate->getAttribute('aria-describedby'));
        $mensagem = $xpath->query('//main//*[@role="alert"]//li[@id="download-to-erro"]')->item(0);
        $this->assertInstanceOf(DOMElement::class, $mensagem);
        $this->assertNotSame('', trim($mensagem->textContent));
        $this->assertFalse($this->campo($xpath, 'de')->hasAttribute('aria-invalid'));
        $this->assertSame('Confira as datas e tente novamente.', trim($xpath->query('//main//*[@role="alert"]//p[1]')->item(0)->textContent));
    }

    public function test_operator_cannot_open_user_forms_or_download_report(): void
    {
        $operador = User::factory()->create(['role' => 'operador', 'status' => 'ativo']);
        $outro = User::factory()->create();
        $this->actingAs($operador);

        $this->get(route('admin.usuarios.create'))->assertForbidden();
        $this->get(route('admin.usuarios.edit', $outro))->assertForbidden();
        $this->get(route('admin.downloads.index', ['q' => 'Praça']))->assertForbidden();
        $this->get(route('admin.downloads.export'))->assertForbidden();
        // Os formulários de catálogo e o próprio perfil continuam disponíveis para operadores.
        $this->get(route('admin.categorias.create'))->assertOk();
        $this->get(route('admin.perfil.edit'))->assertOk();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'ativo']);
    }

    private function dom(TestResponse $resposta): DOMXPath
    {
        $dom = new DOMDocument;
        @$dom->loadHTML('<?xml encoding="utf-8"?>'.$resposta->getContent(), LIBXML_NOERROR);

        return new DOMXPath($dom);
    }

    private function assertCabecalho(DOMXPath $xpath, string $titulo): void
    {
        // Um único h1, no cabeçalho padrão, com fonte sem serifa (sem font-serif).
        $this->assertSame([$titulo], $this->textos($xpath, '//h1'));
        $h1 = $xpath->query('//main//header/div/h1')->item(0);
        $this->assertInstanceOf(DOMElement::class, $h1, 'h1 fora do x-admin.page-header');
        $this->assertStringNotContainsString('font-serif', $h1->getAttribute('class'));
    }

    private function formulario(DOMXPath $xpath, string $metodo, string $acao): DOMElement
    {
        $form = $xpath->query(sprintf('//form[@method="%s"][@action="%s"]', $metodo, $acao))->item(0);
        $this->assertInstanceOf(DOMElement::class, $form, "Formulário {$metodo} {$acao} não encontrado");

        if ($metodo === 'POST') {
            $this->assertSame(1, $xpath->query('.//input[@name="_token"]', $form)->length);
        }

        return $form;
    }

    /** @param  array<string, array{0: string, 1: array<string, string>}>  $campos */
    private function assertCampos(DOMXPath $xpath, DOMElement $form, array $campos): void
    {
        foreach ($campos as $nome => [$tag, $atributos]) {
            $campo = $xpath->query(sprintf('.//*[@name="%s"]', $nome), $form)->item(0);
            $this->assertInstanceOf(DOMElement::class, $campo, "Campo {$nome} não encontrado");
            $this->assertSame($tag, $campo->tagName, $nome);

            $id = $atributos['id'] ?? $nome;
            $this->assertSame($id, $campo->getAttribute('id'), $nome);
            foreach ($atributos as $atributo => $valor) {
                $this->assertSame($valor, $campo->getAttribute($atributo), "{$nome}: {$atributo}");
            }

            // Componentes do Design System: o rótulo envolve o campo e aponta para o id.
            $this->assertContains('focus:shadow-focus', preg_split('/\s+/', $campo->getAttribute('class')), "{$nome}: x-catalog.control");
            $label = $xpath->query('ancestor::label[1]', $campo)->item(0);
            $this->assertInstanceOf(DOMElement::class, $label, "{$nome}: x-catalog.label");
            $this->assertSame($id, $label->getAttribute('for'), $nome);

            // A obrigatoriedade aparece no rótulo exatamente quando o campo é obrigatório.
            $this->assertSame($campo->hasAttribute('required'), str_contains($label->textContent, 'obrigatório'), "{$nome}: indicação de obrigatório");
        }
    }

    private function assertAcoes(DOMXPath $xpath, DOMElement $form, string $enviar, string $cancelar): void
    {
        $this->assertSame([$enviar], $this->textos($xpath, './/button[@type="submit"]', $form));
        // Nenhum outro botão do formulário envia por acidente: os demais (ex.: mostrar senha) declaram type="button".
        $this->assertSame(0, $xpath->query('.//button[not(@type="submit") and not(@type="button")]', $form)->length);
        $this->assertSame($cancelar, $xpath->query('.//a[normalize-space()="Cancelar"]', $form)->item(0)?->getAttribute('href'));
    }

    private function assertCampoInvalido(DOMXPath $xpath, string $nome, string $contexto = '', ?string $descricaoExtra = null): void
    {
        $campo = $this->campo($xpath, $nome);
        $id = $campo->getAttribute('id');
        $esperado = trim(($descricaoExtra ? $descricaoExtra.' ' : '')."{$id}-erro");

        $this->assertSame('true', $campo->getAttribute('aria-invalid'), "{$contexto}: aria-invalid em {$nome}");
        $this->assertSame($esperado, $campo->getAttribute('aria-describedby'), "{$contexto}: aria-describedby em {$nome}");

        $mensagem = $xpath->query(sprintf('//*[@id="%s-erro"]', $id))->item(0);
        $this->assertInstanceOf(DOMElement::class, $mensagem, "{$contexto}: mensagem de {$nome}");
        $this->assertNotSame('', trim($mensagem->textContent));
        // Junto ao campo, mas fora do rótulo: não entra no nome acessível, só na descrição.
        $this->assertSame(0, $xpath->query('ancestor::label', $mensagem)->length);
        // O bloco do campo é o pai do rótulo ou, em x-catalog.password, o avô (o rótulo divide um wrapper com o botão de mostrar).
        $blocos = $xpath->query('ancestor::label[1]/ancestor::*[position() <= 2]', $campo);
        $proxima = false;
        foreach ($blocos as $bloco) {
            $proxima = $proxima || $mensagem->parentNode->isSameNode($bloco);
        }
        $this->assertTrue($proxima, "{$contexto}: mensagem de {$nome} longe do campo");
    }

    private function assertResumoDeErros(DOMXPath $xpath, string $contexto = ''): void
    {
        $resumo = $this->resumoDeErros($xpath);
        $this->assertSame('Verifique os dados informados.', trim($xpath->query('.//p[1]', $resumo)->item(0)->textContent), $contexto);
        // As mensagens ficam junto aos campos; o resumo não as repete.
        $this->assertSame(0, $xpath->query('.//li', $resumo)->length, $contexto);
    }

    private function resumoDeErros(DOMXPath $xpath): DOMElement
    {
        $alertas = $xpath->query('//main//*[@role="alert"]');
        $this->assertSame(1, $alertas->length, 'Esperado um único resumo de erros');

        return $alertas->item(0);
    }

    private function campo(DOMXPath $xpath, string $nome): DOMElement
    {
        $campo = $xpath->query(sprintf('//form//*[@name="%s"]', $nome))->item(0);
        $this->assertInstanceOf(DOMElement::class, $campo, "Campo {$nome} não encontrado");

        return $campo;
    }

    private function valor(DOMElement $campo): string
    {
        return match ($campo->tagName) {
            'textarea' => $campo->textContent,
            'select' => (function () use ($campo): string {
                foreach ($campo->getElementsByTagName('option') as $opcao) {
                    if ($opcao->hasAttribute('selected')) {
                        return $opcao->getAttribute('value');
                    }
                }

                return $campo->getElementsByTagName('option')->item(0)?->getAttribute('value') ?? '';
            })(),
            default => $campo->getAttribute('value'),
        };
    }

    /** @return list<string> */
    private function textos(DOMXPath $xpath, string $consulta, ?DOMElement $contexto = null): array
    {
        $textos = [];
        foreach ($xpath->query($consulta, $contexto) as $no) {
            $textos[] = trim(preg_replace('/\s+/', ' ', $no->textContent));
        }

        return $textos;
    }
}
