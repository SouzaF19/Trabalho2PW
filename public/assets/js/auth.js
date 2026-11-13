// Telas de login e cadastro. Os dois formularios funcionam igual,
// so muda a rota da API.

const formLogin    = document.getElementById('form-login');
const formCadastro = document.getElementById('form-cadastro');

if (formLogin) {
    ligarFormulario(formLogin, '/api/login');
    const avisoCadastro = document.getElementById('aviso-cadastro');
    const tipo = new URLSearchParams(window.location.search).get('cadastro');
    let mensagem = '';
    if (tipo === 'confirmar') mensagem = 'Cadastro criado. Confira seu e-mail para confirmar sua conta.';
    if (tipo === 'envio_falhou') mensagem = 'Conta criada, mas não foi possível enviar o e-mail. Preencha seu e-mail e senha para solicitar o reenvio.';
    if (mensagem && avisoCadastro) {
        avisoCadastro.textContent = mensagem;
        avisoCadastro.hidden = false;
    }

    const botao = document.getElementById('btn-reenviar');
    const estado = document.getElementById('aviso-estado');
    if (botao && estado) botao.addEventListener('click', async () => {
        if (!formLogin.reportValidity()) return;
        const campos = Object.fromEntries(new FormData(formLogin));
        botao.disabled = true;
        estado.textContent = 'Enviando...';
        try {
            await chamarApi('POST', '/api/reenviar-confirmacao', { email: campos.email, senha: campos.senha });
            estado.textContent = 'Solicitação de reenvio concluída. Verifique seu e-mail.';
        } catch (erro) {
            estado.textContent = erro.message;
        } finally {
            botao.disabled = false;
        }
    });
}
if (formCadastro) ligarFormulario(formCadastro, '/api/cadastro');

function ligarFormulario(form, rota) {
    const caixaErro = form.querySelector('#erro');

    form.addEventListener('submit', async (evento) => {
        evento.preventDefault();
        mostrarErro(caixaErro, '');

        // validacao do proprio navegador (required, type=email, minlength)
        if (!form.reportValidity()) return;

        // pega os campos do formulario como objeto { nome, email, senha }
        const corpo = Object.fromEntries(new FormData(form));

        try {
            await chamarApi('POST', rota, corpo);
            window.location.href = BASE + (rota === '/api/cadastro' ? '/login?cadastro=confirmar' : '/cadernos');
        } catch (erro) {
            if (rota === '/api/cadastro' && erro.dados && erro.dados.conta_criada === true && erro.dados.email_enviado === false) {
                window.location.href = BASE + '/login?cadastro=envio_falhou';
                return;
            }
            const mensagem = rota === '/api/login' && erro.dados && erro.dados.codigo === 'email_nao_confirmado'
                ? 'Confirme seu e-mail. Para receber outro link, preencha e-mail e senha e clique em reenviar.'
                : erro.message;
            mostrarErro(caixaErro, mensagem);
        }
    });
}
