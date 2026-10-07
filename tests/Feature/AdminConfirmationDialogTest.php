<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AdminConfirmationDialogTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrative_layout_renders_one_accessible_reusable_dialog(): void
    {
        $response = $this->actingAs($this->internalUser())
            ->get(route('admin.dashboard'))
            ->assertOk();

        $response
            ->assertSee('x-data="confirmationDialog"', false)
            ->assertSee('@submit.window="intercept($event)"', false)
            ->assertSee('role="alertdialog"', false)
            ->assertSee('aria-modal="true"', false)
            ->assertSee('aria-labelledby="confirmation-dialog-title"', false)
            ->assertSee('aria-describedby="confirmation-dialog-message"', false)
            ->assertSee('@cancel.prevent="cancel()"', false)
            ->assertSee('x-ref="cancel"', false)
            ->assertSee('@close="restoreFocus()"', false);

        $this->assertSame(1, substr_count($response->getContent(), 'x-data="confirmationDialog"'));
    }

    public function test_confirmed_form_preserves_action_http_method_and_specific_message(): void
    {
        $categoria = Categoria::create(['titulo' => 'Categoria confirmável']);
        $response = $this->actingAs($this->internalUser())
            ->get(route('admin.categorias.index'))
            ->assertOk()
            ->assertDontSee('onsubmit=', false)
            ->assertDontSee('return confirm(', false)
            ->assertSee('Tem certeza que deseja excluir esta categoria? As associações com os itens do acervo serão removidas.');

        $document = new DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new DOMXPath($document);
        $action = route('admin.categorias.destroy', $categoria);
        $forms = $xpath->query(sprintf('//form[@action="%s"]', $action));

        $this->assertNotFalse($forms);
        $this->assertCount(1, $forms);

        $form = $forms->item(0);

        $this->assertNotNull($form);
        $this->assertSame('POST', $form->getAttribute('method'));
        $this->assertSame(
            'Tem certeza que deseja excluir esta categoria? As associações com os itens do acervo serão removidas.',
            $form->getAttribute('data-confirm-message'),
        );
        $this->assertSame(1, $xpath->query('.//input[@name="_method" and @value="DELETE"]', $form)?->length);
        $this->assertSame(1, $xpath->query('.//input[@name="_token"]', $form)?->length);
    }

    public function test_alpine_controller_resubmits_the_original_form_and_blocks_duplicates(): void
    {
        $script = file_get_contents(resource_path('js/confirmation-dialog.js'));

        $this->assertIsString($script);
        $this->assertStringContainsString('this.form.requestSubmit(this.submitter ?? undefined)', $script);
        $this->assertStringContainsString("this.form.setAttribute('aria-busy', 'true')", $script);
        $this->assertStringContainsString('control.disabled = true', $script);
        $this->assertStringNotContainsString('fetch(', $script);
        $this->assertStringNotContainsString('window.confirm(', $script);
    }

    public function test_administrative_views_no_longer_contain_native_confirmations(): void
    {
        foreach (File::allFiles(resource_path('views/admin')) as $view) {
            $contents = $view->getContents();

            $this->assertDoesNotMatchRegularExpression('/\bconfirm\s*\(/', $contents, $view->getRelativePathname());
            $this->assertStringNotContainsString('onsubmit=', $contents, $view->getRelativePathname());
        }
    }

    private function internalUser(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'status' => 'ativo',
        ]);
    }
}
