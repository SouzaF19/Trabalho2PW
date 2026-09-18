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

// ---- Ferramentas ---------------------------------------------------------

function escolherFerramenta(nome) {
    ferramenta = nome;

    // marca o botao ativo e troca o cursor da folha
    botoes.forEach((b) => b.classList.toggle('ativo', b.dataset.ferramenta === nome));
    folha.className = 'folha folha-' + document.getElementById('editor').dataset.tipoFolha + ' ferramenta-' + nome;

    // textos so podem ser arrastados no modo "mover"
    camada.find('.texto').forEach((t) => t.draggable(nome === 'mover'));
}

botoes.forEach((b) => b.addEventListener('click', () => escolherFerramenta(b.dataset.ferramenta)));
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
        fontFamily: 'Lora, Georgia, serif',
        fill: cor,
        draggable: ferramenta === 'mover',
    });

    // duplo clique edita o texto; apagar tudo remove
    texto.on('dblclick dbltap', () => {
        const novo = prompt('Editar texto:', texto.text());
        if (novo === null) return;

        if (novo.trim() === '') texto.destroy();
        else texto.text(novo);

        marcarAlterado();
    });
    texto.on('dragend', marcarAlterado);

    return texto;
}

// ---- Desenhar (Pointer Events: mouse, toque e caneta) --------------------

stage.on('pointerdown', (evento) => {
    const pos = stage.getPointerPosition();

    if (ferramenta === 'mover') return;   // o Konva cuida do arrastar

    if (ferramenta === 'texto') {
        if (evento.target instanceof Konva.Text) return;   // clicou num texto: o dblclick edita

        const conteudo = prompt('Texto:');
        if (!conteudo || conteudo.trim() === '') return;

        camada.add(criarTexto(pos.x, pos.y, conteudo, 18, inputCor.value));
        marcarAlterado();
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
    const filhos = camada.getChildren();
    if (filhos.length === 0) return;

    filhos[filhos.length - 1].destroy();   // remove o ultimo elemento
    marcarAlterado();
});

btnLimpar.addEventListener('click', () => {
    if (!confirm('Apagar tudo desta página?')) return;

    camada.destroyChildren();
    marcarAlterado();
});

// ---- Converter Konva <-> JSON da API -------------------------------------

// camada -> lista de elementos no formato do docs/api.md
function montarJson() {
    const elementos = [];

    camada.getChildren().forEach((no, i) => {
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
                largura: no.width(),
                altura: no.height(),
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
        Konva.Image.fromURL(BASE + e.dados.url, (img) => {
            img.setAttrs({ name: 'imagem', x: e.x, y: e.y, width: e.largura, height: e.altura, url: e.dados.url });
            camada.add(img);
        });
    }
}

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

    camada.destroyChildren();
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
