# Processamento de imagens

Como o Memórias Vivas gera as versões otimizadas das fotografias e como medir esse processamento com imagens reais antes de regenerar o acervo.

## Visão geral

- O **original** é guardado como foi enviado e nunca é alterado nem exibido no site público.
- A partir dele são geradas até três **versões otimizadas**: `thumbnail` (320 px), `medium` (1024 px) e `large` (1920 px), pelo maior lado. As versões nunca são ampliadas.
- Uma versão que teria exatamente as dimensões de uma versão menor é **dispensada**: uma foto de 800 px tem só `thumbnail` e `medium`. As telas usam a próxima versão disponível.
- Formato e qualidade ficam em `config/acervo.php` (`optimized_output`): WebP com qualidade 80 por padrão.
- Uploads aceitos continuam definidos pela política existente (`ACERVO_ORIGINAL_UPLOAD_MIME_TYPES`, `ACERVO_ORIGINAL_UPLOAD_EXTENSIONS` e `ACERVO_ORIGINAL_UPLOAD_MAX_KB`); os limites não são ampliados por este processamento.
- PDF pode ser preservado como original, mas não gera versões de imagem nem é exibido no catálogo público. Para exibir uma imagem, o registro também precisa estar publicado e público, e ao menos uma derivação otimizada deve ter sido gerada; o painel avisa quando nenhuma ou apenas parte das versões é criada.

### Formatos aceitos

Por padrão, o upload aceita JPEG (`.jpg`, `.jpeg`), PNG (`.png`), WebP (`.webp`), TIFF (`.tif`, `.tiff`) e PDF (`.pdf`), até 50 MB. A lista e o tamanho máximo continuam configuráveis pelas variáveis `ACERVO_ORIGINAL_UPLOAD_MIME_TYPES`, `ACERVO_ORIGINAL_UPLOAD_EXTENSIONS` e `ACERVO_ORIGINAL_UPLOAD_MAX_KB`.

JPEG, PNG e WebP podem ser processados pelo Imagick ou GD. TIFF exige Imagick para gerar derivações; sem esse motor, o original continua preservado, mas o painel avisa que a imagem não terá exibição pública até as versões serem geradas. PDF é armazenado como documento original e não é convertido em imagem.

### Etapas

O código está em `app/Support/OptimizedImageVersions.php`.

1. O original é lido **uma única vez**. Com o Imagick, usa a primeira página do TIFF ou o primeiro quadro do WebP animado.
2. A orientação EXIF é aplicada.
3. As cores são convertidas para sRGB:
   - se o original tem perfil ICC embutido, a conversão usa o perfil sRGB do servidor;
   - se não tem perfil, ou se o perfil do servidor faltar ou falhar, a conversão é feita pelo espaço de cor (aproximada) e um aviso vai para o log.
4. Metadados são removidos: EXIF (inclusive GPS), IPTC, XMP e o perfil ICC, que já foi aplicado aos pixels.
5. Cada versão é reduzida a partir de um clone dessa mesma imagem decodificada (filtro Lanczos) e gravada.

Sobre a transparência: no WebP ela é mantida; no JPEG, vira fundo branco.

### Motores

- **Imagick** é o motor principal.
- **GD** é a alternativa quando o Imagick não está disponível. Lê JPEG, PNG e WebP, mas não lê TIFF e não converte perfis de cor. O contêiner instala a extensão `exif`, necessária para aplicar as oito orientações EXIF no GD, incluindo espelhamento.
- O motor é escolhido em `ACERVO_IMAGEM_MOTOR`: `auto`, `imagick` ou `gd`.

### Limites de recursos para originais grandes

Antes de ler uma imagem com Imagick, o processador aplica tetos por processo: 128 MiB de memória, 256 MiB de mapa, 1 GiB de cache em disco e 16 milhões de pixels na área de memória. Se o servidor já impuser um limite menor, ele é mantido; estes tetos nunca elevam os limites do ImageMagick. Ao ultrapassar memória/área, o ImageMagick pode usar mapa ou cache em disco dentro dos respectivos limites. Se ainda não conseguir processar o arquivo, a derivação falha de forma registrada e o original permanece preservado.

Esses limites não alteram o tamanho máximo do upload nem a lista de formatos aceitos. TIFFs multipágina continuam usando apenas a primeira página, inclusive em imagens de 16 bits. O comando de medição informa os limites efetivos e o pico de memória do processo para avaliar amostras reais antes de regenerar o acervo.

### Perfil sRGB

- A imagem Docker instala o pacote `icc-profiles-free`, que fornece `/usr/share/color/icc/sRGB.icc`.
- O build falha se o arquivo não existir.
- Outro caminho pode ser configurado em `ACERVO_PERFIL_SRGB`.

### Falhas

- Cada falha é registrada no log com a fotografia, o arquivo, o tipo do original e a **etapa**:
  - `configuracao`, `leitura`, `orientacao`, `cor`, `metadados`, `redimensionamento`, `gravacao` ou `registro`.
- Mensagens técnicas ficam só no log:
  - a auditoria guarda apenas quais versões foram geradas, dispensadas ou falharam;
  - o painel mostra só o nome das versões;
  - o site público não recebe nenhuma informação do processamento.
- Uma versão que falha não impede as demais.
- Na regeneração, a versão anterior é mantida quando a nova falha.
- No upload ou substituição, o painel apresenta aviso administrativo se alguma derivação falhar; o original continua guardado e versões ausentes podem ser regeneradas.

## Medir o processamento com imagens reais

O comando `acervo:medir-processamento` processa imagens com o mesmo código da geração das versões, **sem tocar no acervo**:

- cada arquivo é copiado para uma pasta temporária, que é apagada no fim;
- nada é gravado no banco nem na auditoria;
- o arquivo de entrada só é lido.

### Procedimento

1. **Separe uma amostra variada numa pasta** fora do acervo, por exemplo `storage/medicao/amostras`. Inclua:
   - fotos de celular, de preferência com orientação vertical, que dependem do EXIF;
   - scans grandes em TIFF, incluindo 16 bits e CMYK, se houver;
   - PNGs com transparência;
   - JPEGs com perfil Adobe RGB;
   - WebPs.
2. **Rode a medição** dentro do contêiner do PHP:

   ```bash
   docker compose exec app php artisan acervo:medir-processamento storage/medicao/amostras \
       --csv=storage/medicao/resultado-webp-80.csv \
       --saida=storage/medicao/versoes-webp-80
   ```

3. **Compare outras configurações**, se necessário, sem alterar o `.env`:

   ```bash
   docker compose exec app php artisan acervo:medir-processamento storage/medicao/amostras \
       --formato=jpeg --qualidade=85 --csv=storage/medicao/resultado-jpeg-85.csv
   ```

4. **Abra as versões** da pasta `--saida` e compare visualmente com os originais. Confira:
   - a orientação;
   - as cores, principalmente em scans com perfil;
   - a transparência;
   - artefatos de compressão.
5. **Só depois regenere** algumas fotografias do acervo (`php artisan acervo:regenerar-versoes 12 15`) e, por fim, todas (`--todas`).

### O que é medido

| Campo | Significado |
|---|---|
| `largura`, `altura`, `bytes` | Dimensões e tamanho do original |
| `versao_largura`, `versao_altura`, `versao_bytes` | Dimensões e tamanho de cada versão gerada |
| `leitura_ms` | Leitura do original: decodificação, orientação, cores e metadados |
| `versao_ms` | Redimensionamento e gravação de cada versão |
| `total_ms` | Processamento completo daquela imagem |
| `php_pico_bytes` | Pico de memória do PHP (`memory_get_peak_usage`), que conta para o `memory_limit` |
| `processo_pico_bytes` | Pico de memória do processo inteiro (`VmHWM`, Linux), incluindo o ImageMagick, que fica **fora** do `memory_limit` |
| `processo_pico_antes_bytes` | Pico antes do processamento, só com o Laravel carregado; a diferença é o custo da imagem |
| `cor` | `profile` (perfil sRGB), `approximate` (conversão aproximada), `none` (já estava em sRGB ou cinza) ou `gd` |
| `dispensadas`, `falhas` | Versões dispensadas por repetirem dimensões, e versões que falharam (detalhes no log) |

### Como cada imagem é isolada

- Cada imagem roda num processo PHP próprio. Assim, o pico do processo reflete só aquela imagem.
- Se o processo morrer, por exemplo por falta de memória ou por tempo esgotado (`--tempo-limite`, padrão de 600 s), a medição registra o erro e segue para a próxima imagem.
- Os limites de recursos do ImageMagick (memória, mapa, área e disco) aparecem no JSON de cada imagem. Quando a memória acaba, o ImageMagick passa a usar disco, o que deixa o processamento bem mais lento.

### Limitações da medição

- O pico do processo vem de `/proc/self/status` e só existe no Linux, o que inclui o contêiner.
- A medição usa um processo de linha de comando. Uma requisição web roda no PHP-FPM e também carrega o upload e a transação.
- O tempo de gravação não inclui o registro no banco nem a auditoria, que são rápidos em comparação ao processamento.
