<?php
$classeCorpo = 'tela-editor';
require __DIR__ . '/partials/cabecalho.php';
?>
<main class="editor"
      id="editor"
      data-caderno-id="<?= (int) $caderno['id'] ?>"
      data-tipo-folha="<?= escapar($caderno['tipo_folha']) ?>">

    <!-- Lateral: nome do caderno, ferramentas e paginas -->
    <aside class="lateral">
        <a class="lateral-voltar" href="<?= urlBase() . '/cadernos' ?>">&lsaquo; Meus cadernos</a>
        <h1 class="lateral-titulo"><?= escapar($caderno['titulo']) ?></h1>

        <div class="ferramentas">
            <button type="button" class="ferramenta" data-ferramenta="texto" title="Clique na folha para escrever">
                <span class="ferramenta-icone ferramenta-icone-t">T</span>
                Texto
            </button>

            <button type="button" class="ferramenta" data-ferramenta="imagem" disabled title="Em breve">
                <svg viewBox="0 0 24 24" width="34" height="34" fill="currentColor">
                    <path d="M3 4h18v16H3zM5 6v9l4-4 4 4 3-3 3 3V6zm11 2a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3z"/>
                </svg>
                Anexar imagem
            </button>

            <button type="button" class="ferramenta ativo" data-ferramenta="caneta" title="Desenhar">
                <svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M4 20l1-4L17 4l3 3L8 19l-4 1z"/>
                    <path d="M14 7l3 3"/>
                </svg>
                Caneta
            </button>

            <button type="button" class="ferramenta" data-ferramenta="lapis" title="Traço leve">
                <svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M4 20l1-4L15 6l3 3L8 19l-4 1z" stroke-dasharray="3 2"/>
                </svg>
                Lápis
            </button>

            <button type="button" class="ferramenta" data-ferramenta="borracha" title="Apagar traços">
                <svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M4 15l8-8 6 6-6 6H8l-4-4z"/>
                    <path d="M8 11l6 6"/>
                </svg>
                Borracha
            </button>

            <button type="button" class="ferramenta" data-ferramenta="mover" title="Selecionar e arrastar textos">
                <svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M6 3l12 9-5 1 3 6-2 1-3-6-4 4z"/>
                </svg>
                Mover
            </button>
        </div>

        <div class="opcoes-traco">
            <label>Cor <input type="color" id="cor" value="#222222"></label>
            <label>Espessura <input type="range" id="espessura" min="1" max="20" value="3"></label>
        </div>

        <div class="acoes-pagina">
            <button type="button" class="btn-lateral" id="btn-desfazer">Desfazer</button>
            <button type="button" class="btn-lateral" id="btn-limpar">Limpar página</button>
            <button type="button" class="btn-principal btn-pequeno" id="btn-salvar">Salvar</button>
            <span class="status-salvar" id="status-salvar"></span>
        </div>
    </aside>

    <!-- Area da folha -->
    <section class="area-folha">
        <nav class="barra-paginas">
            <button type="button" class="btn-pagina" id="btn-pagina-anterior" title="Página anterior">&lsaquo;</button>
            <span id="rotulo-pagina">Página –</span>
            <button type="button" class="btn-pagina" id="btn-pagina-proxima" title="Próxima página">&rsaquo;</button>
            <button type="button" class="btn-pagina btn-pagina-nova" id="btn-pagina-nova">+ Nova página</button>
        </nav>

        <!-- O Konva desenha dentro desta div; as pautas vem do CSS (.folha-pautada etc.) -->
        <div class="folha folha-<?= escapar($caderno['tipo_folha']) ?>" id="folha"></div>
    </section>
</main>

<script src="https://unpkg.com/konva@9/konva.min.js"></script>
<?php
$scripts = ['editor.js'];
require __DIR__ . '/partials/rodape.php';
