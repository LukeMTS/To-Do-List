#!/bin/bash

# Script para executar testes do projeto Todo
# Autor: Assistente
# Data: $(date)

echo "🚀 Iniciando execução dos testes do Todo App"
echo "=============================================="

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Função para exibir mensagens coloridas
print_message() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Verificar se o PHP está instalado
if ! command -v php &> /dev/null; then
    print_error "PHP não está instalado ou não está no PATH"
    exit 1
fi

# Verificar se o Composer está instalado
if ! command -v composer &> /dev/null; then
    print_error "Composer não está instalado ou não está no PATH"
    exit 1
fi

# Verificar se o PHPUnit está instalado
if ! php vendor/bin/phpunit --version &> /dev/null; then
    print_error "PHPUnit não está instalado. Execute: composer install"
    exit 1
fi

print_message "Verificando dependências..."
composer install --no-interaction --quiet

print_message "Limpando cache..."
php artisan cache:clear
php artisan config:clear

print_message "Executando migrações de teste..."
php artisan migrate:fresh --env=testing --quiet

echo ""
echo "🧪 Executando Testes Unitários"
echo "================================"
php vendor/bin/phpunit --testsuite=Unit --colors=always

if [ $? -eq 0 ]; then
    print_success "Testes unitários passaram!"
else
    print_error "Testes unitários falharam!"
    UNIT_FAILED=true
fi

echo ""
echo "🔍 Executando Testes de Feature"
echo "================================"
php vendor/bin/phpunit --testsuite=Feature --colors=always

if [ $? -eq 0 ]; then
    print_success "Testes de feature passaram!"
else
    print_error "Testes de feature falharam!"
    FEATURE_FAILED=true
fi

echo ""
echo "📊 Executando Todos os Testes"
echo "=============================="
php vendor/bin/phpunit --colors=always

if [ $? -eq 0 ]; then
    print_success "Todos os testes passaram! 🎉"
else
    print_error "Alguns testes falharam! ❌"
fi

echo ""
echo "📈 Relatório de Cobertura (se disponível)"
echo "=========================================="
if command -v phpdbg &> /dev/null; then
    print_message "Executando testes com cobertura de código..."
    phpdbg -qrr vendor/bin/phpunit --coverage-html coverage-report --coverage-text
    if [ -d "coverage-report" ]; then
        print_success "Relatório de cobertura gerado em: coverage-report/index.html"
    fi
else
    print_warning "phpdbg não encontrado. Instale para gerar relatórios de cobertura."
fi

echo ""
echo "🎯 Resumo dos Testes"
echo "===================="

if [ "$UNIT_FAILED" = true ] || [ "$FEATURE_FAILED" = true ]; then
    print_error "Alguns testes falharam. Verifique os resultados acima."
    exit 1
else
    print_success "Todos os testes executados com sucesso!"
    echo ""
    echo "📋 Testes criados:"
    echo "  ✅ TaskControllerTest - Testes de API CRUD"
    echo "  ✅ TaskServiceTest - Testes de lógica de negócio"
    echo "  ✅ TaskModelTest - Testes do modelo Eloquent"
    echo "  ✅ TaskIntegrationTest - Testes de integração"
    echo ""
    echo "🔧 Para executar testes específicos:"
    echo "  php vendor/bin/phpunit --filter TaskControllerTest"
    echo "  php vendor/bin/phpunit --filter TaskServiceTest"
    echo "  php vendor/bin/phpunit --filter TaskModelTest"
    echo "  php vendor/bin/phpunit --filter TaskIntegrationTest"
    echo ""
    echo "📊 Para executar com cobertura:"
    echo "  phpdbg -qrr vendor/bin/phpunit --coverage-html coverage-report"
fi 