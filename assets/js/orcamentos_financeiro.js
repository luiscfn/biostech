/**
 * BIOSTECH - Orcamentos: aba Financeiro
 *
 * Cada linha da composicao pode ser informada em percentual ou em valor:
 *   - percentual informado  -> valor absoluto calculado;
 *   - valor informado       -> percentual calculado;
 *   - a base de calculo e sempre o acumulado da linha anterior, comecando
 *     pelo total das pecas (itens);
 *   - o desconto usa o total geral sem desconto como base;
 *   - "valor a diluir" = pecas - valor aceito - custos indiretos.
 *
 * O calculo definitivo e gravado no servidor (Orcamentos_model::salvar_financeiro);
 * este arquivo apenas antecipa o resultado na tela.
 */
(function () {
    'use strict';

    var form = document.getElementById('form-financeiro');

    if (!form) {
        return;
    }

    var material = parseFloat(form.getAttribute('data-material')) || 0;
    var servico  = parseFloat(form.getAttribute('data-servico')) || 0;

    var CHAVES = ['valor_mao_obra', 'material_fixacao', 'acrescimo_custo', 'valor_imposto'];

    var linhas = [];

    for (var i = 0; i < CHAVES.length; i++) {
        linhas.push({
            perc:  document.getElementById('fin-perc-' + CHAVES[i]),
            valor: document.getElementById('fin-valor-' + CHAVES[i]),
            modo:  document.getElementById('fin-modo-' + CHAVES[i]),
            acum:  document.getElementById('fin-acum-' + CHAVES[i])
        });
    }

    var descPerc  = document.getElementById('fin-perc-desconto');
    var descValor = document.getElementById('fin-valor-desconto');
    var descModo  = document.getElementById('fin-modo-desconto');

    var aceito    = document.getElementById('fin-aceito');
    var indiretos = document.getElementById('fin-indiretos');

    function numero(valor) {
        var texto = String(valor === undefined || valor === null ? '' : valor).trim();

        if (!texto) {
            return 0;
        }

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

        if (el) {
            el.textContent = valor;
        }
    }

    function recalcular() {
        var acumulado = material;
        var encargos = 0;

        for (var i = 0; i < linhas.length; i++) {
            var linha = linhas[i];

            if (!linha.perc || !linha.valor) {
                continue;
            }

            var p = numero(linha.perc.value);
            var v = numero(linha.valor.value);

            if (linha.modo.value === 'valor' && linha.valor.value.trim() !== '') {
                v = arredondar(v, 2);
                p = acumulado > 0 ? arredondar(v / acumulado * 100, 4) : 0;
                linha.perc.value = percentual(p);
            } else {
                p = arredondar(p, 4);
                v = arredondar(p * acumulado / 100, 2);
                linha.valor.value = dinheiro(v);
            }

            acumulado = arredondar(acumulado + v, 2);
            encargos = arredondar(encargos + v, 2);

            if (linha.acum) {
                linha.acum.textContent = dinheiro(acumulado);
            }
        }

        var semDesconto = acumulado;
        var pd = numero(descPerc.value);
        var vd = numero(descValor.value);

        if (descModo.value === 'valor' && descValor.value.trim() !== '') {
            vd = arredondar(vd, 2);
            pd = semDesconto > 0 ? arredondar(vd / semDesconto * 100, 4) : 0;
            descPerc.value = percentual(pd);
        } else {
            pd = arredondar(pd, 4);
            vd = arredondar(pd * semDesconto / 100, 2);
            descValor.value = dinheiro(vd);
        }

        var total = arredondar(semDesconto + servico - vd, 2);

        if (total < 0) {
            total = 0;
        }

        texto('fin-total-encargos', dinheiro(encargos));
        texto('fin-total-sem-desconto', dinheiro(semDesconto));
        texto('fin-total-servicos', dinheiro(servico));
        texto('fin-total-geral', dinheiro(total));

        var valorAceito = numero(aceito.value);
        var valorIndireto = numero(indiretos.value);
        var diluir = arredondar(material - valorAceito - valorIndireto, 2);

        texto('fin-diluir', dinheiro(diluir));
        texto('fin-perc-diluir', percentual(material > 0 ? arredondar(diluir / material * 100, 2) : 0) + ' %');
    }

    function ligar(campo, modo, tipo) {
        if (!campo || campo.disabled) {
            return;
        }

        campo.addEventListener('input', function () {
            if (modo) {
                modo.value = tipo;
            }

            recalcular();
        });

        campo.addEventListener('blur', function () {
            var n = numero(campo.value);

            campo.value = tipo === 'valor' ? dinheiro(n) : percentual(n);
        });
    }

    for (var j = 0; j < linhas.length; j++) {
        ligar(linhas[j].perc, linhas[j].modo, 'percentual');
        ligar(linhas[j].valor, linhas[j].modo, 'valor');
    }

    ligar(descPerc, descModo, 'percentual');
    ligar(descValor, descModo, 'valor');

    if (aceito) {
        aceito.addEventListener('input', recalcular);
        aceito.addEventListener('blur', function () {
            aceito.value = dinheiro(numero(aceito.value));
        });
    }

    if (indiretos) {
        indiretos.addEventListener('input', recalcular);
        indiretos.addEventListener('blur', function () {
            indiretos.value = dinheiro(numero(indiretos.value));
        });
    }
})();
