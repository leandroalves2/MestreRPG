import * as pdfjsLib from '../pdfjs/pdf.min.mjs';

const dados = document.getElementById('indexador').dataset;
const base = new URL('../pdfjs/', import.meta.url).href;
const barra = document.getElementById('progresso');
const status = document.getElementById('status');

pdfjsLib.GlobalWorkerOptions.workerSrc = base + 'pdf.worker.min.mjs';

function proximo() {
    const fila = dados.fila ? dados.fila.split(',').filter(Boolean) : [];
    if (fila.length) {
        const prox = fila.shift();
        window.location.href = dados.indexar + '&id=' + prox + '&fila=' + fila.join(',');
    } else {
        window.location.href = dados.biblioteca;
    }
}

try {
    const pdf = await pdfjsLib.getDocument({
        url: dados.pdf,
        wasmUrl: base + 'wasm/',
        standardFontDataUrl: base + 'standard_fonts/',
        iccUrl: base + 'iccs/',
    }).promise;

    const total = pdf.numPages;
    const paginas = [];
    let caracteres = 0;
    barra.max = total;

    for (let i = 1; i <= total; i++) {
        const pagina = await pdf.getPage(i);
        const conteudo = await pagina.getTextContent();
        let texto = '';
        conteudo.items.forEach(function (item) {
            texto += item.str;
            if (item.hasEOL) {
                texto += '\n';
            }
        });
        caracteres += texto.length;
        paginas.push(texto);
        pagina.cleanup();
        barra.value = i;
        status.textContent = 'Lendo página ' + i + ' de ' + total + '...';
    }

    status.textContent = 'Salvando o índice...';
    const form = new FormData();
    form.append('_csrf', dados.csrf);
    form.append('id', dados.id);
    form.append('paginas', JSON.stringify(paginas));
    const resposta = await fetch(dados.envio, { method: 'POST', body: form });
    const resultado = await resposta.json();
    if (!resultado.ok) {
        throw new Error('o servidor recusou o índice');
    }

    if (caracteres < 200) {
        status.textContent = 'Pronto, mas este PDF parece ser escaneado (quase sem texto). A busca por palavra não vai funcionar nele.';
        await new Promise(function (r) { setTimeout(r, 3500); });
    } else {
        status.textContent = 'Pronto: ' + total + ' páginas indexadas.';
    }
    proximo();
} catch (erro) {
    status.textContent = 'Falha ao indexar: ' + (erro && erro.message ? erro.message : erro);
}
