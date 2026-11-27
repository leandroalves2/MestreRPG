import * as pdfjsLib from '../pdfjs/pdf.min.mjs';

globalThis.pdfjsLib = pdfjsLib;
const { EventBus, PDFViewer, PDFLinkService, PDFFindController } = await import('../pdfjs/pdf_viewer.mjs');

const dados = document.body.dataset;
const base = new URL('../pdfjs/', import.meta.url).href;
const msg = document.getElementById('leitor-msg');
const container = document.getElementById('container');
const campoPag = document.getElementById('pag-atual');
const totalPag = document.getElementById('pag-total');
const campoBusca = document.getElementById('busca');
const infoBusca = document.getElementById('busca-info');
const seletorZoom = document.getElementById('zoom');
const chaveUltima = 'leitor-pagina-' + dados.id;
const chaveZoom = 'leitor-zoom';
const chaveMini = 'leitor-miniaturas';
const painelMini = document.getElementById('painel-miniaturas');
const botaoMini = document.getElementById('btn-miniaturas');

pdfjsLib.GlobalWorkerOptions.workerSrc = base + 'pdf.worker.min.mjs';

const eventBus = new EventBus();
const linkService = new PDFLinkService({ eventBus });
const findController = new PDFFindController({ eventBus, linkService });
const viewer = new PDFViewer({
    container: container,
    viewer: document.getElementById('viewer'),
    eventBus: eventBus,
    linkService: linkService,
    findController: findController,
});
linkService.setViewer(viewer);

let ultimaConsulta = '';

function buscar(anterior) {
    const consulta = campoBusca.value.trim();
    if (!consulta) {
        infoBusca.textContent = '';
        eventBus.dispatch('find', {
            source: window, type: '', query: '', caseSensitive: false,
            entireWord: false, highlightAll: false, findPrevious: false, matchDiacritics: false,
        });
        ultimaConsulta = '';
        return;
    }
    const nova = consulta !== ultimaConsulta;
    ultimaConsulta = consulta;
    infoBusca.textContent = 'buscando...';
    eventBus.dispatch('find', {
        source: window,
        type: nova ? '' : 'again',
        query: consulta,
        caseSensitive: false,
        entireWord: false,
        highlightAll: true,
        findPrevious: anterior,
        matchDiacritics: false,
    });
}

eventBus.on('updatefindmatchescount', function (e) {
    const c = e.matchesCount;
    infoBusca.textContent = c.total ? c.current + ' de ' + c.total : 'sem resultados';
});

eventBus.on('updatefindcontrolstate', function (e) {
    if (e.matchesCount && e.matchesCount.total) {
        infoBusca.textContent = e.matchesCount.current + ' de ' + e.matchesCount.total;
    } else if (e.state === 3) {
        infoBusca.textContent = 'sem resultados';
    }
});

function marcarMiniatura(n) {
    const atual = painelMini.querySelector('.mini.ativa');
    if (atual) {
        atual.classList.remove('ativa');
    }
    const nova = painelMini.querySelector('.mini[data-pag="' + n + '"]');
    if (nova) {
        nova.classList.add('ativa');
        if (!painelMini.hidden) {
            nova.scrollIntoView({ block: 'nearest' });
        }
    }
}

function guardarZoom(valor) {
    try {
        localStorage.setItem(chaveZoom, String(valor));
    } catch (erro) {
        return;
    }
}

function ajustarZoom(fator) {
    const nova = Math.min(5, Math.max(0.25, viewer.currentScale * fator));
    viewer.currentScale = nova;
}

eventBus.on('scalechanging', function (e) {
    const valor = e.presetValue || String(e.scale);
    guardarZoom(valor);
    const existe = Array.prototype.some.call(seletorZoom.options, function (o) {
        return o.value === valor;
    });
    if (existe) {
        seletorZoom.value = valor;
        return;
    }
    let custom = seletorZoom.querySelector('option[data-custom]');
    if (!custom) {
        custom = document.createElement('option');
        custom.setAttribute('data-custom', '1');
        seletorZoom.appendChild(custom);
    }
    custom.value = valor;
    custom.textContent = Math.round(e.scale * 100) + '%';
    seletorZoom.value = valor;
});

eventBus.on('pagechanging', function (e) {
    marcarMiniatura(e.pageNumber);
    campoPag.value = e.pageNumber;
    try {
        localStorage.setItem(chaveUltima, String(e.pageNumber));
    } catch (erro) {
        campoPag.value = e.pageNumber;
    }
});

function montarIndice(itens, pai) {
    const ul = document.createElement('ul');
    itens.forEach(function (item) {
        const li = document.createElement('li');
        const a = document.createElement('a');
        a.textContent = item.title || '(sem título)';
        a.href = '#';
        a.addEventListener('click', function (ev) {
            ev.preventDefault();
            if (item.dest) {
                linkService.goToDestination(item.dest);
            }
        });
        li.appendChild(a);
        if (item.items && item.items.length) {
            li.appendChild(montarIndice(item.items, li));
        }
        ul.appendChild(li);
    });
    return ul;
}

function desenharMiniatura(pdf, el) {
    const n = parseInt(el.dataset.pag, 10);
    const folha = el.querySelector('.mini-folha');
    pdf.getPage(n).then(function (pagina) {
        const largura = 130;
        const dpr = window.devicePixelRatio || 1;
        const base = pagina.getViewport({ scale: 1 });
        const vp = pagina.getViewport({ scale: (largura / base.width) * dpr });
        const canvas = document.createElement('canvas');
        canvas.width = Math.floor(vp.width);
        canvas.height = Math.floor(vp.height);
        canvas.style.width = largura + 'px';
        canvas.style.height = Math.round(vp.height / dpr) + 'px';
        folha.style.height = canvas.style.height;
        folha.appendChild(canvas);
        return pagina.render({ canvasContext: canvas.getContext('2d'), viewport: vp }).promise;
    });
}

async function montarMiniaturas(pdf) {
    const primeira = await pdf.getPage(1);
    const base = primeira.getViewport({ scale: 1 });
    const altura = Math.round(base.height * (130 / base.width));
    const obs = new IntersectionObserver(function (entradas) {
        entradas.forEach(function (en) {
            if (en.isIntersecting) {
                obs.unobserve(en.target);
                desenharMiniatura(pdf, en.target);
            }
        });
    }, { root: painelMini, rootMargin: '400px' });
    for (let n = 1; n <= pdf.numPages; n++) {
        const item = document.createElement('div');
        item.className = 'mini';
        item.dataset.pag = String(n);
        item.innerHTML = '<div class="mini-folha" style="width:130px;height:' + altura + 'px"></div><span>' + n + '</span>';
        item.addEventListener('click', function () {
            linkService.goToPage(n);
        });
        painelMini.appendChild(item);
        obs.observe(item);
    }
    marcarMiniatura(viewer.currentPageNumber || 1);
}

let miniAberto = true;
try {
    miniAberto = localStorage.getItem(chaveMini) !== '0';
} catch (erro) {
    miniAberto = true;
}
painelMini.hidden = !miniAberto;
botaoMini.addEventListener('click', function () {
    painelMini.hidden = !painelMini.hidden;
    try {
        localStorage.setItem(chaveMini, painelMini.hidden ? '0' : '1');
    } catch (erro) {
        return;
    }
    if (!painelMini.hidden) {
        marcarMiniatura(viewer.currentPageNumber || 1);
    }
});

try {
    const pdf = await pdfjsLib.getDocument({
        url: dados.pdf,
        wasmUrl: base + 'wasm/',
        standardFontDataUrl: base + 'standard_fonts/',
        iccUrl: base + 'iccs/',
    }).promise;

    viewer.setDocument(pdf);
    linkService.setDocument(pdf, null);
    totalPag.textContent = pdf.numPages;
    campoPag.max = pdf.numPages;
    msg.hidden = true;

    let paginaInicial = parseInt(dados.pag, 10) || 0;
    if (!paginaInicial) {
        try {
            paginaInicial = parseInt(localStorage.getItem(chaveUltima), 10) || 1;
        } catch (erro) {
            paginaInicial = 1;
        }
    }
    paginaInicial = Math.min(Math.max(1, paginaInicial), pdf.numPages);

    eventBus.on('pagesinit', function () {
        let zoomSalvo = '';
        try {
            zoomSalvo = localStorage.getItem(chaveZoom) || '';
        } catch (erro) {
            zoomSalvo = '';
        }
        viewer.currentScaleValue = zoomSalvo || seletorZoom.value;
        viewer.currentPageNumber = paginaInicial;
        if (dados.q) {
            campoBusca.value = dados.q;
            buscar(false);
        }
    });

    montarMiniaturas(pdf);

    pdf.getOutline().then(function (indice) {
        if (indice && indice.length) {
            const painel = document.getElementById('painel-indice');
            painel.appendChild(montarIndice(indice));
            const botao = document.getElementById('btn-indice');
            botao.hidden = false;
            botao.addEventListener('click', function () {
                painel.hidden = !painel.hidden;
            });
        }
    });
} catch (erro) {
    msg.textContent = 'Não foi possível abrir este PDF: ' + (erro && erro.message ? erro.message : erro);
}

document.getElementById('btn-ant').addEventListener('click', function () {
    viewer.previousPage();
});
document.getElementById('btn-prox').addEventListener('click', function () {
    viewer.nextPage();
});
campoPag.addEventListener('change', function () {
    const n = parseInt(campoPag.value, 10);
    if (n) {
        linkService.goToPage(n);
    }
});
seletorZoom.addEventListener('change', function () {
    viewer.currentScaleValue = seletorZoom.value;
});
document.getElementById('zoom-mais').addEventListener('click', function () {
    ajustarZoom(1.15);
});
document.getElementById('zoom-menos').addEventListener('click', function () {
    ajustarZoom(1 / 1.15);
});
document.getElementById('busca-prox').addEventListener('click', function () {
    buscar(false);
});
document.getElementById('busca-ant').addEventListener('click', function () {
    buscar(true);
});
campoBusca.addEventListener('keydown', function (ev) {
    if (ev.key === 'Enter') {
        ev.preventDefault();
        buscar(ev.shiftKey);
    }
});
campoBusca.addEventListener('search', function () {
    if (!campoBusca.value) {
        buscar(false);
    }
});
document.addEventListener('keydown', function (ev) {
    if ((ev.ctrlKey || ev.metaKey) && ev.key.toLowerCase() === 'f') {
        ev.preventDefault();
        campoBusca.focus();
        campoBusca.select();
    }
});

container.addEventListener('wheel', function (ev) {
    if (!ev.ctrlKey && !ev.metaKey) {
        return;
    }
    ev.preventDefault();
    ajustarZoom(Math.min(1.25, Math.max(0.8, Math.exp(-ev.deltaY * 0.005))));
}, { passive: false });

document.addEventListener('keydown', function (ev) {
    if (!(ev.ctrlKey || ev.metaKey)) {
        return;
    }
    if (ev.key === '+' || ev.key === '=') {
        ev.preventDefault();
        ajustarZoom(1.15);
    } else if (ev.key === '-' || ev.key === '_') {
        ev.preventDefault();
        ajustarZoom(1 / 1.15);
    } else if (ev.key === '0') {
        ev.preventDefault();
        viewer.currentScaleValue = 'page-width';
    }
});
