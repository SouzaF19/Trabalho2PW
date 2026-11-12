// Editor do caderno, feito com Konva.js.
//
// Como funciona:
//   - carrega a lista de paginas do caderno
//   - carrega os elementos da pagina e desenha no Konva
//   - o usuario desenha/escreve na camada do Konva
//   - "Salvar" percorre a camada, monta o JSON e manda com PUT

const LARGURA_FOLHA = 794;   // A4 a 96 dpi (mesmo valor do CSS)
const ALTURA_FOLHA  = 1123;

// Medidas e cores das pautas. Precisam bater com as do CSS (.folha), porque
// na exportacao em PNG a folha e redesenhada no canvas.
const ESPACO_PAUTA  = 34;
const MARGEM_X      = 130;
const COR_PAPEL     = '#f5f5f7';
const COR_PAUTA     = '#a9d6e5';
const COR_MARGEM    = '#e07a7a';
const COR_QUADRICULA = '#c9d7e6';

const cadernoId = Number(document.getElementById('editor').dataset.cadernoId);
const tipoFolha = document.getElementById('editor').dataset.tipoFolha;

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
const btnExcluirSel = document.getElementById('btn-excluir-selecao');
const btnExcluirPag = document.getElementById('btn-pagina-excluir');
const btnExportar   = document.getElementById('btn-exportar');

// estado do editor
let ferramenta   = 'caneta';
let paginas      = [];     // [{id, ordem}]
let indicePagina = 0;
let desenhando   = false;
let linhaAtual   = null;

// Campo de escrever texto que estiver aberto sobre a folha, se houver.
// Ficam aqui em cima porque escolherFerramenta() ja usa o fechamento logo
// no carregamento da pagina, antes do resto do arquivo ser lido.
let campoAberto = null;
let fecharCampoAberto = () => {};

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
    selecionar(null);
    transformador.remove();        // tira da camada, mas nao destroi
    camada.destroyChildren();
    camada.add(transformador);
}

// Os desenhos do usuario, sem o transformador
function elementosDaCamada() {
    return camada.getChildren((no) => no !== transformador);
}

// ---- Selecao (modo "mover") ----------------------------------------------

// Marca um texto ou imagem como selecionado, para dar para excluir.
// Imagem ganha as alcas de redimensionar; texto so a moldura, porque
// esticar texto pelo scale deformaria a fonte.
function selecionar(no) {
    transformador.nodes(no ? [no] : []);
    transformador.resizeEnabled(no !== null && no.hasName('imagem'));

    btnExcluirSel.disabled = no === null;
}

function elementoSelecionado() {
    return transformador.nodes()[0] ?? null;
}

function excluirSelecao() {
    const no = elementoSelecionado();
    if (!no) return;

    selecionar(null);
    no.destroy();
    marcarAlterado();
}

// ---- Ferramentas ---------------------------------------------------------

function escolherFerramenta(nome) {
    // Um campo de texto aberto fica por cima da folha e engole os cliques da
    // area dele. Trocar de ferramenta confirma o que estava escrito e fecha.
    fecharCampoAberto();

    ferramenta = nome;

    // marca o botao ativo e troca o cursor da folha
    botoes.forEach((b) => b.classList.toggle('ativo', b.dataset.ferramenta === nome));
    folha.className = 'folha folha-' + tipoFolha + ' ferramenta-' + nome;

    // textos e imagens so podem ser arrastados no modo "mover"
    camada.find('.texto').forEach((t) => t.draggable(nome === 'mover'));
    camada.find('.imagem').forEach((i) => i.draggable(nome === 'mover'));

    // saiu do modo "mover": esconde as alcas de redimensionar
    if (nome !== 'mover') selecionar(null);
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

// Sem `no`, cria um texto novo em (x, y). Com `no`, edita aquele texto.
function abrirCampoDeTexto(x, y, no) {
    // Ja havia um campo aberto? Confirma o que estava nele antes de abrir o
    // proximo. Nao da para so desistir aqui: o navegador dispara o blur DEPOIS
    // do pointerdown, entao desistir faria o clique seguinte ser engolido -
    // ele fecharia o campo anterior sem abrir o novo.
    fecharCampoAberto();

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
    fecharCampoAberto = () => fechar(true);

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
        fecharCampoAberto = () => {};

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

    // o Konva devolve null quando ainda nao sabe onde o ponteiro esta
    if (pos === null) return;

    if (ferramenta === 'mover') {
        // o Konva cuida do arrastar; aqui so decidimos quem fica selecionado
        const alvo = evento.target;

        if (alvo.getParent() === transformador) return;   // clicou nas proprias alcas

        selecionar(alvo.hasName('imagem') || alvo.hasName('texto') ? alvo : null);
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

// Remove o ultimo elemento. Usado pelo botao e pelo Ctrl+Z.
function desfazer() {
    const filhos = elementosDaCamada();
    if (filhos.length === 0) return;

    selecionar(null);                      // o selecionado pode ser o que vai sumir
    filhos[filhos.length - 1].destroy();    // remove o ultimo elemento
    marcarAlterado();
}

btnDesfazer.addEventListener('click', desfazer);

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

    // o caderno nao pode ficar sem nenhuma pagina
    btnExcluirPag.disabled = paginas.length <= 1;
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

btnExcluirPag.addEventListener('click', async () => {
    if (!confirm('Excluir a página ' + (indicePagina + 1) + '? Tudo que está nela será apagado.')) return;

    try {
        await chamarApi('DELETE', '/api/paginas/' + paginas[indicePagina].id);

        // a lista vem do servidor de novo: as paginas seguintes foram renumeradas
        paginas = await chamarApi('GET', '/api/cadernos/' + cadernoId + '/paginas');

        // fica na mesma posicao; se apagou a ultima, volta uma
        abrirPagina(Math.min(indicePagina, paginas.length - 1));
    } catch (erro) {
        status.textContent = erro.message;
        status.className = 'status-salvar erro';
    }
});

// ---- Excluir o elemento selecionado --------------------------------------

btnExcluirSel.addEventListener('click', excluirSelecao);

// ---- Atalhos de teclado --------------------------------------------------

// Enquanto o usuario escreve num campo, o teclado e dele: Ctrl+Z precisa
// desfazer o que ele digitou, nao o ultimo traco da folha.
function escrevendoNumCampo() {
    const foco = document.activeElement;

    return foco !== null && ['INPUT', 'TEXTAREA', 'SELECT'].includes(foco.tagName);
}

document.addEventListener('keydown', (evento) => {
    if (escrevendoNumCampo()) return;

    // Delete / Backspace: tira o elemento selecionado
    if (evento.key === 'Delete' || evento.key === 'Backspace') {
        if (!elementoSelecionado()) return;

        evento.preventDefault();
        excluirSelecao();
        return;
    }

    // Esc: solta a selecao
    if (evento.key === 'Escape') {
        selecionar(null);
        return;
    }

    // Daqui para baixo, so os atalhos com Ctrl (ou Cmd, no Mac)
    if (!evento.ctrlKey && !evento.metaKey) return;

    const tecla = evento.key.toLowerCase();

    if (tecla === 's') {
        evento.preventDefault();   // senao o navegador abre "salvar pagina"
        salvar();
    } else if (tecla === 'z') {
        evento.preventDefault();
        desfazer();
    }
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

// ---- Exportar a pagina como imagem ---------------------------------------
//
// O Konva so desenha o que o usuario fez: as pautas estao no CSS da div,
// fora do canvas. Para a imagem sair com a folha inteira, desenhamos o
// papel e as pautas numa camada temporaria, exportamos, e tiramos ela.

function criarCamadaDeFundo() {
    const fundo = new Konva.Layer({ listening: false });

    fundo.add(new Konva.Rect({
        x: 0, y: 0, width: LARGURA_FOLHA, height: ALTURA_FOLHA, fill: COR_PAPEL,
    }));

    const linha = (pontos, cor) => fundo.add(new Konva.Line({
        points: pontos, stroke: cor, strokeWidth: 1,
    }));

    // o meio-pixel evita que a linha de 1px saia borrada em dois pixels
    if (tipoFolha === 'pautada' || tipoFolha === 'quadriculada') {
        const cor = tipoFolha === 'pautada' ? COR_PAUTA : COR_QUADRICULA;
        for (let y = ESPACO_PAUTA; y <= ALTURA_FOLHA; y += ESPACO_PAUTA) {
            linha([0, y - 0.5, LARGURA_FOLHA, y - 0.5], cor);
        }
    }

    if (tipoFolha === 'quadriculada') {
        for (let x = ESPACO_PAUTA; x <= LARGURA_FOLHA; x += ESPACO_PAUTA) {
            linha([x - 0.5, 0, x - 0.5, ALTURA_FOLHA], COR_QUADRICULA);
        }
    }

    if (tipoFolha === 'pautada') {
        linha([MARGEM_X + 0.5, 0, MARGEM_X + 0.5, ALTURA_FOLHA], COR_MARGEM);
    }

    return fundo;
}

// "Caderno de Cálculo" + pagina 2 -> caderno-de-calculo-pagina-2.png
function nomeDoArquivo() {
    const titulo = document.querySelector('.lateral-titulo').textContent
        .normalize('NFD').replace(/[̀-ͯ]/g, '')   // tira os acentos
        .replace(/[^a-zA-Z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '')
        .toLowerCase();

    return (titulo || 'caderno') + '-pagina-' + (indicePagina + 1) + '.png';
}

btnExportar.addEventListener('click', () => {
    selecionar(null);   // as alcas de redimensionar nao podem sair na imagem

    const fundo = criarCamadaDeFundo();
    stage.add(fundo);
    fundo.moveToBottom();

    // pixelRatio 2: a imagem sai com o dobro da resolucao, para dar para imprimir
    const imagem = stage.toDataURL({ pixelRatio: 2 });

    fundo.destroy();

    const link = document.createElement('a');
    link.download = nomeDoArquivo();
    link.href = imagem;
    link.click();

    status.textContent = 'Imagem baixada.';
    status.className = 'status-salvar';
});
