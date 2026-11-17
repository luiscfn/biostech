/**
 * BIOSTECH - Orcamentos (comercial + dimensionamento)
 *
 * Responsabilidades:
 *   - calcular o total da linha (quantidade x valor unitario) nas tabelas de
 *     itens do orcamento e de servicos com valor;
 *   - calcular o total do formulario de inclusao de item;
 *   - preencher a descricao do item a partir do produto escolhido no
 *     autocomplete (atributo data-descricao);
 *   - expressar a precedencia do percentual sobre o valor no cabecalho:
 *     quando o usuario informa o percentual, o valor em reais e limpo (o
 *     servidor recalcula) e vice-versa.
 *
 * O calculo definitivo e sempre feito no servidor (Orcamentos_model). Este
 * arquivo apenas antecipa o resultado na tela.
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

        texto = texto.replace(/\s/g, '').replace(/[R$]/g, '');

        if (texto.indexOf(',') !== -1) {
            texto = texto.replace(/\./g, '').replace(',', '.');
        }

        var n = parseFloat(texto);
        return isNaN(n) ? 0 : n;
    }

    function formatar(valor) {
        return Number(valor).toFixed(2).replace('.', ',');
    }

    // ------------------------------------------------------------------
    // Totais das linhas ja existentes (orcamento e servicos com valor)
    // ------------------------------------------------------------------

    function calcularLinha(linha) {
        var qtd   = linha.querySelector('input[name="quantidade"]');
        var unit  = linha.querySelector('input.valor-unit');
        var total = linha.querySelector('input.valor-total');

        if (!qtd || !unit || !total) {
            return;
        }

        var q = numero(qtd.value);
        var u = numero(unit.value);

        if (q > 0) {
            total.value = formatar(q * u);
        }
    }

    function ligarLinhas() {
        var linhas = document.querySelectorAll('tr.linha-item');

        for (var i = 0; i < linhas.length; i++) {
            (function (linha) {
                var campos = linha.querySelectorAll('input[name="quantidade"], input.valor-unit');

                for (var j = 0; j < campos.length; j++) {
                    campos[j].addEventListener('change', function () {
                        calcularLinha(linha);
                    });
                }
            })(linhas[i]);
        }
    }

    // ------------------------------------------------------------------
    // Formulario de inclusao de item
    // ------------------------------------------------------------------

    function ligarNovo(qtd, unit, total) {
        var q = document.getElementById(qtd);
        var u = document.getElementById(unit);
        var t = document.getElementById(total);

        if (!q || !u || !t) {
            return;
        }

        function recalcular() {
            var quantidade = numero(q.value);
            var unitario   = numero(u.value);

            if (quantidade > 0) {
                t.value = formatar(quantidade * unitario);
            }
        }

        q.addEventListener('change', recalcular);
        u.addEventListener('change', recalcular);
    }

    function ligarNovosItens() {
        ligarNovo('item_quantidade', 'item_valor_unitario', 'item_valor_total');
        ligarNovo('sv_quantidade', 'sv_unitario', 'sv_total');
    }

    // ------------------------------------------------------------------
    // Autocomplete: preenche a descricao do item escolhido
    // ------------------------------------------------------------------

    function ligarDescricao() {
        var caixas = document.querySelectorAll('.autocomplete[data-descricao]');

        for (var i = 0; i < caixas.length; i++) {
            (function (box) {
                var nome = box.getAttribute('data-descricao');

                if (!nome) {
                    return;
                }

                var anterior = box.biostechAoSelecionar;

                box.biostechAoSelecionar = function (item) {
                    var alvo = document.getElementById(nome)
                        || document.querySelector('[name="' + nome + '"]');

                    if (alvo) {
                        var descricao = item.descricao || item.nome || '';

                        if (descricao) {
                            alvo.value = descricao;
                        }
                    }

                    if (typeof anterior === 'function') {
                        anterior(item);
                    }
                };
            })(caixas[i]);
        }
    }

    // ------------------------------------------------------------------
    // Cabecalho: percentual x valor (o percentual prevalece)
    // ------------------------------------------------------------------

    function ligarPercentuais() {
        var pares = [
            ['percentual_desconto', 'valor_desconto'],
            ['percentual_mao_obra', 'valor_mao_obra'],
            ['percentual_mat_fixacao', 'material_fixacao'],
            ['percentual_acrescimo_custo', 'acrescimo_custo'],
            ['percentual_nota_fiscal', 'valor_imposto']
        ];

        for (var i = 0; i < pares.length; i++) {
            (function (percentual, valor) {
                var p = document.getElementById(percentual);
                var v = document.getElementById(valor);

                if (!p || !v) {
                    return;
                }

                p.addEventListener('change', function () {
                    if (numero(p.value) > 0) {
                        v.value = '';
                    }
                });

                v.addEventListener('change', function () {
                    if (numero(v.value) > 0) {
                        p.value = '';
                    }
                });
            })(pares[i][0], pares[i][1]);
        }
    }

    // ------------------------------------------------------------------

    document.addEventListener('DOMContentLoaded', function () {
        ligarLinhas();
        ligarNovosItens();
        ligarDescricao();
        ligarPercentuais();
    });
})();
