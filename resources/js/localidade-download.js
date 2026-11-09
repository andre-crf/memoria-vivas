export default function registerLocalidadeDownload(Alpine) {
    // Localidade do formulário de download: país sempre; estado e cidade só para o Brasil.
    // Fora do Brasil, estado e cidade ficam ocultos e desabilitados (não são enviados).
    Alpine.data('localidadeDownload', ({ brasilId, urlMunicipios, pais = '', estado = '', municipio = '', municipios = [] }) => ({
        pais: String(pais ?? ''),
        estado: String(estado ?? ''),
        municipio: String(municipio ?? ''),
        municipios,
        carregando: false,
        erro: false,

        get brasil() {
            return brasilId !== null && this.pais === String(brasilId);
        },

        get statusCidades() {
            if (this.carregando) return 'Carregando cidades…';
            if (this.erro) return 'Não foi possível carregar as cidades. Escolha o estado novamente.';

            return '';
        },

        async carregarMunicipios() {
            const estado = this.estado;
            this.municipio = '';
            this.municipios = [];
            this.erro = false;
            if (!estado) return;

            this.carregando = true;
            try {
                const resposta = await fetch(urlMunicipios.replace('__ESTADO__', encodeURIComponent(estado)), {
                    headers: { Accept: 'application/json' },
                });
                if (!resposta.ok) throw new Error(`HTTP ${resposta.status}`);
                const municipios = await resposta.json();
                // Ignora a resposta de um estado que já foi trocado.
                if (estado === this.estado) this.municipios = municipios;
            } catch {
                if (estado === this.estado) this.erro = true;
            } finally {
                if (estado === this.estado) this.carregando = false;
            }
        },
    }));
}
