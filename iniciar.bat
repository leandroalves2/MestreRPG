@echo off
chcp 65001 >nul
cd /d "%~dp0"

set PHP_EXE=php
where php >nul 2>nul
if errorlevel 1 (
    if exist "C:\xampp\php\php.exe" (
        set PHP_EXE=C:\xampp\php\php.exe
    ) else if exist "C:\laragon\bin\php\php.exe" (
        set PHP_EXE=C:\laragon\bin\php\php.exe
    ) else (
        echo PHP nao encontrado. Instale o PHP ou ajuste a variavel PHP_EXE neste arquivo.
        pause
        exit /b 1
    )
)

echo Grimorio rodando em http://localhost:8000
echo Feche esta janela para encerrar.
start "" http://localhost:8000
"%PHP_EXE%" -d upload_max_filesize=1024M -d post_max_size=2048M -d max_file_uploads=50 -d memory_limit=512M -S localhost:8000 -t public
pause
