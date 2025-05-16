<?php

// Imprimir uma mensagem simples
echo "Verificando conexão com o banco de dados...\n";

// Criar uma conexão PDO
$host = 'localhost';
$dbname = 'laravel';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Conexão bem-sucedida!\n";
    
    // Verificar se as tabelas existem
    $tables = ['subjects', 'topics', 'questions', 'answers', 'study_plans', 'study_sessions'];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
        
        if ($stmt->rowCount() > 0) {
            echo "Tabela {$table} existe.\n";
            
            // Verificar a estrutura da tabela
            $stmt = $pdo->query("DESCRIBE {$table}");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            echo "  Colunas: " . implode(", ", $columns) . "\n";
        } else {
            echo "Tabela {$table} NÃO existe.\n";
        }
    }
    
} catch (PDOException $e) {
    echo "Erro de conexão: " . $e->getMessage() . "\n";
}

echo "Verificação concluída!";
