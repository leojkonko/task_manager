<?php

declare(strict_types=1);

namespace TaskManager\Helper;

/**
 * Helper para gerar mensagens amigáveis ao usuário
 */
class MessageHelper
{
    /**
     * Mensagens de sucesso amigáveis
     */
    public static function getSuccessMessage(string $action, string $entity = 'tarefa'): string
    {
        $messages = [
            'create' => "🎉 {$entity} criada com sucesso! Você pode visualizar os detalhes abaixo.",
            'update' => "✅ {$entity} atualizada com perfeição! Suas alterações foram salvas.",
            'delete' => "🗑️ {$entity} removida com sucesso! Ela foi excluída permanentemente.",
            'complete' => "🏆 Parabéns! A {$entity} foi marcada como concluída.",
            'start' => "🚀 Ótimo! A {$entity} agora está em andamento.",
            'duplicate' => "📋 {$entity} duplicada com sucesso! Uma nova {$entity} foi criada com base na original.",
            'save' => "💾 Perfeito! Suas alterações foram salvas com sucesso."
        ];

        return $messages[$action] ?? "✅ Operação realizada com sucesso!";
    }

    /**
     * Mensagens de erro amigáveis
     */
    public static function getErrorMessage(string $type, string $context = ''): string
    {
        $messages = [
            'not_found' => "😕 Ops! A tarefa que você está procurando não foi encontrada.",
            'validation_error' => "⚠️ Verifique os dados informados. Alguns campos precisam de atenção:",
            'weekday_only' => "📅 Que pena! As tarefas só podem ser criadas durante os dias úteis (segunda a sexta-feira).",
            'pending_only_update' => "🔒 Esta tarefa não pode ser editada porque seu status atual não permite modificações. Apenas tarefas com status 'Pendente' podem ser editadas.\n\n💡 Dica: Tarefas concluídas, canceladas ou em andamento são protegidas contra alterações para manter a integridade dos dados.",
            'pending_only_delete' => "🔒 Esta tarefa não pode ser excluída porque seu status atual não permite a remoção. Apenas tarefas com status 'Pendente' podem ser excluídas.\n\n💡 Motivo: Tarefas que já estão em andamento, concluídas ou canceladas contêm informações importantes do histórico do projeto.",
            'too_recent_delete' => "⏰ Esta tarefa é muito recente para ser excluída. Por questões de segurança, aguarde alguns dias antes de tentar novamente.\n\n🛡️ Esta regra previne exclusões acidentais de tarefas recém-criadas.",
            'status_protection_info' => "🛡️ Proteção de Status: Esta operação só é permitida para tarefas com status 'Pendente'. Tarefas com outros status são protegidas para preservar o histórico do projeto.",
            'general_error' => "😓 Algo não saiu como esperado. Tente novamente em alguns instantes.",
            'form_invalid' => "📝 Por favor, verifique as informações do formulário e tente novamente."
        ];

        $message = $messages[$type] ?? $messages['general_error'];

        if ($context) {
            $message .= "\n\n" . $context;
        }

        return $message;
    }

    /**
     * Mensagens de informação
     */
    public static function getInfoMessage(string $type): string
    {
        $messages = [
            'empty_list' => "📋 Nenhuma tarefa encontrada. Que tal criar sua primeira tarefa?",
            'loading' => "⏳ Carregando suas tarefas...",
            'saving' => "💾 Salvando suas alterações...",
            'processing' => "⚙️ Processando sua solicitação..."
        ];

        return $messages[$type] ?? "ℹ️ Informação não disponível.";
    }

    /**
     * Mensagens de confirmação
     */
    public static function getConfirmationMessage(string $action, string $itemName = ''): string
    {
        $messages = [
            'delete' => "🗑️ Tem certeza que deseja excluir" . ($itemName ? " '{$itemName}'" : " esta tarefa") . "?\n\nEsta ação não pode ser desfeita.",
            'complete' => "🏆 Deseja marcar" . ($itemName ? " '{$itemName}'" : " esta tarefa") . " como concluída?",
            'cancel' => "❌ Tem certeza que deseja cancelar? Todas as alterações não salvas serão perdidas."
        ];

        return $messages[$action] ?? "❓ Deseja continuar com esta ação?";
    }

    /**
     * Gera classe CSS baseada no tipo de mensagem para styling
     */
    public static function getMessageClass(string $type): string
    {
        $classes = [
            'success' => 'alert-success',
            'error' => 'alert-danger',
            'warning' => 'alert-warning',
            'info' => 'alert-info'
        ];

        return $classes[$type] ?? 'alert-info';
    }

    /**
     * Gera ícone baseado no tipo de mensagem
     */
    public static function getMessageIcon(string $type): string
    {
        $icons = [
            'success' => 'fas fa-check-circle',
            'error' => 'fas fa-exclamation-triangle',
            'warning' => 'fas fa-exclamation-circle',
            'info' => 'fas fa-info-circle'
        ];

        return $icons[$type] ?? 'fas fa-info-circle';
    }
}
