<?php

namespace App\Services\Ibge;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

/**
 * Cliente da API pública de localidades do IBGE.
 *
 * Só é usado pelo comando `localidades:sync`. O restante da aplicação lê as
 * localidades do banco local, para continuar funcionando se o IBGE cair.
 */
class IbgeLocalidadesClient
{
    /**
     * Países, no formato bruto da API.
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws RequestException
     */
    public function paises(): array
    {
        return $this->request()->get('/paises')->throw()->json();
    }

    /**
     * Unidades federativas do Brasil, no formato bruto da API.
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws RequestException
     */
    public function estados(): array
    {
        return $this->request()->get('/estados', ['orderBy' => 'nome'])->throw()->json();
    }

    /**
     * Municípios de uma UF, no formato bruto da API.
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws RequestException
     */
    public function municipiosDaUf(string $sigla): array
    {
        return $this->request()
            ->get("/estados/{$sigla}/municipios", ['orderBy' => 'nome'])
            ->throw()
            ->json();
    }

    private function request(): PendingRequest
    {
        $config = config('services.ibge');

        return Http::baseUrl($config['base_url'])
            ->timeout($config['timeout'])
            ->connectTimeout($config['connect_timeout'])
            ->retry($config['tentativas'], $config['intervalo_retry'], throw: false)
            ->acceptJson();
    }
}
