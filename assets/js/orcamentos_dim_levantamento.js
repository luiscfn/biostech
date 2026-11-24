/**
 * BIOSTECH - Dimensionamento, aba Levantamento.
 *
 * Composicao do total: replica a regra da aba Financeiro do orçamento.
 * - informar PERCENTUAL calcula o VALOR;
 * - informar VALOR calcula o PERCENTUAL;
 * - o ACUMULADO de cada linha e atualizado em cascata.
 */
(function () {
    'use strict';

    var raiz = document.getElementById('composicao-dim-levantamento');
    if (!raiz) { return; }

    var material = parseFloat(raiz.getAttribute('data-material')) || 0;

    var CHAVES = ['mao_obra', 'material_fixacao', 'acrescimo_custo', 'nota_fiscal'];
    var RESUMO = ['resumo-mo', 'resumo-mf', 'resumo-ac', 'resumo-nf'];

    var linhas = [];
    for (var i = 0; i < CHAVES.length; i++) {
        linhas.push({
            perc:  document.getElementById('dim-perc-' + CHAVES[i]),
            valor: document.getElementById('dim-valor-' + CHAVES[i]),
            modo:  document.getElementById('dim-modo-' + CHAVES[i]),
            acum:  document.getElementById('dim-acum-' + CHAVES[i])
        });
    }

    function numero(valor) {
        var texto = String(valor === undefined || valor === null ? '' : valor).trim();
        if (!texto) { return 0; }
        texto = texto.replace(/\s/g, '').replace(/[R$%]/g, '');
        if (texto.indexOf(',') !== -1) {
            texto = texto.replace(/\./g, '').replace(',', '.');
        }
        var n = parseFloat(texto);
        return isNaN(n) ? 0 : n;
    }

    function arredondar(valor, casas) {
        var fator = Math.pow(10, casas);
        return Math.round(valor * fator) / fator;
    }

    function dinheiro(valor) {
        var negativo = valor < 0;
        var partes = Math.abs(valor).toFixed(2).split('.');
        var inteiro = partes[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        return (negativo ? '-' : '') + inteiro + ',' + partes[1];
    }

    function percentual(valor) {
        return Number(valor).toFixed(2).replace('.', ',');
    }

    function texto(id, valor) {
        var el = document.getElementById(id);
        if (!el) { return; }
        var alvo = el.querySelector('strong') || el;
        alvo.textContent = valor;
    }

    function recalcular() {
        var acumulado = material;
        var encargos = 0;
        var moValor = 0;

        for (var i = 0; i < linhas.length; i++) {
            var linha = linhas[i];
            if (!linha.perc || !linha.valor) { continue; }

            var p = numero(linha.perc.value);
            var v = numero(linha.valor.value);

            // O hidden modo_ indica qual campo o usuario alterou por ultimo.
            if (linha.modo && linha.modo.value === 'valor' && linha.valor.value.trim() !== '') {
                v = arredondar(v, 2);
                p = acumulado > 0 ? arredondar(v / acumulado * 100, 4) : 0;
                linha.perc.value = percentual(p);
            } else {
                p = arredondar(p, 4);
                v = arredondar(p * acumulado / 100, 2);
                linha.valor.value = dinheiro(v);
            }

            acumulado = arredondar(acumulado + v, 2);
            encargos  = arredondar(encargos + v, 2);
            if (i === 0) { moValor = v; }

            texto(RESUMO[i], dinheiro(v));

            if (linha.acum) {
                linha.acum.textContent = dinheiro(acumulado);
            }
        }

        texto('dim-total-encargos', dinheiro(encargos));
        texto('dim-total-sem-desconto', dinheiro(acumulado));
        texto('resumo-encargos', dinheiro(encargos));
        texto('resumo-geral', dinheiro(acumulado));

        var totalCompra  = numero(raiz.getAttribute('data-total-compra'));
        var lucroAtual   = arredondar(material - totalCompra, 2);
        var lucroLiquido = arredondar(moValor + lucroAtual, 2);
        texto('resumo-lucro-liquido', dinheiro(lucroLiquido));
        texto('resumo-lucro-liquido-perc',
              (acumulado > 0 ? percentual(lucroLiquido / acumulado * 100) : '0,00') + '%');
    }

    function ligar(campo, modo, tipo) {
        if (!campo || campo.disabled) { return; }

        campo.addEventListener('input', function () {
            if (modo) { modo.value = tipo; }
            recalcular();
        });

        campo.addEventListener('blur', function () {
            campo.value = tipo === 'valor' ? dinheiro(numero(campo.value)) : percentual(numero(campo.value));
        });
    }

    for (var j = 0; j < linhas.length; j++) {
        ligar(linhas[j].perc, linhas[j].modo, 'percentual');
        ligar(linhas[j].valor, linhas[j].modo, 'valor');
    }

    recalcular();
})();
