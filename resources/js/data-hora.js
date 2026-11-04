/**
 * Datas e fusos.
 *
 * O servidor envia cada instante em UTC no atributo `datetime` e já escreve
 * dentro do elemento o horário no fuso conhecido (cookie ou fallback). Aqui:
 *
 * 1. publicamos o fuso do navegador em um cookie, para que as próximas
 *    requisições já cheguem renderizadas no fuso de quem está vendo;
 * 2. preenchemos o campo oculto do filtro de auditoria, para o período ser
 *    recortado nesse mesmo fuso;
 * 3. reescrevemos os horários já renderizados, cobrindo o primeiro acesso e
 *    quem acabou de mudar de fuso.
 *
 * Sem JavaScript nada disso acontece e o horário do servidor continua correto.
 */

// Precisa coincidir com App\Support\FusoDoUsuario::COOKIE, que também é o
// nome dispensado da criptografia de cookies em bootstrap/app.php.
const COOKIE = 'fuso_usuario';
const MARCA = 'dataHoraAplicado';

const fusoDoNavegador = () => {
    try {
        return Intl.DateTimeFormat().resolvedOptions().timeZone || null;
    } catch {
        return null;
    }
};

const lerCookie = (nome) =>
    document.cookie
        .split('; ')
        .find((item) => item.startsWith(`${nome}=`))
        ?.split('=')[1];

const publicarFuso = (fuso) => {
    if (decodeURIComponent(lerCookie(COOKIE) ?? '') === fuso) {
        return;
    }

    const umAno = 60 * 60 * 24 * 365;
    document.cookie = `${COOKIE}=${encodeURIComponent(fuso)}; path=/; max-age=${umAno}; SameSite=Lax`;
};

const preencherFiltro = (fuso) => {
    document.querySelectorAll('[data-fuso-usuario]').forEach((campo) => {
        campo.value = fuso;
    });
};

/**
 * Montado peça a peça porque `Intl` com data e hora juntas insere vírgula
 * ("25/09/2026, 09:00:00") e o formato do projeto não tem vírgula.
 */
const formatar = (instante, fuso, comSegundos) => {
    const partes = new Intl.DateTimeFormat('pt-BR', {
        timeZone: fuso,
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        ...(comSegundos ? { second: '2-digit' } : {}),
        hour12: false,
    })
        .formatToParts(instante)
        .reduce((acumulado, { type, value }) => ({ ...acumulado, [type]: value }), {});

    const hora = `${partes.hour}:${partes.minute}${comSegundos ? `:${partes.second}` : ''}`;

    return `${partes.day}/${partes.month}/${partes.year} ${hora}`;
};

const aplicar = (fuso) => {
    document.querySelectorAll('[data-data-hora]').forEach((elemento) => {
        if (elemento.dataset[MARCA] === fuso) {
            return;
        }

        const instante = new Date(elemento.getAttribute('datetime'));

        if (Number.isNaN(instante.getTime())) {
            return;
        }

        elemento.textContent = formatar(instante, fuso, elemento.dataset.formato !== 'curto');
        elemento.dataset[MARCA] = fuso;
    });
};

const executar = () => {
    const fuso = fusoDoNavegador();

    if (fuso === null) {
        return;
    }

    publicarFuso(fuso);
    preencherFiltro(fuso);
    aplicar(fuso);
};

document.addEventListener('DOMContentLoaded', executar);
