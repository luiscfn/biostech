@echo off
REM ============================================================================
REM  BIOSTECH - para o servidor de desenvolvimento (porta 8090)
REM ============================================================================
setlocal
set PORTA=8090

echo Procurando processo na porta %PORTA%...
for /f "tokens=5" %%p in ('netstat -ano ^| findstr /r /c:":%PORTA% .*LISTENING"') do (
    echo Encerrando PID %%p
    taskkill /PID %%p /F >nul 2>&1
)

echo.
echo Servidor da porta %PORTA% encerrado (se estava ativo).
pause
