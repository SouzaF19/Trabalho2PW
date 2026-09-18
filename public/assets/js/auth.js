// Telas de login e cadastro. Os dois formularios funcionam igual,
// so muda a rota da API.

const formLogin    = document.getElementById('form-login');
const formCadastro = document.getElementById('form-cadastro');

if (formLogin)    ligarFormulario(formLogin, '/api/login');
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
            window.location.href = BASE + '/cadernos';
        } catch (erro) {
            mostrarErro(caixaErro, erro.message);
        }
    });
}
