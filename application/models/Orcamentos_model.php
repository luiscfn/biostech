<?php
/**
 * Orcamentos_model - modulo Orcamentos (comercial + dimensionamento).
 *
 * Regras em docs/09-regras-orcamentos.md. Origem Access:
 *   ORCAMENTO_01/01A/01B/01C/02/03/04/05/LOG_01
 *   ORCAM_DIMEN_01/02/03/03A/04/06/07/LOG_01
 *
 * Formulas do comercial (01B / tela 031):
 *   AC_MO          = VALMAT>0 e VALMOBRA>0 ? VALMAT+VALMOBRA
 *                                           : (VALMAT=0 e VALMOBRA>0 ? VALMOBRA : VALMAT)
 *   AC_MO_MF       = MAT_FIXACAO>0 ? AC_MO+MAT_FIXACAO : AC_MO
 *   AC_MO_MF_CT    = ACRESCIMO_CUSTO>0 ? AC_MO_MF+ACRESCIMO_CUSTO : AC_MO_MF
 *   AC_MO_MF_CT_NF = VALIMPOSTO>0 ? AC_MO_MF_CT+VALIMPOSTO : AC_MO_MF_CT
 *   SUBTOTAL       = AC_MO_MF_CT_NF
 *   VALTOTAL       = max(0, SUBTOTAL + VALSERV - VALDESC)
 *
 * Percentuais (quando informados, prevalecem sobre o valor digitado):
 *   mao_obra    = perc * VALMAT / 100
 *   mat_fixacao = perc * AC_MO / 100
 *   acrescimo   = perc * AC_MO_MF / 100
 *   imposto     = perc * AC_MO_MF_CT / 100
 *   desconto    = perc * SUBTOTAL / 100
 */
class Orcamentos_model extends MY_Model
{
    /** campos do cabecalho comercial que entram no log por campo */
    private static $campos = array(
        'data', 'cliente_id', 'chamado_numero', 'equipamento_serie',
        'nota_fiscal_chegada', 'atencao', 'finalidade', 'observacao',
        'condicao_pagamento', 'entrega', 'garantia', 'validade',
        'validade_pecas', 'assinatura', 'dimensionamento_numero',
        'situacao', 'resultado', 'data_envio', 'data_aprovacao',
        'valor_material', 'valor_mao_obra', 'valor_servico', 'valor_imposto',
        'valor_desconto', 'valor_total',
    );

    /** colunas monetarias NOT NULL */
    private static $dinheiro = array(
        'valor_material', 'valor_mao_obra', 'valor_servico', 'valor_imposto',
        'valor_desconto', 'valor_total', 'valor_material_original',
        'material_fixacao', 'acrescimo_custo', 'valor_custos_indiretos',
        'valor_maximo_cliente',
    );

    /** campos do cabecalho do dimensionamento (inclui levantamento/cronograma) */
    private static $campos_dim = array(
        'data', 'chamado_numero', 'cliente_id', 'contato', 'tecnico_id',
        'local_instalacao', 'tipo_contato', 'setor', 'email', 'qtd_tecnicos',
        'qtd_comerciais', 'qtd_nao_comerciais', 'solicitante',
        'qtd_dias', 'que_funciona', 'quer_fazer', 'dificuldades',
        'material_precisa', 'providenciar', 'observacao', 'altura',
        'comprimento_muro', 'lado_direito', 'lado_esquerdo', 'frente', 'fundo',
        'rebocado', 'cerca_arame', 'telhado', 'arvore', 'cinta_cimento',
        'pega_ladrao', 'percentual_mat_fixacao', 'material_fixacao',
        'percentual_mao_obra', 'mao_obra', 'percentual_nota_fiscal',
        'nota_fiscal', 'percentual_acrescimo_custo', 'acrescimo_custo',
        'segunda', 'segunda_inicio', 'segunda_fim',
        'terca', 'terca_inicio', 'terca_fim',
        'quarta', 'quarta_inicio', 'quarta_fim',
        'quinta', 'quinta_inicio', 'quinta_fim',
        'sexta', 'sexta_inicio', 'sexta_fim',
        'sabado', 'sabado_inicio', 'sabado_fim',
        'domingo', 'domingo_inicio', 'domingo_fim',
    );

    /** flags booleanas do dimensionamento */
    private static $flags_dim = array(
        'rebocado', 'cerca_arame', 'telhado', 'arvore', 'cinta_cimento',
        'pega_ladrao', 'segunda', 'terca', 'quarta', 'quinta', 'sexta',
        'sabado', 'domingo',
    );

    /** horas (decimal) do cronograma */
    private static $horas_dim = array(
        'segunda_inicio', 'segunda_fim', 'terca_inicio', 'terca_fim',
        'quarta_inicio', 'quarta_fim', 'quinta_inicio', 'quinta_fim',
        'sexta_inicio', 'sexta_fim', 'sabado_inicio', 'sabado_fim',
        'domingo_inicio', 'domingo_fim',
    );

    public function __construct()
    {
        parent::__construct();
        $this->tabela = T_ORCAMENTOS;
        $this->pk     = 'numero';
        $this->load->library('numeracao');
        $this->load->model('dominio_model');
    }

    // ==================================================================
    // Consultas
    // ==================================================================

    public function stats()
    {
        return $this->db->query(
            "SELECT count(*) AS total,
                    count(*) FILTER (WHERE situacao IN (1,2,3)) AS abertos,
                    count(*) FILTER (WHERE resultado = 1) AS aprovados,
                    count(*) FILTER (WHERE situacao = 5) AS encerrados,
                    COALESCE(SUM(valor_total) FILTER (WHERE situacao <> 5), 0) AS valor_aberto,
                    count(*) FILTER (WHERE chamado_numero IS NOT NULL) AS com_os,
                    count(*) FILTER (WHERE dimensionamento_numero IS NOT NULL) AS com_dim
               FROM " . T_ORCAMENTOS
        )->row_array();
    }

    // ------------------------------------------------------------------

    private function _filtro($f)
    {
        $where  = '1=1';
        $params = array();

        if ( ! empty($f['busca']))
        {
            $where .= " AND (o.numero::text = ? OR o.atencao ILIKE ? OR cl.nome ILIKE ?
                             OR o.chamado_numero ILIKE ?)";
            $termo    = trim($f['busca']);
            $params[] = $termo;
            $params[] = '%' . $termo . '%';
            $params[] = '%' . $termo . '%';
            $params[] = '%' . $termo . '%';
        }

        if ( ! empty($f['cliente']))
        {
            $where .= ' AND o.cliente_id = ?';
            $params[] = (int) $f['cliente'];
        }

        if ( ! empty($f['situacao']))
        {
            $where .= ' AND o.situacao = ?';
            $params[] = (int) $f['situacao'];
        }

        if ( ! empty($f['resultado']))
        {
            $where .= ' AND o.resultado = ?';
            $params[] = (int) $f['resultado'];
        }

        if ( ! empty($f['data_de']))
        {
            $where .= ' AND o.data >= ?';
            $params[] = $f['data_de'];
        }

        if ( ! empty($f['data_ate']))
        {
            $where .= ' AND o.data <= ?';
            $params[] = $f['data_ate'];
        }

        if ( ! empty($f['com_os']))
        {
            $where .= $f['com_os'] === 'S'
                ? ' AND o.chamado_numero IS NOT NULL'
                : ' AND o.chamado_numero IS NULL';
        }

        if ( ! empty($f['com_dim']))
        {
            $where .= $f['com_dim'] === 'S'
                ? ' AND o.dimensionamento_numero IS NOT NULL'
                : ' AND o.dimensionamento_numero IS NULL';
        }

        return array($where, $params);
    }

    // ------------------------------------------------------------------

    public function listar($f = array(), $limit = NULL, $offset = NULL)
    {
        list($where, $params) = $this->_filtro($f);

        $sql = "SELECT o.*,
                       cl.nome AS cliente_nome,
                       cl.fantasia AS cliente_fantasia,
                       ch.categoria AS chamado_categoria,
                       ch.data_abertura AS chamado_data,
                       ds.nome AS situacao_nome,
                       dr.nome AS resultado_nome,
                       COALESCE(it.itens, 0) AS qtd_itens,
                       COALESCE(sv.servicos, 0) AS qtd_servicos
                  FROM " . T_ORCAMENTOS . " o
                  LEFT JOIN " . T_CLIENTES . " cl ON cl.id = o.cliente_id
                  LEFT JOIN " . T_CHAMADOS . " ch ON ch.numero = o.chamado_numero
                  LEFT JOIN " . T_DOMINIOS . " ds
                         ON ds.tipo = 'situacao_orcamento' AND ds.codigo = o.situacao
                  LEFT JOIN " . T_DOMINIOS . " dr
                         ON dr.tipo = 'resultado_orcamento' AND dr.codigo = o.resultado
                  LEFT JOIN (SELECT orcamento_numero, count(*) AS itens
                               FROM " . T_ORCAMENTO_ITENS . "
                              GROUP BY orcamento_numero) it
                         ON it.orcamento_numero = o.numero
                  LEFT JOIN (SELECT orcamento_numero, count(*) AS servicos
                               FROM " . T_ORCAMENTO_SERVICOS . "
                              GROUP BY orcamento_numero) sv
                         ON sv.orcamento_numero = o.numero
                 WHERE {$where}
                 ORDER BY o.data DESC, o.numero DESC";

        if ($limit !== NULL)
        {
            $sql .= ' LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
        }

        return $this->db->query($sql, $params)->result_array();
    }

    // ------------------------------------------------------------------

    public function total($f = array())
    {
        list($where, $params) = $this->_filtro($f);

        $row = $this->db->query(
            "SELECT count(*) AS total
               FROM " . T_ORCAMENTOS . " o
               LEFT JOIN " . T_CLIENTES . " cl ON cl.id = o.cliente_id
              WHERE {$where}",
            $params
        )->row_array();

        return (int) $row['total'];
    }

    // ------------------------------------------------------------------

    public function por_numero($numero)
    {
        return $this->db->query(
            "SELECT o.*,
                    cl.nome AS cliente_nome,
                    cl.fantasia AS cliente_fantasia,
                    cl.cpf_cnpj AS cliente_documento,
                    cl.endereco AS cliente_endereco,
                    cl.numero AS cliente_numero,
                    cl.complemento AS cliente_complemento,
                    cl.bairro AS cliente_bairro,
                    cl.cidade AS cliente_cidade,
                    cl.uf AS cliente_uf,
                    cl.cep AS cliente_cep,
                    cl.telefone AS cliente_telefone,
                    cl.email AS cliente_email,
                    ch.categoria AS chamado_categoria,
                    ch.data_abertura AS chamado_data,
                    ds.nome AS situacao_nome,
                    dr.nome AS resultado_nome,
                    uc.nome AS usuario_cadastro_nome,
                    ua.nome AS usuario_alteracao_nome,
                    uf.nome AS usuario_fechamento_nome
               FROM " . T_ORCAMENTOS . " o
               LEFT JOIN " . T_CLIENTES . " cl ON cl.id = o.cliente_id
               LEFT JOIN " . T_CHAMADOS . " ch ON ch.numero = o.chamado_numero
               LEFT JOIN " . T_DOMINIOS . " ds
                      ON ds.tipo = 'situacao_orcamento' AND ds.codigo = o.situacao
               LEFT JOIN " . T_DOMINIOS . " dr
                      ON dr.tipo = 'resultado_orcamento' AND dr.codigo = o.resultado
               LEFT JOIN " . T_USUARIOS . " uc ON uc.login = o.usuario_cadastro
               LEFT JOIN " . T_USUARIOS . " ua ON ua.login = o.usuario_alteracao
               LEFT JOIN " . T_USUARIOS . " uf ON uf.login = o.usuario_fechamento
              WHERE o.numero = ?",
            array((int) $numero)
        )->row_array();
    }

    // ------------------------------------------------------------------

    public function itens($numero)
    {
        return $this->db->query(
            "SELECT i.*, p.descricao AS produto_descricao, p.unidade AS produto_unidade,
                    p.codigo AS produto_codigo
               FROM " . T_ORCAMENTO_ITENS . " i
               LEFT JOIN " . T_PRODUTOS . " p ON p.id = i.produto_id
              WHERE i.orcamento_numero = ?
              ORDER BY i.numero_item",
            array((int) $numero)
        )->result_array();
    }

    // ------------------------------------------------------------------

    public function servicos($numero)
    {
        return $this->db->query(
            "SELECT * FROM " . T_ORCAMENTO_SERVICOS . "
              WHERE orcamento_numero = ?
              ORDER BY numero_item",
            array((int) $numero)
        )->result_array();
    }

    // ------------------------------------------------------------------

    public function servicos_valores($numero)
    {
        return $this->db->query(
            "SELECT * FROM " . T_ORCAMENTO_SERVICOS_VALORES . "
              WHERE orcamento_numero = ?
              ORDER BY numero_item",
            array((int) $numero)
        )->result_array();
    }

    // ------------------------------------------------------------------

    public function contatos($numero)
    {
        return $this->db->query(
            "SELECT c.*, u.nome AS funcionario_nome
               FROM " . T_ORCAMENTO_CONTATOS . " c
               LEFT JOIN " . T_USUARIOS . " u ON u.funcionario_id = c.funcionario_id
              WHERE c.orcamento_numero = ?
              ORDER BY c.data_mov DESC, c.hora_mov DESC",
            array((int) $numero)
        )->result_array();
    }

    // ------------------------------------------------------------------

    public function log($numero)
    {
        return $this->db->query(
            "SELECT l.*, u.nome AS usuario_nome
               FROM " . T_ORCAMENTO_LOG . " l
               LEFT JOIN " . T_USUARIOS . " u ON u.login = l.usuario
              WHERE l.orcamento_numero = ?
              ORDER BY l.data_mov DESC, l.id DESC",
            array((int) $numero)
        )->result_array();
    }

    // ==================================================================
    // Combos e buscas
    // ==================================================================

    public function situacoes()
    {
        return $this->dominio_model->opcoes('situacao_orcamento');
    }

    public function resultados()
    {
        return $this->dominio_model->opcoes('resultado_orcamento');
    }

    public function finalidades()
    {
        return $this->dominio_model->opcoes('finalidade');
    }

    public function clientes()
    {
        return $this->db->query(
            "SELECT id, nome FROM " . T_CLIENTES . " ORDER BY nome"
        )->result_array();
    }

    public function funcionarios()
    {
        return $this->db->query(
            "SELECT DISTINCT funcionario_id, nome
               FROM " . T_USUARIOS . "
              WHERE funcionario_id IS NOT NULL
              ORDER BY nome"
        )->result_array();
    }

    // ------------------------------------------------------------------

    public function buscar_clientes($termo, $limite = 25)
    {
        $like = '%' . trim((string) $termo) . '%';

        return $this->db->query(
            "SELECT id, nome, fantasia, cpf_cnpj, cidade, uf
               FROM " . T_CLIENTES . "
              WHERE ativo IS NOT FALSE
                AND (nome ILIKE ? OR fantasia ILIKE ? OR cpf_cnpj ILIKE ?)
              ORDER BY nome
              LIMIT " . (int) $limite,
            array($like, $like, $like)
        )->result_array();
    }

    // ------------------------------------------------------------------

    public function buscar_produtos($termo, $limite = 25)
    {
        $termo = trim((string) $termo);

        if ($termo === '')
        {
            return array();
        }

        $like = '%' . $termo . '%';

        return $this->db->query(
            "SELECT id, codigo, descricao, unidade, valor_venda, valor_compra
               FROM " . T_PRODUTOS . "
              WHERE id > 0
                AND (codigo ILIKE ? OR descricao ILIKE ? OR id::text = ?)
              ORDER BY descricao
              LIMIT " . (int) $limite,
            array($like, $like, $termo)
        )->result_array();
    }

    // ==================================================================
    // Gravacao do cabecalho comercial
    // ==================================================================

    /**
     * Cria/atualiza o orcamento. Retorna array(ok, numero, erro).
     */
    public function salvar($dados, $usuario)
    {
        $numero = isset($dados['numero']) ? (int) $dados['numero'] : 0;
        $antes  = $numero ? $this->por_numero($numero) : NULL;

        if ($numero && empty($antes))
        {
            return array('ok' => FALSE, 'erro' => 'Orcamento nao encontrado.');
        }

        $registro = array(
            'data'                       => $this->_data($this->_v($dados, 'data')) ?: date('Y-m-d'),
            'cliente_id'                 => $this->_int($this->_v($dados, 'cliente_id')),
            'chamado_numero'             => $this->_texto($this->_v($dados, 'chamado_numero'), 16),
            'equipamento_serie'          => $this->_texto($this->_v($dados, 'equipamento_serie'), 30),
            'nota_fiscal_chegada'        => $this->_texto($this->_v($dados, 'nota_fiscal_chegada'), 20),
            'atencao'                    => $this->_texto($this->_v($dados, 'atencao'), 70),
            'finalidade'                 => $this->_int($this->_v($dados, 'finalidade')),
            'observacao'                 => $this->_texto($this->_v($dados, 'observacao')),
            'condicao_pagamento'         => $this->_int($this->_v($dados, 'condicao_pagamento')),
            'entrega'                    => $this->_int($this->_v($dados, 'entrega')),
            'garantia'                   => $this->_int($this->_v($dados, 'garantia')),
            'validade'                   => $this->_int($this->_v($dados, 'validade')),
            'validade_pecas'             => $this->_int($this->_v($dados, 'validade_pecas')),
            'assinatura'                 => $this->_texto($this->_v($dados, 'assinatura'), 50),
            'percentual_desconto'        => $this->_dec($this->_v($dados, 'percentual_desconto')),
            'percentual_imposto'         => $this->_dec($this->_v($dados, 'percentual_imposto')),
            'percentual_mao_obra'        => $this->_dec($this->_v($dados, 'percentual_mao_obra')),
            'percentual_servico'         => $this->_dec($this->_v($dados, 'percentual_servico')),
            'percentual_nota_fiscal'     => $this->_dec($this->_v($dados, 'percentual_nota_fiscal')),
            'percentual_mat_fixacao'     => $this->_dec($this->_v($dados, 'percentual_mat_fixacao')),
            'percentual_acrescimo_custo' => $this->_dec($this->_v($dados, 'percentual_acrescimo_custo')),
            'percentual_desconto_item'   => $this->_dec($this->_v($dados, 'percentual_desconto_item')),
            'percentual_acrescimo_item'  => $this->_dec($this->_v($dados, 'percentual_acrescimo_item')),
            'valor_mao_obra'             => (float) ($this->_dec($this->_v($dados, 'valor_mao_obra')) ?: 0),
            'valor_imposto'              => (float) ($this->_dec($this->_v($dados, 'valor_imposto')) ?: 0),
            'valor_desconto'             => (float) ($this->_dec($this->_v($dados, 'valor_desconto')) ?: 0),
            'material_fixacao'           => (float) ($this->_dec($this->_v($dados, 'material_fixacao')) ?: 0),
            'acrescimo_custo'            => (float) ($this->_dec($this->_v($dados, 'acrescimo_custo')) ?: 0),
            'valor_maximo_cliente'       => (float) ($this->_dec($this->_v($dados, 'valor_maximo_cliente')) ?: 0),
            'valor_custos_indiretos'     => (float) ($this->_dec($this->_v($dados, 'valor_custos_indiretos')) ?: 0),
        );

        if ( ! empty($dados['situacao']))
        {
            $registro['situacao'] = (int) $dados['situacao'];
        }

        if ( ! empty($dados['resultado']))
        {
            $registro['resultado'] = (int) $dados['resultado'];
        }

        if ($registro['cliente_id'] !== NULL)
        {
            $cli = $this->db->query(
                "SELECT nome FROM " . T_CLIENTES . " WHERE id = ?",
                array($registro['cliente_id'])
            )->row_array();

            if ( ! $cli)
            {
                $registro['cliente_id'] = NULL;
            }
            elseif ($registro['atencao'] === NULL)
            {
                $registro['atencao'] = $this->_texto($cli['nome'], 70);
            }
        }

        if ($registro['chamado_numero'] !== NULL)
        {
            $ok = $this->db->query(
                "SELECT 1 FROM " . T_CHAMADOS . " WHERE numero = ?",
                array($registro['chamado_numero'])
            )->row_array();

            if ( ! $ok)
            {
                $registro['chamado_numero'] = NULL;
            }
        }

        if (array_key_exists('resultado', $registro))
        {
            $registro['aprovado']     = $registro['resultado'] === 1 ? 'true' : 'false';
            $registro['nao_aprovado'] = $registro['resultado'] === 2 ? 'true' : 'false';
        }

        $this->db->trans_start();

        if ($numero)
        {
            $registro['usuario_alteracao'] = $usuario['login'];
            $registro['data_alteracao']    = date('Y-m-d H:i:s');

            $this->_atualizar(T_ORCAMENTOS, $numero, $registro, 'numero');
        }
        else
        {
            $numero = $this->numeracao->numero('ORC');

            if ( ! $numero)
            {
                $this->db->trans_complete();
                return array('ok' => FALSE, 'erro' => 'Nao foi possivel gerar o numero do orcamento.');
            }

            $registro['numero']           = $numero;
            $registro['situacao']         = isset($registro['situacao']) ? $registro['situacao'] : 1;
            $registro['resultado']        = isset($registro['resultado']) ? $registro['resultado'] : 3;
            $registro['aprovado']         = $registro['resultado'] === 1 ? 'true' : 'false';
            $registro['nao_aprovado']     = $registro['resultado'] === 2 ? 'true' : 'false';
            $registro['usuario_cadastro'] = $usuario['login'];

            $this->_inserir(T_ORCAMENTOS, $registro);
        }

        $this->recalcular($numero, $registro, FALSE);

        $depois = $this->por_numero($numero);

        if ($antes)
        {
            $this->_log_diff($numero, $antes, $depois, $usuario['login'], 'A');
        }
        else
        {
            $this->_log_criacao($numero, $depois, $usuario['login']);
        }

        $this->db->trans_complete();

        return $this->db->trans_status()
            ? array('ok' => TRUE, 'numero' => $numero)
            : array('ok' => FALSE, 'erro' => 'Falha ao gravar o orcamento.');
    }

    // ==================================================================
    // Calculos do comercial
    // ==================================================================

    /**
     * Recalcula os valores do orcamento. $dados tem precedencia sobre o
     * registro gravado (percentuais/valores informados no formulario).
     */
    public function recalcular($numero, $dados = array(), $log = FALSE)
    {
        $reg = $this->por_numero($numero);

        if (empty($reg))
        {
            return array();
        }

        $valor = function ($campo) use ($dados, $reg) {
            if (array_key_exists($campo, $dados))
            {
                return $dados[$campo];
            }

            return isset($reg[$campo]) ? $reg[$campo] : NULL;
        };

        $material = (float) $this->db->query(
            "SELECT COALESCE(SUM(valor_total), 0) AS total
               FROM " . T_ORCAMENTO_ITENS . " WHERE orcamento_numero = ?",
            array((int) $numero)
        )->row()->total;

        $servico = (float) $this->db->query(
            "SELECT COALESCE(SUM(valor_total), 0) AS total
               FROM " . T_ORCAMENTO_SERVICOS_VALORES . " WHERE orcamento_numero = ?",
            array((int) $numero)
        )->row()->total;

        // mao de obra -------------------------------------------------
        $mao_obra = (float) $this->_dec($valor('valor_mao_obra'));
        $perc_mo  = $this->_dec($valor('percentual_mao_obra'));

        if ($perc_mo !== NULL)
        {
            $mao_obra = round($perc_mo * $material / 100, 2);
        }

        $ac_mo = ($material > 0 && $mao_obra > 0)
            ? $material + $mao_obra
            : (($material == 0 && $mao_obra > 0) ? $mao_obra : $material);

        // material de fixacao -----------------------------------------
        $fixacao  = (float) $this->_dec($valor('material_fixacao'));
        $perc_fix = $this->_dec($valor('percentual_mat_fixacao'));

        if ($perc_fix !== NULL)
        {
            $fixacao = round($perc_fix * $ac_mo / 100, 2);
        }

        $ac_mo_mf = $fixacao > 0 ? $ac_mo + $fixacao : $ac_mo;

        // acrescimo de custo ------------------------------------------
        $acrescimo = (float) $this->_dec($valor('acrescimo_custo'));
        $perc_acr  = $this->_dec($valor('percentual_acrescimo_custo'));

        if ($perc_acr !== NULL)
        {
            $acrescimo = round($perc_acr * $ac_mo_mf / 100, 2);
        }

        $ac_mo_mf_ct = $acrescimo > 0 ? $ac_mo_mf + $acrescimo : $ac_mo_mf;

        // imposto / nota fiscal ---------------------------------------
        $imposto  = (float) $this->_dec($valor('valor_imposto'));
        $perc_imp = $this->_dec($valor('percentual_nota_fiscal'));

        if ($perc_imp === NULL)
        {
            $perc_imp = $this->_dec($valor('percentual_imposto'));
        }

        if ($perc_imp !== NULL)
        {
            $imposto = round($perc_imp * $ac_mo_mf_ct / 100, 2);
        }

        $subtotal = $imposto > 0 ? $ac_mo_mf_ct + $imposto : $ac_mo_mf_ct;

        // desconto -----------------------------------------------------
        $desconto  = (float) $this->_dec($valor('valor_desconto'));
        $perc_desc = $this->_dec($valor('percentual_desconto'));

        if ($perc_desc !== NULL)
        {
            $desconto = round($perc_desc * $subtotal / 100, 2);
        }

        $total = max(0, round($subtotal + $servico - $desconto, 2));

        $novo = array(
            'valor_material'   => round($material, 2),
            'valor_servico'    => round($servico, 2),
            'valor_mao_obra'   => round($mao_obra, 2),
            'material_fixacao' => round($fixacao, 2),
            'acrescimo_custo'  => round($acrescimo, 2),
            'valor_imposto'    => round($imposto, 2),
            'valor_desconto'   => round($desconto, 2),
            'valor_total'      => $total,
        );

        $diferencas = array();

        foreach ($novo as $campo => $valorNovo)
        {
            if (round((float) $reg[$campo], 2) !== $valorNovo)
            {
                $diferencas[$campo] = array($reg[$campo], $valorNovo);
            }
        }

        if ($diferencas)
        {
            $this->_atualizar(T_ORCAMENTOS, $numero, $novo, 'numero');

            if ($log)
            {
                foreach ($diferencas as $campo => $par)
                {
                    $this->_log($numero, 'A', $campo, $par[0], $par[1], NULL, NULL);
                }
            }
        }

        return $novo;
    }

    // ==================================================================
    // Exclusao / status
    // ==================================================================

    public function excluir_orcamento($numero, $usuario)
    {
        $reg = $this->por_numero($numero);

        if (empty($reg))
        {
            return array('ok' => FALSE, 'erro' => 'Orcamento nao encontrado.');
        }

        if ( ! empty($reg['chamado_numero']))
        {
            return array('ok' => FALSE, 'erro' => 'Orcamento vinculado ao chamado '
                . $reg['chamado_numero'] . '; exclua o vinculo antes.');
        }

        $this->db->trans_start();

        $this->_log($numero, 'A', NULL, NULL, NULL, array(
            'acao'     => 'exclusao',
            'registro' => $this->_json($reg),
            'usuario'  => $usuario['login'],
        ), $usuario['login']);

        $this->db->query("DELETE FROM " . T_ORCAMENTOS . " WHERE numero = ?", array((int) $numero));

        $this->db->trans_complete();

        return $this->db->trans_status()
            ? array('ok' => TRUE)
            : array('ok' => FALSE, 'erro' => 'Falha ao excluir o orcamento.');
    }

    // ------------------------------------------------------------------

    public function mudar_status($numero, $situacao, $resultado, $dados, $usuario)
    {
        $reg = $this->por_numero($numero);

        if (empty($reg))
        {
            return array('ok' => FALSE, 'erro' => 'Orcamento nao encontrado.');
        }

        $situacao  = ($situacao === NULL || $situacao === '') ? NULL : (int) $situacao;
        $resultado = ($resultado === NULL || $resultado === '') ? NULL : (int) $resultado;

        if ($situacao !== NULL && ($situacao < 1 || $situacao > 5))
        {
            return array('ok' => FALSE, 'erro' => 'Situacao invalida.');
        }

        if ($resultado !== NULL && ($resultado < 1 || $resultado > 7))
        {
            return array('ok' => FALSE, 'erro' => 'Resultado invalido.');
        }

        if (in_array($resultado, array(6, 7), TRUE)
            && $this->_vazio($this->_v($dados, 'motivo_cancelamento')))
        {
            return array('ok' => FALSE, 'erro' => 'Informe o motivo do cancelamento.');
        }

        $novo = array();

        if ($situacao !== NULL && (int) $reg['situacao'] !== $situacao)
        {
            $novo['situacao'] = $situacao;
        }

        if ($resultado !== NULL && (int) $reg['resultado'] !== $resultado)
        {
            $novo['resultado']      = $resultado;
            $novo['aprovado']       = $resultado === 1 ? 'true' : 'false';
            $novo['nao_aprovado']   = $resultado === 2 ? 'true' : 'false';
            $novo['data_resultado'] = date('Y-m-d');
        }

        $data_envio = $this->_data($this->_v($dados, 'data_envio'));

        if ($data_envio !== NULL)
        {
            $novo['data_envio'] = $data_envio;
        }

        $data_aprovacao = $this->_data($this->_v($dados, 'data_aprovacao'));

        if ($data_aprovacao !== NULL)
        {
            $novo['data_aprovacao'] = $data_aprovacao;
        }
        elseif ($resultado === 1 && empty($reg['data_aprovacao']))
        {
            $novo['data_aprovacao'] = date('Y-m-d');
        }

        if (in_array($resultado, array(6, 7), TRUE))
        {
            $novo['motivo_cancelamento'] = $this->_texto($this->_v($dados, 'motivo_cancelamento'));
        }

        if ( ! $novo)
        {
            return array('ok' => TRUE, 'numero' => $numero);
        }

        $novo['usuario_alteracao'] = $usuario['login'];
        $novo['data_alteracao']    = date('Y-m-d H:i:s');

        $this->db->trans_start();

        $this->_atualizar(T_ORCAMENTOS, $numero, $novo, 'numero');
        $this->_log_diff($numero, $reg, $this->por_numero($numero), $usuario['login'], 'A');

        $this->db->trans_complete();

        return $this->db->trans_status()
            ? array('ok' => TRUE, 'numero' => $numero)
            : array('ok' => FALSE, 'erro' => 'Falha ao alterar o status.');
    }

    // ==================================================================
    // Itens (ORCAMENTO_02)
    // ==================================================================

    public function item($id)
    {
        return $this->db->query(
            "SELECT * FROM " . T_ORCAMENTO_ITENS . " WHERE id = ?",
            array((int) $id)
        )->row_array();
    }

    // ------------------------------------------------------------------

    public function adicionar_item($numero, $dados, $usuario)
    {
        if (empty($this->por_numero($numero)))
        {
            return array('ok' => FALSE, 'erro' => 'Orcamento nao encontrado.');
        }

        $qtde = (int) round((float) $this->_dec($this->_v($dados, 'quantidade')));

        if ($qtde <= 0)
        {
            return array('ok' => FALSE, 'erro' => 'Informe a quantidade.');
        }

        $unit  = (float) ($this->_dec($this->_v($dados, 'valor_unitario')) ?: 0);
        $total = $this->_dec($this->_v($dados, 'valor_total'));
        $total = $total !== NULL ? (float) $total : round($qtde * $unit, 2);

        $produto          = $this->_int($this->_v($dados, 'produto_id'));
        $descricaoProduto = $this->_texto($this->_v($dados, 'descricao_produto'), 100);

        if ($produto !== NULL)
        {
            $p = $this->db->query(
                "SELECT descricao FROM " . T_PRODUTOS . " WHERE id = ?",
                array($produto)
            )->row_array();

            if ( ! $p)
            {
                $produto = NULL;
            }
            elseif ($descricaoProduto === NULL)
            {
                $descricaoProduto = $this->_texto($p['descricao'], 100);
            }
        }

        $proximo = (int) $this->db->query(
            "SELECT COALESCE(MAX(numero_item), 0) + 1 AS n
               FROM " . T_ORCAMENTO_ITENS . " WHERE orcamento_numero = ?",
            array((int) $numero)
        )->row()->n;

        $this->db->trans_start();

        $this->_inserir(T_ORCAMENTO_ITENS, array(
            'orcamento_numero'        => (int) $numero,
            'numero_item'             => $proximo,
            'produto_id'              => $produto,
            'descricao'               => $this->_texto($this->_v($dados, 'descricao'), 100),
            'descricao_produto'       => $descricaoProduto,
            'quantidade'              => $qtde,
            'valor_unitario'          => $unit,
            'valor_total'             => $total,
            'valor_unitario_original' => $unit,
            'valor_total_original'    => $total,
        ));

        $this->recalcular($numero, array(), FALSE);

        $this->_log($numero, 'A', 'item_incluido', NULL,
            $descricaoProduto . ' (' . $qtde . ' x ' . number_format($unit, 2, ',', '.') . ')',
            array('item' => $proximo), $usuario['login']);

        $this->db->trans_complete();

        return $this->db->trans_status()
            ? array('ok' => TRUE)
            : array('ok' => FALSE, 'erro' => 'Falha ao incluir o item.');
    }

    // ------------------------------------------------------------------

    public function atualizar_item($id, $dados, $usuario)
    {
        $item = $this->item($id);

        if (empty($item))
        {
            return array('ok' => FALSE, 'erro' => 'Item nao encontrado.');
        }

        $qtde = (int) round((float) $this->_dec($this->_v($dados, 'quantidade')));

        if ($qtde <= 0)
        {
            return array('ok' => FALSE, 'erro' => 'Informe a quantidade.');
        }

        $unit  = (float) ($this->_dec($this->_v($dados, 'valor_unitario')) ?: 0);
        $total = $this->_dec($this->_v($dados, 'valor_total'));
        $total = $total !== NULL ? (float) $total : round($qtde * $unit, 2);

        $novo = array(
            'quantidade'     => $qtde,
            'valor_unitario' => $unit,
            'valor_total'    => $total,
            'descricao'      => $this->_texto($this->_v($dados, 'descricao'), 100),
        );

        // O "original" acompanha o valor digitado: se houver desconto ou
        // acrescimo em massa ativo, o original e recalculado pelo inverso do
        // fator, para que "restaurar" continue devolvendo o valor de tabela.
        $reg   = $this->por_numero((int) $item['orcamento_numero']);
        $fator = 1.0;

        if ($reg && $reg['percentual_desconto_item'] !== NULL)
        {
            $fator = 1 - ((float) $reg['percentual_desconto_item'] / 100);
        }
        elseif ($reg && $reg['percentual_acrescimo_item'] !== NULL)
        {
            $fator = 1 + ((float) $reg['percentual_acrescimo_item'] / 100);
        }

        if ($fator > 0)
        {
            $novo['valor_unitario_original'] = round($unit / $fator, 2);
            $novo['valor_total_original']    = round($total / $fator, 2);
        }

        $this->db->trans_start();

        $this->_atualizar(T_ORCAMENTO_ITENS, $id, $novo, 'id');
        $this->recalcular((int) $item['orcamento_numero'], array(), FALSE);

        $this->_log((int) $item['orcamento_numero'], 'A', 'item_alterado',
            $item['quantidade'] . ' x ' . $item['valor_unitario'],
            $qtde . ' x ' . $unit, array('item' => $item['numero_item']), $usuario['login']);

        $this->db->trans_complete();

        return $this->db->trans_status()
            ? array('ok' => TRUE)
            : array('ok' => FALSE, 'erro' => 'Falha ao alterar o item.');
    }

    // ------------------------------------------------------------------

    public function excluir_item($id, $usuario)
    {
        $item = $this->item($id);

        if (empty($item))
        {
            return array('ok' => FALSE, 'erro' => 'Item nao encontrado.');
        }

        $this->db->trans_start();

        $this->db->query("DELETE FROM " . T_ORCAMENTO_ITENS . " WHERE id = ?", array((int) $id));
        $this->recalcular((int) $item['orcamento_numero'], array(), FALSE);

        $this->_log((int) $item['orcamento_numero'], 'A', 'item_excluido',
            $item['descricao_produto'], NULL, array('item' => $item['numero_item']),
            $usuario['login']);

        $this->db->trans_complete();

        return $this->db->trans_status()
            ? array('ok' => TRUE)
            : array('ok' => FALSE, 'erro' => 'Falha ao excluir o item.');
    }

    // ------------------------------------------------------------------

    /**
     * Aplica desconto/acrescimo percentual em todos os itens. $tipo:
     * desconto | acrescimo. A base e sempre o valor original.
     */
    public function aplicar_percentual($numero, $tipo, $percentual, $usuario)
    {
        if (empty($this->por_numero($numero)))
        {
            return array('ok' => FALSE, 'erro' => 'Orcamento nao encontrado.');
        }

        $percentual = (float) $this->_dec($percentual);

        if ($percentual < 0 || $percentual > 100)
        {
            return array('ok' => FALSE, 'erro' => 'Informe um percentual entre 0 e 100.');
        }

        $itens = $this->itens($numero);

        if (empty($itens))
        {
            return array('ok' => FALSE, 'erro' => 'O orcamento nao possui itens.');
        }

        $fator = $tipo === 'desconto' ? (1 - ($percentual / 100)) : (1 + ($percentual / 100));

        $this->db->trans_start();

        foreach ($itens as $item)
        {
            $unit  = (float) $item['valor_unitario_original'];
            $total = (float) $item['valor_total_original'];

            if ($unit == 0.0 && $total == 0.0)
            {
                $unit  = (float) $item['valor_unitario'];
                $total = (float) $item['valor_total'];
            }

            $this->_atualizar(T_ORCAMENTO_ITENS, $item['id'], array(
                'valor_unitario_original' => $unit,
                'valor_total_original'    => $total,
                'valor_unitario'          => round($unit * $fator, 2),
                'valor_total'             => round($total * $fator, 2),
            ), 'id');
        }

        $this->_atualizar(T_ORCAMENTOS, $numero, array(
            'percentual_desconto_item'  => $tipo === 'desconto' ? $percentual : NULL,
            'percentual_acrescimo_item' => $tipo === 'acrescimo' ? $percentual : NULL,
        ), 'numero');
        $this->recalcular($numero, array(), FALSE);

        $this->_log($numero, 'A', $tipo === 'desconto' ? 'desconto_itens' : 'acrescimo_itens',
            NULL, number_format($percentual, 2, ',', '.') . '%', NULL, $usuario['login']);

        $this->db->trans_complete();

        return $this->db->trans_status()
            ? array('ok' => TRUE)
            : array('ok' => FALSE, 'erro' => 'Falha ao aplicar o percentual.');
    }

    // ------------------------------------------------------------------

    /** Volta os itens aos valores originais (C_RETORNA_VALOR_ORIGINAL_ITEM_*). */
    public function restaurar_valores($numero, $usuario)
    {
        $itens = $this->itens($numero);

        if (empty($itens))
        {
            return array('ok' => FALSE, 'erro' => 'O orcamento nao possui itens.');
        }

        $this->db->trans_start();

        foreach ($itens as $item)
        {
            if ((float) $item['valor_unitario_original'] == 0.0
                && (float) $item['valor_total_original'] == 0.0)
            {
                continue;
            }

            $this->_atualizar(T_ORCAMENTO_ITENS, $item['id'], array(
                'valor_unitario' => $item['valor_unitario_original'],
                'valor_total'    => $item['valor_total_original'],
            ), 'id');
        }

        $this->_atualizar(T_ORCAMENTOS, $numero, array(
            'percentual_desconto_item'  => NULL,
            'percentual_acrescimo_item' => NULL,
        ), 'numero');

        $this->recalcular($numero, array(), FALSE);
        $this->_log($numero, 'A', 'valores_restaurados', NULL, NULL, NULL, $usuario['login']);

        $this->db->trans_complete();

        return $this->db->trans_status()
            ? array('ok' => TRUE)
            : array('ok' => FALSE, 'erro' => 'Falha ao restaurar os valores.');
    }

    // ==================================================================
    // Servicos a executar (ORCAMENTO_05)
    // ==================================================================

    public function adicionar_servico($numero, $dados, $usuario)
    {
        $descricao = $this->_texto($this->_v($dados, 'descricao'));

        if ($descricao === NULL)
        {
            return array('ok' => FALSE, 'erro' => 'Informe a descricao do servico.');
        }

        if (empty($this->por_numero($numero)))
        {
            return array('ok' => FALSE, 'erro' => 'Orcamento nao encontrado.');
        }

        $proximo = (int) $this->db->query(
            "SELECT COALESCE(MAX(numero_item), 0) + 1 AS n
               FROM " . T_ORCAMENTO_SERVICOS . " WHERE orcamento_numero = ?",
            array((int) $numero)
        )->row()->n;

        $this->_inserir(T_ORCAMENTO_SERVICOS, array(
            'orcamento_numero' => (int) $numero,
            'numero_item'      => $proximo,
            'descricao'        => $descricao,
            'concluido'        => bd_bool($this->_v($dados, 'concluido')) ? 'true' : 'false',
        ));

        $this->_log($numero, 'A', 'servico_incluido', NULL, $descricao, NULL, $usuario['login']);

        return array('ok' => TRUE);
    }

    // ------------------------------------------------------------------

    public function atualizar_servico($id, $dados, $usuario)
    {
        $servico = $this->db->query(
            "SELECT * FROM " . T_ORCAMENTO_SERVICOS . " WHERE id = ?",
            array((int) $id)
        )->row_array();

        if (empty($servico))
        {
            return array('ok' => FALSE, 'erro' => 'Servico nao encontrado.');
        }

        $novo = array();

        if (array_key_exists('descricao', $dados))
        {
            $novo['descricao'] = $this->_texto($this->_v($dados, 'descricao')) ?: $servico['descricao'];
        }

        if (array_key_exists('concluido', $dados))
        {
            $novo['concluido'] = bd_bool($this->_v($dados, 'concluido')) ? 'true' : 'false';
        }

        if ($novo)
        {
            $this->_atualizar(T_ORCAMENTO_SERVICOS, $id, $novo, 'id');

            $this->_log((int) $servico['orcamento_numero'], 'A', 'servico_alterado',
                $servico['descricao'], isset($novo['descricao']) ? $novo['descricao'] : NULL,
                NULL, $usuario['login']);
        }

        return array('ok' => TRUE);
    }

    // ------------------------------------------------------------------

    public function excluir_servico($id, $usuario)
    {
        $servico = $this->db->query(
            "SELECT * FROM " . T_ORCAMENTO_SERVICOS . " WHERE id = ?",
            array((int) $id)
        )->row_array();

        if (empty($servico))
        {
            return array('ok' => FALSE, 'erro' => 'Servico nao encontrado.');
        }

        $this->db->query("DELETE FROM " . T_ORCAMENTO_SERVICOS . " WHERE id = ?", array((int) $id));

        $this->_log((int) $servico['orcamento_numero'], 'A', 'servico_excluido',
            $servico['descricao'], NULL, NULL, $usuario['login']);

        return array('ok' => TRUE);
    }

    // ==================================================================
    // Servicos com valor (ORCAMENTO_03) - compoem VALSERV
    // ==================================================================

    public function adicionar_servico_valor($numero, $dados, $usuario)
    {
        if (empty($this->por_numero($numero)))
        {
            return array('ok' => FALSE, 'erro' => 'Orcamento nao encontrado.');
        }

        $descricao = $this->_texto($this->_v($dados, 'descricao'));

        if ($descricao === NULL)
        {
            return array('ok' => FALSE, 'erro' => 'Informe a descricao do servico.');
        }

        $qtde = (int) round((float) $this->_dec($this->_v($dados, 'quantidade')));

        if ($qtde <= 0)
        {
            return array('ok' => FALSE, 'erro' => 'Informe a quantidade.');
        }

        $unit  = (float) ($this->_dec($this->_v($dados, 'valor_unitario')) ?: 0);
        $total = $this->_dec($this->_v($dados, 'valor_total'));
        $total = $total !== NULL ? (float) $total : round($qtde * $unit, 2);

        $proximo = (int) $this->db->query(
            "SELECT COALESCE(MAX(numero_item), 0) + 1 AS n
               FROM " . T_ORCAMENTO_SERVICOS_VALORES . " WHERE orcamento_numero = ?",
            array((int) $numero)
        )->row()->n;

        $this->db->trans_start();

        $this->_inserir(T_ORCAMENTO_SERVICOS_VALORES, array(
            'orcamento_numero' => (int) $numero,
            'numero_item'      => $proximo,
            'descricao'        => $descricao,
            'quantidade'       => $qtde,
            'valor_unitario'   => $unit,
            'valor_total'      => $total,
        ));

        $this->recalcular($numero, array(), FALSE);
        $this->_log($numero, 'A', 'servico_valor_incluido', NULL, $descricao, NULL, $usuario['login']);

        $this->db->trans_complete();

        return $this->db->trans_status()
            ? array('ok' => TRUE)
            : array('ok' => FALSE, 'erro' => 'Falha ao incluir o servico.');
    }

    // ------------------------------------------------------------------

    public function atualizar_servico_valor($id, $dados, $usuario)
    {
        $servico = $this->db->query(
            "SELECT * FROM " . T_ORCAMENTO_SERVICOS_VALORES . " WHERE id = ?",
            array((int) $id)
        )->row_array();

        if (empty($servico))
        {
            return array('ok' => FALSE, 'erro' => 'Servico nao encontrado.');
        }

        $qtde = (int) round((float) $this->_dec($this->_v($dados, 'quantidade')));

        if ($qtde <= 0)
        {
            return array('ok' => FALSE, 'erro' => 'Informe a quantidade.');
        }

        $unit  = (float) ($this->_dec($this->_v($dados, 'valor_unitario')) ?: 0);
        $total = $this->_dec($this->_v($dados, 'valor_total'));
        $total = $total !== NULL ? (float) $total : round($qtde * $unit, 2);

        $this->db->trans_start();

        $this->_atualizar(T_ORCAMENTO_SERVICOS_VALORES, $id, array(
            'descricao'      => $this->_texto($this->_v($dados, 'descricao')) ?: $servico['descricao'],
            'quantidade'     => $qtde,
            'valor_unitario' => $unit,
            'valor_total'    => $total,
        ), 'id');

        $this->recalcular((int) $servico['orcamento_numero'], array(), FALSE);

        $this->_log((int) $servico['orcamento_numero'], 'A', 'servico_valor_alterado',
            $servico['valor_total'], $total, NULL, $usuario['login']);

        $this->db->trans_complete();

        return $this->db->trans_status()
            ? array('ok' => TRUE)
            : array('ok' => FALSE, 'erro' => 'Falha ao alterar o servico.');
    }

    // ------------------------------------------------------------------

    public function excluir_servico_valor($id, $usuario)
    {
        $servico = $this->db->query(
            "SELECT * FROM " . T_ORCAMENTO_SERVICOS_VALORES . " WHERE id = ?",
            array((int) $id)
        )->row_array();

        if (empty($servico))
        {
            return array('ok' => FALSE, 'erro' => 'Servico nao encontrado.');
        }

        $this->db->trans_start();

        $this->db->query("DELETE FROM " . T_ORCAMENTO_SERVICOS_VALORES . " WHERE id = ?",
            array((int) $id));

        $this->recalcular((int) $servico['orcamento_numero'], array(), FALSE);

        $this->_log((int) $servico['orcamento_numero'], 'A', 'servico_valor_excluido',
            $servico['descricao'], NULL, NULL, $usuario['login']);

        $this->db->trans_complete();

        return $this->db->trans_status()
            ? array('ok' => TRUE)
            : array('ok' => FALSE, 'erro' => 'Falha ao excluir o servico.');
    }

    // ==================================================================
    // Contatos / follow-up (ORCAMENTO_04)
    // ==================================================================

    public function adicionar_contato($numero, $dados, $usuario)
    {
        if (empty($this->por_numero($numero)))
        {
            return array('ok' => FALSE, 'erro' => 'Orcamento nao encontrado.');
        }

        $this->_inserir(T_ORCAMENTO_CONTATOS, array(
            'orcamento_numero'     => (int) $numero,
            'data_mov'             => $this->_data($this->_v($dados, 'data_mov')) ?: date('Y-m-d'),
            'hora_mov'             => $this->_hora($this->_v($dados, 'hora_mov')) ?: date('H:i:s'),
            'assunto'              => $this->_texto($this->_v($dados, 'assunto'), 80),
            'funcionario_id'       => $this->_int($this->_v($dados, 'funcionario_id')),
            'contato'              => $this->_texto($this->_v($dados, 'contato'), 50),
            'descricao'            => $this->_texto($this->_v($dados, 'descricao')),
            'data_proximo_contato' => $this->_data($this->_v($dados, 'data_proximo_contato')),
            'hora_proximo_contato' => $this->_hora($this->_v($dados, 'hora_proximo_contato')),
        ));

        $this->_log($numero, 'A', 'contato_incluido', NULL,
            $this->_texto($this->_v($dados, 'assunto'), 80), NULL, $usuario['login']);

        return array('ok' => TRUE);
    }

    // ------------------------------------------------------------------

    public function excluir_contato($id, $usuario)
    {
        $contato = $this->db->query(
            "SELECT * FROM " . T_ORCAMENTO_CONTATOS . " WHERE id = ?",
            array((int) $id)
        )->row_array();

        if (empty($contato))
        {
            return array('ok' => FALSE, 'erro' => 'Contato nao encontrado.');
        }

        $this->db->query("DELETE FROM " . T_ORCAMENTO_CONTATOS . " WHERE id = ?", array((int) $id));

        $this->_log((int) $contato['orcamento_numero'], 'A', 'contato_excluido',
            $contato['assunto'], NULL, NULL, $usuario['login']);

        return array('ok' => TRUE);
    }

    // ==================================================================
    // Geracao de chamado (C_INCLUI_GERA_CTO_01..08A)
    // ==================================================================

    public function pode_gerar_chamado($numero)
    {
        $reg = $this->por_numero($numero);

        if (empty($reg))
        {
            return array('ok' => FALSE, 'erro' => 'Orcamento nao encontrado.');
        }

        if ( ! empty($reg['chamado_numero']))
        {
            return array('ok' => FALSE, 'erro' => 'Ja existe chamado gerado deste orcamento.');
        }

        if ((int) $reg['situacao'] < 4)
        {
            return array('ok' => FALSE, 'erro' => 'Mude o status do orcamento para Concluido ou Encerrado.');
        }

        if ((int) $reg['resultado'] !== 1)
        {
            return array('ok' => FALSE, 'erro' => 'Mude a situacao do orcamento para Aprovado.');
        }

        if (empty($reg['data_envio']))
        {
            return array('ok' => FALSE, 'erro' => 'Informe a data do envio.');
        }

        if (empty($reg['data_aprovacao']))
        {
            return array('ok' => FALSE, 'erro' => 'Informe a data da aprovacao.');
        }

        if (empty($reg['cliente_id']))
        {
            return array('ok' => FALSE, 'erro' => 'O orcamento nao possui cliente.');
        }

        $existe = $this->db->query(
            "SELECT numero FROM " . T_CHAMADOS . " WHERE orcamento_numero = ? LIMIT 1",
            array((int) $numero)
        )->row_array();

        if ($existe)
        {
            return array('ok' => FALSE, 'erro' => 'Ja existe chamado (' . $existe['numero']
                . ') para este orcamento.');
        }

        return array('ok' => TRUE);
    }

    // ------------------------------------------------------------------

    public function gerar_chamado($numero, $usuario)
    {
        $check = $this->pode_gerar_chamado($numero);

        if ( ! $check['ok'])
        {
            return $check;
        }

        $reg      = $this->por_numero($numero);
        $itens    = $this->itens($numero);
        $servicos = $this->servicos($numero);
        $valores  = $this->servicos_valores($numero);

        $this->db->trans_start();

        $chamado = $this->numeracao->numero_os(date('Y-m-d'));

        if ( ! $chamado)
        {
            $this->db->trans_complete();
            return array('ok' => FALSE, 'erro' => 'Nao foi possivel gerar o numero do chamado.');
        }

        $this->_inserir(T_CHAMADOS, array(
            'numero'           => $chamado,
            'categoria'        => 'Chamado',
            'cliente_id'       => (int) $reg['cliente_id'],
            'orcamento_numero' => (int) $numero,
            'data_abertura'    => date('Y-m-d'),
            'hora_abertura'    => date('H:i:s'),
            'status_codigo'    => 1,
            'situacao_codigo'  => 16,
            'valor_material'   => (float) $reg['valor_material'],
            'valor_servico'    => (float) $reg['valor_servico'],
            'valor_mao_obra'   => (float) $reg['valor_mao_obra'],
            'valor_imposto'    => (float) $reg['valor_imposto'],
            'acrescimo_custo'  => (float) $reg['acrescimo_custo'],
            'material_fixacao' => (float) $reg['material_fixacao'],
            'valor_desconto'   => (float) $reg['valor_desconto'],
            'defeito_relatado' => $reg['observacao'],
            'usuario_cadastro' => $usuario['login'],
        ));

        $finalidade = NULL;

        if ( ! empty($reg['finalidade']))
        {
            $finalidade = $this->dominio_model->nome('finalidade', (int) $reg['finalidade']);
        }

        $this->_inserir(T_CHAMADO_EQUIPAMENTO, array(
            'chamado_numero'      => $chamado,
            'equipamento_serie'   => $reg['equipamento_serie'],
            'nota_fiscal_chegada' => $reg['nota_fiscal_chegada'],
            'lista_equipamento'   => $finalidade,
            'recolhido'           => 'false',
        ));

        $this->_inserir(T_CHAMADO_ATENDIMENTO, array(
            'chamado_numero'      => $chamado,
            'tecnico_abertura_id' => $this->funcionario_id($usuario),
        ));

        $n = 0;
        foreach ($itens as $item)
        {
            $n++;
            $this->_inserir(T_CHAMADO_PECAS, array(
                'chamado_numero'    => $chamado,
                'numero_item'       => $n,
                'produto_id'        => $item['produto_id'],
                'descricao_produto' => $item['descricao_produto'] ?: $item['descricao'],
                'quantidade'        => $item['quantidade'],
                'valor_unitario'    => $item['valor_unitario'],
                'valor_total'       => $item['valor_total'],
                'saiu_estoque'      => 'false',
            ));
        }

        $n = 0;
        foreach ($servicos as $servico)
        {
            $n++;
            $this->_inserir(T_CHAMADO_SERVICOS, array(
                'chamado_numero' => $chamado,
                'numero_item'    => $n,
                'tipo'           => 'previsto',
                'descricao'      => $servico['descricao'],
                'quantidade'     => 1,
                'valor_unitario' => 0,
                'valor_total'    => 0,
                'concluido'      => $servico['concluido'] ? 'true' : 'false',
            ));
        }

        foreach ($valores as $servico)
        {
            $n++;
            $this->_inserir(T_CHAMADO_SERVICOS, array(
                'chamado_numero' => $chamado,
                'numero_item'    => $n,
                'tipo'           => 'previsto',
                'descricao'      => $servico['descricao'],
                'quantidade'     => $servico['quantidade'],
                'valor_unitario' => $servico['valor_unitario'],
                'valor_total'    => $servico['valor_total'],
                'concluido'      => 'false',
            ));
        }

        $this->_inserir(T_CHAMADO_EVENTOS, array(
            'chamado_numero' => $chamado,
            'tipo'           => 'orcamento',
            'descricao'      => 'Chamado gerado a partir do orcamento ' . (int) $numero,
            'usuario'        => $usuario['login'],
            'dados'          => json_encode(array('orcamento' => (int) $numero)),
        ));

        $this->_atualizar(T_ORCAMENTOS, $numero, array(
            'chamado_numero'     => $chamado,
            'usuario_fechamento' => $usuario['login'],
            'data_fechamento'    => date('Y-m-d H:i:s'),
        ), 'numero');

        $this->_log($numero, 'A', 'chamado_gerado', NULL, $chamado, NULL, $usuario['login']);

        $this->db->trans_complete();

        return $this->db->trans_status()
            ? array('ok' => TRUE, 'chamado' => $chamado)
            : array('ok' => FALSE, 'erro' => 'Falha ao gerar o chamado.');
    }

    // ==================================================================
    // Dimensionamento - consultas
    // ==================================================================

    public function stats_dimensionamento()
    {
        return $this->db->query(
            "SELECT count(*) AS total,
                    count(*) FILTER (WHERE orcamento_numero IS NULL) AS sem_orcamento,
                    count(*) FILTER (WHERE orcamento_numero IS NOT NULL) AS com_orcamento,
                    count(*) FILTER (WHERE chamado_numero IS NOT NULL) AS com_os
               FROM " . T_ORCAMENTOS_DIMENSIONAMENTO
        )->row_array();
    }

    // ------------------------------------------------------------------

    private function _filtro_dim($f)
    {
        $where  = '1=1';
        $params = array();

        if ( ! empty($f['busca']))
        {
            $where .= " AND (d.numero::text = ? OR cl.nome ILIKE ? OR d.contato ILIKE ?
                             OR d.local_instalacao ILIKE ? OR d.chamado_numero ILIKE ?)";
            $termo    = trim($f['busca']);
            $params[] = $termo;
            $params[] = '%' . $termo . '%';
            $params[] = '%' . $termo . '%';
            $params[] = '%' . $termo . '%';
            $params[] = '%' . $termo . '%';
        }

        if ( ! empty($f['cliente']))
        {
            $where .= ' AND d.cliente_id = ?';
            $params[] = (int) $f['cliente'];
        }

        if ( ! empty($f['data_de']))
        {
            $where .= ' AND d.data >= ?';
            $params[] = $f['data_de'];
        }

        if ( ! empty($f['data_ate']))
        {
            $where .= ' AND d.data <= ?';
            $params[] = $f['data_ate'];
        }

        if ( ! empty($f['com_orcamento']))
        {
            $where .= $f['com_orcamento'] === 'S'
                ? ' AND d.orcamento_numero IS NOT NULL'
                : ' AND d.orcamento_numero IS NULL';
        }

        if ( ! empty($f['com_os']))
        {
            $where .= $f['com_os'] === 'S'
                ? ' AND d.chamado_numero IS NOT NULL'
                : ' AND d.chamado_numero IS NULL';
        }

        return array($where, $params);
    }

    // ------------------------------------------------------------------

    public function listar_dimensionamentos($f = array(), $limit = NULL, $offset = NULL)
    {
        list($where, $params) = $this->_filtro_dim($f);

        $sql = "SELECT d.*,
                       cl.nome AS cliente_nome,
                       u.nome AS tecnico_nome,
                       o.data AS orcamento_data,
                       o.valor_total AS orcamento_valor,
                       COALESCE(it.itens, 0) AS qtd_itens,
                       COALESCE(it.total, 0) AS total_venda
                  FROM " . T_ORCAMENTOS_DIMENSIONAMENTO . " d
                  LEFT JOIN " . T_CLIENTES . " cl ON cl.id = d.cliente_id
                  LEFT JOIN " . T_USUARIOS . " u ON u.funcionario_id = d.tecnico_id
                  LEFT JOIN " . T_ORCAMENTOS . " o ON o.numero = d.orcamento_numero
                  LEFT JOIN (SELECT dimensionamento_numero,
                                    count(*) AS itens,
                                    SUM(total_venda) AS total
                               FROM " . T_ORCAMENTO_DIMENSIONAMENTO_ITENS . "
                              GROUP BY dimensionamento_numero) it
                         ON it.dimensionamento_numero = d.numero
                 WHERE {$where}
                 ORDER BY d.data DESC, d.numero DESC";

        if ($limit !== NULL)
        {
            $sql .= ' LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
        }

        return $this->db->query($sql, $params)->result_array();
    }

    // ------------------------------------------------------------------

    public function total_dimensionamentos($f = array())
    {
        list($where, $params) = $this->_filtro_dim($f);

        $row = $this->db->query(
            "SELECT count(*) AS total
               FROM " . T_ORCAMENTOS_DIMENSIONAMENTO . " d
               LEFT JOIN " . T_CLIENTES . " cl ON cl.id = d.cliente_id
              WHERE {$where}",
            $params
        )->row_array();

        return (int) $row['total'];
    }

    // ------------------------------------------------------------------

    public function dimensionamento($numero)
    {
        return $this->db->query(
            "SELECT d.*,
                    cl.nome AS cliente_nome,
                    cl.cpf_cnpj AS cliente_documento,
                    cl.telefone AS cliente_telefone,
                    u.nome AS tecnico_nome,
                    ch.categoria AS chamado_categoria,
                    o.data AS orcamento_data,
                    o.valor_total AS orcamento_valor,
                    o.situacao AS orcamento_situacao,
                    o.resultado AS orcamento_resultado
               FROM " . T_ORCAMENTOS_DIMENSIONAMENTO . " d
               LEFT JOIN " . T_CLIENTES . " cl ON cl.id = d.cliente_id
               LEFT JOIN " . T_USUARIOS . " u ON u.funcionario_id = d.tecnico_id
               LEFT JOIN " . T_CHAMADOS . " ch ON ch.numero = d.chamado_numero
               LEFT JOIN " . T_ORCAMENTOS . " o ON o.numero = d.orcamento_numero
              WHERE d.numero = ?",
            array((int) $numero)
        )->row_array();
    }

    // ------------------------------------------------------------------

    public function itens_dimensionamento($numero)
    {
        return $this->db->query(
            "SELECT i.*, p.unidade AS produto_unidade, p.codigo AS produto_codigo,
                    p.valor_venda AS produto_valor_venda
               FROM " . T_ORCAMENTO_DIMENSIONAMENTO_ITENS . " i
               LEFT JOIN " . T_PRODUTOS . " p ON p.id = i.produto_id
              WHERE i.dimensionamento_numero = ?
              ORDER BY i.numero_item",
            array((int) $numero)
        )->result_array();
    }

    // ------------------------------------------------------------------

    public function servicos_dimensionamento($numero)
    {
        return $this->db->query(
            "SELECT * FROM " . T_ORCAMENTO_DIMENSIONAMENTO_SERVICOS . "
              WHERE dimensionamento_numero = ?
              ORDER BY numero_item",
            array((int) $numero)
        )->result_array();
    }

    // ------------------------------------------------------------------

    public function materiais_dimensionamento($numero)
    {
        return $this->db->query(
            "SELECT * FROM " . T_ORCAMENTO_DIMENSIONAMENTO_MATERIAIS . "
              WHERE dimensionamento_numero = ?
              ORDER BY numero_item",
            array((int) $numero)
        )->result_array();
    }

    // ------------------------------------------------------------------

    public function cotacoes_dimensionamento($numero)
    {
        return $this->db->query(
            "SELECT c.*, p.descricao AS produto_descricao
               FROM " . T_ORCAMENTO_DIMENSIONAMENTO_COTACOES . " c
               LEFT JOIN " . T_PRODUTOS . " p ON p.id = c.produto_id
              WHERE c.dimensionamento_numero = ?
              ORDER BY c.produto_id, c.preco_unitario",
            array((int) $numero)
        )->result_array();
    }

    // ------------------------------------------------------------------

    public function log_dimensionamento($numero)
    {
        return $this->db->query(
            "SELECT l.*, u.nome AS usuario_nome
               FROM " . T_ORCAMENTO_DIMENSIONAMENTO_LOG . " l
               LEFT JOIN " . T_USUARIOS . " u ON u.login = l.usuario
              WHERE l.dimensionamento_numero = ?
              ORDER BY l.data_mov DESC, l.id DESC",
            array((int) $numero)
        )->result_array();
    }

    // ==================================================================
    // Dimensionamento - gravacao
    // ==================================================================

    public function salvar_dimensionamento($dados, $usuario)
    {
        $numero = isset($dados['numero']) ? (int) $dados['numero'] : 0;
        $antes  = $numero ? $this->dimensionamento($numero) : NULL;

        if ($numero && empty($antes))
        {
            return array('ok' => FALSE, 'erro' => 'Dimensionamento nao encontrado.');
        }

        $registro = array();

        foreach (self::$campos_dim as $campo)
        {
            if ( ! array_key_exists($campo, $dados))
            {
                continue;
            }

            $valor = $this->_v($dados, $campo);

            if ($campo === 'data')
            {
                $registro[$campo] = $this->_data($valor) ?: date('Y-m-d');
            }
            elseif ($campo === 'chamado_numero')
            {
                $registro[$campo] = $this->_texto($valor, 16);
            }
            elseif (in_array($campo, array('cliente_id', 'tecnico_id', 'qtd_tecnicos', 'qtd_dias'), TRUE))
            {
                $registro[$campo] = $this->_int($valor);
            }
            elseif (in_array($campo, self::$flags_dim, TRUE))
            {
                $registro[$campo] = bd_bool($valor) ? 'true' : 'false';
            }
            elseif (in_array($campo, self::$horas_dim, TRUE))
            {
                $registro[$campo] = $this->_horas($valor);
            }
            elseif (in_array($campo, array('qtd_comerciais', 'qtd_nao_comerciais'), TRUE))
            {
                $registro[$campo] = (float) $this->_dec($valor);
            }
            elseif (in_array($campo, array('percentual_mat_fixacao', 'percentual_mao_obra',
                                           'percentual_nota_fiscal', 'percentual_acrescimo_custo'), TRUE))
            {
                $registro[$campo] = $this->_dec($valor);
            }
            elseif (in_array($campo, array('material_fixacao', 'mao_obra', 'nota_fiscal',
                                           'acrescimo_custo'), TRUE))
            {
                $registro[$campo] = (float) ($this->_dec($valor) ?: 0);
            }
            elseif (in_array($campo, array('altura', 'comprimento_muro', 'lado_direito',
                                           'lado_esquerdo', 'frente', 'fundo'), TRUE))
            {
                $registro[$campo] = $this->_dec($valor);
            }
            elseif ($campo === 'contato')
            {
                $registro[$campo] = $this->_texto($valor, 30);
            }
            elseif ($campo === 'tipo_contato')
            {
                $registro[$campo] = $this->_texto($valor, 15);
            }
            elseif ($campo === 'setor')
            {
                $registro[$campo] = $this->_texto($valor, 30);
            }
            elseif ($campo === 'email')
            {
                $registro[$campo] = $this->_texto($valor, 100);
            }
            elseif ($campo === 'solicitante')
            {
                $registro[$campo] = $this->_texto($valor, 50);
            }
            else
            {
                $registro[$campo] = $this->_texto($valor);
            }
        }

        if ( ! isset($registro['data']))
        {
            $registro['data'] = date('Y-m-d');
        }

        if ( ! empty($registro['cliente_id']))
        {
            $ok = $this->db->query(
                "SELECT 1 FROM " . T_CLIENTES . " WHERE id = ?",
                array($registro['cliente_id'])
            )->row_array();

            if ( ! $ok)
            {
                $registro['cliente_id'] = NULL;
            }
        }

        if ( ! empty($registro['chamado_numero']))
        {
            $ok = $this->db->query(
                "SELECT 1 FROM " . T_CHAMADOS . " WHERE numero = ?",
                array($registro['chamado_numero'])
            )->row_array();

            if ( ! $ok)
            {
                $registro['chamado_numero'] = NULL;
            }
        }

        $this->db->trans_start();

        if ($numero)
        {
            $registro['usuario_alteracao'] = $usuario['login'];
            $registro['data_alteracao']    = date('Y-m-d H:i:s');

            $this->_atualizar(T_ORCAMENTOS_DIMENSIONAMENTO, $numero, $registro, 'numero');
        }
        else
        {
            $numero = $this->numeracao->numero('DIM');

            if ( ! $numero)
            {
                $this->db->trans_complete();
                return array('ok' => FALSE, 'erro' => 'Nao foi possivel gerar o numero do dimensionamento.');
            }

            $registro['numero']           = $numero;
            $registro['usuario_cadastro'] = $usuario['login'];

            $this->_inserir(T_ORCAMENTOS_DIMENSIONAMENTO, $registro);
        }

        $this->recalcular_dimensionamento($numero, $registro, FALSE);

        $depois = $this->dimensionamento($numero);

        if ($antes)
        {
            $this->_log_diff_dim($numero, $antes, $depois, $usuario['login'], 'A');
        }
        else
        {
            $this->_log_dim($numero, 'C', NULL, NULL, NULL,
                array('registro' => $this->_json($depois)), $usuario['login']);
        }

        $this->db->trans_complete();

        return $this->db->trans_status()
            ? array('ok' => TRUE, 'numero' => $numero)
            : array('ok' => FALSE, 'erro' => 'Falha ao gravar o dimensionamento.');
    }

    // ------------------------------------------------------------------

    /**
     * Totais do dimensionamento (C_PEGA_VALOR_MAT/MOBRA_ORC_DIM_01).
     * Retorna o total de venda usado na tela F_ORC_DIMEN_05.
     */
    public function recalcular_dimensionamento($numero, $dados = array(), $log = FALSE)
    {
        $reg = $this->dimensionamento($numero);

        if (empty($reg))
        {
            return array();
        }

        $valor = function ($campo) use ($dados, $reg) {
            if (array_key_exists($campo, $dados))
            {
                return $dados[$campo];
            }

            return isset($reg[$campo]) ? $reg[$campo] : NULL;
        };

        $material = (float) $this->db->query(
            "SELECT COALESCE(SUM(total_venda), 0) AS total
               FROM " . T_ORCAMENTO_DIMENSIONAMENTO_ITENS . "
              WHERE dimensionamento_numero = ?",
            array((int) $numero)
        )->row()->total;

        $mao_obra = (float) $this->_dec($valor('mao_obra'));
        $perc_mo  = $this->_dec($valor('percentual_mao_obra'));

        if ($perc_mo !== NULL)
        {
            $mao_obra = round($perc_mo * $material / 100, 2);
        }

        $ac_mo = ($material > 0 && $mao_obra > 0)
            ? $material + $mao_obra
            : (($material == 0 && $mao_obra > 0) ? $mao_obra : $material);

        $fixacao  = (float) $this->_dec($valor('material_fixacao'));
        $perc_fix = $this->_dec($valor('percentual_mat_fixacao'));

        if ($perc_fix !== NULL)
        {
            $fixacao = round($perc_fix * $ac_mo / 100, 2);
        }

        $ac_mo_mf = $fixacao > 0 ? $ac_mo + $fixacao : $ac_mo;

        $acrescimo = (float) $this->_dec($valor('acrescimo_custo'));
        $perc_acr  = $this->_dec($valor('percentual_acrescimo_custo'));

        if ($perc_acr !== NULL)
        {
            $acrescimo = round($perc_acr * $ac_mo_mf / 100, 2);
        }

        $ac_mo_mf_ct = $acrescimo > 0 ? $ac_mo_mf + $acrescimo : $ac_mo_mf;

        $imposto  = (float) $this->_dec($valor('nota_fiscal'));
        $perc_imp = $this->_dec($valor('percentual_nota_fiscal'));

        if ($perc_imp !== NULL)
        {
            $imposto = round($perc_imp * $ac_mo_mf_ct / 100, 2);
        }

        $novo = array(
            'mao_obra'         => round($mao_obra, 2),
            'material_fixacao' => round($fixacao, 2),
            'acrescimo_custo'  => round($acrescimo, 2),
            'nota_fiscal'      => round($imposto, 2),
        );

        $diferencas = array();

        foreach ($novo as $campo => $valorNovo)
        {
            if (round((float) $reg[$campo], 2) !== $valorNovo)
            {
                $diferencas[$campo] = array($reg[$campo], $valorNovo);
            }
        }

        if ($diferencas)
        {
            $this->_atualizar(T_ORCAMENTOS_DIMENSIONAMENTO, $numero, $novo, 'numero');

            if ($log)
            {
                foreach ($diferencas as $campo => $par)
                {
                    $this->_log_dim($numero, 'A', $campo, $par[0], $par[1], NULL, NULL);
                }
            }
        }

        $total = $imposto > 0 ? $ac_mo_mf_ct + $imposto : $ac_mo_mf_ct;

        return $novo + array('total_venda' => round($total, 2));
    }

    // ------------------------------------------------------------------

    public function excluir_dimensionamento($numero, $usuario)
    {
        $reg = $this->dimensionamento($numero);

        if (empty($reg))
        {
            return array('ok' => FALSE, 'erro' => 'Dimensionamento nao encontrado.');
        }

        if ( ! empty($reg['orcamento_numero']))
        {
            return array('ok' => FALSE, 'erro' => 'Dimensionamento ja gerou o orcamento '
                . $reg['orcamento_numero'] . '.');
        }

        $this->db->trans_start();

        $this->_log_dim($numero, 'A', NULL, NULL, NULL, array(
            'acao'     => 'exclusao',
            'registro' => $this->_json($reg),
            'usuario'  => $usuario['login'],
        ), $usuario['login']);

        $this->db->query("DELETE FROM " . T_ORCAMENTOS_DIMENSIONAMENTO . " WHERE numero = ?",
            array((int) $numero));

        $this->db->trans_complete();

        return $this->db->trans_status()
            ? array('ok' => TRUE)
            : array('ok' => FALSE, 'erro' => 'Falha ao excluir o dimensionamento.');
    }

    // ==================================================================
    // Dimensionamento - itens
    // ==================================================================

    public function adicionar_item_dimensionamento($numero, $dados, $usuario)
    {
        if (empty($this->dimensionamento($numero)))
        {
            return array('ok' => FALSE, 'erro' => 'Dimensionamento nao encontrado.');
        }

        $qtde = (int) round((float) $this->_dec($this->_v($dados, 'quantidade')));

        if ($qtde <= 0)
        {
            return array('ok' => FALSE, 'erro' => 'Informe a quantidade.');
        }

        $produto          = $this->_int($this->_v($dados, 'produto_id'));
        $descricaoProduto = $this->_texto($this->_v($dados, 'descricao_produto'), 100);

        if ($produto !== NULL)
        {
            $p = $this->db->query(
                "SELECT descricao, valor_compra, valor_venda FROM " . T_PRODUTOS . " WHERE id = ?",
                array($produto)
            )->row_array();

            if ( ! $p)
            {
                $produto = NULL;
            }
            elseif ($descricaoProduto === NULL)
            {
                $descricaoProduto = $this->_texto($p['descricao'], 100);
            }
        }

        $compra = $this->_dec($this->_v($dados, 'valor_compra'));
        $venda  = $this->_dec($this->_v($dados, 'valor_venda'));

        if ($compra === NULL && isset($p['valor_compra']))
        {
            $compra = (float) $p['valor_compra'];
        }

        if ($venda === NULL && isset($p['valor_venda']))
        {
            $venda = (float) $p['valor_venda'];
        }

        $compra = (float) ($compra ?: 0);
        $venda  = (float) ($venda ?: 0);

        $proximo = (int) $this->db->query(
            "SELECT COALESCE(MAX(numero_item), 0) + 1 AS n
               FROM " . T_ORCAMENTO_DIMENSIONAMENTO_ITENS . "
              WHERE dimensionamento_numero = ?",
            array((int) $numero)
        )->row()->n;

        $this->db->trans_start();

        $this->_inserir(T_ORCAMENTO_DIMENSIONAMENTO_ITENS, array(
            'dimensionamento_numero' => (int) $numero,
            'numero_item'            => $proximo,
            'produto_id'             => $produto,
            'descricao'              => $this->_texto($this->_v($dados, 'descricao'), 80),
            'descricao_produto'      => $descricaoProduto,
            'quantidade'             => $qtde,
            'valor_compra'           => $compra,
            'valor_gasto'            => (float) ($this->_dec($this->_v($dados, 'valor_gasto')) ?: 0),
            'total_compra'           => round($qtde * $compra, 2),
            'valor_venda'            => $venda,
            'total_venda'            => round($qtde * $venda, 2),
            'grupo'                  => $this->_texto($this->_v($dados, 'grupo'), 3),
            'subgrupo'               => $this->_texto($this->_v($dados, 'subgrupo'), 3),
            'pg'                     => $this->_texto($this->_v($dados, 'pg'), 3),
            'psg'                    => $this->_texto($this->_v($dados, 'psg'), 3),
        ));

        $this->recalcular_dimensionamento($numero, array(), FALSE);

        $this->_log_dim($numero, 'A', 'item_incluido', NULL, $descricaoProduto,
            array('item' => $proximo), $usuario['login']);

        $this->db->trans_complete();

        return $this->db->trans_status()
            ? array('ok' => TRUE)
            : array('ok' => FALSE, 'erro' => 'Falha ao incluir o item.');
    }

    // ------------------------------------------------------------------

    public function atualizar_item_dimensionamento($id, $dados, $usuario)
    {
        $item = $this->db->query(
            "SELECT * FROM " . T_ORCAMENTO_DIMENSIONAMENTO_ITENS . " WHERE id = ?",
            array((int) $id)
        )->row_array();

        if (empty($item))
        {
            return array('ok' => FALSE, 'erro' => 'Item nao encontrado.');
        }

        $qtde = (int) round((float) $this->_dec($this->_v($dados, 'quantidade')));

        if ($qtde <= 0)
        {
            return array('ok' => FALSE, 'erro' => 'Informe a quantidade.');
        }

        $compra = (float) ($this->_dec($this->_v($dados, 'valor_compra')) ?: 0);
        $venda  = (float) ($this->_dec($this->_v($dados, 'valor_venda')) ?: 0);

        $this->db->trans_start();

        $this->_atualizar(T_ORCAMENTO_DIMENSIONAMENTO_ITENS, $id, array(
            'quantidade'   => $qtde,
            'valor_compra' => $compra,
            'total_compra' => round($qtde * $compra, 2),
            'valor_venda'  => $venda,
            'total_venda'  => round($qtde * $venda, 2),
        ), 'id');

        $this->recalcular_dimensionamento((int) $item['dimensionamento_numero'], array(), FALSE);

        $this->_log_dim((int) $item['dimensionamento_numero'], 'A', 'item_alterado',
            $item['quantidade'] . ' x ' . $item['valor_venda'],
            $qtde . ' x ' . $venda, array('item' => $item['numero_item']), $usuario['login']);

        $this->db->trans_complete();

        return $this->db->trans_status()
            ? array('ok' => TRUE)
            : array('ok' => FALSE, 'erro' => 'Falha ao alterar o item.');
    }

    // ------------------------------------------------------------------

    public function excluir_item_dimensionamento($id, $usuario)
    {
        $item = $this->db->query(
            "SELECT * FROM " . T_ORCAMENTO_DIMENSIONAMENTO_ITENS . " WHERE id = ?",
            array((int) $id)
        )->row_array();

        if (empty($item))
        {
            return array('ok' => FALSE, 'erro' => 'Item nao encontrado.');
        }

        $this->db->trans_start();

        $this->db->query("DELETE FROM " . T_ORCAMENTO_DIMENSIONAMENTO_ITENS . " WHERE id = ?",
            array((int) $id));

        $this->recalcular_dimensionamento((int) $item['dimensionamento_numero'], array(), FALSE);

        $this->_log_dim((int) $item['dimensionamento_numero'], 'A', 'item_excluido',
            $item['descricao_produto'], NULL, array('item' => $item['numero_item']),
            $usuario['login']);

        $this->db->trans_complete();

        return $this->db->trans_status()
            ? array('ok' => TRUE)
            : array('ok' => FALSE, 'erro' => 'Falha ao excluir o item.');
    }

    // ==================================================================
    // Dimensionamento - servicos
    // ==================================================================

    public function adicionar_servico_dimensionamento($numero, $dados, $usuario)
    {
        $descricao = $this->_texto($this->_v($dados, 'descricao'));

        if ($descricao === NULL)
        {
            return array('ok' => FALSE, 'erro' => 'Informe a descricao do servico.');
        }

        if (empty($this->dimensionamento($numero)))
        {
            return array('ok' => FALSE, 'erro' => 'Dimensionamento nao encontrado.');
        }

        $proximo = (int) $this->db->query(
            "SELECT COALESCE(MAX(numero_item), 0) + 1 AS n
               FROM " . T_ORCAMENTO_DIMENSIONAMENTO_SERVICOS . "
              WHERE dimensionamento_numero = ?",
            array((int) $numero)
        )->row()->n;

        $this->_inserir(T_ORCAMENTO_DIMENSIONAMENTO_SERVICOS, array(
            'dimensionamento_numero' => (int) $numero,
            'numero_item'            => $proximo,
            'descricao'              => $descricao,
            'concluido'              => bd_bool($this->_v($dados, 'concluido')) ? 'true' : 'false',
        ));

        $this->_log_dim($numero, 'A', 'servico_incluido', NULL, $descricao, NULL,
            $usuario['login']);

        return array('ok' => TRUE);
    }

    // ------------------------------------------------------------------

    public function atualizar_servico_dimensionamento($id, $dados, $usuario)
    {
        $servico = $this->db->query(
            "SELECT * FROM " . T_ORCAMENTO_DIMENSIONAMENTO_SERVICOS . " WHERE id = ?",
            array((int) $id)
        )->row_array();

        if (empty($servico))
        {
            return array('ok' => FALSE, 'erro' => 'Servico nao encontrado.');
        }

        $novo = array();

        if (array_key_exists('descricao', $dados))
        {
            $novo['descricao'] = $this->_texto($this->_v($dados, 'descricao')) ?: $servico['descricao'];
        }

        if (array_key_exists('concluido', $dados))
        {
            $novo['concluido'] = bd_bool($this->_v($dados, 'concluido')) ? 'true' : 'false';
        }

        if ($novo)
        {
            $this->_atualizar(T_ORCAMENTO_DIMENSIONAMENTO_SERVICOS, $id, $novo, 'id');

            $this->_log_dim((int) $servico['dimensionamento_numero'], 'A', 'servico_alterado',
                $servico['descricao'], isset($novo['descricao']) ? $novo['descricao'] : NULL,
                NULL, $usuario['login']);
        }

        return array('ok' => TRUE);
    }

    // ------------------------------------------------------------------

    public function excluir_servico_dimensionamento($id, $usuario)
    {
        $servico = $this->db->query(
            "SELECT * FROM " . T_ORCAMENTO_DIMENSIONAMENTO_SERVICOS . " WHERE id = ?",
            array((int) $id)
        )->row_array();

        if (empty($servico))
        {
            return array('ok' => FALSE, 'erro' => 'Servico nao encontrado.');
        }

        $this->db->query("DELETE FROM " . T_ORCAMENTO_DIMENSIONAMENTO_SERVICOS . " WHERE id = ?",
            array((int) $id));

        $this->_log_dim((int) $servico['dimensionamento_numero'], 'A', 'servico_excluido',
            $servico['descricao'], NULL, NULL, $usuario['login']);

        return array('ok' => TRUE);
    }

    // ==================================================================
    // Dimensionamento -> orcamento (C_INCLUI_ORCAMENTO_DIMEN_*)
    // ==================================================================

    public function pode_gerar_orcamento($numero)
    {
        $dim = $this->dimensionamento($numero);

        if (empty($dim))
        {
            return array('ok' => FALSE, 'erro' => 'Dimensionamento nao encontrado.');
        }

        if ( ! empty($dim['orcamento_numero']))
        {
            return array('ok' => FALSE, 'erro' => 'Este dimensionamento ja gerou o orcamento '
                . $dim['orcamento_numero'] . '.');
        }

        if (empty($dim['cliente_id']))
        {
            return array('ok' => FALSE, 'erro' => 'Informe o cliente do dimensionamento.');
        }

        $itens = $this->itens_dimensionamento($numero);

        if (empty($itens))
        {
            return array('ok' => FALSE, 'erro' => 'O dimensionamento nao possui itens.');
        }

        $sem_preco = array();

        foreach ($itens as $item)
        {
            if ((float) $item['valor_venda'] <= 0)
            {
                $sem_preco[] = $item['numero_item'];
            }
        }

        if ($sem_preco)
        {
            return array('ok' => FALSE, 'erro' => 'Itens sem valor de venda: '
                . implode(', ', array_slice($sem_preco, 0, 10)));
        }

        return array('ok' => TRUE);
    }

    // ------------------------------------------------------------------

    public function gerar_orcamento($numero, $usuario)
    {
        $check = $this->pode_gerar_orcamento($numero);

        if ( ! $check['ok'])
        {
            return $check;
        }

        $dim      = $this->dimensionamento($numero);
        $itens    = $this->itens_dimensionamento($numero);
        $servicos = $this->servicos_dimensionamento($numero);

        $this->db->trans_start();

        $orcamento = $this->numeracao->numero('ORC');

        if ( ! $orcamento)
        {
            $this->db->trans_complete();
            return array('ok' => FALSE, 'erro' => 'Nao foi possivel gerar o numero do orcamento.');
        }

        $observacao = array();

        if ( ! empty($dim['que_funciona']))
        {
            $observacao[] = 'O QUE FUNCIONA: ' . $dim['que_funciona'];
        }

        if ( ! empty($dim['quer_fazer']))
        {
            $observacao[] = 'O QUE QUER FAZER: ' . $dim['quer_fazer'];
        }

        if ( ! empty($dim['dificuldades']))
        {
            $observacao[] = 'DIFICULDADES: ' . $dim['dificuldades'];
        }

        if ( ! empty($dim['observacao']))
        {
            $observacao[] = 'OBSERVACAO: ' . $dim['observacao'];
        }

        $this->_inserir(T_ORCAMENTOS, array(
            'numero'                     => $orcamento,
            'data'                       => date('Y-m-d'),
            'atencao'                    => $this->_texto($dim['cliente_nome'], 70),
            'cliente_id'                 => (int) $dim['cliente_id'],
            'dimensionamento_numero'     => (int) $numero,
            'situacao'                   => 1,
            'resultado'                  => 3,
            'aprovado'                   => 'false',
            'nao_aprovado'               => 'false',
            'observacao'                 => $observacao ? implode("\n", $observacao) : NULL,
            'percentual_mao_obra'        => $dim['percentual_mao_obra'],
            'percentual_mat_fixacao'     => $dim['percentual_mat_fixacao'],
            'percentual_acrescimo_custo' => $dim['percentual_acrescimo_custo'],
            'percentual_nota_fiscal'     => $dim['percentual_nota_fiscal'],
            'percentual_imposto'         => $dim['percentual_nota_fiscal'],
            'usuario_cadastro'           => $usuario['login'],
        ));

        $n = 0;
        foreach ($itens as $item)
        {
            $n++;
            $this->_inserir(T_ORCAMENTO_ITENS, array(
                'orcamento_numero'        => $orcamento,
                'numero_item'             => $n,
                'produto_id'              => $item['produto_id'],
                'descricao'               => $item['descricao'],
                'descricao_produto'       => $item['descricao_produto'],
                'quantidade'              => $item['quantidade'],
                'valor_unitario'          => $item['valor_venda'],
                'valor_total'             => $item['total_venda'],
                'valor_unitario_original' => $item['valor_venda'],
                'valor_total_original'    => $item['total_venda'],
            ));
        }

        $n = 0;
        foreach ($servicos as $servico)
        {
            $n++;
            $this->_inserir(T_ORCAMENTO_SERVICOS, array(
                'orcamento_numero' => $orcamento,
                'numero_item'      => $n,
                'descricao'        => $servico['descricao'],
                'concluido'        => $servico['concluido'] ? 'true' : 'false',
            ));
        }

        $this->_atualizar(T_ORCAMENTOS_DIMENSIONAMENTO, $numero, array(
            'orcamento_numero'   => $orcamento,
            'usuario_fechamento' => $usuario['login'],
            'data_fechamento'    => date('Y-m-d H:i:s'),
        ), 'numero');

        $this->recalcular($orcamento, array(), FALSE);

        $this->_log($orcamento, 'C', NULL, NULL, NULL, array(
            'origem'          => 'dimensionamento',
            'dimensionamento' => (int) $numero,
            'registro'        => $this->_json($this->por_numero($orcamento)),
        ), $usuario['login']);

        $this->_log_dim($numero, 'A', 'orcamento_gerado', NULL, $orcamento, NULL,
            $usuario['login']);

        $this->db->trans_complete();

        return $this->db->trans_status()
            ? array('ok' => TRUE, 'orcamento' => $orcamento)
            : array('ok' => FALSE, 'erro' => 'Falha ao gerar o orcamento.');
    }

    // ==================================================================
    // Helpers internos
    // ==================================================================

    private function _inserir($tabela, array $dados)
    {
        $colunas = array_keys($dados);
        $marcas  = rtrim(str_repeat('?, ', count($colunas)), ', ');

        $sql = "INSERT INTO {$tabela} (" . implode(', ', $colunas) . ")
                VALUES ({$marcas})";

        return $this->db->query($sql, $this->_params_bd($dados));
    }

    // ------------------------------------------------------------------

    private function _atualizar($tabela, $id, array $dados, $pk)
    {
        if ( ! $dados)
        {
            return TRUE;
        }

        $sets = array();

        foreach (array_keys($dados) as $coluna)
        {
            $sets[] = "$coluna = ?";
        }

        $sql = "UPDATE {$tabela} SET " . implode(', ', $sets) . " WHERE {$pk} = ?";

        $params   = $this->_params_bd($dados);
        $params[] = $id;

        return $this->db->query($sql, $params);
    }

    // ------------------------------------------------------------------

    /** Normaliza booleanos (o PDO envia FALSE como string vazia). */
    private function _params_bd(array $dados)
    {
        $saida = array();

        foreach ($dados as $valor)
        {
            if (is_bool($valor))
            {
                $saida[] = $valor ? 'true' : 'false';
            }
            elseif (is_array($valor))
            {
                $saida[] = json_encode($this->_json($valor), JSON_UNESCAPED_UNICODE);
            }
            else
            {
                $saida[] = $valor;
            }
        }

        return $saida;
    }

    // ------------------------------------------------------------------

    /** Log por campo. $dados (array) grava um JSONB com o contexto. */
    private function _log($numero, $tipo, $campo, $anterior, $novo, $dados, $usuario)
    {
        $json = $dados === NULL ? NULL : json_encode($this->_json($dados), JSON_UNESCAPED_UNICODE);

        $this->db->query(
            "INSERT INTO " . T_ORCAMENTO_LOG . "
                (orcamento_numero, data_mov, usuario, tipo_mov, campo,
                 valor_anterior, valor_novo, dados)
             VALUES (?, now(), ?, ?, ?, ?, ?, ?::jsonb)",
            array(
                (int) $numero,
                $usuario,
                $tipo,
                $campo,
                $anterior === NULL ? NULL : (string) $anterior,
                $novo === NULL ? NULL : (string) $novo,
                $json,
            )
        );
    }

    // ------------------------------------------------------------------

    private function _log_criacao($numero, $registro, $usuario)
    {
        $this->_log($numero, 'C', NULL, NULL, NULL,
            array('registro' => $this->_json($registro)), $usuario);
    }

    // ------------------------------------------------------------------

    private function _log_diff($numero, $antes, $depois, $usuario, $tipo = 'A')
    {
        foreach (self::$campos as $campo)
        {
            if ( ! array_key_exists($campo, $depois))
            {
                continue;
            }

            $a = isset($antes[$campo]) ? (string) $antes[$campo] : NULL;
            $d = isset($depois[$campo]) ? (string) $depois[$campo] : NULL;

            if ($a === $d)
            {
                continue;
            }

            $this->_log($numero, $tipo, $campo, $a, $d, NULL, $usuario);
        }
    }

    // ------------------------------------------------------------------

    private function _log_dim($numero, $tipo, $campo, $anterior, $novo, $dados, $usuario)
    {
        $json = $dados === NULL ? NULL : json_encode($this->_json($dados), JSON_UNESCAPED_UNICODE);

        $this->db->query(
            "INSERT INTO " . T_ORCAMENTO_DIMENSIONAMENTO_LOG . "
                (dimensionamento_numero, data_mov, usuario, tipo_mov, campo,
                 valor_anterior, valor_novo, dados)
             VALUES (?, now(), ?, ?, ?, ?, ?, ?::jsonb)",
            array(
                (int) $numero,
                $usuario,
                $tipo,
                $campo,
                $anterior === NULL ? NULL : (string) $anterior,
                $novo === NULL ? NULL : (string) $novo,
                $json,
            )
        );
    }

    // ------------------------------------------------------------------

    private function _log_diff_dim($numero, $antes, $depois, $usuario, $tipo = 'A')
    {
        foreach (self::$campos_dim as $campo)
        {
            if ( ! array_key_exists($campo, $depois))
            {
                continue;
            }

            $a = isset($antes[$campo]) ? (string) $antes[$campo] : NULL;
            $d = isset($depois[$campo]) ? (string) $depois[$campo] : NULL;

            if ($a === $d)
            {
                continue;
            }

            $this->_log_dim($numero, $tipo, $campo, $a, $d, NULL, $usuario);
        }
    }

    // ------------------------------------------------------------------

    private function _v($a, $k)
    {
        return isset($a[$k]) ? $a[$k] : NULL;
    }

    private function _vazio($v)
    {
        return $v === NULL || $v === '' || $v === FALSE
            || (is_string($v) && trim($v) === '');
    }

    private function _int($v)
    {
        return $this->_vazio($v) ? NULL : (int) $v;
    }

    private function _dec($v)
    {
        if ($this->_vazio($v))
        {
            return NULL;
        }

        if (is_string($v))
        {
            $v = trim($v);
            $v = str_replace(array('R$', ' ', "\xc2\xa0"), '', $v);

            if (strpos($v, ',') !== FALSE && strpos($v, '.') !== FALSE)
            {
                $v = str_replace('.', '', $v);
            }

            $v = str_replace(',', '.', $v);
        }

        return is_numeric($v) ? (float) $v : NULL;
    }

    private function _texto($v, $limite = NULL)
    {
        if ($this->_vazio($v))
        {
            return NULL;
        }

        $v = trim((string) $v);

        if ( ! mb_check_encoding($v, 'UTF-8'))
        {
            $v = @iconv('UTF-8', 'UTF-8//IGNORE', $v);
        }

        $v = preg_replace('/[\x{0000}-\x{0008}\x{000B}\x{000C}\x{000E}-\x{001F}\x{007F}-\x{009F}]/u', ' ', $v);
        $v = trim(preg_replace('/[ ]{2,}/', ' ', $v));

        if ($v === '')
        {
            return NULL;
        }

        return $limite === NULL ? $v : mb_substr($v, 0, $limite, 'UTF-8');
    }

    private function _data($v)
    {
        $v = $this->_texto($v);

        if ($v === NULL)
        {
            return NULL;
        }

        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})/', $v, $m))
        {
            return ((int) $m[3] < 1901) ? NULL : $m[3] . '-' . $m[2] . '-' . $m[1];
        }

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $v, $m))
        {
            return ((int) $m[1] < 1901) ? NULL : $m[1] . '-' . $m[2] . '-' . $m[3];
        }

        return NULL;
    }

    private function _hora($v)
    {
        $v = $this->_texto($v);

        if ($v === NULL || ! preg_match('/(\d{2}):(\d{2})(?::(\d{2}))?/', $v, $m))
        {
            return NULL;
        }

        return $m[1] . ':' . $m[2] . ':' . (isset($m[3]) && $m[3] !== '' ? $m[3] : '00');
    }

    private function _horas($v)
    {
        if ($this->_vazio($v))
        {
            return 0;
        }

        if (is_numeric($v))
        {
            return round((float) $v, 2);
        }

        if (preg_match('/(\d{1,2}):(\d{2})(?::(\d{2}))?/', (string) $v, $m))
        {
            return round((int) $m[1] + ((int) $m[2] / 60), 2);
        }

        return 0;
    }

    private function _json($valor)
    {
        if (is_array($valor))
        {
            $saida = array();

            foreach ($valor as $k => $v)
            {
                $saida[$k] = $this->_json($v);
            }

            return $saida;
        }

        if (is_string($valor))
        {
            $valor = preg_replace('/[\x{0000}-\x{0008}\x{000B}\x{000C}\x{000E}-\x{001F}\x{007F}-\x{009F}]/u', ' ', $valor);
            return trim($valor);
        }

        return $valor;
    }

    // ------------------------------------------------------------------

    /** funcionario_id do usuario logado (para o chamado). */
    public function funcionario_id($usuario)
    {
        if (empty($usuario['funcionario_id']))
        {
            return NULL;
        }

        return (int) $usuario['funcionario_id'];
    }
}
