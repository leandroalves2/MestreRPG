document.addEventListener('click', function (ev) {
    var alvo = ev.target;

    var zoom = alvo.closest('img.zoom');
    if (zoom) {
        var box = document.getElementById('lightbox');
        box.querySelector('img').src = zoom.src;
        box.querySelector('p').textContent = zoom.alt || '';
        box.hidden = false;
        return;
    }

    var caixa = alvo.closest('#lightbox');
    if (caixa) {
        if (alvo.tagName === 'IMG') {
            caixa.classList.toggle('cheia');
        } else {
            caixa.hidden = true;
            caixa.classList.remove('cheia');
        }
        return;
    }

    var add = alvo.closest('.add-par');
    if (add) {
        var conj = add.closest('.pares');
        var modelo = conj.querySelector('template').content.cloneNode(true);
        conj.querySelector('.pares-linhas').appendChild(modelo);
        var campos = conj.querySelectorAll('.pares-linhas .par:last-child input');
        if (campos[0]) {
            campos[0].focus();
        }
        return;
    }

    var rm = alvo.closest('.rm-par');
    if (rm) {
        rm.closest('.par').remove();
    }
});

document.addEventListener('keydown', function (ev) {
    if (ev.key === 'Escape') {
        var caixa = document.getElementById('lightbox');
        caixa.hidden = true;
        caixa.classList.remove('cheia');
    }
});

document.addEventListener('submit', function (ev) {
    var msg = ev.target.getAttribute('data-confirma');
    if (msg && !window.confirm(msg)) {
        ev.preventDefault();
    }
});

document.addEventListener('input', function (ev) {
    var campo = ev.target.closest('[data-filtro]');
    if (!campo) {
        return;
    }
    var termo = campo.value.trim().toLowerCase();
    document.querySelectorAll(campo.getAttribute('data-filtro') + ' .card').forEach(function (card) {
        card.style.display = card.getAttribute('data-busca').indexOf(termo) === -1 ? 'none' : '';
    });
});

(function () {
    var painel = document.getElementById('painel-dados');
    var log = document.getElementById('dado-log');
    var qtd = document.getElementById('dado-qtd');
    var mod = document.getElementById('dado-mod');

    document.getElementById('abrir-dados').addEventListener('click', function () {
        painel.hidden = !painel.hidden;
    });
    document.getElementById('fechar-dados').addEventListener('click', function () {
        painel.hidden = true;
    });

    document.querySelectorAll('[data-dado]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var lados = parseInt(btn.getAttribute('data-dado'), 10);
            var n = Math.min(50, Math.max(1, parseInt(qtd.value, 10) || 1));
            var bonus = parseInt(mod.value, 10) || 0;
            var buf = new Uint32Array(n);
            window.crypto.getRandomValues(buf);
            var rolagens = Array.prototype.map.call(buf, function (x) {
                return (x % lados) + 1;
            });
            var soma = rolagens.reduce(function (a, b) {
                return a + b;
            }, 0);
            var total = soma + bonus;
            var rotulo = n + 'd' + lados + (bonus ? (bonus > 0 ? '+' : '') + bonus : '');
            var li = document.createElement('li');
            li.innerHTML = '<span>' + rotulo + '</span> <small>[' + rolagens.join(', ') + ']</small> <strong>' + total + '</strong>';
            log.insertBefore(li, log.firstChild);
            while (log.children.length > 12) {
                log.removeChild(log.lastChild);
            }
        });
    });
})();

document.addEventListener('change', function (ev) {
    var campo = ev.target;
    var zona = campo.closest('.dropzone');
    if (zona && campo.type === 'file' && campo.files.length) {
        zona.classList.add('enviando');
        zona.querySelector('p').innerHTML = '<strong>Enviando ' + campo.files.length + ' arquivo(s)...</strong>';
        zona.submit();
    }
});

document.addEventListener('dragover', function (ev) {
    var zona = ev.target.closest ? ev.target.closest('.dropzone') : null;
    document.querySelectorAll('.dropzone.sobre').forEach(function (z) {
        if (z !== zona) {
            z.classList.remove('sobre');
        }
    });
    if (zona) {
        zona.classList.add('sobre');
    }
});

document.addEventListener('dragleave', function (ev) {
    if (!ev.relatedTarget) {
        document.querySelectorAll('.dropzone.sobre').forEach(function (z) {
            z.classList.remove('sobre');
        });
    }
});

document.addEventListener('drop', function () {
    document.querySelectorAll('.dropzone.sobre').forEach(function (z) {
        z.classList.remove('sobre');
    });
});

(function () {
    var lateral = document.querySelector('.lateral[data-campanha]');
    if (lateral) {
        var campanha = lateral.getAttribute('data-campanha');
        var chave = 'grimorio-aberto-' + campanha;
        var chaveRolagem = 'grimorio-rolagem-' + campanha;
        var todos = function () {
            return lateral.querySelectorAll('details[data-tipo], details[data-pasta]');
        };
        var idDe = function (d) {
            return (d.hasAttribute('data-pasta') ? 'p:' : 't:') + (d.getAttribute('data-pasta') || d.getAttribute('data-tipo'));
        };
        var salvar = function () {
            var abertos = [];
            todos().forEach(function (d) {
                if (d.open) {
                    abertos.push(idDe(d));
                }
            });
            try {
                localStorage.setItem(chave, JSON.stringify(abertos));
            } catch (e) {}
        };

        try {
            var guardado = JSON.parse(localStorage.getItem(chave) || '[]');
            todos().forEach(function (d) {
                d.open = guardado.indexOf(idDe(d)) !== -1;
            });
            var rolagem = parseInt(sessionStorage.getItem(chaveRolagem) || '0', 10);
            if (rolagem > 0) {
                lateral.scrollTop = rolagem;
            }
        } catch (e) {}

        lateral.addEventListener('toggle', salvar, true);

        lateral.addEventListener('scroll', function () {
            try {
                sessionStorage.setItem(chaveRolagem, String(lateral.scrollTop));
            } catch (e) {}
        });

        lateral.addEventListener('click', function (ev) {
            var botao = ev.target.closest ? ev.target.closest('[data-lateral-todos]') : null;
            if (!botao) {
                return;
            }
            var abrir = botao.getAttribute('data-lateral-todos') === 'abrir';
            todos().forEach(function (d) {
                d.open = abrir;
            });
            salvar();
        });
    }

    var lista = document.getElementById('lista');
    if (!lista || !lista.getAttribute('data-reordenar')) {
        return;
    }
    var arrastado = null;
    var grade = null;

    lista.addEventListener('dragstart', function (ev) {
        var card = ev.target.closest ? ev.target.closest('.card[data-id]') : null;
        if (!card) {
            return;
        }
        arrastado = card;
        grade = card.parentNode;
        ev.dataTransfer.effectAllowed = 'move';
        ev.dataTransfer.setData('text/plain', card.getAttribute('data-id'));
        setTimeout(function () {
            card.classList.add('arrastando');
        }, 0);
    });

    lista.addEventListener('dragover', function (ev) {
        if (!arrastado) {
            return;
        }
        var alvo = ev.target.closest ? ev.target.closest('.card[data-id]') : null;
        if (!alvo || alvo === arrastado || alvo.parentNode !== grade) {
            return;
        }
        ev.preventDefault();
        ev.dataTransfer.dropEffect = 'move';
        var r = alvo.getBoundingClientRect();
        var depois = ev.clientX > r.left + r.width / 2;
        var ref = depois ? alvo.nextSibling : alvo;
        if (ref !== arrastado) {
            grade.insertBefore(arrastado, ref);
        }
    });

    lista.addEventListener('drop', function (ev) {
        if (arrastado) {
            ev.preventDefault();
        }
    });

    lista.addEventListener('dragend', function () {
        if (!arrastado) {
            return;
        }
        arrastado.classList.remove('arrastando');
        var ids = [];
        grade.querySelectorAll('.card[data-id]').forEach(function (c) {
            ids.push(c.getAttribute('data-id'));
        });
        arrastado = null;
        var corpo = new URLSearchParams();
        corpo.append('_csrf', lista.getAttribute('data-csrf'));
        ids.forEach(function (id) {
            corpo.append('ids[]', id);
        });
        fetch(lista.getAttribute('data-reordenar'), { method: 'POST', body: corpo })
            .then(function (r) {
                return r.json();
            })
            .then(function () {
                var aviso = document.createElement('div');
                aviso.className = 'salvo-aviso';
                aviso.textContent = 'Ordem salva';
                document.body.appendChild(aviso);
                setTimeout(function () {
                    aviso.remove();
                }, 1400);
            });
    });
})();

document.addEventListener('change', function (ev) {
    var campo = ev.target;
    if (campo.matches && campo.matches('input[type=file][data-auto]') && campo.files && campo.files.length) {
        campo.form.submit();
    }
});

document.addEventListener('click', function (ev) {
    var alvo = ev.target.closest ? ev.target.closest('[data-imprimir]') : null;
    if (alvo) {
        window.print();
    }
});

document.addEventListener('click', function (ev) {
    var botao = ev.target.closest ? ev.target.closest('[data-aba-alvo]') : null;
    if (!botao) {
        return;
    }
    var alvo = botao.getAttribute('data-aba-alvo');
    document.querySelectorAll('[data-aba-alvo]').forEach(function (b) {
        b.classList.toggle('ativa', b === botao);
    });
    document.querySelectorAll('.aba-painel').forEach(function (p) {
        p.hidden = p.getAttribute('data-aba') !== alvo;
    });
});

(function () {
    var lateral = document.querySelector('.lateral[data-reordenar]');
    if (!lateral) {
        return;
    }
    var arrastado = null;
    var pai = null;
    var mudou = false;

    lateral.addEventListener('dragstart', function (ev) {
        var item = ev.target.closest ? ev.target.closest('.lateral-reg[data-id]') : null;
        if (!item) {
            return;
        }
        arrastado = item;
        pai = item.parentNode;
        mudou = false;
        ev.dataTransfer.effectAllowed = 'move';
        ev.dataTransfer.setData('text/plain', item.getAttribute('data-id'));
        setTimeout(function () {
            item.classList.add('arrastando');
        }, 0);
    });

    lateral.addEventListener('dragover', function (ev) {
        if (!arrastado) {
            return;
        }
        var alvo = ev.target.closest ? ev.target.closest('.lateral-reg[data-id]') : null;
        if (!alvo || alvo === arrastado || alvo.parentNode !== pai) {
            return;
        }
        ev.preventDefault();
        ev.dataTransfer.dropEffect = 'move';
        var r = alvo.getBoundingClientRect();
        var depois = ev.clientY > r.top + r.height / 2;
        var ref = depois ? alvo.nextSibling : alvo;
        if (ref !== arrastado) {
            pai.insertBefore(arrastado, ref);
            mudou = true;
        }
    });

    lateral.addEventListener('drop', function (ev) {
        if (arrastado) {
            ev.preventDefault();
        }
    });

    lateral.addEventListener('dragend', function () {
        if (!arrastado) {
            return;
        }
        arrastado.classList.remove('arrastando');
        arrastado = null;
        if (!mudou) {
            return;
        }
        var corpo = new URLSearchParams();
        corpo.append('_csrf', lateral.getAttribute('data-csrf'));
        pai.querySelectorAll(':scope > .lateral-reg[data-id]').forEach(function (c) {
            corpo.append('ids[]', c.getAttribute('data-id'));
        });
        fetch(lateral.getAttribute('data-reordenar'), { method: 'POST', body: corpo })
            .then(function (r) {
                return r.json();
            })
            .then(function () {
                var aviso = document.createElement('div');
                aviso.className = 'salvo-aviso';
                aviso.textContent = 'Ordem salva';
                document.body.appendChild(aviso);
                setTimeout(function () {
                    aviso.remove();
                }, 1400);
            });
    });
})();

document.addEventListener('click', function (ev) {
    var alvo = ev.target.closest ? ev.target.closest('[data-imprimir-cidade]') : null;
    if (!alvo) {
        return;
    }
    document.body.classList.add('imprimir-cidade');
    window.print();
    setTimeout(function () {
        document.body.classList.remove('imprimir-cidade');
    }, 500);
});

window.addEventListener('afterprint', function () {
    document.body.classList.remove('imprimir-cidade');
});
