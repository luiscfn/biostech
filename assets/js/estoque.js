/**
 * BIOSTECH - linhas dinamicas de itens do Estoque (entrada/saida).
 *
 * Marcacao:
 *   <table class="tabela tabela-itens" data-itens data-template="linha-entrada"
 *          data-total="total-entrada">
 *       <tbody></tbody>
 *   </table>
 *   <template id="linha-entrada"> ... <tr class="linha-item"> ... </tr> ... </template>
 *
 * Cada linha precisa ter:
 *   .autocomplete-busca, input[name="produto_id[]"], input[name="quantidade[]"],
 *   input[name="custo[]"], .total-linha e o botao .remover-item.
 */
(function () {
    'use strict';

    function numero(valor) {
        if (typeof valor === 'number') {
            return valor;
        }

        var texto = String(valor === undefined || valor === null ? '' : valor).trim();

        if (!texto) {
            return 0;
        }

        texto = texto.replace(/\s/g, '').replace(/\./g, '').replace(',', '.');

        var n = parseFloat(texto);
        return isNaN(n) ? 0 : n;
    }

    function formatar(valor) {
        return Number(valor).toFixed(2).replace('.', ',');
    }

    function linhas(tabela) {
        return tabela.querySelectorAll('tbody tr.linha-item');
    }

    function renumerar(tabela) {
        var itens = linhas(tabela);

        for (var i = 0; i < itens.length; i++) {
            var celula = itens[i].querySelector('.numero-item');

            if (celula) {
                celula.textContent = i + 1;
            }
        }
    }

    function recalcular(tabela) {
        var itens = linhas(tabela);
        var total = 0;

        for (var i = 0; i < itens.length; i++) {
            var linha = itens[i];
            var qtd = linha.querySelector('input[name="quantidade[]"]');
            var custo = linha.querySelector('input[name="custo[]"]');
            var subtotal = numero(qtd ? qtd.value : 0) * numero(custo ? custo.value : 0);

            total += subtotal;

            var celula = linha.querySelector('.total-linha');

            if (celula) {
                celula.textContent = formatar(subtotal);
            }
        }

        var alvo = tabela.getAttribute('data-total');
        var saida = alvo ? document.getElementById(alvo) : null;

        if (saida) {
            saida.textContent = formatar(total);
        }
    }

    function adicionar(tabela) {
        var idTemplate = tabela.getAttribute('data-template');
        var template = document.getElementById(idTemplate);

        if (!template) {
            return;
        }

        var corpo = tabela.querySelector('tbody');

        if (!corpo) {
            return;
        }

        var fragmento = template.content.cloneNode(true);
        var linha = fragmento.querySelector('tr.linha-item');

        if (!linha) {
            return;
        }

        corpo.appendChild(linha);
        renumerar(tabela);

        if (window.BiostechAutocomplete) {
            window.BiostechAutocomplete.montarTudo(linha);
        }

        recalcular(tabela);

        var busca = linha.querySelector('.autocomplete-busca');

        if (busca) {
            busca.focus();
        }
    }

    function remover(botao, tabela) {
        var linha = botao.closest('tr.linha-item');

        if (!linha) {
            return;
        }

        if (linhas(tabela).length <= 1) {
            // Mantem pelo menos uma linha; apenas limpa os campos.
            var busca = linha.querySelector('.autocomplete-busca');
            var oculto = linha.querySelector('input[name="produto_id[]"]');
            var qtd = linha.querySelector('input[name="quantidade[]"]');
            var custo = linha.querySelector('input[name="custo[]"]');
            var unidade = linha.querySelector('.unidade');

            if (busca) { busca.value = ''; }
            if (oculto) { oculto.value = ''; }
            if (qtd) { qtd.value = '1'; }
            if (custo) { custo.value = ''; }
            if (unidade) { unidade.value = ''; }

            recalcular(tabela);
            return;
        }

        linha.parentNode.removeChild(linha);
        renumerar(tabela);
        recalcular(tabela);
    }

    function iniciar(tabela) {
        if (tabela.getAttribute('data-pronto') === '1') {
            return;
        }

        tabela.setAttribute('data-pronto', '1');

        if (tabela.querySelectorAll('tbody tr.linha-item').length === 0) {
            adicionar(tabela);
        }

        tabela.addEventListener('input', function () {
            recalcular(tabela);
        });

        tabela.addEventListener('change', function () {
            recalcular(tabela);
        });

        tabela.addEventListener('autocomplete:selecionar', function () {
            recalcular(tabela);
        });

        tabela.addEventListener('click', function (ev) {
            var botao = ev.target.closest('.remover-item');

            if (botao && tabela.contains(botao)) {
                ev.preventDefault();
                remover(botao, tabela);
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var tabelas = document.querySelectorAll('table[data-itens]');

        for (var i = 0; i < tabelas.length; i++) {
            iniciar(tabelas[i]);
        }

        var botoes = document.querySelectorAll('[data-adicionar-item]');

        for (var j = 0; j < botoes.length; j++) {
            botoes[j].addEventListener('click', function (ev) {
                ev.preventDefault();

                var alvo = this.getAttribute('data-adicionar-item');
                var tabela = document.querySelector('table[data-itens][data-grupo="' + alvo + '"]')
                          || document.querySelector('table[data-itens]');

                if (tabela) {
                    adicionar(tabela);
                }
            });
        }
    });
})();
