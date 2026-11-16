param(
    [string]$Mdb = "C:\SISBIOST\Dados\SISBIOSD_01.mdb",
    [string]$Config = "C:\desenvolvimento\PROJETOS_PHP\biostech\scripts\etl\tabelas.json",
    [string]$OutDir = "C:\desenvolvimento\PROJETOS_PHP\biostech\scripts\etl\dados"
)
Add-Type -AssemblyName System.Data

if (-not (Test-Path $OutDir)) { New-Item -ItemType Directory -Path $OutDir -Force | Out-Null }

$cfg = Get-Content $Config -Raw -Encoding UTF8 | ConvertFrom-Json
$conn = New-Object System.Data.OleDb.OleDbConnection("Provider=Microsoft.Jet.OLEDB.4.0;Data Source=$Mdb;Mode=Read")
$conn.Open()

foreach ($t in $cfg.tabelas) {
    $cols = @($t.colunas)
    $sql = "SELECT " + ($cols -join ", ") + " FROM [" + $t.tabela + "]"
    $cmd = $conn.CreateCommand()
    $cmd.CommandText = $sql
    $rd = $cmd.ExecuteReader()

    $list = New-Object System.Collections.ArrayList
    $i = 0
    while ($rd.Read()) {
        $obj = [ordered]@{}
        for ($c = 0; $c -lt $cols.Count; $c++) {
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
            $obj[$cols[$c]] = $val
        }
        [void]$list.Add([pscustomobject]$obj)
        $i++
    }
    $rd.Close()

    $file = Join-Path $OutDir ($t.tabela.Replace(' ', '_') + ".json")
    $json = ConvertTo-Json -InputObject $list -Depth 3
    [System.IO.File]::WriteAllText($file, $json, (New-Object System.Text.UTF8Encoding($false)))
    "{0,-28} {1,7} registros -> {2}" -f $t.tabela, $i, (Split-Path $file -Leaf)
}

$conn.Close()
