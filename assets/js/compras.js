/**
 * BIOSTECH - Compras
 *
 * Tabelas dinamicas:
 *   table[data-compra]    itens do pedido de compra
 *   table[data-cotacao]   itens da cotacao (3 fornecedores por item)
 *   table[data-parcelas]  parcelas do fechamento
 *
 * O calculo replica o Access:
 *   base  = qtd * valor unitario
 *   novo valor > 0  -> total = qtd * novo valor; desconto = base - total
 *   desconto % > 0  -> desconto = base * % / 100; total = base - desconto
 *   senao           -> total = base - desconto informado
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
        } else if (/^\d{1,3}(\.\d{3})+$/.test(texto)) {
            texto = texto.replace(/\./g, '');
        }

        var n = parseFloat(texto);
        return isNaN(n) ? 0 : n;
    }

    function formatar(valor) {
        var n = Number(valor);

        if (!isFinite(n)) {
            n = 0;
        }

        return n.toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function formatarQtd(valor) {
        var n = Number(valor);

        if (!isFinite(n)) {
            n = 0;
        }

        return n.toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 3
        });
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

    function clonarLinha(tabela) {
        var template = document.getElementById(tabela.getAttribute('data-template'));

        if (!template) {
            return null;
        }

        var fragmento = template.content.cloneNode(true);

        return fragmento.querySelector('tr.linha-item');
    }

    function adicionarLinha(tabela, dados) {
        var linha = clonarLinha(tabela);
        var corpo = tabela.querySelector('tbody');

        if (!linha || !corpo) {
            return null;
        }

        if (dados) {
            preencherLinha(linha, dados);
        }

        corpo.appendChild(linha);
        renumerar(tabela);

        if (window.BiostechAutocomplete) {
            window.BiostechAutocomplete.montarTudo(linha);
        }

        return linha;
    }

    function preencherLinha(linha, dados) {
        var busca = linha.querySelector('.autocomplete-busca') || linha.querySelector('.produto-desc');
        var oculto = linha.querySelector('input[name="produto_id[]"]');

        if (busca) { busca.value = dados.descricao || ''; }
        if (oculto) { oculto.value = dados.produto_id; }

        var unidade = linha.querySelector('.unidade');
        if (unidade) { unidade.value = dados.unidade || ''; }

        var saldo = linha.querySelector('.saldo');
        if (saldo) { saldo.value = dados.saldo !== undefined ? formatarQtd(dados.saldo) : ''; }

        var qtd = linha.querySelector('.qtd');
        if (qtd) { qtd.value = formatarQtd(dados.quantidade); }

        var unit = linha.querySelector('.valor-unit');
        if (unit) { unit.value = formatar(dados.valor_unitario || 0); }

        var novo = linha.querySelector('.novo-valor');
        if (novo) { novo.value = dados.novo_valor_unitario ? formatar(dados.novo_valor_unitario) : ''; }

        var perc = linha.querySelector('.desc-perc');
        if (perc) { perc.value = dados.desconto_percentual ? formatar(dados.desconto_percentual) : ''; }

        var desc = linha.querySelector('.desc-item');
        if (desc) { desc.value = formatar(dados.desconto_item || 0); }

        var obs = linha.querySelector('input[name="observacao_item[]"]');
        if (obs) { obs.value = dados.observacao || ''; }

        var check = linha.querySelector('input[name="atualiza_preco_compra[]"]');
        if (check) { check.checked = !!dados.atualiza_preco_compra; }
    }

    // ==================================================================
    // Pedido de compra
    // ==================================================================

    function recalcularLinha(linha) {
        var qtd = numero(linha.querySelector('.qtd') ? linha.querySelector('.qtd').value : 0);
        var unit = numero(linha.querySelector('.valor-unit') ? linha.querySelector('.valor-unit').value : 0);
        var novo = numero(linha.querySelector('.novo-valor') ? linha.querySelector('.novo-valor').value : 0);
        var perc = numero(linha.querySelector('.desc-perc') ? linha.querySelector('.desc-perc').value : 0);
        var campDesc = linha.querySelector('.desc-item');
        var desc = numero(campDesc ? campDesc.value : 0);

        var base = qtd * unit;
        var total;

        if (novo > 0) {
            total = qtd * novo;
            desc = base - total;

            if (campDesc) { campDesc.value = formatar(desc); }
        } else {
            if (perc > 0) {
                desc = base * perc / 100;

                if (campDesc) { campDesc.value = formatar(desc); }
            }

            total = base - desc;
        }

        if (total < 0) {
            total = 0;
        }

        var celula = linha.querySelector('.total-linha');

        if (celula) {
            celula.textContent = formatar(total);
        }

        return total;
    }

    function atualizarTotaisPedido(tabela) {
        var itens = linhas(tabela);
        var bruto = 0;

        for (var i = 0; i < itens.length; i++) {
            bruto += recalcularLinha(itens[i]);
        }

        var saida = document.getElementById('pedido-bruto');

        if (saida) {
            saida.textContent = formatar(bruto);
        }

        var perc = numero(document.getElementById('desconto_percentual') ? document.getElementById('desconto_percentual').value : 0);
        var desc = numero(document.getElementById('desconto') ? document.getElementById('desconto').value : 0);

        if (perc > 0) {
            desc = bruto * perc / 100;
        }

        var total = bruto - desc;

        if (total < 0) {
            total = 0;
        }

        var alvo = document.getElementById('pedido-total');

        if (alvo) {
            alvo.textContent = formatar(total);
        }
    }

    function iniciarPedido(tabela) {
        var existentes = window.BIOSTECH_ITENS || [];

        if (existentes.length) {
            for (var i = 0; i < existentes.length; i++) {
                adicionarLinha(tabela, existentes[i]);
            }
        } else {
            var vazio = document.createElement('tr');
            vazio.className = 'linha-vazia';
            vazio.innerHTML = '<td colspan="11" class="vazio">Nenhum item no pedido.</td>';
            tabela.querySelector('tbody').appendChild(vazio);
        }

        var botaoItem = document.getElementById('btn-adicionar-item-pedido');

        if (botaoItem) {
            botaoItem.addEventListener('click', function () {
                var id = parseInt(document.getElementById('item-produto-id').value, 10);
                var descricao = document.getElementById('item-produto').value.trim();

                if (!id) {
                    window.alert('Selecione um produto.');
                    return;
                }

                var vazio = tabela.querySelector('tbody tr.linha-vazia');
                if (vazio) { vazio.remove(); }

                var nova = adicionarLinha(tabela, {
                    produto_id: id,
                    descricao: descricao,
                    unidade: document.getElementById('item-unidade').value,
                    saldo: numero(document.getElementById('item-saldo').value),
                    quantidade: numero(document.getElementById('item-qtd').value) || 1,
                    valor_unitario: numero(document.getElementById('item-valor-unit').value),
                    novo_valor_unitario: numero(document.getElementById('item-novo-valor').value),
                    desconto_percentual: numero(document.getElementById('item-desc-perc').value),
                    desconto_item: numero(document.getElementById('item-desc-valor').value),
                    observacao: ''
                });

                if (nova) { recalcularLinha(nova); }
                renumerar(tabela);
                atualizarTotaisPedido(tabela);

                ['item-produto', 'item-produto-id', 'item-unidade', 'item-saldo',
                 'item-valor-unit', 'item-novo-valor', 'item-desc-perc', 'item-desc-valor'].forEach(function (campo) {
                    var el = document.getElementById(campo);
                    if (el) { el.value = ''; }
                });

                var qtd = document.getElementById('item-qtd');
                if (qtd) { qtd.value = '1'; }

                var totalItem = document.getElementById('item-total');
                if (totalItem) { totalItem.textContent = '0,00'; }
            });

            function calcularItemCadastro() {
                var alvo = document.getElementById('item-total');
                if (!alvo) { return; }

                var qtd = numero(document.getElementById('item-qtd').value);
                var unit = numero(document.getElementById('item-valor-unit').value);
                var novo = numero(document.getElementById('item-novo-valor').value);
                var perc = numero(document.getElementById('item-desc-perc').value);
                var desc = numero(document.getElementById('item-desc-valor').value);
                var base = qtd * unit;
                var total = novo > 0 ? qtd * novo : base - (perc > 0 ? base * perc / 100 : desc);

                if (total < 0) { total = 0; }
                alvo.textContent = formatar(total);
            }

            ['item-qtd', 'item-valor-unit', 'item-novo-valor', 'item-desc-perc', 'item-desc-valor'].forEach(function (id) {
                var campo = document.getElementById(id);
                if (!campo) { return; }

                campo.addEventListener('input', calcularItemCadastro);

                campo.addEventListener('focusout', function () {
                    if (campo.value.trim() === '') { return; }

                    campo.value = id === 'item-qtd'
                        ? formatarQtd(numero(campo.value))
                        : formatar(numero(campo.value));
                    calcularItemCadastro();
                });
            });

            document.addEventListener('autocomplete:selecionar', function (ev) {
                var box = ev.target && ev.target.closest ? ev.target.closest('.autocomplete') : null;

                if (!box) { return; }

                var custo = box.getAttribute('data-custo');
                if (custo) {
                    var campoCusto = document.querySelector(custo);
                    if (campoCusto && campoCusto.value !== '') {
                        campoCusto.value = formatar(numero(campoCusto.value));
                    }
                }

                var estoque = box.getAttribute('data-estoque');
                if (estoque) {
                    var campoEstoque = document.querySelector(estoque);
                    if (campoEstoque && campoEstoque.value !== '') {
                        campoEstoque.value = formatarQtd(numero(campoEstoque.value));
                    }
                }
            });
        }

        atualizarTotaisPedido(tabela);

        tabela.addEventListener('input', function () {
            atualizarTotaisPedido(tabela);
        });

        tabela.addEventListener('change', function () {
            atualizarTotaisPedido(tabela);
        });

        tabela.addEventListener('focusout', function (ev) {
            var campo = ev.target;

            if (!campo || !campo.matches) { return; }

            if (campo.classList.contains('qtd') || campo.classList.contains('saldo')) {
                if (campo.value.trim() !== '') {
                    campo.value = formatarQtd(numero(campo.value));
                }
            } else if (campo.matches('.valor-unit, .novo-valor, .desc-item, .desc-perc')) {
                if (campo.value.trim() !== '') {
                    campo.value = formatar(numero(campo.value));
                }
            } else {
                return;
            }

            atualizarTotaisPedido(tabela);
        });

        tabela.addEventListener('autocomplete:selecionar', function () {
            atualizarTotaisPedido(tabela);
        });

        tabela.addEventListener('click', function (ev) {
            var botao = ev.target.closest('.remover-item');

            if (botao && tabela.contains(botao)) {
                ev.preventDefault();
                removerLinha(botao, tabela, atualizarTotaisPedido);
            }
        });

        ['desconto', 'desconto_percentual'].forEach(function (id) {
            var campo = document.getElementById(id);

            if (campo) {
                campo.addEventListener('input', function () {
                    atualizarTotaisPedido(tabela);
                });

                campo.addEventListener('focusout', function () {
                    if (campo.value.trim() === '') { return; }

                    campo.value = formatar(numero(campo.value));
                    atualizarTotaisPedido(tabela);
                });
            }
        });
    }

    function removerLinha(botao, tabela, recalcular) {
        var linha = botao.closest('tr.linha-item');

        if (!linha) {
            return;
        }

        if (linhas(tabela).length <= 1) {
            var campos = linha.querySelectorAll('input');

            for (var i = 0; i < campos.length; i++) {
                if (campos[i].type === 'checkbox') {
                    campos[i].checked = false;
                } else if (campos[i].name === 'quantidade[]') {
                    campos[i].value = '1';
                } else {
                    campos[i].value = '';
                }
            }
        } else {
            linha.parentNode.removeChild(linha);
            renumerar(tabela);
        }

        if (typeof recalcular === 'function') {
            recalcular(tabela);
        }
    }

    // ==================================================================
    // Cotacao de precos
    // ==================================================================

    function atualizarEscolhido(linha) {
        var select = linha.querySelector('.fornecedor-escolhido');

        if (!select) {
            return;
        }

        var atual = select.value;
        var opcoes = '<option value="">-</option>';

        [1, 2, 3].forEach(function (n) {
            var oculto = linha.querySelector('input[name="fornecedor_' + n + '[]"]');
            var busca = linha.querySelector('.fornecedor-' + n + ' .autocomplete-busca');

            if (oculto && oculto.value) {
                var rotulo = busca && busca.value ? busca.value : 'Fornecedor ' + oculto.value;
                opcoes += '<option value="' + oculto.value + '">' + rotulo.replace(/</g, '&lt;') + '</option>';
            }
        });

        select.innerHTML = opcoes;

        if (atual) {
            select.value = atual;
        }
    }

    function iniciarCotacao(tabela) {
        var existentes = window.BIOSTECH_COTACAO_ITENS || [];

        if (existentes.length) {
            for (var i = 0; i < existentes.length; i++) {
                var linha = adicionarLinha(tabela, {
                    produto_id: existentes[i].produto_id,
                    descricao: existentes[i].descricao,
                    quantidade: existentes[i].quantidade,
                });

                if (!linha) {
                    continue;
                }

                [
                    ['fornecedor_1', 'fornecedor_1_nome', 'preco_1'],
                    ['fornecedor_2', 'fornecedor_2_nome', 'preco_2'],
                    ['fornecedor_3', 'fornecedor_3_nome', 'preco_3'],
                ].forEach(function (par) {
                    var oculto = linha.querySelector('input[name="' + par[0] + '[]"]');
                    var busca = linha.querySelector('.fornecedor-' + par[0].slice(-1) + ' .autocomplete-busca');
                    var preco = linha.querySelector('input[name="' + par[2] + '[]"]');

                    if (oculto && existentes[i][par[0]]) {
                        oculto.value = existentes[i][par[0]];
                    }

                    if (busca && existentes[i][par[1]]) {
                        busca.value = existentes[i][par[1]];
                    }

                    if (preco && existentes[i][par[2]] !== null && existentes[i][par[2]] !== undefined) {
                        preco.value = formatar(existentes[i][par[2]]);
                    }
                });

                var escolhido = linha.querySelector('.fornecedor-escolhido');

                if (escolhido) {
                    atualizarEscolhido(linha);
                    escolhido.value = existentes[i].fornecedor_escolhido || '';
                }
            }
        } else {
            adicionarLinha(tabela);
        }

        tabela.addEventListener('autocomplete:selecionar', function (ev) {
            var linha = ev.target.closest('tr.linha-item');

            if (linha) {
                atualizarEscolhido(linha);
            }
        });

        tabela.addEventListener('change', function (ev) {
            var linha = ev.target.closest('tr.linha-item');

            if (linha) {
                atualizarEscolhido(linha);
            }
        });

        tabela.addEventListener('click', function (ev) {
            var botao = ev.target.closest('.remover-item');

            if (botao && tabela.contains(botao)) {
                ev.preventDefault();
                removerLinha(botao, tabela, null);
            }
        });
    }

    // ==================================================================
    // Parcelas do fechamento
    // ==================================================================

    function somarDias(data, dias) {
        if (!data) {
            return '';
        }

        var partes = data.split('-');

        if (partes.length !== 3) {
            return '';
        }

        var d = new Date(Number(partes[0]), Number(partes[1]) - 1, Number(partes[2]));
        d.setDate(d.getDate() + dias);

        var mes = String(d.getMonth() + 1);
        var dia = String(d.getDate());

        return d.getFullYear() + '-' + (mes.length < 2 ? '0' + mes : mes) + '-' + (dia.length < 2 ? '0' + dia : dia);
    }

    function atualizarParcelas(tabela) {
        var itens = linhas(tabela);
        var fonte = document.getElementById('valor-total-fechamento');
        var total = fonte ? numero(fonte.getAttribute('data-valor')) : numero(tabela.getAttribute('data-total'));
        var n = itens.length;
        var base = total > 0 && n > 0 ? Math.round((total / n) * 100) / 100 : 0;
        var soma = Math.round(base * n * 100) / 100;
        var valores = [];

        for (var i = 0; i < n; i++) {
            valores.push(base);
        }

        if (n > 0 && soma < total) {
            valores[0] = Math.round((valores[0] + (total - soma)) * 100) / 100;
        } else if (n > 0 && soma > total) {
            valores[n - 1] = Math.round((valores[n - 1] - (soma - total)) * 100) / 100;
        }

        var baseData = document.getElementById('data_vencimento') ? document.getElementById('data_vencimento').value : '';

        for (var j = 0; j < itens.length; j++) {
            var linha = itens[j];
            var dias = linha.querySelector('input[name="parcela_dias[]"]');
            var venc = linha.querySelector('input[name="parcela_vencimento[]"]');
            var celula = linha.querySelector('.valor-parcela');

            if (dias && venc && baseData) {
                venc.value = somarDias(baseData, numero(dias.value));
            }

            if (celula) {
                celula.textContent = formatar(valores[j] || 0);
            }
        }
    }

    function iniciarParcelas(tabela) {
        var quantos = parseInt(tabela.getAttribute('data-iniciais'), 10);

        if (!quantos || quantos < 1) {
            quantos = 1;
        }

        for (var i = 0; i < quantos; i++) {
            adicionarLinha(tabela);
        }

        var dias = linhas(tabela);

        for (var j = 0; j < dias.length; j++) {
            var campo = dias[j].querySelector('input[name="parcela_dias[]"]');

            if (campo) {
                campo.value = j * 30;
            }
        }

        atualizarParcelas(tabela);

        tabela.addEventListener('input', function (ev) {
            if (ev.target.name === 'parcela_dias[]') {
                atualizarParcelas(tabela);
            }
        });

        tabela.addEventListener('click', function (ev) {
            var botao = ev.target.closest('.remover-item');

            if (botao && tabela.contains(botao)) {
                ev.preventDefault();

                if (linhas(tabela).length <= 1) {
                    return;
                }

                removerLinha(botao, tabela, null);
                atualizarParcelas(tabela);
            }
        });

        var base = document.getElementById('data_vencimento');

        if (base) {
            base.addEventListener('change', function () {
                atualizarParcelas(tabela);
            });
        }
    }

    // ==================================================================
    // Solicitacao interna
    // ==================================================================

    function aviso(texto) {
        var antigo = document.getElementById('modal-aviso-solicitacao');

        if (antigo) { antigo.remove(); }

        var modal = document.createElement('div');
        modal.className = 'modal-confirmacao visivel';
        modal.id = 'modal-aviso-solicitacao';
        modal.innerHTML = '<div class="modal-confirmacao-caixa">'
            + '<h4>Aten&ccedil;&atilde;o</h4>'
            + '<div class="alert alert-warning"></div>'
            + '<div class="modal-confirmacao-acoes"><button type="button" class="botao pequeno">Ok</button></div>'
            + '</div>';
        modal.querySelector('.alert').textContent = texto;

        document.body.appendChild(modal);

        var ok = modal.querySelector('button');
        ok.addEventListener('click', function () { modal.remove(); });
        ok.focus();
    }

    function avisoProdutoDuplicado() {
        var antigo = document.getElementById('modal-produto-duplicado');

        if (antigo) { antigo.remove(); }

        var modal = document.createElement('div');
        modal.className = 'modal-confirmacao visivel';
        modal.id = 'modal-produto-duplicado';
        modal.innerHTML = '<div class="modal-confirmacao-caixa">'
            + '<h4>Aten&ccedil;&atilde;o</h4>'
            + '<div class="alert alert-warning">Produto j&aacute; est&aacute; cadastrado nesta solicita&ccedil;&atilde;o.</div>'
            + '<div class="modal-confirmacao-acoes"><button type="button" class="botao pequeno">Ok</button></div>'
            + '</div>';

        document.body.appendChild(modal);

        var ok = modal.querySelector('button');
        ok.addEventListener('click', function () { modal.remove(); });
        ok.focus();
    }

    function produtoJaCadastrado(tabela, produto_id, linhaAtual) {
        var linhasAtuais = linhas(tabela);

        for (var i = 0; i < linhasAtuais.length; i++) {
            if (linhaAtual && linhasAtuais[i] === linhaAtual) { continue; }

            var oculto = linhasAtuais[i].querySelector('input[name="produto_id[]"]');

            if (oculto && parseInt(oculto.value, 10) === parseInt(produto_id, 10)) {
                return true;
            }
        }

        return false;
    }

    function iniciarSolicitacao(tabela) {
        var existentes = window.BIOSTECH_SOLICITACAO_ITENS || [];

        if (existentes.length) {
            for (var i = 0; i < existentes.length; i++) {
                var linha = adicionarLinha(tabela, {
                    produto_id: existentes[i].produto_id,
                    descricao: existentes[i].descricao,
                    unidade: existentes[i].unidade,
                    saldo: existentes[i].saldo,
                    quantidade: existentes[i].quantidade,
                });

                if (!linha) {
                    continue;
                }

                var repor = linha.querySelector('input[name="repor_estoque[]"]');
                if (repor) { repor.checked = !!existentes[i].repor_estoque; }

                var util = linha.querySelector('input[name="data_utilizacao[]"]');
                if (util) { util.value = existentes[i].data_utilizacao || ''; }

                var periodo = linha.querySelector('input[name="periodo[]"]');
                if (periodo) { periodo.value = existentes[i].periodo || ''; }

                var selecionado = linha.querySelector('input[name="selecionado[]"]');
                if (selecionado) { selecionado.checked = !!existentes[i].selecionado; }
            }
        } else {
            var vazio = document.createElement('tr');
            vazio.className = 'linha-vazia';
            vazio.innerHTML = '<td colspan="7" class="vazio">Nenhum item na solicitacao.</td>';
            tabela.querySelector('tbody').appendChild(vazio);
        }

        var botaoAdicionar = document.getElementById('btn-adicionar-produto');

        if (botaoAdicionar) {
            botaoAdicionar.addEventListener('click', function () {
                var id = parseInt(document.getElementById('pf_novo_produto_id').value, 10);
                var qtd = parseInt(document.getElementById('pf_novo_qtd').value, 10);
                var descricao = document.getElementById('pf_novo_produto').value.trim();
                var unidade = document.getElementById('pf_novo_unidade').value;
                var saldo = parseFloat(document.getElementById('pf_novo_saldo').value || '0');
                var repor = document.getElementById('pf_novo_repor').checked;

                if (!id) {
                    aviso('Selecione um produto.');
                    return;
                }

                if (!qtd || qtd <= 0) {
                    aviso('Informe uma quantidade maior que zero.');
                    return;
                }

                if (produtoJaCadastrado(tabela, id)) {
                    avisoProdutoDuplicado();
                    return;
                }

                var vazio = tabela.querySelector('tbody tr.linha-vazia');
                if (vazio) { vazio.remove(); }

                adicionarLinha(tabela, {
                    produto_id: id,
                    descricao: descricao,
                    unidade: unidade,
                    saldo: saldo,
                    quantidade: qtd
                });

                var nova = linhas(tabela);
                var ultima = nova[nova.length - 1];
                var caixaRepor = ultima ? ultima.querySelector('input[name="repor_estoque[]"]') : null;
                if (caixaRepor) { caixaRepor.checked = repor; }

                renumerar(tabela);

                document.getElementById('pf_novo_produto').value = '';
                document.getElementById('pf_novo_produto_id').value = '';
                document.getElementById('pf_novo_unidade').value = '';
                document.getElementById('pf_novo_saldo').value = '';
                document.getElementById('pf_novo_qtd').value = '1';
                document.getElementById('pf_novo_repor').checked = false;
            });
        }

        tabela.addEventListener('click', function (ev) {
            var botao = ev.target.closest('.remover-item');

            if (botao && tabela.contains(botao)) {
                ev.preventDefault();
                removerLinha(botao, tabela, null);
            }
        });

        tabela.addEventListener('autocomplete:selecionar', function (ev) {
            var item = ev.detail || {};
            var id = parseInt(item.id, 10);
            var linhaAtual = ev.target.closest('tr');

            if (!id || !linhaAtual) { return; }

            if (produtoJaCadastrado(tabela, id, linhaAtual)) {
                var busca = linhaAtual.querySelector('.autocomplete-busca');
                var oculto = linhaAtual.querySelector('input[name="produto_id[]"]');

                if (busca) { busca.value = ''; }
                if (oculto) { oculto.value = ''; }

                avisoProdutoDuplicado();
            }
        });
    }

    // ==================================================================
    // Checkboxes em arrays (mantem alinhamento com produto_id[])
    // ==================================================================

    function sincronizarCheckboxes(form) {
        var nomes = {};

        form.querySelectorAll('table.tabela-itens tbody tr.linha-item input[type=checkbox]').forEach(function (cb) {
            nomes[cb.name] = true;
        });

        Object.keys(nomes).forEach(function (nome) {
            var caixas = form.querySelectorAll(
                'table.tabela-itens tbody tr.linha-item input[type=checkbox][name="' + nome + '"]'
            );
            var valores = [];

            for (var i = 0; i < caixas.length; i++) {
                valores.push(caixas[i].checked ? (caixas[i].value || 'S') : 'N');
                caixas[i].disabled = true;
            }

            valores.forEach(function (valor) {
                var oculto = document.createElement('input');
                oculto.type = 'hidden';
                oculto.name = nome;
                oculto.value = valor;
                form.appendChild(oculto);
            });
        });
    }

    // ==================================================================
    // Produtos abaixo do minimo -> solicitacao
    // ==================================================================

    function iniciarSugestoes(tabela) {
        var botoes = document.querySelectorAll('[data-add-produto]');

        for (var i = 0; i < botoes.length; i++) {
            botoes[i].addEventListener('click', function () {
                var id = parseInt(this.getAttribute('data-produto-id'), 10);

                if (!id) {
                    return;
                }

                var existentes = linhas(tabela);

                for (var j = 0; j < existentes.length; j++) {
                    var oculto = existentes[j].querySelector('input[name="produto_id[]"]');

                    if (oculto && parseInt(oculto.value, 10) === id) {
                        avisoProdutoDuplicado();
                        return;
                    }
                }

                adicionarLinha(tabela, {
                    produto_id: id,
                    descricao: this.getAttribute('data-descricao'),
                    unidade: this.getAttribute('data-unidade'),
                    saldo: parseFloat(this.getAttribute('data-saldo') || '0'),
                    quantidade: parseFloat(this.getAttribute('data-quantidade') || '1')
                });
            });
        }
    }

    // ==================================================================
    // Inicializacao
    // ==================================================================

    document.addEventListener('DOMContentLoaded', function () {
        var compras = document.querySelectorAll('table[data-compra]');

        for (var i = 0; i < compras.length; i++) {
            iniciarPedido(compras[i]);
        }

        var cotacoes = document.querySelectorAll('table[data-cotacao]');

        for (var j = 0; j < cotacoes.length; j++) {
            iniciarCotacao(cotacoes[j]);
        }

        var parcelas = document.querySelectorAll('table[data-parcelas]');

        for (var k = 0; k < parcelas.length; k++) {
            iniciarParcelas(parcelas[k]);
        }

        var solicitacoes = document.querySelectorAll('table[data-solicitacao]');

        for (var s = 0; s < solicitacoes.length; s++) {
            iniciarSolicitacao(solicitacoes[s]);
            iniciarSugestoes(solicitacoes[s]);
        }

        var formularios = document.querySelectorAll('form');

        for (var f = 0; f < formularios.length; f++) {
            if (formularios[f].querySelector('table.tabela-itens')) {
                formularios[f].addEventListener('submit', function () {
                    sincronizarCheckboxes(this);
                });
            }
        }

        var botoes = document.querySelectorAll('[data-adicionar-item]');

        for (var b = 0; b < botoes.length; b++) {
            botoes[b].addEventListener('click', function (ev) {
                ev.preventDefault();

                var grupo = this.getAttribute('data-adicionar-item');
                var tabela = document.querySelector('table[data-grupo="' + grupo + '"]')
                          || document.querySelector('table[data-compra]')
                          || document.querySelector('table[data-cotacao]')
                          || document.querySelector('table[data-solicitacao]');

                if (!tabela) {
                    return;
                }

                if (tabela.hasAttribute('data-parcelas') && linhas(tabela).length >= 12) {
                    return;
                }

                var anterior = null;

                if (tabela.hasAttribute('data-parcelas')) {
                    var existentes = linhas(tabela);

                    if (existentes.length) {
                        var diasAnterior = existentes[existentes.length - 1].querySelector('input[name="parcela_dias[]"]');
                        anterior = diasAnterior ? numero(diasAnterior.value) : 0;
                    }
                }

                var nova = adicionarLinha(tabela);
                renumerar(tabela);

                if (tabela.hasAttribute('data-parcelas') && nova && anterior !== null) {
                    var diasNovo = nova.querySelector('input[name="parcela_dias[]"]');

                    if (diasNovo) {
                        diasNovo.value = anterior + 30;
                    }
                }

                if (tabela.hasAttribute('data-compra')) {
                    atualizarTotaisPedido(tabela);
                } else if (tabela.hasAttribute('data-parcelas')) {
                    atualizarParcelas(tabela);
                }
            });
        }
    });
})();
