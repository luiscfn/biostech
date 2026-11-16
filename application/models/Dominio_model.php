<?php
/**
 * Dominio_model - tabela unica de dominios (status, situacao, garantia...).
 */
class Dominio_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->tabela = T_DOMINIOS;
        $this->pk     = 'codigo';
    }

    // ------------------------------------------------------------------

    public function opcoes($tipo)
    {
        $sql = "SELECT codigo, nome
                  FROM {$this->tabela}
                 WHERE tipo = ? AND ativo
                 ORDER BY ordem NULLS LAST, codigo";
        return $this->db->query($sql, array($tipo))->result_array();
    }

    /** @return array codigo => nome */
    public function mapa($tipo)
    {
        $mapa = array();
        foreach ($this->opcoes($tipo) as $o)
        {
            $mapa[(int) $o['codigo']] = $o['nome'];
        }
        return $mapa;
    }

    public function nome($tipo, $codigo)
    {
        if ($codigo === NULL || $codigo === '')
        {
            return NULL;
        }

        $row = $this->db->query(
            "SELECT nome FROM {$this->tabela} WHERE tipo = ? AND codigo = ?",
            array($tipo, (int) $codigo)
        )->row_array();

        return $row ? $row['nome'] : NULL;
    }

    // ------------------------------------------------------------------

    /** Grava/atualiza um dominio (usado pelas telas de cadastro). */
    public function salvar($tipo, $codigo, $nome, $ordem = NULL)
    {
        $sql = "INSERT INTO {$this->tabela} (tipo, codigo, nome, ordem)
                VALUES (?, ?, ?, ?)
                ON CONFLICT (tipo, codigo)
                DO UPDATE SET nome = EXCLUDED.nome, ordem = EXCLUDED.ordem";
        $this->db->query($sql, array($tipo, (int) $codigo, $nome, $ordem));
    }
}
