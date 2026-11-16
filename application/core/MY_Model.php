<?php
/**
 * MY_Model - base dos models
 */
class MY_Model extends CI_Model
{
    protected $tabela = '';
    protected $pk = 'id';

    public function __construct()
    {
        parent::__construct();
    }

    // ------------------------------------------------------------------

    public function por_id($id)
    {
        $sql = "SELECT * FROM {$this->tabela} WHERE {$this->pk} = ?";
        return $this->db->query($sql, array($id))->row_array();
    }

    public function todos($ordem = NULL)
    {
        $ordem = $ordem ? $ordem : $this->pk;
        $sql = "SELECT * FROM {$this->tabela} ORDER BY {$ordem}";
        return $this->db->query($sql)->result_array();
    }

    public function contar($where = '', $params = array())
    {
        $sql = "SELECT count(*) AS total FROM {$this->tabela} {$where}";
        $row = $this->db->query($sql, $params)->row_array();
        return (int) $row['total'];
    }

    public function inserir($dados)
    {
        $colunas = array_keys($dados);
        $marcas  = rtrim(str_repeat('?, ', count($colunas)), ', ');

        $sql = "INSERT INTO {$this->tabela} (" . implode(', ', $colunas) . ")
                VALUES ({$marcas})";

        $this->db->query($sql, $this->_params($dados));
        return TRUE;
    }

    public function atualizar($id, $dados)
    {
        $sets = array();
        foreach (array_keys($dados) as $coluna)
        {
            $sets[] = "$coluna = ?";
        }

        $params   = $this->_params($dados);
        $params[] = $id;

        $sql = "UPDATE {$this->tabela} SET " . implode(', ', $sets) . ", updated_at = now()
                 WHERE {$this->pk} = ?";

        $this->db->query($sql, $params);
        return TRUE;
    }

    /**
     * Normaliza os valores antes de enviar ao PostgreSQL.
     *
     * O PDO envia FALSE como string vazia, o que o PostgreSQL rejeita em
     * colunas boolean. Booleanos viram as literais 'true'/'false'.
     */
    protected function _params($dados)
    {
        $params = array();

        foreach ($dados as $valor)
        {
            if (is_bool($valor))
            {
                $params[] = $valor ? 'true' : 'false';
            }
            else
            {
                $params[] = $valor;
            }
        }

        return $params;
    }

    public function excluir($id)
    {
        $sql = "DELETE FROM {$this->tabela} WHERE {$this->pk} = ?";
        $this->db->query($sql, array($id));
        return TRUE;
    }

    /**
     * Proximo id manual (tabelas com chave herdada do Access).
     */
    public function proximo_id()
    {
        $row = $this->db->query("SELECT COALESCE(MAX({$this->pk}), 0) + 1 AS proximo FROM {$this->tabela}")->row_array();
        return (int) $row['proximo'];
    }
}
