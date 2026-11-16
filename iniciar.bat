@echo off
REM ============================================================================
REM  BIOSTECH - inicia o servidor de desenvolvimento e abre o navegador
REM  Duplo clique neste arquivo. Feche a janela (ou Ctrl+C) para parar.
REM ============================================================================
setlocal

cd /d "%~dp0"

set PHP=C:\php\php.exe
set PORTA=8090

if not exist "%PHP%" (
    echo [erro] PHP nao encontrado em %PHP%
    echo        Ajuste a variavel PHP neste arquivo.
    pause
    exit /b 1
)

echo ============================================================
echo  BIOSTECH - servidor de desenvolvimento
echo  URL:  http://127.0.0.1:%PORTA%
echo  Para parar: Ctrl+C ou feche esta janela
echo ============================================================
echo.

start "" "http://127.0.0.1:%PORTA%/login"

"%PHP%" -S 127.0.0.1:%PORTA% router.php

echo.
echo Servidor encerrado.
pause
