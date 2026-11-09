export default function registerPasswordField(Alpine) {
    // Campo de senha (x-catalog.password): mostra/oculta o valor e acompanha os requisitos de senha forte.
    // Os critérios espelham Password::defaults() (AppServiceProvider): 8+ caracteres, maiúscula e minúscula, número e símbolo.
    Alpine.data('passwordField', () => ({
        visivel: false,
        valor: '',

        alternar() {
            this.visivel = !this.visivel;
        },

        get requisitos() {
            const valor = this.valor;

            return {
                tamanho: [...valor].length >= 8,
                caixa: /\p{Lu}/u.test(valor) && /\p{Ll}/u.test(valor),
                numero: /\p{N}/u.test(valor),
                simbolo: /[\p{Z}\p{S}\p{P}]/u.test(valor),
            };
        },
    }));
}
