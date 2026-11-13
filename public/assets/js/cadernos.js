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

    ligarEdicao(card, caderno);

    return card;
}

// Renomear e trocar o tipo de folha, sem sair da lista
function ligarEdicao(card, caderno) {
    const nome      = card.querySelector('.caderno-nome');
    const miniatura = card.querySelector('.miniatura');
    const form      = card.querySelector('.caderno-edicao');
    const botao     = card.querySelector('.caderno-editar');

    botao.addEventListener('click', () => {
        form.titulo.value     = caderno.titulo;
        form.tipo_folha.value = caderno.tipo_folha;

        form.hidden = false;
        card.classList.add('editando');
        form.titulo.focus();
        form.titulo.select();
    });

    form.querySelector('.cancelar').addEventListener('click', fechar);

    form.addEventListener('submit', async (evento) => {
        evento.preventDefault();
        if (!form.reportValidity()) return;

        const corpo = { titulo: form.titulo.value.trim(), tipo_folha: form.tipo_folha.value };

        try {
            const resposta = await chamarApi('PUT', '/api/cadernos/' + caderno.id, corpo);

            // a miniatura precisa trocar de pauta se o tipo de folha mudou
            miniatura.classList.remove('miniatura-' + caderno.tipo_folha);
            miniatura.classList.add('miniatura-' + resposta.caderno.tipo_folha);

            caderno.titulo     = resposta.caderno.titulo;
            caderno.tipo_folha = resposta.caderno.tipo_folha;
            nome.textContent   = caderno.titulo;
            nome.title         = caderno.titulo;

            fechar();
        } catch (erro) {
            mostrarErro(caixaErro, erro.message);
        }
    });

    function fechar() {
        form.hidden = true;
        card.classList.remove('editando');
    }
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
