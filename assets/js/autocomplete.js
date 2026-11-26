/**
 * BIOSTECH - autocomplete simples (clientes, produtos e fornecedores).
 *
 * Marcacao:
 *   <div class="autocomplete" data-url="..."
 *        [data-preco="alvo"] [data-custo="alvo"]
 *        [data-estoque="alvo"] [data-unidade="alvo"]>
 *       <input type="text" class="autocomplete-busca">
 *       <input type="hidden" name="...">
 *       <div class="autocomplete-lista"></div>
 *   </div>
 *
 * Os alvos podem ser:
 *   - id do campo:        "valor_unitario"
 *   - seletor com # ou .: "#valor_unitario" / ".valor-unitario"
 *   - seletor de nome:    "[name='valor_unitario[]']"
 * Quando o seletor nao e global, a busca acontece na linha mais proxima
 * (tr, .linha-item) - util para formularios multi-item.
 *
 * API publica:
 *   BiostechAutocomplete.montar(box)
 *   BiostechAutocomplete.montarTudo(root)
 */
(function () {
    'use strict';

    function textoItem(item) {
        if (item.nome) {
            return item.nome;
        }
        return item.descricao || '';
    }

    function detalheItem(item) {
        var partes = [];

        if (item.cpf_cnpj) {
            partes.push(item.cpf_cnpj);
        }
        if (item.cidade) {
            partes.push(item.cidade + (item.uf ? '/' + item.uf : ''));
        }
        if (item.codigo) {
            partes.push('codigo ' + item.codigo);
        }
        if (item.unidade) {
            partes.push(item.unidade);
        }
        if (item.valor_venda !== undefined && item.valor_venda !== null) {
            partes.push('venda ' + Number(item.valor_venda).toFixed(2).replace('.', ','));
            if (Number(item.especial) === 1) {
                partes.push('preco especial');
            }
        }
        if (item.valor_compra !== undefined && item.valor_compra !== null) {
            partes.push('custo ' + Number(item.valor_compra).toFixed(2).replace('.', ','));
        }
        if (item.saldo !== undefined && item.saldo !== null) {
            partes.push('estoque ' + item.saldo);
        }

        return partes.join(' · ');
    }

    function limpar(lista) {
        lista.innerHTML = '';
        lista.style.display = 'none';
    }

    /**
     * Listas dentro de tabelas com rolagem sao fixadas no viewport para nao
     * ficarem cortadas pelo container da tabela.
     */
    function posicionar(box, busca, lista) {
        var pai = box.parentElement;

        while (pai && pai !== document.body) {
            var estilo = window.getComputedStyle(pai);

            if (/(auto|scroll)/.test(estilo.overflowY)) {
                var r = busca.getBoundingClientRect();

                lista.style.position = 'fixed';
                lista.style.top = (r.bottom + 3) + 'px';
                lista.style.left = r.left + 'px';
                lista.style.right = 'auto';
                lista.style.width = r.width + 'px';
                lista.style.maxHeight = Math.min(260, Math.max(120, window.innerHeight - r.bottom - 24)) + 'px';
                lista.style.zIndex = '1000';

                if (box.getAttribute('data-scroll-listener') !== '1') {
                    box.setAttribute('data-scroll-listener', '1');
                    pai.addEventListener('scroll', function () { limpar(lista); }, { passive: true });
                }
                return;
            }

            pai = pai.parentElement;
        }

        lista.style.position = '';
        lista.style.top = '';
        lista.style.left = '';
        lista.style.right = '';
        lista.style.width = '';
        lista.style.maxHeight = '';
        lista.style.zIndex = '';
    }

    function acharCampo(box, alvo) {
        if (!alvo) {
            return null;
        }

        var escopo = box.closest('tr, .linha-item, .item') || document;
        var el = null;

        if (alvo.charAt(0) === '#') {
            return document.getElementById(alvo.slice(1));
        }

        if (alvo.charAt(0) === '.' || alvo.charAt(0) === '[') {
            el = escopo.querySelector(alvo);
            return el || document.querySelector(alvo);
        }

        el = document.getElementById(alvo);

        if (el) {
            return el;
        }

        el = escopo.querySelector('[name="' + alvo + '"]');

        if (el) {
            return el;
        }

        return escopo.querySelector('[name="' + alvo + '[]"]');
    }

    function definir(el, valor) {
        if (!el || valor === undefined || valor === null) {
            return;
        }

        el.value = valor;
    }

    function montar(box) {
        if (!box || box.getAttribute('data-montado') === '1') {
            return;
        }

        var url = box.getAttribute('data-url');
        var busca = box.querySelector('.autocomplete-busca');
        var oculto = box.querySelector('input[type=hidden]');
        var lista = box.querySelector('.autocomplete-lista');
        var timer = null;
        var ignorar = false;

        if (!url || !busca || !oculto || !lista) {
            return;
        }

        box.setAttribute('data-montado', '1');

        function selecionar(item) {
            oculto.value = item.id;
            busca.value = textoItem(item);
            ignorar = true;

            var preco = acharCampo(box, box.getAttribute('data-preco'));
            if (preco && item.valor_venda !== undefined && item.valor_venda !== null) {
                preco.value = Number(item.valor_venda).toFixed(2);
            }

            var custo = acharCampo(box, box.getAttribute('data-custo'));
            if (custo && item.valor_compra !== undefined && item.valor_compra !== null) {
                custo.value = Number(item.valor_compra).toFixed(2);
            }

            var estoque = acharCampo(box, box.getAttribute('data-estoque'));
            if (estoque) {
                estoque.value = item.saldo !== undefined && item.saldo !== null ? item.saldo : '';
            }

            var unidade = acharCampo(box, box.getAttribute('data-unidade'));
            if (unidade) {
                unidade.value = item.unidade !== undefined && item.unidade !== null ? item.unidade : '';
            }

            [['data-cidade', 'cidade'], ['data-uf', 'uf'], ['data-endereco', 'endereco'],
             ['data-bairro', 'bairro'], ['data-numero', 'endereco_numero'],
             ['data-cep', 'cep'], ['data-fone', 'fone']].forEach(function (par) {
                var alvo = acharCampo(box, box.getAttribute(par[0]));
                if (alvo) { alvo.value = item[par[1]] || ''; }
            });

            limpar(lista);
            busca.focus();

            if (typeof box.biostechAoSelecionar === 'function') {
                box.biostechAoSelecionar(item);
            }

            box.dispatchEvent(new CustomEvent('autocomplete:selecionar', { detail: item, bubbles: true }));
        }

        function desenhar(itens) {
            lista.innerHTML = '';
            posicionar(box, busca, lista);

            if (!itens.length) {
                var vazio = document.createElement('div');
                vazio.className = 'autocomplete-vazio';
                vazio.textContent = 'Nenhum resultado.';
                lista.appendChild(vazio);
                lista.style.display = 'block';
                return;
            }

            itens.forEach(function (item) {
                var opcao = document.createElement('div');
                opcao.className = 'autocomplete-opcao';

                var principal = document.createElement('strong');
                principal.textContent = textoItem(item);
                opcao.appendChild(principal);

                var detalhe = detalheItem(item);
                if (detalhe) {
                    var pequeno = document.createElement('span');
                    pequeno.textContent = detalhe;
                    opcao.appendChild(pequeno);
                }

                opcao.addEventListener('mousedown', function (ev) {
                    ev.preventDefault();
                    selecionar(item);
                });

                lista.appendChild(opcao);
            });

            lista.style.display = 'block';
        }

        function consultar() {
            var termo = busca.value.trim();

            if (termo.length < 2) {
                limpar(lista);
                return;
            }

            fetch(url + (url.indexOf('?') === -1 ? '?' : '&') + 'termo=' + encodeURIComponent(termo), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            })
                .then(function (r) { return r.json(); })
                .then(function (dados) { desenhar(Array.isArray(dados) ? dados : []); })
                .catch(function () { limpar(lista); });
        }

        busca.addEventListener('input', function () {
            if (ignorar) {
                ignorar = false;
            }

            oculto.value = '';
            clearTimeout(timer);
            timer = setTimeout(consultar, 250);
        });

        busca.addEventListener('keydown', function (ev) {
            if (ev.key === 'Escape') {
                limpar(lista);
            }
        });

        document.addEventListener('mousedown', function (ev) {
            if (!box.contains(ev.target)) {
                limpar(lista);
            }
        });
    }

    function montarTudo(root) {
        var escopo = root || document;
        var caixas = escopo.querySelectorAll('.autocomplete[data-url]');

        for (var i = 0; i < caixas.length; i++) {
            montar(caixas[i]);
        }
    }

    window.BiostechAutocomplete = {
        montar: montar,
        montarTudo: montarTudo
    };

    document.addEventListener('DOMContentLoaded', function () {
        montarTudo(document);
    });
})();
