<#
.SYNOPSIS
    Runner centralizado de desarrollo para Proyecto Tlatoa (PowerShell / Windows).
.DESCRIPTION
    Punto de entrada unificado para chequeos de salud, linting y comandos comunes.
#>
param (
    [Parameter(Position = 0, Mandatory = $false)]
    [string]$Action = "help",

    [Parameter(Position = 1, Mandatory = $false)]
    [string]$Target = "",

    [Parameter(Position = 2, Mandatory = $false, ValueFromRemainingArguments = $true)]
    [string[]]$Extra = @()
)

$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$rootDir = Split-Path -Parent $scriptDir

switch ($Action) {
    "health" {
        Write-Host "🔍 Verificando salud del proyecto Tlatoa..." -ForegroundColor Cyan
        python -m py_compile "$rootDir\app\main.py"
        python "$rootDir\tools\audit_encoding.py"
        Write-Host "✅ Entorno saludable y verificado." -ForegroundColor Green
        exit 0
    }
    "lint" {
        Write-Host "🔍 Validando sintaxis Python..." -ForegroundColor Cyan
        if ($Target) {
            python -m py_compile $Target
        } else {
            python -m py_compile "$rootDir\app\main.py"
        }
        exit $LASTEXITCODE
    }
    "git:status" {
        git status
        exit $LASTEXITCODE
    }
    "python" {
        python $Target $Extra
        exit $LASTEXITCODE
    }
    "py" {
        python $Target $Extra
        exit $LASTEXITCODE
    }
    default {
        Write-Host "Comandos disponibles:" -ForegroundColor Yellow
        Write-Host "  health      - Verifica sintaxis y codificación del proyecto"
        Write-Host "  lint        - Valida sintaxis Python"
        Write-Host "  git:status  - Muestra el estado del repositorio Git"
        Write-Host "  python [args] - Ejecuta script de Python"
    }
}
