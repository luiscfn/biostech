<?php
/**
 * Numeracao - sequenciais das OS, orcamentos, titulos e recibos.
 *
 * Substitui Contador()/TB_DATA, ULTORC e ULTCR do Access.
 * O incremento e atomico (INSERT ... ON CONFLICT ... RETURNING), eliminando
 * a duplicidade de numeros que o DLookup + UPDATE do Access permitia.
 */
class Numeracao
{
    private $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
    }

    // ------------------------------------------------------------------

    /**
     * Proximo sequencial de um tipo/competencia.
     * $competencia: 'yyyy-mm' para OS, '' para sequenciais globais.
     */
    public function proximo($tipo, $competencia = '')
    {
        // CI 2.1.2 devolve TRUE (e nao um result) para comandos de escrita,
        // mesmo com RETURNING. Usamos pg_query_params para ler o retorno.
        $sql = "INSERT INTO " . T_NUMERACAO . " (tipo, competencia, ultimo)
                VALUES ($1, $2, 1)
                ON CONFLICT (tipo, competencia)
                DO UPDATE SET ultimo = biostech_numeracao.ultimo + 1,
                              updated_at = now()
                RETURNING ultimo";

        $res = pg_query_params($this->CI->db->conn_id, $sql, array($tipo, $competencia));

        if ($res === FALSE)
        {
            log_message('error', 'Numeracao: ' . pg_last_error($this->CI->db->conn_id));
            return FALSE;
        }

        $row = pg_fetch_assoc($res);

        return $row ? (int) $row['ultimo'] : FALSE;
    }

    // ------------------------------------------------------------------

    /**
     * Numero da OS/Chamado: yyyy + mm + 4 digitos, na competencia da data.
     */
    public function numero_os($data = NULL)
    {
        $data = $data ? $data : date('Y-m-d');
        $ts   = strtotime($data);

        if ($ts === FALSE)
        {
            $ts = time();
        }

        $seq = $this->proximo('OS', date('Y-m', $ts));

        return date('Y', $ts) . date('m', $ts) . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    // ------------------------------------------------------------------

    /**
     * Sequenciais globais: ORC, CR, RECIBO.
     */
    public function numero($tipo)
    {
        return $this->proximo(strtoupper($tipo), '');
    }
}
