// Coisas usadas em todas as telas.

// Chama a API mandando/recebendo JSON. Devolve o JSON da resposta.
// Se a resposta for erro, lanca um Error com a mensagem do backend.
async function chamarApi(metodo, caminho, corpo) {
    const opcoes = { method: metodo, headers: {} };

    if (corpo !== undefined) {
        opcoes.headers['Content-Type'] = 'application/json';
        opcoes.body = JSON.stringify(corpo);
    }

    const resposta = await fetch(BASE + caminho, opcoes);
    const dados = await resposta.json();

    if (!resposta.ok) {
        throw new Error(dados.erro || 'Erro ' + resposta.status);
    }

    return dados;
}

// Botao "sair" do cabecalho (so existe nas telas logadas)
const btnSair = document.getElementById('btn-sair');
if (btnSair) {
    btnSair.addEventListener('click', async () => {
        await chamarApi('POST', '/api/logout', {});
        window.location.href = BASE + '/login';
    });
}

// Mostra a mensagem na caixa de erro (texto vazio esconde)
function mostrarErro(caixa, texto) {
    caixa.textContent = texto;
    caixa.hidden = texto === '';
}
