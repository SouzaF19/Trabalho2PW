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

// Aviso de "confirme seu e-mail" (so aparece para quem ainda nao confirmou)
const btnReenviar = document.getElementById('btn-reenviar');
if (btnReenviar) {
    const estado = document.getElementById('aviso-estado');

    btnReenviar.addEventListener('click', async () => {
        btnReenviar.disabled = true;
        estado.textContent = 'Enviando...';

        try {
            const resposta = await chamarApi('POST', '/api/reenviar-confirmacao', {});

            // Em modo de teste nenhum e-mail sai de verdade: o backend devolve
            // o link para dar para confirmar a conta na hora.
            if (resposta.link_confirmacao) {
                estado.innerHTML = '';
                const atalho = document.createElement('a');
                atalho.href = resposta.link_confirmacao;
                atalho.textContent = 'Modo de teste: clique aqui para confirmar';
                estado.appendChild(atalho);
            } else {
                estado.textContent = 'E-mail enviado. Olhe sua caixa de entrada.';
            }
        } catch (erro) {
            estado.textContent = erro.message;
            btnReenviar.disabled = false;
        }
    });
}

// Mostra a mensagem na caixa de erro (texto vazio esconde)
function mostrarErro(caixa, texto) {
    caixa.textContent = texto;
    caixa.hidden = texto === '';
}
