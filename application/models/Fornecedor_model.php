<?php
/**
 * Fornecedor_model - cadastro de fornecedores (origem Access: FORNECEDOR).
 *
 * O fornecedor pode ser copiado para a base de clientes (botao "Copiar para
 * cliente"), caso em que o registro de cliente fica ligado ao fornecedor
 * atraves da coluna biostech_clientes.fornecedor_codigo.
 */
class Fornecedor_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->tabela = T_FORNECEDORES;
        $this->pk     = 'codigo';
    }

    // ------------------------------------------------------------------
    // Listagem / pesquisa
    // ------------------------------------------------------------------

    private function _filtro($busca)
    {
        $busca = trim((string) $busca);

        if ($busca === '')
        {
            return array('', array());
        }

        $sem_acento = "translate(lower(%s), 'áàãâäéèêëíìîïóòõôöúùûüç', 'aaaaaeeeeiiiiooooouuuuc')";
        $termo      = '%' . mb_strtolower($busca, 'UTF-8') . '%';
        $digitos    = preg_replace('/\D+/', '', $busca);

        $onde   = array(
            sprintf($sem_acento, 'f.razao_social') . ' LIKE ?',
            sprintf($sem_acento, 'f.nome_fantasia') . ' LIKE ?',
            sprintf($sem_acento, 'f.cidade') . ' LIKE ?',
            'f.cnpj LIKE ?',
        );

        $params = array($termo, $termo, $termo, '%' . $busca . '%');

        if ($digitos !== '')
        {
            $onde[]   = "regexp_replace(coalesce(f.cnpj, ''), '[^0-9]', '', 'g') LIKE ?";
            $params[] = '%' . $digitos . '%';
        }

        return array('WHERE (' . implode(' OR ', $onde) . ')', $params);
    }

    // ------------------------------------------------------------------

    public function listar($busca = '', $limit = NULL, $offset = NULL, $ordem = 'f.razao_social')
    {
        list($where, $params) = $this->_filtro($busca);

        $sql = "SELECT f.codigo, f.razao_social, f.nome_fantasia, f.cidade, f.uf,
                       f.fone, f.contato, f.ativo,
                       c.id AS cliente_id
                  FROM " . T_FORNECEDORES . " f
                  LEFT JOIN " . T_CLIENTES . " c ON c.fornecedor_codigo = f.codigo
                  {$where}
                 ORDER BY {$ordem}";

        if ($limit !== NULL)
        {
            $sql .= ' LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
        }

        return $this->db->query($sql, $params)->result_array();
    }

    // ------------------------------------------------------------------

    public function total($busca = '')
    {
        list($where, $params) = $this->_filtro($busca);

        $row = $this->db->query(
            "SELECT count(*) AS total FROM " . T_FORNECEDORES . " f {$where}",
            $params
        )->row_array();

        return (int) $row['total'];
    }

    // ------------------------------------------------------------------

    public function por_codigo($codigo)
    {
        return $this->db->query(
            "SELECT * FROM " . T_FORNECEDORES . " WHERE codigo = ?",
            array((int) $codigo)
        )->row_array();
    }

    public function proximo_codigo()
    {
        $row = $this->db->query(
            "SELECT COALESCE(MAX(codigo), 0) + 1 AS proximo FROM " . T_FORNECEDORES
        )->row_array();

        return (int) $row['proximo'];
    }

    public function codigo_em_uso($codigo, $ignorar = 0)
    {
        $row = $this->db->query(
            "SELECT 1 FROM " . T_FORNECEDORES . " WHERE codigo = ? AND codigo <> ?",
            array((int) $codigo, (int) $ignorar)
        )->row_array();

        return ! empty($row);
    }

    // ------------------------------------------------------------------
    // Gravacao
    // ------------------------------------------------------------------

    private function _valores($dados)
    {
        return array(
            trim((string) $dados['razao_social']),
            $this->_texto($dados, 'nome_fantasia', 100),
            $this->_texto($dados, 'inscricao_estadual', 20),
            $this->_texto($dados, 'cnpj', 20),
            $this->_texto($dados, 'endereco', 60),
            $this->_texto($dados, 'numero', 10),
            $this->_texto($dados, 'complemento', 50),
            $this->_texto($dados, 'bairro', 50),
            $this->_texto($dados, 'cidade', 20),
            $this->_texto($dados, 'cep', 10),
            $this->_texto($dados, 'uf', 2),
            $this->_texto($dados, 'fone', 15),
            $this->_texto($dados, 'fax', 15),
            $this->_texto($dados, 'email', 50),
            $this->_texto($dados, 'contato', 25),
            isset($dados['ponto_referencia']) && trim((string) $dados['ponto_referencia']) !== ''
                ? trim((string) $dados['ponto_referencia'])
                : NULL,
            isset($dados['ativo']) && $dados['ativo'] ? 'true' : 'false',
        );
    }

    private function _texto($dados, $campo, $max = NULL)
    {
        $valor = isset($dados[$campo]) ? trim((string) $dados[$campo]) : '';

        if ($valor === '')
        {
            return NULL;
        }

        return ($max !== NULL) ? mb_substr($valor, 0, $max, 'UTF-8') : $valor;
    }

    // ------------------------------------------------------------------

    public function criar($codigo, $dados)
    {
        $sql = "INSERT INTO " . T_FORNECEDORES . "
                    (codigo, razao_social, nome_fantasia, inscricao_estadual, cnpj,
                     endereco, numero, complemento, bairro, cidade, cep, uf,
                     fone, fax, email, contato, ponto_referencia, ativo)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $params = array_merge(array((int) $codigo), $this->_valores($dados));

        $this->db->query($sql, $params);
        return (int) $codigo;
    }

    // ------------------------------------------------------------------

    public function alterar($codigo, $dados)
    {
        $sql = "UPDATE " . T_FORNECEDORES . "
                   SET razao_social = ?, nome_fantasia = ?, inscricao_estadual = ?, cnpj = ?,
                       endereco = ?, numero = ?, complemento = ?, bairro = ?, cidade = ?,
                       cep = ?, uf = ?, fone = ?, fax = ?, email = ?, contato = ?,
                       ponto_referencia = ?, ativo = ?, updated_at = now()
                 WHERE codigo = ?";

        $params   = $this->_valores($dados);
        $params[] = (int) $codigo;

        $this->db->query($sql, $params);
        return TRUE;
    }

    // ------------------------------------------------------------------
    // Prazos oferecidos (origem: FORNECEDOR_PRAZOS)
    // ------------------------------------------------------------------

    public function prazos($codigo)
    {
        return $this->db->query(
            "SELECT * FROM " . T_FORNECEDOR_PRAZOS . "
              WHERE fornecedor_codigo = ?
              ORDER BY codigo",
            array((int) $codigo)
        )->result_array();
    }

    public function prazo($codigo, $prazo)
    {
        return $this->db->query(
            "SELECT * FROM " . T_FORNECEDOR_PRAZOS . "
              WHERE fornecedor_codigo = ? AND codigo = ?",
            array((int) $codigo, (int) $prazo)
        )->row_array();
    }

    public function proximo_prazo($codigo)
    {
        $row = $this->db->query(
            "SELECT COALESCE(MAX(codigo), 0) + 1 AS proximo
               FROM " . T_FORNECEDOR_PRAZOS . " WHERE fornecedor_codigo = ?",
            array((int) $codigo)
        )->row_array();

        return (int) $row['proximo'];
    }

    public function salvar_prazo($codigo, $dados)
    {
        $prazo = isset($dados['prazo_codigo']) && (int) $dados['prazo_codigo'] > 0
            ? (int) $dados['prazo_codigo']
            : $this->proximo_prazo($codigo);

        $this->db->query(
            "INSERT INTO " . T_FORNECEDOR_PRAZOS . "
                (fornecedor_codigo, codigo, forma_pagamento, prazo)
             VALUES (?, ?, ?, ?)
             ON CONFLICT (fornecedor_codigo, codigo) DO UPDATE SET
                forma_pagamento = EXCLUDED.forma_pagamento,
                prazo = EXCLUDED.prazo",
            array(
                (int) $codigo,
                $prazo,
                $this->_texto($dados, 'forma_pagamento', 40),
                $this->_texto($dados, 'prazo', 60),
            )
        );

        return $prazo;
    }

    public function excluir_prazo($codigo, $prazo)
    {
        $this->db->query(
            "DELETE FROM " . T_FORNECEDOR_PRAZOS . " WHERE fornecedor_codigo = ? AND codigo = ?",
            array((int) $codigo, (int) $prazo)
        );

        return TRUE;
    }

    // ------------------------------------------------------------------
    // Produtos fornecidos (origem: PROD_FORNEC_01)
    // ------------------------------------------------------------------

    public function produtos($codigo)
    {
        return $this->db->query(
            "SELECT pf.*, p.descricao, p.unidade, p.valor_compra, p.valor_venda,
                    COALESCE(s.quantidade, 0) AS saldo
               FROM " . T_PRODUTO_FORNECEDOR . " pf
               JOIN " . T_PRODUTOS . " p ON p.id = pf.produto_id
               LEFT JOIN " . T_ESTOQUE_SALDOS . " s ON s.produto_id = p.id
              WHERE pf.fornecedor_codigo = ?
              ORDER BY p.descricao",
            array((int) $codigo)
        )->result_array();
    }

    public function vincular_produto($codigo, $dados)
    {
        $produto_id = isset($dados['produto_id']) ? (int) $dados['produto_id'] : 0;

        if ((int) $codigo <= 0 || $produto_id <= 0)
        {
            return array('ok' => FALSE, 'erro' => 'Selecione o produto.');
        }

        $existe = $this->db->query(
            "SELECT 1 FROM " . T_PRODUTOS . " WHERE id = ?",
            array($produto_id)
        )->row_array();

        if (empty($existe))
        {
            return array('ok' => FALSE, 'erro' => 'Produto nao encontrado.');
        }

        $codigo_fornecedor = $this->_texto($dados, 'codigo_fornecedor', 25);

        if ($codigo_fornecedor === NULL)
        {
            $codigo_fornecedor = (string) $produto_id;
        }

        $preco = isset($dados['preco_venda']) ? trim((string) $dados['preco_venda']) : '';
        $preco = ($preco === '') ? NULL : (float) str_replace(',', '.', $preco);

        $this->db->query(
            "INSERT INTO " . T_PRODUTO_FORNECEDOR . "
                (fornecedor_codigo, produto_id, codigo_fornecedor, nome_fornecedor, preco_venda)
             VALUES (?, ?, ?, ?, ?)
             ON CONFLICT (fornecedor_codigo, produto_id) DO UPDATE SET
                codigo_fornecedor = EXCLUDED.codigo_fornecedor,
                nome_fornecedor = EXCLUDED.nome_fornecedor,
                preco_venda = EXCLUDED.preco_venda",
            array(
                (int) $codigo,
                $produto_id,
                $codigo_fornecedor,
                $this->_texto($dados, 'nome_fornecedor', 80),
                $preco,
            )
        );

        return array('ok' => TRUE, 'erro' => NULL);
    }

    public function desvincular_produto($codigo, $produto_id)
    {
        $this->db->query(
            "DELETE FROM " . T_PRODUTO_FORNECEDOR . " WHERE fornecedor_codigo = ? AND produto_id = ?",
            array((int) $codigo, (int) $produto_id)
        );

        return TRUE;
    }

    // ------------------------------------------------------------------
    // Cliente vinculado / exclusao
    // ------------------------------------------------------------------

    public function cliente_vinculado($codigo)
    {
        return $this->db->query(
            "SELECT id, nome, ativo FROM " . T_CLIENTES . "
              WHERE fornecedor_codigo = ?
              ORDER BY id LIMIT 1",
            array((int) $codigo)
        )->row_array();
    }

    /**
     * Documentos que usam o fornecedor e impedem a exclusao.
     */
    public function uso($codigo)
    {
        $codigo = (int) $codigo;

        return $this->db->query(
            "SELECT
                (SELECT count(*) FROM " . T_COMPRAS_PEDIDOS . " WHERE fornecedor_codigo = ?) AS pedidos,
                (SELECT count(*) FROM " . T_COMPRAS_COTACAO_ITENS . "
                  WHERE fornecedor_1 = ? OR fornecedor_2 = ? OR fornecedor_3 = ?
                     OR fornecedor_escolhido = ?) AS cotacoes,
                (SELECT count(*) FROM " . T_CONTAS_PAGAR . " WHERE fornecedor_codigo = ?) AS contas,
                (SELECT count(*) FROM " . T_ORCAMENTO_DIMENSIONAMENTO_COTACOES . "
                  WHERE fornecedor_codigo = ?) AS dimensionamentos",
            array($codigo, $codigo, $codigo, $codigo, $codigo, $codigo, $codigo)
        )->row_array();
    }

    /**
     * Exclui o fornecedor e seus vinculos internos (prazos e produtos).
     * O cliente copiado permanece, apenas perde o vinculo.
     */
    public function excluir_fornecedor($codigo)
    {
        $codigo = (int) $codigo;

        $this->db->trans_start();

        $this->db->query(
            "UPDATE " . T_CLIENTES . " SET fornecedor_codigo = NULL, updated_at = now()
              WHERE fornecedor_codigo = ?",
            array($codigo)
        );

        $this->db->query(
            "DELETE FROM " . T_FORNECEDOR_PRAZOS . " WHERE fornecedor_codigo = ?",
            array($codigo)
        );

        $this->db->query(
            "DELETE FROM " . T_PRODUTO_FORNECEDOR . " WHERE fornecedor_codigo = ?",
            array($codigo)
        );

        $this->db->query(
            "DELETE FROM " . T_FORNECEDORES . " WHERE codigo = ?",
            array($codigo)
        );

        $this->db->trans_complete();

        return $this->db->trans_status();
    }
}
