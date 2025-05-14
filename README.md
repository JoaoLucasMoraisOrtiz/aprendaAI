# aprendaAI
Sistema IA para ensinar estudantes

## Tutorial para iniciar o projeto Laravel

### 1. Instale o PHP e o Laravel

#### Para Linux:
```bash
/bash -c "$(curl -fsSL https://php.new/install/linux/8.4)"
```

#### Para Windows (executar como administrador):
```powershell
Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://php.new/install/windows/8.4'))
```

#### Se o PHP já estiver instalado:
```bash
composer global require laravel/installer
```

### 2. Configuração do ambiente

Copie o arquivo `.env.example` para `.env`:
```bash
cp .env.example .env
```
Edite o arquivo `.env` e configure:
- O nome do banco de dados
- O login e a senha do MySQL

### 3. Crie o banco de dados

No MySQL, crie um banco de dados com o nome definido no `.env`.

### 4. Instale as dependências do projeto

No diretório do projeto Laravel, execute:
```bash
composer install
```

### 5. Gere a chave da aplicação
```bash
php artisan key:generate
```

### 6. Rode as migrations
```bash
php artisan migrate:fresh
```

### 7. Inicie o servidor de desenvolvimento
```bash
php artisan serve
```
