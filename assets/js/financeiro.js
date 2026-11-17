/**
 * BIOSTECH - financeiro.js
 *
 * - Formata campos decimais (inputmode="decimal") no padrao brasileiro.
 * - Calcula o valor da baixa (multa + juros - desconto) nas telas de
 *   contas a pagar e a receber.
 * - Calcula ISS e valor liquido no formulario de conta a receber.
 *
 * Marcacao esperada:
 *   <input ... inputmode="decimal">
 *   <input ... data-calculo="baixa"|"baixa-cr"|"cr">
 *   <input ... data-valor-base="123.45">   (valor original do titulo)
 */
(function () {
    'use strict';

    function paraNumero(valor) {
        if (typeof valor === 'number') {
            return isFinite(valor) ? valor : 0;
        }

        if (typeof valor !== 'string') {
            return 0;
        }

        var limpo = valor.replace(/[^\d,.-]/g, '').trim();

        if (limpo === '' || limpo === '-' || limpo === ',') {
            return 0;
        }

        // Com virgula: ponto e separador de milhar.
        if (limpo.indexOf(',') >= 0) {
            limpo = limpo.replace(/\./g, '').replace(',', '.');
        }

        var n = parseFloat(limpo);
        return isNaN(n) ? 0 : n;
    }

    function formatar(valor, casas) {
        casas = typeof casas === 'number' ? casas : 2;

        if ( ! isFinite(valor)) {
            valor = 0;
        }

        var negativo = valor < 0;
        var texto = Math.abs(valor).toFixed(casas).replace('.', ',');

        return (negativo ? '-' : '') + texto;
    }

    function arredondar(valor, casas) {
        var fator = Math.pow(10, casas || 2);
        return Math.round((valor + Number.EPSILON) * fator) / fator;
    }

    function formatarCampo(campo) {
        var casas = campo.getAttribute('data-casas') === '4' ? 4 : 2;

        if (campo.value.trim() === '') {
            return;
        }

        campo.value = formatar(paraNumero(campo.value), casas);
    }

    // ------------------------------------------------------------------
    // Baixa de contas a pagar e a receber
    // ------------------------------------------------------------------

    function recalcularBaixa(form) {
        var base = paraNumero(form.querySelector('[data-valor-base]')
            ? form.querySelector('[data-valor-base]').value : 0);

        var multa = paraNumero((form.querySelector('#multa') || {}).value || 0);
        var juros = paraNumero((form.querySelector('#juros') || {}).value || 0);
        var desconto = paraNumero((form.querySelector('#desconto') || {}).value || 0);

        var alvo = form.querySelector('#valor_pago') || form.querySelector('#valor_recebido');

        if ( ! alvo) {
            return;
        }

        var total = arredondar(base + multa + juros - desconto, 2);

        if (total < 0) {
            total = 0;
        }

        alvo.value = formatar(total, 2);
    }

    // ------------------------------------------------------------------
    // Conta a receber (ISS e valor liquido)
    // ------------------------------------------------------------------

    function recalcularContaReceber(form) {
        var valor = paraNumero((form.querySelector('#valor') || {}).value || 0);
        var desconto = paraNumero((form.querySelector('#desconto_especial') || {}).value || 0);
        var despesas = paraNumero((form.querySelector('#vdesp') || {}).value || 0);
        var aliquota = paraNumero((form.querySelector('#aliq_issqn') || {}).value || 0);

        var campoIss = form.querySelector('#viss');
        var campoLiquido = form.querySelector('#vliq');

        var iss = campoIss ? paraNumero(campoIss.value) : 0;

        if (aliquota > 0) {
            iss = arredondar(valor * aliquota / 100, 2);

            if (campoIss) {
                campoIss.value = formatar(iss, 2);
            }
        }

        if (campoLiquido) {
            campoLiquido.value = formatar(arredondar(valor - desconto + despesas - iss, 2), 2);
        }
    }

    // ------------------------------------------------------------------

    function ligar() {
        var decimais = document.querySelectorAll('input[inputmode="decimal"]');

        Array.prototype.forEach.call(decimais, function (campo) {
            campo.addEventListener('blur', function () {
                formatarCampo(campo);
            });

            campo.addEventListener('focus', function () {
                // Remove separador de milhar ao editar.
                campo.value = campo.value.replace(/\./g, '');
            });
        });

        Array.prototype.forEach.call(document.querySelectorAll('form'), function (form) {
            var temBase = form.querySelector('[data-valor-base]');

            if (temBase) {
                Array.prototype.forEach.call(
                    form.querySelectorAll('[data-calculo="baixa"], [data-calculo="baixa-cr"]'),
                    function (campo) {
                        campo.addEventListener('input', function () {
                            recalcularBaixa(form);
                        });

                        campo.addEventListener('blur', function () {
                            formatarCampo(campo);
                            recalcularBaixa(form);
                        });
                    }
                );

                recalcularBaixa(form);
            }

            if (form.querySelector('#vliq') && form.querySelector('#valor')) {
                Array.prototype.forEach.call(
                    form.querySelectorAll('[data-calculo="cr"]'),
                    function (campo) {
                        campo.addEventListener('input', function () {
                            recalcularContaReceber(form);
                        });

                        campo.addEventListener('blur', function () {
                            formatarCampo(campo);
                            recalcularContaReceber(form);
                        });
                    }
                );
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', ligar);
    } else {
        ligar();
    }
})();
