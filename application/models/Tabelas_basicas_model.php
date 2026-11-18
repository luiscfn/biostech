<?php
/**
 * Tabelas_basicas_model - itens do menu F_MODULO_TABELAS do Access.
 *
 * A maior parte dos itens e um dominio simples em biostech_dominios
 * (tipo + codigo + nome + valor + ordem + ativo). Setores usam a tabela
 * biostech_setores ja existente; o orcamento nao aprovado usa uma tabela de
 * parametros de linha unica; e as permissoes usam modulos/programas.
 *
 * Ver docs/11-regras-tabelas-basicas.md.
 */
class Tabelas_basicas_model extends MY_Model
{
    /** itens do menu, na ordem do Access (A..K) */
    private $tipos = array(
        'setores' => array(
            'letra'      => 'A',
            'rotulo'     => 'Setores e Filiais',
            'descricao'  => 'Setores usados nos chamados, usuarios e demais modulos.',
            'fonte'      => 'setores',
            'rotulo_uso' => 'Usuarios',
            'uso'        => array(
                array(T_USUARIOS, 'setor_id', 'usuario', FALSE),
            ),
        ),
        'tipos_equipamento' => array(
            'letra'      => 'B',
            'rotulo'     => 'Tipos de Equipamentos',
            'descricao'  => 'Classificacao dos equipamentos atendidos.',
            'fonte'      => 'dominio',
            'dominio'    => 'tipo_equipamento',
            'rotulo_uso' => 'Qtd. equip.',
            'uso'        => array(
                array(T_EQUIPAMENTOS, 'tipo_codigo', 'equipamento', FALSE),
            ),
        ),
        'status_os' => array(
            'letra'      => 'C',
            'rotulo'     => 'Status da Ordem de Servico',
            'descricao'  => 'Situacao operacional do chamado/ordem de servico.',
            'fonte'      => 'dominio',
            'dominio'    => 'status_os',
            'rotulo_uso' => 'Chamados',
            'uso'        => array(
                array(T_CHAMADOS, 'status_codigo', 'chamado', FALSE),
            ),
        ),
        'status_equipamento' => array(
            'letra'      => 'D',
            'rotulo'     => 'Status do Equipamento',
            'descricao'  => 'Situacao do equipamento durante o atendimento.',
            'fonte'      => 'dominio',
            'dominio'    => 'status_equipamento',
            'uso'        => array(),
        ),
        'condicoes_pagamento' => array(
            'letra'      => 'E',
            'rotulo'     => 'Condicoes de Pagamento',
            'descricao'  => 'Prazos e condicoes usados em orcamentos e pedidos.',
            'fonte'      => 'dominio',
            'dominio'    => 'condicao_pagamento',
            'rotulo_valor' => 'Desconto (%)',
            'rotulo_uso' => 'Uso',
            'uso'        => array(
                array(T_CHAMADO_PLANOS, 'condicao_pagamento_codigo', 'plano de pagamento', FALSE),
                array(T_COMPRAS_PEDIDOS, 'condicao_pagamento', 'pedido de compra', FALSE),
                array(T_ORCAMENTOS, 'condicao_pagamento', 'orcamento', FALSE),
            ),
        ),
        'situacoes_orcamento' => array(
            'letra'      => '',
            'rotulo'     => 'Status do Orcamento',
            'descricao'  => 'Andamento do orcamento (aberto, enviado, concluido...).',
            'fonte'      => 'dominio',
            'dominio'    => 'situacao_orcamento',
            'rotulo_uso' => 'Orcamentos',
            'uso'        => array(
                array(T_ORCAMENTOS, 'situacao', 'orcamento', FALSE),
            ),
        ),
        'locais_atendimento' => array(
            'letra'      => '',
            'rotulo'     => 'Locais de Atendimento',
            'descricao'  => 'Locais onde o equipamento fica durante o atendimento.',
            'fonte'      => 'dominio',
            'dominio'    => 'local_atendimento',
            'rotulo_uso' => 'Atendimentos',
            'uso'        => array(
                array(T_CHAMADO_ATENDIMENTO, 'local_atendimento', 'atendimento', TRUE),
                array(T_CHAMADO_EQUIPAMENTO, 'local_atendimento', 'equipamento do chamado', TRUE),
            ),
        ),
        'valor_orcamento_nao_aprovado' => array(
            'letra'     => '',
            'rotulo'    => 'Valor Orcamento Nao Aprovado',
            'descricao' => 'Valor cobrado, prazos e texto de rodape do orcamento.',
            'fonte'     => 'parametros',
        ),
        'finalidades' => array(
            'letra'      => 'F',
            'rotulo'     => 'Finalidade do Orcamento',
            'descricao'  => 'Finalidade do servico (manutencao, instalacao, venda...).',
            'fonte'      => 'dominio',
            'dominio'    => 'finalidade',
            'rotulo_uso' => 'Orcamentos',
            'uso'        => array(
                array(T_ORCAMENTOS, 'finalidade', 'orcamento', FALSE),
            ),
        ),
        'naturezas_nota' => array(
            'letra'      => 'G',
            'rotulo'     => 'Natureza das Notas Fiscais',
            'descricao'  => 'Natureza de operacao usada nas notas fiscais.',
            'fonte'      => 'dominio',
            'dominio'    => 'natureza_operacao',
            'rotulo_uso' => 'Despesas',
            'uso'        => array(
                array(T_FIN_DESPESAS_FIXAS, 'natureza_codigo', 'despesa fixa', FALSE),
            ),
        ),
        'funcoes' => array(
            'letra'        => 'H',
            'rotulo'       => 'Cargos e Funcoes',
            'descricao'    => 'Cargos/funcoes dos funcionarios.',
            'fonte'        => 'dominio',
            'dominio'      => 'funcao',
            'rotulo_valor' => 'Valor',
            'uso'          => array(),
        ),
        'permissoes' => array(
            'letra'     => 'I',
            'rotulo'    => 'Permissoes de Acesso',
            'descricao' => 'Modulos e programas do sistema legado.',
            'fonte'     => 'permissoes',
        ),
        'tipos_pagamento' => array(
            'letra'      => 'K',
            'rotulo'     => 'Tipos de Pagamentos',
            'descricao'  => 'Formas de pagamento aceitas nos titulos.',
            'fonte'      => 'dominio',
            'dominio'    => 'tipo_pagamento',
            'rotulo_uso' => 'Planos',
            'uso'        => array(
                array(T_CHAMADO_PLANOS, 'tipo_pagamento', 'plano de pagamento', FALSE),
            ),
        ),
    );

    // ==================================================================
    // Configuracao
    // ==================================================================

    public function tipos()
    {
        return $this->tipos;
    }

    public function tipo($chave)
    {
        return isset($this->tipos[$chave]) ? $this->tipos[$chave] : NULL;
    }

    // ==================================================================
    // Contagens do menu
    // ==================================================================

    public function contagens()
    {
        $contagens = array();

        foreach (array_keys($this->tipos) as $chave)
        {
            $contagens[$chave] = $this->contagem($chave);
        }

        return $contagens;
    }

    public function contagem($chave)
    {
        $cfg = $this->tipo($chave);

        if ($cfg === NULL)
        {
            return 0;
        }

        if ($cfg['fonte'] === 'setores')
        {
            return (int) $this->_valor("SELECT count(*) FROM " . T_SETORES);
        }

        if ($cfg['fonte'] === 'parametros')
        {
            return (int) $this->_valor("SELECT count(*) FROM " . T_BASICO_PARAMETROS);
        }

        if ($cfg['fonte'] === 'permissoes')
        {
            return (int) $this->_valor("SELECT count(*) FROM " . T_BASICO_MODULOS);
        }

        return (int) $this->_valor(
            "SELECT count(*) FROM " . T_DOMINIOS . " WHERE tipo = ?",
            array($cfg['dominio'])
        );
    }

    // ==================================================================
    // Lista / formulario dos itens simples
    // ==================================================================

    public function registros($chave, $busca = '', $limit = NULL, $offset = NULL)
    {
        $cfg = $this->tipo($chave);

        if ($cfg === NULL)
        {
            return array();
        }

        $busca = trim((string) $busca);

        if ($cfg['fonte'] === 'setores')
        {
            $sql = "SELECT s.id AS codigo, s.nome, NULL::numeric AS valor,
                           s.id AS ordem, s.ativo,
                           (SELECT count(*) FROM " . T_USUARIOS . " u
                             WHERE u.setor_id = s.id) AS uso
                      FROM " . T_SETORES . " s";
            $params = array();

            if ($busca !== '')
            {
                $sql .= " WHERE (s.nome ILIKE ? OR s.id::text = ?)";
                $params[] = '%' . $busca . '%';
                $params[] = $busca;
            }

            $sql .= " ORDER BY s.id";

            if ($limit !== NULL)
            {
                $sql .= " LIMIT " . (int) $limit . " OFFSET " . (int) $offset;
            }

            return $this->db->query($sql, $params)->result_array();
        }

        $sql = "SELECT d.codigo, d.nome, d.valor, d.ordem, d.ativo";

        foreach ($this->uso_sql($cfg) as $sub)
        {
            $sql .= ", " . $sub;
        }

        $sql .= " FROM " . T_DOMINIOS . " d WHERE d.tipo = ?";
        $params = array($cfg['dominio']);

        if ($busca !== '')
        {
            $sql .= " AND (d.nome ILIKE ? OR d.codigo::text = ?)";
            $params[] = '%' . $busca . '%';
            $params[] = $busca;
        }

        $sql .= " ORDER BY d.ordem NULLS LAST, d.codigo";

        if ($limit !== NULL)
        {
            $sql .= " LIMIT " . (int) $limit . " OFFSET " . (int) $offset;
        }

        return $this->db->query($sql, $params)->result_array();
    }

    public function total_registros($chave, $busca = '')
    {
        $cfg = $this->tipo($chave);

        if ($cfg === NULL)
        {
            return 0;
        }

        $busca = trim((string) $busca);

        if ($cfg['fonte'] === 'setores')
        {
            $sql = "SELECT count(*) FROM " . T_SETORES . " s";
            $params = array();

            if ($busca !== '')
            {
                $sql .= " WHERE (s.nome ILIKE ? OR s.id::text = ?)";
                $params[] = '%' . $busca . '%';
                $params[] = $busca;
            }

            return (int) $this->_valor($sql, $params);
        }

        if ($cfg['fonte'] !== 'dominio')
        {
            return 0;
        }

        $sql = "SELECT count(*) FROM " . T_DOMINIOS . " d WHERE d.tipo = ?";
        $params = array($cfg['dominio']);

        if ($busca !== '')
        {
            $sql .= " AND (d.nome ILIKE ? OR d.codigo::text = ?)";
            $params[] = '%' . $busca . '%';
            $params[] = $busca;
        }

        return (int) $this->_valor($sql, $params);
    }

    public function registro($chave, $codigo)
    {
        $cfg = $this->tipo($chave);

        if ($cfg === NULL)
        {
            return NULL;
        }

        if ($cfg['fonte'] === 'setores')
        {
            return $this->db->query(
                "SELECT id AS codigo, nome, ativo FROM " . T_SETORES . " WHERE id = ?",
                array((int) $codigo)
            )->row_array();
        }

        return $this->db->query(
            "SELECT codigo, nome, valor, ordem, ativo FROM " . T_DOMINIOS . "
              WHERE tipo = ? AND codigo = ?",
            array($cfg['dominio'], (int) $codigo)
        )->row_array();
    }

    public function proximo_codigo($chave)
    {
        $cfg = $this->tipo($chave);

        if ($cfg === NULL)
        {
            return 1;
        }

        if ($cfg['fonte'] === 'setores')
        {
            return (int) $this->_valor("SELECT COALESCE(MAX(id), 0) + 1 FROM " . T_SETORES);
        }

        return (int) $this->_valor(
            "SELECT COALESCE(MAX(codigo), 0) + 1 FROM " . T_DOMINIOS . " WHERE tipo = ?",
            array($cfg['dominio'])
        );
    }

    public function salvar($chave, $dados, $codigo = NULL)
    {
        $cfg = $this->tipo($chave);

        if ($cfg === NULL)
        {
            return $this->_erro('Item invalido.');
        }

        $nome = $this->_nome($dados);

        if ($nome === NULL)
        {
            return $this->_erro('Informe o nome.');
        }

        if ($cfg['fonte'] === 'setores')
        {
            return $this->_salvar_setor($dados, $codigo);
        }

        if ($cfg['fonte'] !== 'dominio')
        {
            return $this->_erro('Este item nao usa cadastro simples.');
        }

        $codigo_novo = isset($dados['codigo']) ? (int) $dados['codigo'] : 0;

        if ($codigo_novo <= 0)
        {
            return $this->_erro('Informe um codigo maior que zero.');
        }

        $valor = $this->_dec(isset($dados['valor']) ? $dados['valor'] : NULL);
        $ordem = $this->_int(isset($dados['ordem']) ? $dados['ordem'] : NULL);
        $ativo = ! empty($dados['ativo']);

        if ($ordem === NULL)
        {
            $ordem = $codigo_novo;
        }

        if ($codigo === NULL)
        {
            $ja = $this->registro($chave, $codigo_novo);

            if ( ! empty($ja))
            {
                return $this->_erro('Ja existe um item com o codigo ' . $codigo_novo . '.');
            }

            $this->db->query(
                "INSERT INTO " . T_DOMINIOS . " (tipo, codigo, nome, valor, ordem, ativo)
                 VALUES (?, ?, ?, ?, ?, ?)",
                array($cfg['dominio'], $codigo_novo, $nome, $valor, $ordem,
                      $ativo ? 'true' : 'false')
            );
        }
        else
        {
            $this->db->query(
                "UPDATE " . T_DOMINIOS . "
                    SET nome = ?, valor = ?, ordem = ?, ativo = ?
                  WHERE tipo = ? AND codigo = ?",
                array($nome, $valor, $ordem, $ativo ? 'true' : 'false',
                      $cfg['dominio'], (int) $codigo)
            );
        }

        return array('ok' => TRUE, 'erro' => NULL, 'mensagem' => 'Item gravado.');
    }

    private function _salvar_setor($dados, $id = NULL)
    {
        $nome  = $this->_nome($dados);
        $ativo = ! empty($dados['ativo']);

        if ($id === NULL)
        {
            $novo = isset($dados['codigo']) ? (int) $dados['codigo'] : 0;

            if ($novo <= 0)
            {
                return $this->_erro('Informe um codigo maior que zero.');
            }

            if ( ! empty($this->registro('setores', $novo)))
            {
                return $this->_erro('Ja existe um setor com o codigo ' . $novo . '.');
            }

            $this->db->query(
                "INSERT INTO " . T_SETORES . " (id, nome, ativo) VALUES (?, ?, ?)",
                array($novo, $nome, $ativo ? 'true' : 'false')
            );
        }
        else
        {
            $this->db->query(
                "UPDATE " . T_SETORES . " SET nome = ?, ativo = ?, updated_at = now() WHERE id = ?",
                array($nome, $ativo ? 'true' : 'false', (int) $id)
            );
        }

        return array('ok' => TRUE, 'erro' => NULL, 'mensagem' => 'Setor gravado.');
    }

    public function excluir_item($chave, $codigo)
    {
        $cfg = $this->tipo($chave);

        if ($cfg === NULL)
        {
            return $this->_erro('Item invalido.');
        }

        $codigo = (int) $codigo;
        $row    = $this->registro($chave, $codigo);

        if (empty($row))
        {
            return $this->_erro('Registro nao encontrado.');
        }

        $uso = $this->uso_item($cfg, $row);

        if ($uso['total'] > 0)
        {
            return $this->_erro('Nao e possivel excluir: existem ' . $uso['total']
                . ' registro(s) vinculados (' . $uso['rotulo'] . ').');
        }

        if ($cfg['fonte'] === 'setores')
        {
            $this->db->query("DELETE FROM " . T_SETORES . " WHERE id = ?", array($codigo));
        }
        else
        {
            $this->db->query(
                "DELETE FROM " . T_DOMINIOS . " WHERE tipo = ? AND codigo = ?",
                array($cfg['dominio'], $codigo)
            );
        }

        return array('ok' => TRUE, 'erro' => NULL, 'mensagem' => 'Registro excluido.');
    }

    // ==================================================================
    // Uso dos itens
    // ==================================================================

    public function uso_item($cfg, $row)
    {
        $total = 0;
        $rotulos = array();

        foreach ($cfg['uso'] as $ref)
        {
            list($tabela, $coluna, $rotulo, $por_nome) = $ref;

            $valor = $por_nome ? $row['nome'] : $row['codigo'];
            $n = (int) $this->_valor(
                "SELECT count(*) FROM " . $tabela . " WHERE " . $coluna . " = ?",
                array($valor)
            );

            if ($n > 0)
            {
                $total += $n;
                $rotulos[] = $n . ' ' . $rotulo . ($n > 1 ? 's' : '');
            }
        }

        return array(
            'total'  => $total,
            'rotulo' => $rotulos ? implode(', ', $rotulos) : 'registros',
        );
    }

    private function uso_sql($cfg)
    {
        $partes = array();
        $i = 0;

        foreach ($cfg['uso'] as $ref)
        {
            list($tabela, $coluna, $rotulo, $por_nome) = $ref;
            $i++;

            $partes[] = "(SELECT count(*) FROM " . $tabela . " r" . $i
                . " WHERE r" . $i . "." . $coluna
                . ($por_nome ? " = d.nome)" : " = d.codigo)");
        }

        if (empty($partes))
        {
            return array("0 AS uso");
        }

        return array(implode(' + ', $partes) . " AS uso");
    }

    // ==================================================================
    // Parametros do orcamento nao aprovado
    // ==================================================================

    public function parametros()
    {
        $row = $this->db->query(
            "SELECT * FROM " . T_BASICO_PARAMETROS . " WHERE id = 1"
        )->row_array();

        return empty($row) ? array(
            'id' => 1, 'valor' => 0, 'prazo_ret_equip' => NULL,
            'valor_taxa_perm' => NULL, 'prazo_ini_envio_orc' => NULL,
            'prazo_fim_envio_orc' => NULL, 'dias_limite' => NULL,
            'texto_rodape' => '',
        ) : $row;
    }

    public function salvar_parametros($dados)
    {
        $this->db->query(
            "INSERT INTO " . T_BASICO_PARAMETROS . "
                (id, valor, prazo_ret_equip, valor_taxa_perm, prazo_ini_envio_orc,
                 prazo_fim_envio_orc, dias_limite, texto_rodape, updated_at)
             VALUES (1, ?, ?, ?, ?, ?, ?, ?, now())
             ON CONFLICT (id) DO UPDATE SET
                valor               = EXCLUDED.valor,
                prazo_ret_equip     = EXCLUDED.prazo_ret_equip,
                valor_taxa_perm     = EXCLUDED.valor_taxa_perm,
                prazo_ini_envio_orc = EXCLUDED.prazo_ini_envio_orc,
                prazo_fim_envio_orc = EXCLUDED.prazo_fim_envio_orc,
                dias_limite         = EXCLUDED.dias_limite,
                texto_rodape        = EXCLUDED.texto_rodape,
                updated_at          = now()",
            array(
                $this->_dec(isset($dados['valor']) ? $dados['valor'] : NULL),
                $this->_int(isset($dados['prazo_ret_equip']) ? $dados['prazo_ret_equip'] : NULL),
                $this->_dec(isset($dados['valor_taxa_perm']) ? $dados['valor_taxa_perm'] : NULL),
                $this->_int(isset($dados['prazo_ini_envio_orc']) ? $dados['prazo_ini_envio_orc'] : NULL),
                $this->_int(isset($dados['prazo_fim_envio_orc']) ? $dados['prazo_fim_envio_orc'] : NULL),
                $this->_int(isset($dados['dias_limite']) ? $dados['dias_limite'] : NULL),
                $this->_texto(isset($dados['texto_rodape']) ? $dados['texto_rodape'] : NULL),
            )
        );

        return array('ok' => TRUE, 'erro' => NULL, 'mensagem' => 'Parametros gravados.');
    }

    // ==================================================================
    // Permissoes de acesso (modulos e programas)
    // ==================================================================

    public function modulos($busca = '', $limit = NULL, $offset = NULL)
    {
        $sql = "SELECT m.codigo, m.nome, m.ordem, m.ativo,
                       (SELECT count(*) FROM " . T_BASICO_PROGRAMAS . " p
                         WHERE p.modulo_codigo = m.codigo) AS qtd_programas
                  FROM " . T_BASICO_MODULOS . " m";
        $params = array();

        if (trim((string) $busca) !== '')
        {
            $sql .= " WHERE (m.nome ILIKE ? OR m.codigo::text = ?)";
            $params[] = '%' . trim($busca) . '%';
            $params[] = trim($busca);
        }

        $sql .= " ORDER BY m.ordem NULLS LAST, m.codigo";

        if ($limit !== NULL)
        {
            $sql .= " LIMIT " . (int) $limit . " OFFSET " . (int) $offset;
        }

        return $this->db->query($sql, $params)->result_array();
    }

    public function total_modulos($busca = '')
    {
        $sql = "SELECT count(*) FROM " . T_BASICO_MODULOS . " m";
        $params = array();

        if (trim((string) $busca) !== '')
        {
            $sql .= " WHERE (m.nome ILIKE ? OR m.codigo::text = ?)";
            $params[] = '%' . trim($busca) . '%';
            $params[] = trim($busca);
        }

        return (int) $this->_valor($sql, $params);
    }

    public function modulo($codigo)
    {
        return $this->db->query(
            "SELECT * FROM " . T_BASICO_MODULOS . " WHERE codigo = ?",
            array((int) $codigo)
        )->row_array();
    }

    public function proximo_codigo_modulo()
    {
        return (int) $this->_valor(
            "SELECT COALESCE(MAX(codigo), 0) + 1 FROM " . T_BASICO_MODULOS
        );
    }

    public function salvar_modulo($dados, $codigo = NULL)
    {
        $nome = $this->_nome($dados);

        if ($nome === NULL)
        {
            return $this->_erro('Informe o nome do modulo.');
        }

        $ordem = $this->_int(isset($dados['ordem']) ? $dados['ordem'] : NULL);
        $ativo = ! empty($dados['ativo']);

        if ($codigo === NULL)
        {
            $novo = isset($dados['codigo']) ? (int) $dados['codigo'] : 0;

            if ($novo <= 0)
            {
                return $this->_erro('Informe um codigo maior que zero.');
            }

            if ( ! empty($this->modulo($novo)))
            {
                return $this->_erro('Ja existe um modulo com o codigo ' . $novo . '.');
            }

            if ($ordem === NULL) { $ordem = $novo; }

            $this->db->query(
                "INSERT INTO " . T_BASICO_MODULOS . " (codigo, nome, ordem, ativo)
                 VALUES (?, ?, ?, ?)",
                array($novo, $nome, $ordem, $ativo ? 'true' : 'false')
            );

            $codigo = $novo;
        }
        else
        {
            $this->db->query(
                "UPDATE " . T_BASICO_MODULOS . "
                    SET nome = ?, ordem = ?, ativo = ?, updated_at = now()
                  WHERE codigo = ?",
                array($nome, $ordem, $ativo ? 'true' : 'false', (int) $codigo)
            );
        }

        return array('ok' => TRUE, 'erro' => NULL, 'mensagem' => 'Modulo gravado.',
                     'codigo' => (int) $codigo);
    }

    public function excluir_modulo($codigo)
    {
        $codigo = (int) $codigo;
        $modulo = $this->modulo($codigo);

        if (empty($modulo))
        {
            return $this->_erro('Modulo nao encontrado.');
        }

        $programas = (int) $this->_valor(
            "SELECT count(*) FROM " . T_BASICO_PROGRAMAS . " WHERE modulo_codigo = ?",
            array($codigo)
        );

        if ($programas > 0)
        {
            return $this->_erro('Nao e possivel excluir: existem ' . $programas
                . ' programa(s) vinculados a este modulo.');
        }

        $this->db->query("DELETE FROM " . T_BASICO_MODULOS . " WHERE codigo = ?", array($codigo));

        return array('ok' => TRUE, 'erro' => NULL, 'mensagem' => 'Modulo excluido.');
    }

    public function programas($modulo, $limit = NULL, $offset = NULL)
    {
        $sql = "SELECT codigo, nome, formulario, ordem, ativo, 0 AS uso
                  FROM " . T_BASICO_PROGRAMAS . "
                 WHERE modulo_codigo = ?
                 ORDER BY ordem NULLS LAST, codigo";

        if ($limit !== NULL)
        {
            $sql .= " LIMIT " . (int) $limit . " OFFSET " . (int) $offset;
        }

        return $this->db->query($sql, array((int) $modulo))->result_array();
    }

    public function total_programas($modulo)
    {
        return (int) $this->_valor(
            "SELECT count(*) FROM " . T_BASICO_PROGRAMAS . " WHERE modulo_codigo = ?",
            array((int) $modulo)
        );
    }

    public function proximo_codigo_programa($modulo)
    {
        return (int) $this->_valor(
            "SELECT COALESCE(MAX(codigo), 0) + 1 FROM " . T_BASICO_PROGRAMAS . "
              WHERE modulo_codigo = ?",
            array((int) $modulo)
        );
    }

    public function programa($modulo, $codigo)
    {
        return $this->db->query(
            "SELECT codigo, nome, formulario, ordem, ativo
               FROM " . T_BASICO_PROGRAMAS . "
              WHERE modulo_codigo = ? AND codigo = ?",
            array((int) $modulo, (int) $codigo)
        )->row_array();
    }

    public function salvar_programa($modulo, $dados, $codigo = NULL)
    {
        $modulo = (int) $modulo;
        $nome   = $this->_nome($dados);

        if ($nome === NULL)
        {
            return $this->_erro('Informe o nome do programa.');
        }

        if (empty($this->modulo($modulo)))
        {
            return $this->_erro('Modulo nao encontrado.');
        }

        $ordem      = $this->_int(isset($dados['ordem']) ? $dados['ordem'] : NULL);
        $ativo      = ! empty($dados['ativo']);
        $formulario = $this->_texto(isset($dados['formulario']) ? $dados['formulario'] : NULL);

        if ($codigo === NULL)
        {
            $novo = isset($dados['codigo']) ? (int) $dados['codigo'] : 0;

            if ($novo <= 0)
            {
                return $this->_erro('Informe um codigo maior que zero.');
            }

            if ( ! empty($this->programa($modulo, $novo)))
            {
                return $this->_erro('Ja existe um programa com o codigo ' . $novo . ' neste modulo.');
            }

            if ($ordem === NULL) { $ordem = $novo; }

            $this->db->query(
                "INSERT INTO " . T_BASICO_PROGRAMAS . "
                    (modulo_codigo, codigo, nome, formulario, ordem, ativo)
                 VALUES (?, ?, ?, ?, ?, ?)",
                array($modulo, $novo, $nome, $formulario, $ordem, $ativo ? 'true' : 'false')
            );
        }
        else
        {
            $this->db->query(
                "UPDATE " . T_BASICO_PROGRAMAS . "
                    SET nome = ?, formulario = ?, ordem = ?, ativo = ?, updated_at = now()
                  WHERE modulo_codigo = ? AND codigo = ?",
                array($nome, $formulario, $ordem, $ativo ? 'true' : 'false', $modulo, (int) $codigo)
            );
        }

        return array('ok' => TRUE, 'erro' => NULL, 'mensagem' => 'Programa gravado.');
    }

    public function excluir_programa($modulo, $codigo)
    {
        $modulo = (int) $modulo;
        $codigo = (int) $codigo;

        if (empty($this->programa($modulo, $codigo)))
        {
            return $this->_erro('Programa nao encontrado.');
        }

        $this->db->query(
            "DELETE FROM " . T_BASICO_PROGRAMAS . " WHERE modulo_codigo = ? AND codigo = ?",
            array($modulo, $codigo)
        );

        return array('ok' => TRUE, 'erro' => NULL, 'mensagem' => 'Programa excluido.');
    }

    // ==================================================================
    // Auxiliares
    // ==================================================================

    private function _nome($dados)
    {
        $nome = isset($dados['nome']) ? trim((string) $dados['nome']) : '';

        if ($nome === '')
        {
            return NULL;
        }

        $nome = mb_strtoupper($nome, 'UTF-8');

        return mb_strlen($nome) > 120 ? mb_substr($nome, 0, 120) : $nome;
    }

    private function _texto($v)
    {
        if ($v === NULL)
        {
            return NULL;
        }

        $v = trim((string) $v);

        return $v === '' ? NULL : $v;
    }

    private function _int($v)
    {
        if ($v === NULL || $v === '' || (is_string($v) && trim($v) === ''))
        {
            return NULL;
        }

        return (int) $v;
    }

    private function _dec($v)
    {
        if ($v === NULL || $v === '' || (is_string($v) && trim($v) === ''))
        {
            return NULL;
        }

        if (is_string($v))
        {
            $v = str_replace(array('R$', ' ', "\xc2\xa0"), '', trim($v));

            if (strpos($v, ',') !== FALSE)
            {
                $v = str_replace('.', '', $v);
                $v = str_replace(',', '.', $v);
            }
        }

        return round((float) $v, 4);
    }

    private function _valor($sql, $params = array())
    {
        $row = $this->db->query($sql, $params)->row_array();

        return empty($row) ? 0 : reset($row);
    }

    private function _erro($mensagem)
    {
        return array('ok' => FALSE, 'erro' => $mensagem, 'mensagem' => $mensagem);
    }
}
