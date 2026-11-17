param(
    [string]$Mdb = "C:\SISBIOST\Dados\SISBIOSD_01.mdb",
    [string]$Config = "C:\desenvolvimento\PROJETOS_PHP\biostech\scripts\etl\tabelas-basicas.json",
    [string]$OutDir = "C:\desenvolvimento\PROJETOS_PHP\biostech\scripts\etl\dados"
)

# Executar com o PowerShell de 32 bits (driver Jet 4.0):
#   C:\Windows\SysWOW64\WindowsPowerShell\v1.0\powershell.exe -File export-tabelas-basicas.ps1
#
# Exporta as tabelas de apoio do modulo Tabelas Basicas.
# Aceita duas formas de configuracao:
#   { "tabela": "...", "colunas": [...] }  -> SELECT colunas FROM tabela
#   { "sql": "SELECT ..." }                -> consulta livre (ex.: DISTINCT)

Add-Type -AssemblyName System.Data

if (-not (Test-Path $OutDir)) { New-Item -ItemType Directory -Path $OutDir -Force | Out-Null }

$cfg = Get-Content $Config -Raw -Encoding UTF8 | ConvertFrom-Json
$conn = New-Object System.Data.OleDb.OleDbConnection("Provider=Microsoft.Jet.OLEDB.4.0;Data Source=$Mdb;Mode=Read")
$conn.Open()

foreach ($t in $cfg.tabelas) {
    if ($t.sql) {
        $sql = [string]$t.sql
        $arquivo = [string]$t.arquivo
    }
    else {
        $cols = @($t.colunas)
        $sql = "SELECT " + ($cols -join ", ") + " FROM [" + $t.tabela + "]"
        $arquivo = [string]$t.tabela
    }

    $cmd = $conn.CreateCommand()
    $cmd.CommandText = $sql
    $rd = $cmd.ExecuteReader()

    $list = New-Object System.Collections.ArrayList
    $i = 0
    while ($rd.Read()) {
        $obj = [ordered]@{}
        for ($c = 0; $c -lt $rd.FieldCount; $c++) {
            $nome = $rd.GetName($c)
            $val = $null
            if (-not $rd.IsDBNull($c)) {
                $v = $rd.GetValue($c)
                if ($v -is [datetime]) { $val = $v.ToString('yyyy-MM-dd') }
                elseif ($v -is [bool]) { $val = $v }
                elseif ($v -is [double] -or $v -is [decimal] -or $v -is [single]) { $val = [double]$v }
                elseif ($v -is [int] -or $v -is [long] -or $v -is [int16] -or $v -is [byte]) { $val = [long]$v }
                else {
                    $s = [string]$v
                    if ($s.Length -eq 0) { $val = $null } else { $val = $s.TrimEnd() }
                }
            }
            $obj[$nome] = $val
        }
        [void]$list.Add([pscustomobject]$obj)
        $i++
    }
    $rd.Close()

    $file = Join-Path $OutDir ($arquivo.Replace(' ', '_') + ".json")
    $json = ConvertTo-Json -InputObject $list -Depth 3
    [System.IO.File]::WriteAllText($file, $json, (New-Object System.Text.UTF8Encoding($false)))
    "{0,-40} {1,6} registros -> {2}" -f $arquivo, $i, (Split-Path $file -Leaf)
}

$conn.Close()
