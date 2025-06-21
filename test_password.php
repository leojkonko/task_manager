<?php
// Script para gerar hash de senha para o usuário de teste
// Senha: password123

$password = 'password123';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Senha: " . $password . "\n";
echo "Hash: " . $hash . "\n";
echo "Verificação: " . (password_verify($password, $hash) ? 'OK' : 'FALHOU') . "\n";

// Verificar se o hash existente funciona
$existingHash = '$2y$10$EIXcHWjcb4QU4n8Q1qN8nOaVm0P9Wj7Q2k3L5m8P1z6A7b9C3d4E5f';
echo "Hash existente funciona: " . (password_verify($password, $existingHash) ? 'OK' : 'FALHOU') . "\n";