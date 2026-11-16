/**
 * BIOSTECH - autocomplete simples (clientes e produtos).
 *
 * Marcação:
 *   <div class="autocomplete" data-url="..." [data-preco="idDoCampo"] [data-estoque="idDoCampo"]>
 *       <input type="text" class="autocomplete-busca">
 *       <input type="hidden" name="...">
 *       <div class="autocomplete-lista"></div>
 *   </div>
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
        if (item.valor_venda !== undefined && item.valor_venda !== null) {
            partes.push('R$ ' + Number(item.valor_venda).toFixed(2).replace('.', ','));
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

    function montar(box) {
        var url = box.getAttribute('data-url');
        var busca = box.querySelector('.autocomplete-busca');
        var oculto = box.querySelector('input[type=hidden]');
        var lista = box.querySelector('.autocomplete-lista');
        var campoPreco = box.getAttribute('data-preco');
        var campoEstoque = box.getAttribute('data-estoque');
        var timer = null;
        var ignorar = false;

        if (!url || !busca || !oculto || !lista) {
            return;
        }

        function selecionar(item) {
            oculto.value = item.id;
            busca.value = textoItem(item);
            ignorar = true;

            if (campoPreco) {
                var preco = document.getElementById(campoPreco);
                if (preco && item.valor_venda !== undefined && item.valor_venda !== null) {
                    preco.value = Number(item.valor_venda).toFixed(2);
                }
            }

            if (campoEstoque) {
                var est = document.getElementById(campoEstoque);
                if (est) {
                    est.value = item.saldo !== undefined ? item.saldo : '';
                }
            }

            limpar(lista);
            busca.focus();
        }

        function desenhar(itens) {
            lista.innerHTML = '';

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

    document.addEventListener('DOMContentLoaded', function () {
        var caixas = document.querySelectorAll('.autocomplete[data-url]');
        for (var i = 0; i < caixas.length; i++) {
            montar(caixas[i]);
        }
    });
})();
