<?php
$pode_editar = in_array($usuario['perfil'], array(PERFIL_ADMIN, PERFIL_GESTOR), TRUE);
$tem_os      = ! empty($registro['chamado_numero']);

$abas_rotulos = array(
    'dados'            => 'Dados',
    'servicos'         => 'Servicos a executar',
    'itens'            => 'Pecas e Materiais',
    'servicos_valores' => 'Servicos com valor',
    'financeiro'       => 'Financeiro',
    'contatos'         => 'Contatos',
    'log'              => 'Historico',
);
?>
<div class="painel">
    <div class="painel-cabecalho">
        <h3>
            Orcamento <?= (int) $registro['numero'] ?>
            <?php if ((int) $registro['situacao'] === 5): ?>
                <span class="badge off">Encerrado</span>
            <?php elseif ((int) $registro['situacao'] === 4): ?>
                <span class="badge ok">Concluido</span>
            <?php elseif ($registro['situacao'] !== NULL): ?>
                <span class="badge alerta"><?= html_escape($registro['situacao_nome']) ?></span>
            <?php endif; ?>

            <?php if ((int) $registro['resultado'] === 1): ?>
                <span class="badge ok">Aprovado</span>
            <?php elseif (in_array((int) $registro['resultado'], array(2, 6, 7), TRUE)): ?>
                <span class="badge off"><?= html_escape($registro['resultado_nome']) ?></span>
            <?php elseif ($registro['resultado'] !== NULL): ?>
                <span class="badge info"><?= html_escape($registro['resultado_nome']) ?></span>
            <?php endif; ?>
        </h3>

        <div class="acoes-ficha">
            <?php if ($pode_editar): ?>
                <a class="botao secundario pequeno" href="<?= site_url('orcamentos/editar/' . $registro['numero']) ?>">Editar</a>
                <a class="botao secundario pequeno" href="<?= site_url('orcamentos/status/' . $registro['numero']) ?>">Status</a>

                <?php if ( ! $tem_os): ?>
                    <?= form_open('orcamentos/gerar_chamado/' . $registro['numero'], array('id' => 'form-gerar-chamado', 'style' => 'display:inline')) ?>
                        <button type="submit" class="botao pequeno"
                                title="Exige situacao Concluido/Encerrado, resultado Aprovado e datas de envio e aprovacao">Gerar chamado</button>
                    <?= form_close() ?>
                <?php endif; ?>
            <?php endif; ?>

            <a class="botao secundario pequeno" href="<?= site_url('orcamentos/imprimir/' . $registro['numero']) ?>" target="_blank">Imprimir</a>
            <a class="botao secundario pequeno" href="<?= site_url('orcamentos') ?>">Voltar</a>
        </div>
    </div>

    <div class="painel-corpo">
        <div class="detalhes">
            <div class="detalhe">
                <div class="rotulo">Cliente</div>
                <div class="valor">
                    <?php if ( ! empty($registro['cliente_id'])): ?>
                        <a href="<?= site_url('clientes/ver/' . $registro['cliente_id']) ?>"><?= html_escape($registro['cliente_nome']) ?></a>
                        <?php if ($registro['cliente_fantasia']): ?>
                            <div class="ajuda"><?= html_escape($registro['cliente_fantasia']) ?></div>
                        <?php endif; ?>
                    <?php else: ?>
                        <?= html_escape($registro['atencao'] ? $registro['atencao'] : '-') ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="detalhe">
                <div class="rotulo">Atencao</div>
                <div class="valor"><?= html_escape($registro['atencao'] ? $registro['atencao'] : '-') ?></div>
            </div>

            <div class="detalhe">
                <div class="rotulo">Data</div>
                <div class="valor"><?= data_br($registro['data']) ?></div>
            </div>

            <div class="detalhe">
                <div class="rotulo">Chamado / OS</div>
                <div class="valor">
                    <?php if ($tem_os): ?>
                        <a href="<?= site_url('chamados/ver/' . $registro['chamado_numero']) ?>"><?= html_escape($registro['chamado_numero']) ?></a>
                    <?php elseif ( ! empty($registro['chamado_numero_legado'])): ?>
                        <span title="OS nao existente na base de chamados"><?= html_escape($registro['chamado_numero_legado']) ?></span>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </div>
            </div>

            <div class="detalhe">
                <div class="rotulo">Orc. Dim</div>
                <div class="valor">
                    <?php if ( ! empty($registro['dimensionamento_numero'])): ?>
                        <a href="<?= site_url('orcamentos/dimensionamento/' . $registro['dimensionamento_numero']) ?>"><?= (int) $registro['dimensionamento_numero'] ?></a>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </div>
            </div>

            <div class="detalhe">
                <div class="rotulo">Material</div>
                <div class="valor"><?= moeda_br($registro['valor_material']) ?></div>
            </div>

            <div class="detalhe">
                <div class="rotulo">Mao de obra</div>
                <div class="valor"><?= moeda_br($registro['valor_mao_obra']) ?></div>
            </div>

            <div class="detalhe">
                <div class="rotulo">Servicos</div>
                <div class="valor"><?= moeda_br($registro['valor_servico']) ?></div>
            </div>

            <div class="detalhe">
                <div class="rotulo">Desconto</div>
                <div class="valor"><?= moeda_br($registro['valor_desconto']) ?></div>
            </div>

            <div class="detalhe">
                <div class="rotulo">Total</div>
                <div class="valor"><strong><?= moeda_br($registro['valor_total']) ?></strong></div>
            </div>
        </div>
    </div>

    <div class="abas abas-orcamento">
        <?php foreach ($abas_rotulos as $chave => $rotulo): ?>
            <a class="aba <?= $aba === $chave ? 'ativa' : '' ?>" href="<?= site_url('orcamentos/ver/' . $registro['numero']) ?>?aba=<?= $chave ?>">
                <?= html_escape($rotulo) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="aba-conteudo aba-conteudo-orcamento">
        <?php $this->load->view('orcamentos/_' . $aba, array(
            'registro'         => $registro,
            'itens'            => $itens,
            'servicos'         => $servicos,
            'servicos_valores' => $servicos_valores,
            'contatos'         => $contatos,
            'log'              => $log,
            'funcionarios'     => $funcionarios,
            'pode_editar'      => $pode_editar,
        )); ?>
    </div>
</div>

<?php if ( ! $tem_os && $pode_editar): ?>
    <div class="modal-confirmacao" id="modal-gerar-chamado" aria-hidden="true">
        <div class="modal-confirmacao-caixa">
            <h4>Gerar chamado</h4>
            <p>Deseja gerar o chamado a partir deste orcamento?</p>
            <div class="modal-confirmacao-acoes">
                <button type="button" class="botao" id="confirmar-gerar-chamado">Sim</button>
                <button type="button" class="botao secundario" id="cancelar-gerar-chamado">Nao</button>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var form  = document.getElementById('form-gerar-chamado');
            var modal = document.getElementById('modal-gerar-chamado');

            if ( ! form || ! modal) {
                return;
            }

            var btnSim = document.getElementById('confirmar-gerar-chamado');
            var btnNao = document.getElementById('cancelar-gerar-chamado');

            function fechar() {
                modal.classList.remove('visivel');
            }

            form.addEventListener('submit', function (evento) {
                evento.preventDefault();
                modal.classList.add('visivel');
            });

            btnNao.addEventListener('click', fechar);
            modal.addEventListener('click', function (evento) {
                if (evento.target === modal) {
                    fechar();
                }
            });
            document.addEventListener('keydown', function (evento) {
                if (evento.key === 'Escape') {
                    fechar();
                }
            });

            btnSim.addEventListener('click', function () {
                fechar();
                form.submit();
            });
        })();
    </script>
<?php endif; ?>

<script src="<?= base_url('assets/js/autocomplete.js') ?>"></script>
<script src="<?= base_url('assets/js/orcamentos.js') ?>"></script>
<script src="<?= base_url('assets/js/orcamentos_financeiro.js') ?>"></script>
