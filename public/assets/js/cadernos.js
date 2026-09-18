// Tela "Meus cadernos": lista, cria e exclui cadernos.

const lista     = document.getElementById('lista-cadernos');
const vazio     = document.getElementById('painel-vazio');
const molde     = document.getElementById('tpl-caderno');
const formNovo  = document.getElementById('form-novo-caderno');
const caixaErro = document.getElementById('erro');

carregarCadernos();

async function carregarCadernos() {
    try {
        const cadernos = await chamarApi('GET', '/api/cadernos');

        lista.innerHTML = '';
        vazio.hidden = cadernos.length > 0;

        for (const caderno of cadernos) {
            lista.appendChild(criarCard(caderno));
        }
    } catch (erro) {
        mostrarErro(caixaErro, erro.message);
    }
}

// Copia o <template> e preenche com os dados do caderno
function criarCard(caderno) {
    const card = molde.content.firstElementChild.cloneNode(true);

    card.querySelector('.caderno-capa').href = BASE + '/cadernos/' + caderno.id;
    card.querySelector('.caderno-nome').textContent = caderno.titulo;
    card.querySelector('.miniatura').classList.add('miniatura-' + caderno.tipo_folha);

    card.querySelector('.caderno-excluir').addEventListener('click', async () => {
        if (!confirm('Excluir o caderno "' + caderno.titulo + '"?')) return;

        try {
            await chamarApi('DELETE', '/api/cadernos/' + caderno.id);
            card.remove();
            vazio.hidden = lista.children.length > 0;
        } catch (erro) {
            mostrarErro(caixaErro, erro.message);
        }
    });

    return card;
}

// Criar caderno novo
formNovo.addEventListener('submit', async (evento) => {
    evento.preventDefault();
    mostrarErro(caixaErro, '');

    if (!formNovo.reportValidity()) return;

    const corpo = Object.fromEntries(new FormData(formNovo));

    try {
        const resposta = await chamarApi('POST', '/api/cadernos', corpo);
        formNovo.reset();

        lista.prepend(criarCard(resposta.caderno));
        vazio.hidden = true;
    } catch (erro) {
        mostrarErro(caixaErro, erro.message);
    }
});
