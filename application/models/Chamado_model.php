<?php
/**
 * Chamado_model - Chamados / Ordens de Servico.
 *
 * Concentra as regras levantadas em docs/06-regras-chamados-os.md.
 * Toda operacao que muda estado grava evento em biostech_chamado_eventos
 * e diff de campos em biostech_chamado_log.
 */
class Chamado_model extends MY_Model
{
    /** situacoes que permitem encerrar (RN-201) */
    private static $situacoes_encerramento = array(6, 7, 8, 9, 10, 15);

    /** status que a tela de mudanca oferece (RN-010) */
    private static $status_mudanca = array(2, 3, 4, 6);

    /** campos do registro principal que podem ser alterados */
    private static $campos_chamado = array(
        'categoria', 'cliente_id', 'escritorio_id', 'numero_par',
        'data_abertura', 'hora_abertura', 'status_codigo', 'situacao_codigo',
        'valor_material', 'valor_servico', 'valor_mao_obra', 'valor_imposto',
        'acrescimo_custo', 'material_fixacao', 'valor_desconto', 'valor_faturado',
        'qtd_parcelas', 'defeito_relatado', 'defeito_constatado',
        'servico_executado', 'observacao',
    );

    private static $campos_equipamento = array(
        'equipamento_serie', 'voltagem', 'tipo_garantia_codigo', 'autorizada',
        'acessorios', 'lista_equipamento', 'patrimonio', 'solicitante',
        'local_atendimento', 'voltagem_saida', 'nota_fiscal_chegada',
        'recolhido', 'contato',
        'rede_fn', 'rede_ft', 'rede_nt', 'estabilizador', 'estab_marca',
        'estab_potencia', 'estab_kva', 'def_virus', 'def_software',
        'def_disquete', 'def_cd_dvd', 'def_tensao_imp', 'def_cabos_desc',
        'def_nao_canc_chamado', 'def_abert_equip', 'def_outros',
    );

    private static $campos_atendimento = array(
        'previsao_data', 'previsao_hora', 'atendimento_data', 'atendimento_hora',
        'atendimento_hora_fim', 'solucao_data', 'solucao_hora',
        'devolucao_data', 'devolucao_hora', 'tecnico_atendimento_id',
        'tecnico_abertura_id', 'tecnico_conclusao_id', 'responsavel_encerramento_id',
        'responsavel_entrega', 'responsavel_recebimento', 'quem_devolveu',
        'quem_recebeu', 'prioridade_codigo', 'segmento_codigo',
        'valor_recolhimento', 'valor_entrega', 'pago_recolhimento',
        'pago_entrega', 'autorizada', 'garantia', 'recolhido',
        'contato', 'fone_contato', 'solicitante', 'fone_solicitante',
        'local_atendimento',
    );

    public function __construct()
    {
        parent::__construct();
        $this->tabela = T_CHAMADOS;
        $this->pk     = 'numero';
        $this->load->library('numeracao');
        $this->load->model('dominio_model');
    }

    // ==================================================================
    // Consultas
    // ==================================================================

    private function _filtro($f)
    {
        $onde   = array('c.excluido_em IS NULL');
        $params = array();

        if ( ! empty($f['busca']))
        {
            $termo    = '%' . $f['busca'] . '%';
            $onde[]   = "(c.numero ILIKE ? OR cl.nome ILIKE ? OR eq.equipamento_serie ILIKE ?
                          OR c.defeito_relatado ILIKE ? OR c.defeito_constatado ILIKE ?)";
            $params[] = $termo;
            $params[] = $termo;
            $params[] = $termo;
            $params[] = $termo;
            $params[] = $termo;
        }

        if ( ! empty($f['categoria']))
        {
            $onde[]   = 'c.categoria = ?';
            $params[] = $f['categoria'];
        }

        if ( ! empty($f['status']))
        {
            $onde[]   = 'c.status_codigo = ?';
            $params[] = (int) $f['status'];
        }

        if ( ! empty($f['situacao']))
        {
            $onde[]   = 'c.situacao_codigo = ?';
            $params[] = (int) $f['situacao'];
        }

        if ( ! empty($f['cliente_id']))
        {
            $onde[]   = 'c.cliente_id = ?';
            $params[] = (int) $f['cliente_id'];
        }

        if ( ! empty($f['de']))
        {
            $onde[]   = 'c.data_abertura >= ?';
            $params[] = $f['de'];
        }

        if ( ! empty($f['ate']))
        {
            $onde[]   = 'c.data_abertura <= ?';
            $params[] = $f['ate'];
        }

        if (isset($f['encerrado']) && $f['encerrado'] !== '')
        {
            $onde[]   = 'c.encerrado = ' . ($f['encerrado'] ? 'true' : 'false');
        }

        return array('WHERE ' . implode(' AND ', $onde), $params);
    }

    // ------------------------------------------------------------------

    private function _from()
    {
        return "FROM " . T_CHAMADOS . " c
                LEFT JOIN " . T_CLIENTES . " cl ON cl.id = c.cliente_id
                LEFT JOIN " . T_CHAMADO_EQUIPAMENTO . " eq ON eq.chamado_numero = c.numero";
    }

    // ------------------------------------------------------------------

    public function listar($filtros, $limit = NULL, $offset = NULL)
    {
        list($where, $params) = $this->_filtro($filtros);

        $sql = "SELECT c.numero, c.categoria, c.data_abertura, c.status_codigo,
                       c.situacao_codigo, c.encerrado, c.valor_material,
                       c.valor_servico, c.valor_mao_obra, c.valor_desconto,
                       cl.nome AS cliente_nome,
                       eq.equipamento_serie,
                       ds.nome AS status_nome,
                       di.nome AS situacao_nome
                " . $this->_from() . "
                LEFT JOIN " . T_DOMINIOS . " ds
                       ON ds.tipo = 'status_os' AND ds.codigo = c.status_codigo
                LEFT JOIN " . T_DOMINIOS . " di
                       ON di.tipo = 'situacao_os' AND di.codigo = c.situacao_codigo
                {$where}
                ORDER BY c.data_abertura DESC, c.numero DESC";

        if ($limit !== NULL)
        {
            $sql .= ' LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
        }

        return $this->db->query($sql, $params)->result_array();
    }

    // ------------------------------------------------------------------

    public function total($filtros)
    {
        list($where, $params) = $this->_filtro($filtros);

        $sql = "SELECT count(*) AS total " . $this->_from() . " {$where}";
        $row = $this->db->query($sql, $params)->row_array();

        return (int) $row['total'];
    }

    // ------------------------------------------------------------------

    public function por_numero($numero)
    {
        $sql = "SELECT c.*,
                       cl.nome AS cliente_nome, cl.fantasia, cl.cpf_cnpj,
                       cl.telefone, cl.celular, cl.email,
                       ds.nome AS status_nome,
                       di.nome AS situacao_nome,
                       (SELECT count(*) FROM " . T_CHAMADO_INTERVENCOES . " i
                         WHERE i.chamado_numero = c.numero) AS qtd_intervencoes,
                       (SELECT COALESCE(sum(p.valor_total), 0) FROM " . T_CHAMADO_PECAS . " p
                         WHERE p.chamado_numero = c.numero AND p.excluido_em IS NULL) AS total_pecas,
                       (SELECT COALESCE(sum(s.valor_total), 0) FROM " . T_CHAMADO_SERVICOS . " s
                         WHERE s.chamado_numero = c.numero AND s.tipo = 'avulso') AS total_servicos,
                       (SELECT count(*) FROM " . T_CHAMADO_VINCULOS . " v
                          JOIN " . T_CHAMADOS . " f ON f.numero = v.filho_numero
                         WHERE v.pai_numero = c.numero
                           AND NOT f.encerrado AND f.excluido_em IS NULL) AS filhos_abertos
                FROM " . T_CHAMADOS . " c
                LEFT JOIN " . T_CLIENTES . " cl ON cl.id = c.cliente_id
                LEFT JOIN " . T_DOMINIOS . " ds
                       ON ds.tipo = 'status_os' AND ds.codigo = c.status_codigo
                LEFT JOIN " . T_DOMINIOS . " di
                       ON di.tipo = 'situacao_os' AND di.codigo = c.situacao_codigo
                WHERE c.numero = ? AND c.excluido_em IS NULL
                LIMIT 1";

        return $this->db->query($sql, array($numero))->row_array();
    }

    // ------------------------------------------------------------------

    public function equipamento($numero)
    {
        $row = $this->db->query(
            "SELECT * FROM " . T_CHAMADO_EQUIPAMENTO . " WHERE chamado_numero = ?",
            array($numero)
        )->row_array();

        return $row ? $row : array();
    }

    public function atendimento($numero)
    {
        $row = $this->db->query(
            "SELECT * FROM " . T_CHAMADO_ATENDIMENTO . " WHERE chamado_numero = ?",
            array($numero)
        )->row_array();

        return $row ? $row : array();
    }

    // ==================================================================
    // Gravacao do registro principal
    // ==================================================================

    public function criar($dados, $usuario)
    {
        $this->db->trans_start();

        $numero = $this->numeracao->numero_os($dados['data_abertura']);

        $reg = array(
            'numero'           => $numero,
            'categoria'        => $dados['categoria'],
            'cliente_id'       => (int) $dados['cliente_id'],
            'numero_par'       => $this->nullify($dados['numero_par']),
            'data_abertura'    => $dados['data_abertura'],
            'hora_abertura'    => $this->nullify($dados['hora_abertura']),
            'status_codigo'    => 1,
            'situacao_codigo'  => 11,
            'usuario_cadastro' => $usuario['login'],
            'defeito_relatado' => $this->nullify($dados['defeito_relatado']),
            'observacao'       => $this->nullify($dados['observacao']),
        );

        $this->_insert(T_CHAMADOS, $reg);

        $this->_garantir_equipamento($dados['equipamento_serie'], (int) $dados['cliente_id']);

        $eq = array(
            'chamado_numero'      => $numero,
            'equipamento_serie'   => $this->nullify($dados['equipamento_serie']),
            'tipo_garantia_codigo'=> $this->inteiro_ou_nulo($dados['tipo_garantia_codigo']),
            'patrimonio'          => $this->nullify($dados['patrimonio']),
            'solicitante'         => $this->nullify($dados['solicitante']),
            'local_atendimento'   => $this->nullify($dados['local_atendimento']),
            'contato'             => $this->nullify($dados['contato']),
            'nota_fiscal_chegada' => $this->nullify($dados['nota_fiscal_chegada']),
            'recolhido'           => FALSE,
        );
        $this->_insert(T_CHAMADO_EQUIPAMENTO, $eq);

        $at = array(
            'chamado_numero'       => $numero,
            'tecnico_abertura_id'  => $this->funcionario_id($usuario),
            'prioridade_codigo'    => $this->inteiro_ou_nulo($dados['prioridade_codigo']),
            'segmento_codigo'      => $this->inteiro_ou_nulo($dados['segmento_codigo']),
            'solicitante'          => $this->nullify($dados['solicitante']),
            'contato'              => $this->nullify($dados['contato']),
            'local_atendimento'    => $this->nullify($dados['local_atendimento']),
        );
        $this->_insert(T_CHAMADO_ATENDIMENTO, $at);

        $this->_evento($numero, 'abertura', 'Registro criado', $usuario['login']);

        $this->db->trans_complete();

        return $this->db->trans_status() ? $numero : FALSE;
    }

    // ------------------------------------------------------------------

    public function alterar($numero, $dados, $usuario)
    {
        $antes = $this->por_numero($numero);

        if (empty($antes) || bd_bool($antes['encerrado']))
        {
            return FALSE;
        }

        $novo = array();
        foreach (self::$campos_chamado as $campo)
        {
            if (array_key_exists($campo, $dados))
            {
                $novo[$campo] = $dados[$campo];
            }
        }

        if (empty($novo))
        {
            return TRUE;
        }

        $novo['usuario_alteracao'] = $usuario['login'];
        $novo['data_alteracao']    = date('Y-m-d H:i:s');

        $this->db->trans_start();

        $this->_update(T_CHAMADOS, 'numero', $numero, $novo);

        $evento_id = $this->_evento($numero, 'alteracao', 'Dados alterados', $usuario['login']);
        $this->_diff($numero, $antes, $novo, $evento_id, $usuario['login']);

        $this->db->trans_complete();

        return $this->db->trans_status();
    }

    // ------------------------------------------------------------------

    public function salvar_equipamento($numero, $dados, $usuario)
    {
        $antes = $this->equipamento($numero);

        $novo = array();
        foreach (self::$campos_equipamento as $campo)
        {
            if (array_key_exists($campo, $dados))
            {
                $novo[$campo] = $dados[$campo];
            }
        }

        $this->_garantir_equipamento(
            isset($novo['equipamento_serie']) ? $novo['equipamento_serie'] : NULL,
            (int) $this->por_numero($numero)['cliente_id']
        );

        $this->db->trans_start();

        if (empty($antes))
        {
            $novo['chamado_numero'] = $numero;
            $this->_insert(T_CHAMADO_EQUIPAMENTO, $novo);
        }
        else
        {
            $this->_update(T_CHAMADO_EQUIPAMENTO, 'chamado_numero', $numero, $novo);
        }

        $evento_id = $this->_evento($numero, 'equipamento', 'Equipamento atualizado', $usuario['login']);
        $this->_diff($numero, $antes, $novo, $evento_id, $usuario['login']);

        $this->db->trans_complete();

        return $this->db->trans_status();
    }

    // ------------------------------------------------------------------

    public function salvar_atendimento($numero, $dados, $usuario)
    {
        $antes = $this->atendimento($numero);

        $novo = array();
        foreach (self::$campos_atendimento as $campo)
        {
            if (array_key_exists($campo, $dados))
            {
                $novo[$campo] = $dados[$campo];
            }
        }

        if (empty($antes))
        {
            $novo['chamado_numero'] = $numero;
        }

        $this->db->trans_start();

        if (empty($antes))
        {
            $this->_insert(T_CHAMADO_ATENDIMENTO, $novo);
        }
        else
        {
            $this->_update(T_CHAMADO_ATENDIMENTO, 'chamado_numero', $numero, $novo);
        }

        $evento_id = $this->_evento($numero, 'atendimento', 'Atendimento atualizado', $usuario['login']);
        $this->_diff($numero, $antes, $novo, $evento_id, $usuario['login']);

        $this->db->trans_complete();

        return $this->db->trans_status();
    }

    // ==================================================================
    // Status
    // ==================================================================

    public function status_mudanca()
    {
        $opcoes = array();
        foreach ($this->dominio_model->opcoes('status_os') as $o)
        {
            if (in_array((int) $o['codigo'], self::$status_mudanca, TRUE))
            {
                $opcoes[] = $o;
            }
        }
        return $opcoes;
    }

    public function situacoes_encerramento()
    {
        return self::$situacoes_encerramento;
    }

    // ------------------------------------------------------------------

    public function mudar_status($numero, $status, $situacao, $dados, $usuario)
    {
        $status = (int) $status;

        if ( ! in_array($status, self::$status_mudanca, TRUE))
        {
            return array('ok' => FALSE, 'erro' => 'Status invalido para alteracao manual.');
        }

        if ($situacao === NULL || $situacao === '')
        {
            return array('ok' => FALSE, 'erro' => 'Informe a Situacao Atual.');
        }

        $c = $this->por_numero($numero);

        if (empty($c) || bd_bool($c['encerrado']))
        {
            return array('ok' => FALSE, 'erro' => 'Registro encerrado nao pode ter o status alterado.');
        }

        $this->db->trans_start();

        $novo = array(
            'status_codigo'   => $status,
            'situacao_codigo' => (int) $situacao,
            'usuario_alteracao' => $usuario['login'],
            'data_alteracao'    => date('Y-m-d H:i:s'),
        );

        if ($status === 5)
        {
            $novo['status_anterior'] = (int) $c['status_codigo'];
        }

        $this->_update(T_CHAMADOS, 'numero', $numero, $novo);

        $at = array();
        if ( ! empty($dados['tecnico_atendimento_id']))
        {
            $at['tecnico_atendimento_id'] = (int) $dados['tecnico_atendimento_id'];
        }
        if ($status === 2 && empty($c['atendimento_data']))
        {
            $at['atendimento_data'] = date('Y-m-d');
            $at['atendimento_hora'] = date('H:i');
        }
        if ( ! empty($dados['atendimento_hora_fim']))
        {
            $at['atendimento_hora_fim'] = $dados['atendimento_hora_fim'];
        }

        if ( ! empty($at))
        {
            $this->_update(T_CHAMADO_ATENDIMENTO, 'chamado_numero', $numero, $at);
        }

        $evento_id = $this->_evento(
            $numero,
            'status',
            'Status ' . (int) $c['status_codigo'] . ' -> ' . $status . ' / situacao ' . (int) $situacao,
            $usuario['login']
        );
        $this->_diff($numero, $c, $novo, $evento_id, $usuario['login']);

        $this->db->trans_complete();

        return array('ok' => $this->db->trans_status(), 'erro' => NULL);
    }

    // ==================================================================
    // Intervencoes (append-only)
    // ==================================================================

    public function intervencoes($numero)
    {
        $sql = "SELECT i.*, u.nome AS tecnico_nome
                  FROM " . T_CHAMADO_INTERVENCOES . " i
                  LEFT JOIN " . T_USUARIOS . " u ON u.funcionario_id = i.tecnico_id
                 WHERE i.chamado_numero = ?
                 ORDER BY i.data_movimento, i.hora_movimento";
        return $this->db->query($sql, array($numero))->result_array();
    }

    public function adicionar_intervencao($numero, $dados, $usuario)
    {
        $tecnico = $this->funcionario_id($usuario);

        if ($tecnico === NULL)
        {
            return array('ok' => FALSE, 'erro' => 'Seu usuario nao esta vinculado a um funcionario. Procure o administrador.');
        }

        if (empty($dados['data_movimento']))
        {
            return array('ok' => FALSE, 'erro' => 'Informe a Data da Intervencao.');
        }
        if (empty($dados['hora_movimento']))
        {
            return array('ok' => FALSE, 'erro' => 'Informe a Hora Inicial da Intervencao.');
        }
        if (trim((string) $dados['descricao']) === '')
        {
            return array('ok' => FALSE, 'erro' => 'Informe a descricao da Intervencao.');
        }

        $existe = $this->db->query(
            "SELECT 1 FROM " . T_CHAMADO_INTERVENCOES . "
              WHERE chamado_numero = ? AND data_movimento = ? AND hora_movimento = ?",
            array($numero, $dados['data_movimento'], $dados['hora_movimento'])
        )->row_array();

        if ($existe)
        {
            return array('ok' => FALSE, 'erro' => 'Intervencao ja cadastrada nesta Data e Hora.');
        }

        $this->db->trans_start();

        $this->_insert(T_CHAMADO_INTERVENCOES, array(
            'chamado_numero' => $numero,
            'data_movimento' => $dados['data_movimento'],
            'hora_movimento' => $dados['hora_movimento'],
            'hora_inicio'    => $this->nullify($dados['hora_inicio']),
            'hora_fim'       => $this->nullify($dados['hora_fim']),
            'tecnico_id'     => $tecnico,
            'descricao'      => trim($dados['descricao']),
            'resolveu'       => ! empty($dados['resolveu']),
        ));

        $this->_evento($numero, 'intervencao', 'Intervencao registrada', $usuario['login']);

        $this->db->trans_complete();

        return array('ok' => $this->db->trans_status(), 'erro' => NULL);
    }

    // ==================================================================
    // Pecas
    // ==================================================================

    public function pecas($numero)
    {
        $sql = "SELECT p.*, pr.descricao AS produto_descricao
                  FROM " . T_CHAMADO_PECAS . " p
                  LEFT JOIN " . T_PRODUTOS . " pr ON pr.id = p.produto_id
                 WHERE p.chamado_numero = ? AND p.excluido_em IS NULL
                 ORDER BY p.numero_item";
        return $this->db->query($sql, array($numero))->result_array();
    }

    public function adicionar_peca($numero, $dados, $usuario)
    {
        $produto_id = (int) $dados['produto_id'];
        $qtd        = (int) $dados['quantidade'];

        if ($produto_id <= 0)
        {
            return array('ok' => FALSE, 'erro' => 'Informe o produto.');
        }
        if ($qtd <= 0)
        {
            return array('ok' => FALSE, 'erro' => 'Quantidade invalida.');
        }

        $produto = $this->db->query(
            "SELECT id, descricao, valor_venda FROM " . T_PRODUTOS . " WHERE id = ?",
            array($produto_id)
        )->row_array();

        if (empty($produto))
        {
            return array('ok' => FALSE, 'erro' => 'Produto invalido.');
        }

        $repetido = $this->db->query(
            "SELECT 1 FROM " . T_CHAMADO_PECAS . "
              WHERE chamado_numero = ? AND produto_id = ? AND excluido_em IS NULL",
            array($numero, $produto_id)
        )->row_array();

        if ($repetido)
        {
            return array('ok' => FALSE, 'erro' => 'Produto ja cadastrado nesta Ordem de Servico.');
        }

        $saldo = $this->saldo_produto($produto_id);

        if ($saldo < $qtd)
        {
            return array('ok' => FALSE, 'erro' => 'Quantidade maior que a disponivel no estoque. Estoque atual: ' . $saldo . '.');
        }

        $valor_unit = isset($dados['valor_unitario']) && $dados['valor_unitario'] !== ''
            ? (float) str_replace(',', '.', $dados['valor_unitario'])
            : (float) $produto['valor_venda'];

        $proximo = $this->db->query(
            "SELECT COALESCE(max(numero_item), 0) + 1 AS proximo
               FROM " . T_CHAMADO_PECAS . " WHERE chamado_numero = ?",
            array($numero)
        )->row_array();

        $this->db->trans_start();

        $this->_insert(T_CHAMADO_PECAS, array(
            'chamado_numero' => $numero,
            'numero_item'    => (int) $proximo['proximo'],
            'produto_id'     => $produto_id,
            'descricao_produto' => $produto['descricao'],
            'quantidade'     => $qtd,
            'valor_unitario' => $this->dinheiro($valor_unit),
            'valor_total'    => $this->dinheiro($valor_unit * $qtd),
        ));

        $this->_evento($numero, 'peca', 'Peca incluida: ' . $produto['descricao'] . ' x' . $qtd, $usuario['login']);

        $this->db->trans_complete();

        return array('ok' => $this->db->trans_status(), 'erro' => NULL);
    }

    public function excluir_peca($numero, $id, $usuario)
    {
        $peca = $this->db->query(
            "SELECT * FROM " . T_CHAMADO_PECAS . " WHERE id = ? AND chamado_numero = ?",
            array((int) $id, $numero)
        )->row_array();

        if (empty($peca))
        {
            return array('ok' => FALSE, 'erro' => 'Registro nao encontrado.');
        }

        if (bd_bool($peca['saiu_estoque']))
        {
            return array('ok' => FALSE, 'erro' => 'Peca ja baixada do estoque. Estorne a baixa antes de excluir.');
        }

        $this->db->trans_start();

        $this->_update(T_CHAMADO_PECAS, 'id', (int) $id, array(
            'excluido_em' => date('Y-m-d H:i:s'),
            'excluido_por' => $usuario['login'],
        ));

        $this->_evento($numero, 'peca', 'Peca excluida: ' . $peca['descricao_produto'], $usuario['login']);

        $this->db->trans_complete();

        return array('ok' => $this->db->trans_status(), 'erro' => NULL);
    }

    public function saldo_produto($produto_id)
    {
        $row = $this->db->query(
            "SELECT quantidade FROM " . T_ESTOQUE_SALDOS . " WHERE produto_id = ?",
            array((int) $produto_id)
        )->row_array();

        return $row ? (int) $row['quantidade'] : 0;
    }

    // ==================================================================
    // Servicos
    // ==================================================================

    public function servicos($numero)
    {
        $sql = "SELECT s.*, u.nome AS tecnico_nome
                  FROM " . T_CHAMADO_SERVICOS . " s
                  LEFT JOIN " . T_USUARIOS . " u ON u.funcionario_id = s.tecnico_id
                 WHERE s.chamado_numero = ?
                 ORDER BY s.tipo, s.numero_item";
        return $this->db->query($sql, array($numero))->result_array();
    }

    public function adicionar_servico($numero, $dados, $usuario)
    {
        $tipo = isset($dados['tipo']) ? $dados['tipo'] : 'avulso';

        if ( ! in_array($tipo, array('previsto', 'avulso'), TRUE))
        {
            return array('ok' => FALSE, 'erro' => 'Tipo de servico invalido.');
        }

        if (trim((string) $dados['descricao']) === '')
        {
            return array('ok' => FALSE, 'erro' => 'Informe a descricao do servico.');
        }

        $qtd = isset($dados['quantidade']) ? (int) $dados['quantidade'] : 1;

        if ($qtd <= 0)
        {
            return array('ok' => FALSE, 'erro' => 'Quantidade invalida.');
        }

        $valor = 0;
        if ($tipo === 'avulso')
        {
            $valor = (float) str_replace(',', '.', (string) $dados['valor_unitario']);
            if ($valor < 0)
            {
                return array('ok' => FALSE, 'erro' => 'Valor invalido.');
            }
        }

        $proximo = $this->db->query(
            "SELECT COALESCE(max(numero_item), 0) + 1 AS proximo
               FROM " . T_CHAMADO_SERVICOS . " WHERE chamado_numero = ? AND tipo = ?",
            array($numero, $tipo)
        )->row_array();

        $this->db->trans_start();

        $this->_insert(T_CHAMADO_SERVICOS, array(
            'chamado_numero' => $numero,
            'numero_item'    => (int) $proximo['proximo'],
            'tipo'           => $tipo,
            'descricao'      => trim($dados['descricao']),
            'quantidade'     => $qtd,
            'valor_unitario' => $this->dinheiro($valor),
            'valor_total'    => $this->dinheiro($valor * $qtd),
            'concluido'      => FALSE,
        ));

        $this->_evento($numero, 'servico', 'Servico incluido (' . $tipo . ')', $usuario['login']);

        $this->db->trans_complete();

        return array('ok' => $this->db->trans_status(), 'erro' => NULL);
    }

    /**
     * Conclui um servico previsto: vira executado (nao apaga o previsto).
     */
    public function concluir_servico($numero, $id, $usuario)
    {
        $s = $this->db->query(
            "SELECT * FROM " . T_CHAMADO_SERVICOS . " WHERE id = ? AND chamado_numero = ?",
            array((int) $id, $numero)
        )->row_array();

        if (empty($s))
        {
            return array('ok' => FALSE, 'erro' => 'Servico nao encontrado.');
        }

        if ($s['tipo'] !== 'previsto')
        {
            return array('ok' => FALSE, 'erro' => 'Somente servicos previstos podem ser concluidos.');
        }

        $this->db->trans_start();

        $this->_update(T_CHAMADO_SERVICOS, 'id', (int) $id, array(
            'tipo'          => 'executado',
            'concluido'     => TRUE,
            'data_execucao' => date('Y-m-d'),
            'tecnico_id'    => $this->funcionario_id($usuario),
        ));

        $this->_evento($numero, 'servico', 'Servico concluido: ' . $s['descricao'], $usuario['login']);

        $this->db->trans_complete();

        return array('ok' => $this->db->trans_status(), 'erro' => NULL);
    }

    // ==================================================================
    // Contatos
    // ==================================================================

    public function contatos($numero)
    {
        $sql = "SELECT c.*, u.nome AS responsavel_nome
                  FROM " . T_CHAMADO_CONTATOS . " c
                  LEFT JOIN " . T_USUARIOS . " u ON u.id = c.responsavel_id
                 WHERE c.chamado_numero = ?
                 ORDER BY c.data_movimento DESC, c.hora_movimento DESC";
        return $this->db->query($sql, array($numero))->result_array();
    }

    public function adicionar_contato($numero, $dados, $usuario)
    {
        if (empty($dados['data_movimento']))
        {
            return array('ok' => FALSE, 'erro' => 'Informe a Data do contato.');
        }
        if (empty($dados['hora_movimento']))
        {
            return array('ok' => FALSE, 'erro' => 'Informe a Hora do contato.');
        }
        if (trim((string) $dados['descricao']) === '')
        {
            return array('ok' => FALSE, 'erro' => 'Informe a descricao do contato.');
        }

        $existe = $this->db->query(
            "SELECT 1 FROM " . T_CHAMADO_CONTATOS . "
              WHERE chamado_numero = ? AND data_movimento = ? AND hora_movimento = ?",
            array($numero, $dados['data_movimento'], $dados['hora_movimento'])
        )->row_array();

        if ($existe)
        {
            return array('ok' => FALSE, 'erro' => 'Contato ja cadastrado nesta Data e Hora.');
        }

        $this->db->trans_start();

        $this->_insert(T_CHAMADO_CONTATOS, array(
            'chamado_numero'      => $numero,
            'data_movimento'      => $dados['data_movimento'],
            'hora_movimento'      => $dados['hora_movimento'],
            'descricao'           => trim($dados['descricao']),
            'responsavel_id'      => (int) $usuario['id'],
            'contato'             => $this->nullify($dados['contato']),
            'proximo_contato_data'=> $this->nullify($dados['proximo_contato_data']),
            'proximo_contato_hora'=> $this->nullify($dados['proximo_contato_hora']),
            'concluido'           => ! empty($dados['concluido']),
        ));

        $this->_evento($numero, 'contato', 'Contato registrado', $usuario['login']);

        $this->db->trans_complete();

        return array('ok' => $this->db->trans_status(), 'erro' => NULL);
    }

    // ==================================================================
    // Financeiro: plano de parcelas e contas a receber
    // ==================================================================

    public function plano($numero)
    {
        $row = $this->db->query(
            "SELECT * FROM " . T_CHAMADO_PLANOS . " WHERE chamado_numero = ?",
            array($numero)
        )->row_array();

        return $row ? $row : array();
    }

    public function parcelas($numero)
    {
        return $this->db->query(
            "SELECT * FROM " . T_CHAMADO_PARCELAS . "
              WHERE chamado_numero = ? ORDER BY numero",
            array($numero)
        )->result_array();
    }

    public function emite_parcelas($numero, $dados, $usuario)
    {
        $total = isset($dados['valor_total']) ? (float) $dados['valor_total'] : 0;
        $qtd   = isset($dados['quantidade_parcelas']) ? (int) $dados['quantidade_parcelas'] : 1;

        if ($qtd < 1 || $qtd > 12)
        {
            return array('ok' => FALSE, 'erro' => 'Quantidade de Parcelas deve ser entre 1 e 12.');
        }
        if (empty($dados['data_emissao']))
        {
            return array('ok' => FALSE, 'erro' => 'Informe a Data de Emissao.');
        }
        if (empty($dados['primeiro_vencimento']))
        {
            return array('ok' => FALSE, 'erro' => 'Informe a Data de vencimento.');
        }

        $intervalo = isset($dados['intervalo_dias']) ? (int) $dados['intervalo_dias'] : 30;

        if ($intervalo < 1)
        {
            return array('ok' => FALSE, 'erro' => 'Informe o Intervalo de Dias das Parcelas.');
        }

        $valores = $this->distribuir($total, $qtd);

        $this->db->trans_start();

        $plano = array(
            'chamado_numero'   => $numero,
            'data_emissao'     => $dados['data_emissao'],
            'data_vencimento_base' => $dados['primeiro_vencimento'],
            'condicao_pagamento_codigo' => $this->inteiro_ou_nulo($dados['condicao_pagamento_codigo']),
            'forma_recebimento_codigo'  => $this->inteiro_ou_nulo($dados['forma_recebimento_codigo']),
            'documento_codigo' => $this->inteiro_ou_nulo($dados['documento_codigo']),
            'cliente_nota_id'  => $this->inteiro_ou_nulo($dados['cliente_nota_id']),
            'valor_total'      => $this->dinheiro($total),
            'quantidade_parcelas' => $qtd,
        );

        $existe = $this->plano($numero);

        if (empty($existe))
        {
            $this->_insert(T_CHAMADO_PLANOS, $plano);
        }
        else
        {
            unset($plano['chamado_numero']);
            $plano['updated_at'] = date('Y-m-d H:i:s');
            $this->_update(T_CHAMADO_PLANOS, 'chamado_numero', $numero, $plano);
        }

        $this->db->query("DELETE FROM " . T_CHAMADO_PARCELAS . " WHERE chamado_numero = ?", array($numero));

        for ($i = 1; $i <= $qtd; $i++)
        {
            $venc = date('Y-m-d', strtotime($dados['primeiro_vencimento'] . ' +' . (($i - 1) * $intervalo) . ' days'));

            $this->_insert(T_CHAMADO_PARCELAS, array(
                'chamado_numero' => $numero,
                'numero'         => $i,
                'valor'          => $this->dinheiro($valores[$i - 1]),
                'dias'           => ($i === 1) ? 0 : $intervalo,
                'vencimento'     => $venc,
            ));
        }

        $this->_update(T_CHAMADOS, 'numero', $numero, array('qtd_parcelas' => $qtd));

        $evento_id = $this->_evento($numero, 'parcelas', 'Plano de parcelas atualizado', $usuario['login']);

        $this->db->trans_complete();

        return array('ok' => $this->db->trans_status(), 'erro' => NULL);
    }

    public function contas_receber($numero)
    {
        return $this->db->query(
            "SELECT cr.*,
                    (SELECT count(*) FROM " . T_CONTAS_RECEBER_BAIXAS . " b
                      WHERE b.conta_codigo = cr.codigo) AS qtd_baixas
               FROM " . T_CONTAS_RECEBER . " cr
              WHERE cr.chamado_numero = ?
              ORDER BY cr.parcela",
            array($numero)
        )->result_array();
    }

    // ==================================================================
    // Encerramento
    // ==================================================================

    /**
     * Calcula o total do encerramento (RN-213).
     */
    public function total_encerramento($numero, $valores = array())
    {
        $c = $this->por_numero($numero);

        if (empty($c))
        {
            return 0;
        }

        $mao_obra  = isset($valores['valor_mao_obra']) ? (float) $valores['valor_mao_obra'] : (float) $c['valor_mao_obra'];
        $imposto   = isset($valores['valor_imposto']) ? (float) $valores['valor_imposto'] : (float) $c['valor_imposto'];
        $acrescimo = isset($valores['acrescimo_custo']) ? (float) $valores['acrescimo_custo'] : (float) $c['acrescimo_custo'];
        $fixacao   = isset($valores['material_fixacao']) ? (float) $valores['material_fixacao'] : (float) $c['material_fixacao'];
        $desconto  = isset($valores['valor_desconto']) ? (float) $valores['valor_desconto'] : (float) $c['valor_desconto'];

        return round(
            (float) $c['total_pecas']
            + (float) $c['total_servicos']
            + $mao_obra
            + $fixacao
            + $imposto
            + $acrescimo
            - $desconto,
            2
        );
    }

    // ------------------------------------------------------------------

    public function encerrar($numero, $dados, $usuario)
    {
        $c = $this->por_numero($numero);

        if (empty($c))
        {
            return array('ok' => FALSE, 'erro' => 'Registro nao encontrado.');
        }

        if (bd_bool($c['encerrado']))
        {
            return array('ok' => FALSE, 'erro' => 'Registro ja esta encerrado.');
        }

        if ((int) $c['qtd_intervencoes'] === 0)
        {
            return array('ok' => FALSE, 'erro' => 'Deve haver pelo menos uma intervencao.');
        }

        if ( ! in_array((int) $c['situacao_codigo'], self::$situacoes_encerramento, TRUE))
        {
            return array('ok' => FALSE, 'erro' => 'Situacao Atual do(a) ' . $c['categoria'] . ' incoerente com o Encerramento.');
        }

        $at = $this->atendimento($numero);

        $solucao_data = ! empty($dados['solucao_data']) ? $dados['solucao_data'] : (isset($at['solucao_data']) ? $at['solucao_data'] : NULL);
        $solucao_hora = ! empty($dados['solucao_hora']) ? $dados['solucao_hora'] : (isset($at['solucao_hora']) ? $at['solucao_hora'] : NULL);

        if (empty($solucao_data) || empty($solucao_hora))
        {
            return array('ok' => FALSE, 'erro' => 'Informe a Data e Hora da Conclusao.');
        }

        $forma = isset($dados['forma_recebimento_codigo']) && $dados['forma_recebimento_codigo'] !== ''
            ? (int) $dados['forma_recebimento_codigo']
            : NULL;

        $sem_valor = ($forma === 8);

        $total = $this->total_encerramento($numero, $dados);

        if ( ! $sem_valor && $total <= 0)
        {
            return array('ok' => FALSE, 'erro' => 'Informe o Valor do Pagamento na aba do Financeiro.');
        }

        $parcelas = $this->parcelas($numero);

        if ( ! $sem_valor && empty($parcelas))
        {
            return array('ok' => FALSE, 'erro' => 'Para cadastrar no Contas a Receber pelo menos a 1a parcela deve ser informada na aba do Financeiro.');
        }

        $this->db->trans_start();

        // 1) atualiza valores e situacao
        $this->_update(T_CHAMADOS, 'numero', $numero, array(
            'valor_material'   => $this->dinheiro((float) $c['total_pecas']),
            'valor_servico'    => $this->dinheiro((float) $c['total_servicos']),
            'valor_mao_obra'   => $this->dinheiro(isset($dados['valor_mao_obra']) ? $dados['valor_mao_obra'] : $c['valor_mao_obra']),
            'valor_imposto'    => $this->dinheiro(isset($dados['valor_imposto']) ? $dados['valor_imposto'] : $c['valor_imposto']),
            'acrescimo_custo'  => $this->dinheiro(isset($dados['acrescimo_custo']) ? $dados['acrescimo_custo'] : $c['acrescimo_custo']),
            'material_fixacao' => $this->dinheiro(isset($dados['material_fixacao']) ? $dados['material_fixacao'] : $c['material_fixacao']),
            'valor_desconto'   => $this->dinheiro(isset($dados['valor_desconto']) ? $dados['valor_desconto'] : $c['valor_desconto']),
            'qtd_parcelas'     => $sem_valor ? 0 : count($parcelas),
        ));

        // 2) contas a receber (somente se ainda nao existir titulo para a OS)
        $foi_cr = FALSE;
        if ( ! $sem_valor)
        {
            $existe = $this->db->query(
                "SELECT 1 FROM " . T_CONTAS_RECEBER . "
                  WHERE chamado_numero = ? AND NOT cancelado",
                array($numero)
            )->row_array();

            if ( ! $existe)
            {
                $qtd = count($parcelas);
                $portador = isset($at['segmento_codigo']) ? $this->inteiro_ou_nulo($at['segmento_codigo']) : NULL;

                foreach ($parcelas as $p)
                {
                    $codigo = $this->numeracao->numero('CR');

                    $historico = 'Parcela ' . (int) $p['numero'] . '/' . $qtd
                        . ' - Valor ' . number_format((float) $p['valor'], 2, ',', '.')
                        . ' - OS/CT ' . $numero
                        . ' - Orc ' . ($c['orcamento_numero'] ? $c['orcamento_numero'] : '-')
                        . ' - Cliente ' . $c['cliente_nome'];

                    $this->_insert(T_CONTAS_RECEBER, array(
                        'codigo'          => $codigo,
                        'parcela'         => (int) $p['numero'],
                        'data_emissao'    => isset($dados['data_emissao']) && $dados['data_emissao'] ? $dados['data_emissao'] : date('Y-m-d'),
                        'chamado_numero'  => $numero,
                        'tipo'            => 1,
                        'forma_recebimento_codigo' => $forma,
                        'dias'            => (int) $p['dias'],
                        'data_vencimento' => $p['vencimento'],
                        'valor'           => $this->dinheiro($p['valor']),
                        'vliq'            => $this->dinheiro($p['valor']),
                        'cliente_id'      => (int) $c['cliente_id'],
                        'portador_codigo' => $portador,
                        'num_parcelas'    => $qtd,
                        'posicao'         => 1,
                        'historico'       => $historico,
                        'cliente_nota_id' => (int) $c['cliente_id'],
                        'usuario_cadastro'=> $usuario['login'],
                    ));
                }

                $foi_cr = TRUE;
            }
        }

        // 3) baixa de estoque
        $baixa = $this->baixar_estoque($numero, $usuario, TRUE);

        // 4) encerra
        $this->_update(T_CHAMADOS, 'numero', $numero, array(
            'encerrado'            => TRUE,
            'status_codigo'        => 5,
            'status_anterior'      => (int) $c['status_codigo'],
            'usuario_encerramento' => $usuario['login'],
            'data_encerramento'    => date('Y-m-d H:i:s'),
            'baixa_estoque_feita'  => TRUE,
        ));

        // 5) atendimento: conclusao
        $this->_update(T_CHAMADO_ATENDIMENTO, 'chamado_numero', $numero, array(
            'solucao_data' => $solucao_data,
            'solucao_hora' => $solucao_hora,
        ));

        // 6) equipamento: ultima OS
        $eq = $this->equipamento($numero);
        if ( ! empty($eq['equipamento_serie']))
        {
            $this->db->query(
                "UPDATE " . T_EQUIPAMENTOS . "
                    SET ultimo_chamado = ?, data_ultimo_chamado = ?, updated_at = now()
                  WHERE numero_serie = ?",
                array($numero, $c['data_abertura'], $eq['equipamento_serie'])
            );
        }

        $this->_evento($numero, 'encerramento', $foi_cr
            ? 'Encerramento efetuado. Enviado para o Contas a Receber.'
            : 'Encerramento efetuado. Nao enviado para o Contas a Receber.',
            $usuario['login']);

        $this->db->trans_complete();

        return array(
            'ok'      => $this->db->trans_status(),
            'erro'    => NULL,
            'foi_cr'  => $foi_cr,
            'baixa'   => $baixa,
            'total'   => $total,
        );
    }

    // ------------------------------------------------------------------

    /**
     * Baixa de estoque das pecas da OS.
     * $exigir_saldo = TRUE bloqueia item sem saldo (decisao D-04).
     */
    public function baixar_estoque($numero, $usuario, $exigir_saldo = TRUE)
    {
        $pecas = $this->db->query(
            "SELECT * FROM " . T_CHAMADO_PECAS . "
              WHERE chamado_numero = ? AND excluido_em IS NULL AND NOT saiu_estoque
              ORDER BY numero_item",
            array($numero)
        )->result_array();

        $baixadas  = 0;
        $ignoradas = 0;
        $avisos    = array();

        foreach ($pecas as $p)
        {
            $saldo = $this->saldo_produto($p['produto_id']);
            $qtd   = (int) $p['quantidade'];

            if ($exigir_saldo && $saldo < $qtd)
            {
                $ignoradas++;
                $avisos[] = $p['descricao_produto'] . ': estoque ' . $saldo . ', solicitado ' . $qtd . '.';
                continue;
            }

            $anterior = $saldo;
            $posterior = $saldo - $qtd;

            $this->db->query(
                "INSERT INTO " . T_ESTOQUE_SALDOS . " (produto_id, quantidade, quantidade_consumida)
                 VALUES (?, ?, ?)
                 ON CONFLICT (produto_id)
                 DO UPDATE SET quantidade = biostech_estoque_saldos.quantidade - ?,
                               quantidade_consumida = biostech_estoque_saldos.quantidade_consumida + ?,
                               updated_at = now()",
                array((int) $p['produto_id'], $posterior, $qtd, $qtd, $qtd)
            );

            $this->_insert(T_ESTOQUE_MOVIMENTOS, array(
                'produto_id'      => (int) $p['produto_id'],
                'chamado_numero'  => $numero,
                'tipo'            => 'saida',
                'quantidade'      => $qtd,
                'saldo_anterior'  => $anterior,
                'saldo_posterior' => $posterior,
                'usuario'         => $usuario['login'],
            ));

            $mov = $this->db->query(
                "SELECT currval(pg_get_serial_sequence(?, 'id')) AS id",
                array(T_ESTOQUE_MOVIMENTOS)
            )->row_array();

            $this->_update(T_CHAMADO_PECAS, 'id', (int) $p['id'], array(
                'quantidade_atendida' => $qtd,
                'saiu_estoque'        => TRUE,
                'data_baixa'          => date('Y-m-d'),
                'movimento_id'        => (int) $mov['id'],
            ));

            $baixadas++;
        }

        if ($baixadas > 0)
        {
            $this->_update(T_CHAMADOS, 'numero', $numero, array('baixa_estoque_feita' => TRUE));
            $this->_evento($numero, 'estoque', $baixadas . ' peca(s) baixada(s) do estoque', $usuario['login']);
        }

        return array(
            'baixadas'  => $baixadas,
            'ignoradas' => $ignoradas,
            'avisos'    => $avisos,
        );
    }

    // ==================================================================
    // Reabertura e cancelamento de encerramento
    // ==================================================================

    public function reabrir($numero, $motivo, $usuario)
    {
        $c = $this->por_numero($numero);

        if (empty($c) || ! bd_bool($c['encerrado']))
        {
            return array('ok' => FALSE, 'erro' => 'Somente registros encerrados podem ser reabertos.');
        }

        $this->db->trans_start();

        $this->_update(T_CHAMADOS, 'numero', $numero, array(
            'encerrado' => FALSE,
            'reaberto'  => TRUE,
        ));

        $this->_evento($numero, 'reabertura', 'Reaberto. Motivo: ' . trim((string) $motivo), $usuario['login']);

        $this->db->trans_complete();

        return array('ok' => $this->db->trans_status(), 'erro' => NULL);
    }

    // ------------------------------------------------------------------

    public function cancelar_encerramento($numero, $usuario)
    {
        $c = $this->por_numero($numero);

        if (empty($c) || ! bd_bool($c['encerrado']))
        {
            return array('ok' => FALSE, 'erro' => $c['categoria'] . ' nao esta Encerrado(a).');
        }

        $quitada = $this->db->query(
            "SELECT count(*) AS total
               FROM " . T_CONTAS_RECEBER . " cr
               JOIN " . T_CONTAS_RECEBER_BAIXAS . " b ON b.conta_codigo = cr.codigo
              WHERE cr.chamado_numero = ? AND NOT cr.cancelado",
            array($numero)
        )->row_array();

        if ((int) $quitada['total'] > 0)
        {
            return array('ok' => FALSE, 'erro' => 'Conta a Receber relativa a este(a) ' . $c['categoria'] . ' ja possui baixa. Cancele a baixa antes.');
        }

        $this->db->trans_start();

        $volta = ! empty($c['status_anterior']) ? (int) $c['status_anterior'] : 4;

        $this->_update(T_CHAMADOS, 'numero', $numero, array(
            'encerrado'    => FALSE,
            'reaberto'     => FALSE,
            'status_codigo'=> $volta,
            'usuario_encerramento' => NULL,
            'data_encerramento'    => NULL,
        ));

        $this->db->query(
            "UPDATE " . T_CONTAS_RECEBER . "
                SET cancelado = true, usuario_cancelamento = ?, data_cancelamento = now()
              WHERE chamado_numero = ? AND NOT cancelado",
            array($usuario['login'], $numero)
        );

        $this->_evento($numero, 'cancelamento_encerramento', 'Encerramento cancelado', $usuario['login']);

        $this->db->trans_complete();

        return array('ok' => $this->db->trans_status(), 'erro' => NULL);
    }

    // ==================================================================
    // Geracao cruzada Chamado <-> OS
    // ==================================================================

    public function gerar_par($numero, $usuario)
    {
        $c = $this->por_numero($numero);

        if (empty($c))
        {
            return array('ok' => FALSE, 'erro' => 'Registro nao encontrado.');
        }

        if ( ! empty($c['numero_gerado']))
        {
            return array('ok' => FALSE, 'erro' => 'Este registro ja gerou o numero ' . $c['numero_gerado'] . '.');
        }

        $nova_categoria = ($c['categoria'] === 'Chamado') ? 'Ordem Serviço' : 'Chamado';
        $novo = $this->numeracao->numero_os(date('Y-m-d'));

        $this->db->trans_start();

        $this->_insert(T_CHAMADOS, array(
            'numero'         => $novo,
            'numero_par'     => $c['numero_par'],
            'categoria'      => $nova_categoria,
            'cliente_id'     => (int) $c['cliente_id'],
            'escritorio_id'  => $c['escritorio_id'],
            'data_abertura'  => date('Y-m-d'),
            'hora_abertura'  => date('H:i'),
            'status_codigo'  => 1,
            'situacao_codigo'=> 11,
            'numero_gerado'  => $numero,
            'data_geracao'   => $c['data_abertura'],
            'defeito_relatado' => $c['defeito_relatado'],
            'defeito_constatado' => $c['defeito_constatado'],
            'observacao'     => $c['observacao'],
            'usuario_cadastro' => $usuario['login'],
        ));

        $eq = $this->equipamento($numero);
        if ( ! empty($eq))
        {
            $copia = array('chamado_numero' => $novo, 'recolhido' => ($nova_categoria === 'Ordem Serviço'));
            foreach (self::$campos_equipamento as $campo)
            {
                if (array_key_exists($campo, $eq) && $campo !== 'recolhido')
                {
                    $copia[$campo] = $eq[$campo];
                }
            }
            $this->_insert(T_CHAMADO_EQUIPAMENTO, $copia);
        }

        $at = $this->atendimento($numero);
        if ( ! empty($at))
        {
            $copia = array('chamado_numero' => $novo);
            foreach (self::$campos_atendimento as $campo)
            {
                if (array_key_exists($campo, $at) && ! in_array($campo, array('atendimento_data', 'atendimento_hora', 'atendimento_hora_fim', 'solucao_data', 'solucao_hora', 'devolucao_data', 'devolucao_hora'), TRUE))
                {
                    $copia[$campo] = $at[$campo];
                }
            }
            $this->_insert(T_CHAMADO_ATENDIMENTO, $copia);
        }

        $this->_update(T_CHAMADOS, 'numero', $numero, array(
            'numero_gerado' => $novo,
            'data_geracao'  => date('Y-m-d'),
            'usuario_alteracao' => $usuario['login'],
            'data_alteracao'    => date('Y-m-d H:i:s'),
        ));

        $this->_insert(T_CHAMADO_VINCULOS, array(
            'pai_numero'   => $numero,
            'filho_numero' => $novo,
        ));

        $this->_evento($numero, 'geracao', 'Gerado ' . $nova_categoria . ' numero ' . $novo, $usuario['login']);
        $this->_evento($novo, 'abertura', 'Gerado a partir de ' . $numero, $usuario['login']);

        $this->db->trans_complete();

        if ( ! $this->db->trans_status())
        {
            return array('ok' => FALSE, 'erro' => 'Nao foi possivel gerar o registro.');
        }

        return array('ok' => TRUE, 'numero' => $novo, 'categoria' => $nova_categoria);
    }

    // ==================================================================
    // Historico
    // ==================================================================

    public function eventos($numero)
    {
        return $this->db->query(
            "SELECT * FROM " . T_CHAMADO_EVENTOS . "
              WHERE chamado_numero = ? ORDER BY created_at DESC, id DESC",
            array($numero)
        )->result_array();
    }

    public function log($numero)
    {
        return $this->db->query(
            "SELECT * FROM " . T_CHAMADO_LOG . "
              WHERE chamado_numero = ? ORDER BY created_at DESC, id DESC LIMIT 300",
            array($numero)
        )->result_array();
    }

    // ==================================================================
    // Autocomplete
    // ==================================================================

    public function buscar_clientes($termo, $limite = 20)
    {
        $sql = "SELECT id, nome, cpf_cnpj, cidade, uf
                  FROM " . T_CLIENTES . "
                 WHERE nome ILIKE ? OR cpf_cnpj ILIKE ?
                 ORDER BY nome
                 LIMIT " . (int) $limite;

        $t = '%' . $termo . '%';
        return $this->db->query($sql, array($t, $t))->result_array();
    }

    public function buscar_produtos($termo, $limite = 20)
    {
        $sql = "SELECT p.id, p.descricao, p.codigo, p.valor_venda,
                       COALESCE(es.quantidade, 0) AS saldo
                  FROM " . T_PRODUTOS . " p
                  LEFT JOIN " . T_ESTOQUE_SALDOS . " es ON es.produto_id = p.id
                 WHERE p.descricao ILIKE ? OR p.codigo ILIKE ?
                 ORDER BY p.descricao
                 LIMIT " . (int) $limite;

        $t = '%' . $termo . '%';
        return $this->db->query($sql, array($t, $t))->result_array();
    }

    // ==================================================================
    // Helpers internos
    // ==================================================================

    /** Distribui o total em N parcelas (RN-194). */
    public function distribuir($total, $qtd)
    {
        $total = round((float) $total, 2);
        $qtd   = (int) $qtd;

        if ($qtd <= 0)
        {
            return array();
        }

        $base = round($total / $qtd, 2);
        $soma = round($base * $qtd, 2);
        $dif  = round($total - $soma, 2);

        $valores = array_fill(0, $qtd, $base);

        if ($dif > 0)
        {
            $valores[0] = round($base + $dif, 2);
        }
        elseif ($dif < 0)
        {
            $valores[$qtd - 1] = round($base + $dif, 2);
        }

        return $valores;
    }

    // ------------------------------------------------------------------

    private function _garantir_equipamento($serie, $cliente_id)
    {
        $serie = $this->nullify($serie);

        if ($serie === NULL)
        {
            return;
        }

        $existe = $this->db->query(
            "SELECT numero_serie FROM " . T_EQUIPAMENTOS . " WHERE numero_serie = ?",
            array($serie)
        )->row_array();

        if (empty($existe))
        {
            $this->_insert(T_EQUIPAMENTOS, array(
                'numero_serie' => $serie,
                'cliente_id'   => $cliente_id ? (int) $cliente_id : NULL,
            ));
        }
    }

    // ------------------------------------------------------------------

    private function _insert($tabela, $dados)
    {
        $colunas = array();
        $marcas  = array();
        $params  = array();

        foreach ($dados as $coluna => $valor)
        {
            $colunas[] = $coluna;
            $marcas[]  = '?';
            $params[]  = $this->_valor($valor);
        }

        $sql = "INSERT INTO {$tabela} (" . implode(', ', $colunas) . ")
                VALUES (" . implode(', ', $marcas) . ")";

        $this->db->query($sql, $params);
    }

    // ------------------------------------------------------------------

    private function _update($tabela, $chave, $valor_chave, $dados)
    {
        if (empty($dados))
        {
            return;
        }

        $sets   = array();
        $params = array();

        foreach ($dados as $coluna => $valor)
        {
            $sets[]   = "$coluna = ?";
            $params[] = $this->_valor($valor);
        }

        $params[] = $valor_chave;

        $sql = "UPDATE {$tabela} SET " . implode(', ', $sets) . " WHERE {$chave} = ?";
        $this->db->query($sql, $params);
    }

    // ------------------------------------------------------------------

    private function _valor($valor)
    {
        if (is_bool($valor))
        {
            return $valor ? 'true' : 'false';
        }

        return $valor;
    }

    // ------------------------------------------------------------------

    private function _evento($numero, $tipo, $descricao, $usuario, $dados = NULL)
    {
        $this->db->query(
            "INSERT INTO " . T_CHAMADO_EVENTOS . " (chamado_numero, tipo, descricao, usuario, dados)
             VALUES (?, ?, ?, ?, ?::jsonb)",
            array(
                $numero,
                $tipo,
                $descricao,
                $usuario,
                $dados === NULL ? NULL : json_encode($dados),
            )
        );

        $row = $this->db->query("SELECT currval(pg_get_serial_sequence(?, 'id')) AS id",
            array(T_CHAMADO_EVENTOS))->row_array();

        return $row ? (int) $row['id'] : NULL;
    }

    // ------------------------------------------------------------------

    private function _diff($numero, $antes, $depois, $evento_id, $usuario)
    {
        foreach ($depois as $campo => $novo)
        {
            if ( ! array_key_exists($campo, $antes))
            {
                continue;
            }

            $velho = $antes[$campo];

            if ($this->_igual($velho, $novo))
            {
                continue;
            }

            $this->db->query(
                "INSERT INTO " . T_CHAMADO_LOG . "
                    (chamado_numero, evento_id, campo, valor_anterior, valor_novo, usuario)
                 VALUES (?, ?, ?, ?, ?, ?)",
                array(
                    $numero,
                    $evento_id,
                    $campo,
                    $velho === NULL ? NULL : (string) $velho,
                    $novo === NULL ? NULL : (string) $novo,
                    $usuario,
                )
            );
        }
    }

    // ------------------------------------------------------------------

    private function _igual($a, $b)
    {
        if (($a === NULL || $a === '') && ($b === NULL || $b === ''))
        {
            return TRUE;
        }

        if (is_bool($b))
        {
            return bd_bool($a) === $b;
        }

        return (string) $a === (string) $b;
    }

    // ------------------------------------------------------------------

    public function nullify($valor)
    {
        if ($valor === NULL)
        {
            return NULL;
        }

        $valor = trim((string) $valor);

        return $valor === '' ? NULL : $valor;
    }

    public function inteiro_ou_nulo($valor)
    {
        if ($valor === NULL || $valor === '' || $valor === FALSE)
        {
            return NULL;
        }

        return (int) $valor;
    }

    public function dinheiro($valor)
    {
        return number_format((float) $valor, 2, '.', '');
    }

    public function funcionario_id($usuario)
    {
        if ( ! empty($usuario['funcionario_id']))
        {
            return (int) $usuario['funcionario_id'];
        }

        // sessoes antigas nao tem a chave: busca no banco
        $row = $this->db->query(
            "SELECT funcionario_id FROM " . T_USUARIOS . " WHERE id = ?",
            array((int) $usuario['id'])
        )->row_array();

        return ($row && $row['funcionario_id'] !== NULL) ? (int) $row['funcionario_id'] : NULL;
    }
}
