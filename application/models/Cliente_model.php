<?php
class Cliente_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->tabela = T_CLIENTES;
        $this->pk     = 'id';
    }

    // ------------------------------------------------------------------

    private function _filtro($busca)
    {
        if ($busca === '')
        {
            return array('', array());
        }

        $sem_acento = "translate(lower(%s), 'áàãâäéèêëíìîïóòõôöúùûüç', 'aaaaaeeeeiiiiooooouuuuc')";

        $campos = array('nome', 'fantasia', 'cpf_cnpj', 'cidade', 'bairro', 'email');
        $onde   = array();
        $params = array();

        foreach ($campos as $campo)
        {
            $onde[]   = sprintf($sem_acento, $campo) . " LIKE ?";
            $params[] = '%' . strtolower($busca) . '%';
        }

        return array('WHERE (' . implode(' OR ', $onde) . ')', $params);
    }

    // ------------------------------------------------------------------

    public function listar($busca = '', $limit = NULL, $offset = NULL, $ordem = 'nome')
    {
        list($where, $params) = $this->_filtro($busca);

        $sql = "SELECT id, nome, fantasia, cpf_cnpj, cidade, uf, telefone, celular, email, ativo
                  FROM {$this->tabela}
                  {$where}
                 ORDER BY {$ordem}";

        if ($limit !== NULL)
        {
            $sql .= " LIMIT " . (int) $limit . " OFFSET " . (int) $offset;
        }

        return $this->db->query($sql, $params)->result_array();
    }

    public function total($busca = '')
    {
        list($where, $params) = $this->_filtro($busca);

        $row = $this->db->query("SELECT count(*) AS total FROM {$this->tabela} {$where}", $params)->row_array();
        return (int) $row['total'];
    }

    // ------------------------------------------------------------------

    public function criar($dados)
    {
        $id = $this->proximo_id();

        $sql = "INSERT INTO {$this->tabela}
                    (id, nome, fantasia, tipo, cpf_cnpj, ie, rg, endereco, numero, complemento,
                     bairro, cidade, uf, cep, telefone, celular, fax, contato, email, observacao, ativo)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $this->db->query($sql, array(
            $id,
            $dados['nome'],
            $dados['fantasia'],
            $dados['tipo'],
            $dados['cpf_cnpj'],
            $dados['ie'],
            $dados['rg'],
            $dados['endereco'],
            $dados['numero'],
            $dados['complemento'],
            $dados['bairro'],
            $dados['cidade'],
            $dados['uf'],
            $dados['cep'],
            $dados['telefone'],
            $dados['celular'],
            $dados['fax'],
            $dados['contato'],
            $dados['email'],
            $dados['observacao'],
            $dados['ativo'] ? 'true' : 'false',
        ));

        return $id;
    }

    public function alterar($id, $dados)
    {
        $sql = "UPDATE {$this->tabela}
                   SET nome = ?, fantasia = ?, tipo = ?, cpf_cnpj = ?, ie = ?, rg = ?,
                       endereco = ?, numero = ?, complemento = ?, bairro = ?, cidade = ?,
                       uf = ?, cep = ?, telefone = ?, celular = ?, fax = ?, contato = ?,
                       email = ?, observacao = ?, ativo = ?, updated_at = now()
                 WHERE id = ?";

        $this->db->query($sql, array(
            $dados['nome'],
            $dados['fantasia'],
            $dados['tipo'],
            $dados['cpf_cnpj'],
            $dados['ie'],
            $dados['rg'],
            $dados['endereco'],
            $dados['numero'],
            $dados['complemento'],
            $dados['bairro'],
            $dados['cidade'],
            $dados['uf'],
            $dados['cep'],
            $dados['telefone'],
            $dados['celular'],
            $dados['fax'],
            $dados['contato'],
            $dados['email'],
            $dados['observacao'],
            $dados['ativo'] ? 'true' : 'false',
            (int) $id,
        ));
    }

    public function stats()
    {
        $row = $this->db->query(
            "SELECT count(*) AS total,
                    count(*) FILTER (WHERE ativo) AS ativos,
                    count(*) FILTER (WHERE NOT ativo) AS inativos
               FROM {$this->tabela}"
        )->row_array();

        return $row;
    }
}
