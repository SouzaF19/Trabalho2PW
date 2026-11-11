// Editor do caderno, feito com Konva.js.
//
// Como funciona:
//   - carrega a lista de paginas do caderno
//   - carrega os elementos da pagina e desenha no Konva
//   - o usuario desenha/escreve na camada do Konva
//   - "Salvar" percorre a camada, monta o JSON e manda com PUT

const LARGURA_FOLHA = 794;   // A4 a 96 dpi (mesmo valor do CSS)
const ALTURA_FOLHA  = 1123;

const cadernoId = Number(document.getElementById('editor').dataset.cadernoId);

const folha        = document.getElementById('folha');
const botoes       = document.querySelectorAll('.ferramenta[data-ferramenta]');
const inputCor     = document.getElementById('cor');
const inputEsp     = document.getElementById('espessura');
const btnDesfazer  = document.getElementById('btn-desfazer');
const btnLimpar    = document.getElementById('btn-limpar');
const btnSalvar    = document.getElementById('btn-salvar');
const status       = document.getElementById('status-salvar');
const btnAnterior  = document.getElementById('btn-pagina-anterior');
const btnProxima   = document.getElementById('btn-pagina-proxima');
const btnNova      = document.getElementById('btn-pagina-nova');
const rotuloPagina = document.getElementById('rotulo-pagina');
const inputArquivo = document.getElementById('arquivo-imagem');

// estado do editor
let ferramenta   = 'caneta';
let paginas      = [];     // [{id, ordem}]
let indicePagina = 0;
let desenhando   = false;
let linhaAtual   = null;

// ---- Konva ---------------------------------------------------------------

const stage = new Konva.Stage({
    container: 'folha',
    width: LARGURA_FOLHA,
    height: ALTURA_FOLHA,
});
const camada = new Konva.Layer();
stage.add(camada);

// Alcas de redimensionar da imagem selecionada. E filho da camada, mas nao e
// um desenho do usuario: nao entra no JSON e nao conta no "Desfazer".
const transformador = new Konva.Transformer({
    rotateEnabled: false,
    keepRatio: true,
    enabledAnchors: ['top-left', 'top-right', 'bottom-left', 'bottom-right'],
    // nao deixa encolher a imagem ate sumir
    boundBoxFunc: (antes, depois) => (depois.width < 30 || depois.height < 30 ? antes : depois),
});
camada.add(transformador);

// Esvazia a folha sem perder o transformador
function limparCamada() {
    transformador.nodes([]);
    transformador.remove();        // tira da camada, mas nao destroi
    camada.destroyChildren();
    camada.add(transformador);
}

// Os desenhos do usuario, sem o transformador
function elementosDaCamada() {
    return camada.getChildren((no) => no !== transformador);
}

// ---- Ferramentas ---------------------------------------------------------

function escolherFerramenta(nome) {
    ferramenta = nome;

    // marca o botao ativo e troca o cursor da folha
    botoes.forEach((b) => b.classList.toggle('ativo', b.dataset.ferramenta === nome));
    folha.className = 'folha folha-' + document.getElementById('editor').dataset.tipoFolha + ' ferramenta-' + nome;

    // textos e imagens so podem ser arrastados no modo "mover"
    camada.find('.texto').forEach((t) => t.draggable(nome === 'mover'));
    camada.find('.imagem').forEach((i) => i.draggable(nome === 'mover'));

    // saiu do modo "mover": esconde as alcas de redimensionar
    if (nome !== 'mover') transformador.nodes([]);
}

botoes.forEach((b) => b.addEventListener('click', () => {
    // "Anexar imagem" nao e um modo de desenho: abre a janela de arquivos
    // e deixa a ferramenta atual como esta.
    if (b.dataset.ferramenta === 'imagem') {
        inputArquivo.click();
        return;
    }

    escolherFerramenta(b.dataset.ferramenta);
}));
escolherFerramenta('caneta');

// Cria a linha de um traco (usado ao desenhar e ao carregar da API)
function criarLinha(tipoFerramenta, cor, espessura, pontos) {
    let largura = espessura;
    if (tipoFerramenta === 'lapis')    largura = espessura / 2;
    if (tipoFerramenta === 'borracha') largura = espessura * 4;

    return new Konva.Line({
        name: 'traco',
        points: pontos,
        stroke: cor,
        strokeWidth: largura,
        opacity: tipoFerramenta === 'lapis' ? 0.6 : 1,
        lineCap: 'round',
        lineJoin: 'round',
        // a borracha "recorta" o que ja foi desenhado na camada
        globalCompositeOperation: tipoFerramenta === 'borracha' ? 'destination-out' : 'source-over',
        listening: false,
        // guardamos para conseguir salvar depois
        ferramenta: tipoFerramenta,
        espessura: espessura,
    });
}

// Cria um texto (usado ao escrever e ao carregar da API)
function criarTexto(x, y, conteudo, tamanho, cor) {
    const texto = new Konva.Text({
        name: 'texto',
        x: x,
        y: y,
        text: conteudo,
        fontSize: tamanho,
        fontFamily: FONTE_TEXTO,
        fill: cor,
        draggable: ferramenta === 'mover',
    });

    // duplo clique reabre o campo de escrever em cima do texto
    texto.on('dblclick dbltap', () => abrirCampoDeTexto(texto.x(), texto.y(), texto));
    texto.on('dragend', marcarAlterado);

    return texto;
}

// ---- Escrever texto na folha ---------------------------------------------
//
// Em vez de um prompt() do navegador, abrimos um <textarea> em cima da folha,
// no lugar exato do clique e com a mesma fonte do Konva.Text. Para o usuario
// parece que ele esta escrevendo direto no papel.

const FONTE_TEXTO = 'Lora, Georgia, serif';
const TAMANHO_TEXTO = 18;

let campoAberto = null;

// Sem `no`, cria um texto novo em (x, y). Com `no`, edita aquele texto.
function abrirCampoDeTexto(x, y, no) {
    if (campoAberto) return;

    const tamanho = no ? no.fontSize() : TAMANHO_TEXTO;
    const cor     = no ? no.fill() : inputCor.value;

    if (no) no.hide();   // o original some enquanto o campo esta aberto

    const campo = document.createElement('textarea');
    campo.className = 'editor-texto';
    campo.wrap = 'off';   // nao quebra linha sozinho, igual ao Konva.Text
    campo.value = no ? no.text() : '';
    campo.style.left = x + 'px';
    campo.style.top = y + 'px';
    campo.style.fontSize = tamanho + 'px';
    campo.style.color = cor;

    folha.appendChild(campo);
    campoAberto = campo;

    ajustarTamanhoDoCampo(campo);
    campo.addEventListener('input', () => ajustarTamanhoDoCampo(campo));

    campo.addEventListener('keydown', (evento) => {
        // Enter confirma, Shift+Enter pula linha, Esc desiste
        if (evento.key === 'Enter' && !evento.shiftKey) {
            evento.preventDefault();
            fechar(true);
        } else if (evento.key === 'Escape') {
            fechar(false);
        }
    });

    // clicou fora: confirma o que foi escrito
    campo.addEventListener('blur', () => fechar(true));

    campo.focus();

    function fechar(confirmar) {
        if (campoAberto !== campo) return;   // ja foi fechado
        campoAberto = null;

        const conteudo = campo.value.trim();
        campo.remove();

        if (!confirmar) {
            if (no) no.show();
            return;
        }

        if (no) {
            if (conteudo === '') {
                no.destroy();           // apagou tudo: o texto sai da folha
            } else {
                if (conteudo === no.text()) { no.show(); return; }   // nada mudou
                no.text(conteudo);
                no.show();
            }
        } else {
            if (conteudo === '') return;   // desistiu de escrever
            camada.add(criarTexto(x, y, conteudo, tamanho, cor));
        }

        marcarAlterado();
    }
}

// O campo cresce conforme o usuario escreve, em vez de ganhar barra de rolagem
function ajustarTamanhoDoCampo(campo) {
    campo.style.height = 'auto';
    campo.style.height = campo.scrollHeight + 'px';
    campo.style.width = 'auto';
    campo.style.width = Math.min(campo.scrollWidth + 4, LARGURA_FOLHA - campo.offsetLeft) + 'px';
}

// ---- Desenhar (Pointer Events: mouse, toque e caneta) --------------------

stage.on('pointerdown', (evento) => {
    const pos = stage.getPointerPosition();

    if (ferramenta === 'mover') {
        // o Konva cuida do arrastar; aqui so decidimos quem fica selecionado
        const alvo = evento.target;

        if (alvo.getParent() === transformador) return;   // clicou nas proprias alcas

        transformador.nodes(alvo.hasName('imagem') ? [alvo] : []);
        return;
    }

    if (ferramenta === 'texto') {
        if (evento.target instanceof Konva.Text) return;   // clicou num texto: o dblclick edita

        abrirCampoDeTexto(pos.x, pos.y, null);
        return;
    }

    // lapis, caneta ou borracha: comeca um traco novo
    desenhando = true;
    linhaAtual = criarLinha(ferramenta, inputCor.value, Number(inputEsp.value), [pos.x, pos.y]);
    camada.add(linhaAtual);
});

stage.on('pointermove', () => {
    if (!desenhando) return;

    const pos = stage.getPointerPosition();
    // Konva.Line usa uma lista plana [x1, y1, x2, y2, ...],
    // o mesmo formato que a API guarda
    linhaAtual.points(linhaAtual.points().concat([pos.x, pos.y]));
});

function terminarTraco() {
    if (!desenhando) return;
    desenhando = false;

    // um clique sem arrastar vira um pontinho
    if (linhaAtual.points().length === 2) {
        linhaAtual.points(linhaAtual.points().concat(linhaAtual.points()));
    }

    linhaAtual = null;
    marcarAlterado();
}

stage.on('pointerup', terminarTraco);
folha.addEventListener('pointerleave', terminarTraco);

// ---- Desfazer / limpar ---------------------------------------------------

btnDesfazer.addEventListener('click', () => {
    const filhos = elementosDaCamada();
    if (filhos.length === 0) return;

    transformador.nodes([]);                // o selecionado pode ser o que vai sumir
    filhos[filhos.length - 1].destroy();    // remove o ultimo elemento
    marcarAlterado();
});

btnLimpar.addEventListener('click', () => {
    if (!confirm('Apagar tudo desta página?')) return;

    limparCamada();
    marcarAlterado();
});

// ---- Converter Konva <-> JSON da API -------------------------------------

// camada -> lista de elementos no formato do docs/api.md
function montarJson() {
    const elementos = [];

    elementosDaCamada().forEach((no, i) => {
        if (no.hasName('traco')) {
            elementos.push({
                tipo: 'traco',
                z_index: i,
                dados: {
                    ferramenta: no.getAttr('ferramenta'),
                    cor: no.stroke(),
                    espessura: no.getAttr('espessura'),
                    pontos: no.points(),
                },
            });
        } else if (no.hasName('texto')) {
            elementos.push({
                tipo: 'texto',
                x: no.x(),
                y: no.y(),
                z_index: i,
                dados: { conteudo: no.text(), tamanho: no.fontSize(), cor: no.fill() },
            });
        } else if (no.hasName('imagem')) {
            elementos.push({
                tipo: 'imagem',
                x: no.x(),
                y: no.y(),
                // o scale normalmente e 1 (ver criarImagem), mas multiplicamos
                // para o tamanho sair certo mesmo se sobrar escala
                largura: no.width() * no.scaleX(),
                altura: no.height() * no.scaleY(),
                z_index: i,
                dados: { url: no.getAttr('url') },
            });
        }
    });

    return elementos;
}

// um elemento da API -> desenho na camada
function desenharElemento(e) {
    if (e.tipo === 'traco') {
        camada.add(criarLinha(e.dados.ferramenta, e.dados.cor, e.dados.espessura, e.dados.pontos));
    } else if (e.tipo === 'texto') {
        camada.add(criarTexto(e.x, e.y, e.dados.conteudo, e.dados.tamanho, e.dados.cor));
    } else if (e.tipo === 'imagem') {
        criarImagem(e.dados.url, e.x, e.y, e.largura, e.altura);
    }
}

// ---- Imagens -------------------------------------------------------------

// Coloca a imagem na folha (usado ao anexar e ao carregar da API).
// Sem largura/altura, a imagem entra reduzida para caber na folha.
function criarImagem(url, x, y, largura, altura) {
    Konva.Image.fromURL(
        BASE + url,
        (img) => {
            img.setAttrs({
                name: 'imagem',
                x: x,
                y: y,
                url: url,
                draggable: ferramenta === 'mover',
            });

            if (largura && altura) {
                img.size({ width: largura, height: altura });
            } else {
                const escala = Math.min(1, (LARGURA_FOLHA * 0.5) / img.width());
                img.size({ width: img.width() * escala, height: img.height() * escala });
            }

            // O transformador redimensiona mexendo no scale. Aqui passamos esse
            // scale para largura/altura e voltamos o scale para 1, senao a imagem
            // seria salva com o tamanho original.
            img.on('transformend', () => {
                img.size({ width: img.width() * img.scaleX(), height: img.height() * img.scaleY() });
                img.scale({ x: 1, y: 1 });
                marcarAlterado();
            });
            img.on('dragend', marcarAlterado);

            camada.add(img);
            transformador.moveToTop();   // as alcas ficam sempre por cima
        },
        () => {
            status.textContent = 'Não foi possível carregar a imagem ' + url;
            status.className = 'status-salvar erro';
        }
    );
}

// Botao "Anexar imagem" -> janela de arquivos -> POST /api/upload
inputArquivo.addEventListener('change', async () => {
    const arquivo = inputArquivo.files[0];
    if (!arquivo) return;

    status.textContent = 'Enviando imagem...';
    status.className = 'status-salvar';

    // Aqui nao da para usar chamarApi(): ele manda JSON, e o upload precisa de
    // multipart/form-data. Com FormData o proprio fetch monta o cabecalho.
    const dados = new FormData();
    dados.append('arquivo', arquivo);

    try {
        const resposta = await fetch(BASE + '/api/upload', { method: 'POST', body: dados });
        const json = await resposta.json();

        if (!resposta.ok) throw new Error(json.erro || 'Erro ' + resposta.status);

        criarImagem(json.url, 60, 60);
        escolherFerramenta('mover');   // ja deixa pronto para posicionar
        marcarAlterado();
    } catch (erro) {
        status.textContent = 'Erro ao enviar: ' + erro.message;
        status.className = 'status-salvar erro';
    }

    inputArquivo.value = '';   // permite escolher o mesmo arquivo de novo
});

// ---- Salvar --------------------------------------------------------------

function marcarAlterado() {
    status.textContent = 'Alterações não salvas';
    status.className = 'status-salvar pendente';
}

async function salvar() {
    const paginaId = paginas[indicePagina].id;

    status.textContent = 'Salvando...';
    status.className = 'status-salvar';

    try {
        await chamarApi('PUT', '/api/paginas/' + paginaId, { elementos: montarJson() });

        const hora = new Date().toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
        status.textContent = 'Salvo às ' + hora;
    } catch (erro) {
        status.textContent = 'Erro ao salvar: ' + erro.message;
        status.className = 'status-salvar erro';
    }
}

btnSalvar.addEventListener('click', salvar);

// ---- Paginas -------------------------------------------------------------

async function abrirPagina(indice) {
    indicePagina = indice;

    const pagina = await chamarApi('GET', '/api/paginas/' + paginas[indice].id);

    limparCamada();
    pagina.elementos.forEach(desenharElemento);

    status.textContent = '';
    status.className = 'status-salvar';

    rotuloPagina.textContent = 'Página ' + (indice + 1) + ' de ' + paginas.length;
    btnAnterior.disabled = indice === 0;
    btnProxima.disabled  = indice === paginas.length - 1;
}

// Antes de trocar de pagina, pergunta se quer salvar
async function trocarPagina(indice) {
    if (status.classList.contains('pendente') && confirm('Salvar as alterações desta página antes de trocar?')) {
        await salvar();
    }
    abrirPagina(indice);
}

btnAnterior.addEventListener('click', () => trocarPagina(indicePagina - 1));
btnProxima.addEventListener('click', () => trocarPagina(indicePagina + 1));

btnNova.addEventListener('click', async () => {
    const resposta = await chamarApi('POST', '/api/cadernos/' + cadernoId + '/paginas', {});
    paginas.push(resposta.pagina);
    trocarPagina(paginas.length - 1);
});

// ---- Inicio --------------------------------------------------------------

async function iniciar() {
    paginas = await chamarApi('GET', '/api/cadernos/' + cadernoId + '/paginas');

    // caderno sem nenhuma pagina: cria a primeira
    if (paginas.length === 0) {
        const resposta = await chamarApi('POST', '/api/cadernos/' + cadernoId + '/paginas', {});
        paginas.push(resposta.pagina);
    }

    abrirPagina(0);
}

iniciar();
