<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Helpers do BIOSTECH
 */

if ( ! function_exists('bd_bool'))
{
    /**
     * Converte valores booleanos vindos do PostgreSQL ('t'/'f') ou do Access.
     */
    function bd_bool($valor)
    {
        if (is_bool($valor))
        {
            return $valor;
        }

        return in_array(strtolower((string) $valor), array('t', 'true', '1', 's', 'sim'), TRUE);
    }
}

if ( ! function_exists('data_br'))
{
    function data_br($data, $vazio = '-')
    {
        if (empty($data) || $data === '0000-00-00')
        {
            return $vazio;
        }

        $ts = strtotime($data);
        return $ts ? date('d/m/Y', $ts) : $vazio;
    }
}

if ( ! function_exists('data_hora_br'))
{
    function data_hora_br($data, $vazio = '-')
    {
        if (empty($data))
        {
            return $vazio;
        }

        $ts = strtotime($data);
        return $ts ? date('d/m/Y H:i', $ts) : $vazio;
    }
}

if ( ! function_exists('moeda_br'))
{
    function moeda_br($valor)
    {
        return number_format((float) $valor, 2, ',', '.');
    }
}

if ( ! function_exists('numero_br'))
{
    function numero_br($valor, $decimais = 0)
    {
        return number_format((float) $valor, $decimais, ',', '.');
    }
}

if ( ! function_exists('idade_anos'))
{
    function idade_anos($data)
    {
        if (empty($data))
        {
            return NULL;
        }

        $ts = strtotime($data);
        return $ts ? (int) floor((time() - $ts) / (365.25 * 24 * 3600)) : NULL;
    }
}
