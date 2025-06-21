<?php

declare(strict_types=1);

namespace TaskManager\Helper;

/**
 * Helper for generating user-friendly messages
 */
class MessageHelper
{
    /**
     * Friendly success messages
     */
    public static function getSuccessMessage(string $action, string $entity = 'task'): string
    {
        $messages = [
            'create' => "🎉 {$entity} created successfully! You can view the details below.",
            'update' => "✅ {$entity} updated perfectly! Your changes have been saved.",
            'delete' => "🗑️ {$entity} removed successfully! It has been permanently deleted.",
            'complete' => "🏆 Congratulations! The {$entity} has been marked as completed.",
            'start' => "🚀 Great! The {$entity} is now in progress.",
            'duplicate' => "📋 {$entity} duplicated successfully! A new {$entity} has been created based on the original.",
            'save' => "💾 Perfect! Your changes have been saved successfully."
        ];

        return $messages[$action] ?? "✅ Operation completed successfully!";
    }

    /**
     * Friendly error messages
     */
    public static function getErrorMessage(string $type, string $context = ''): string
    {
        $messages = [
            'not_found' => "😕 Oops! The task you're looking for was not found.",
            'validation_error' => "⚠️ Please check the information provided. Some fields need attention:",
            'weekday_only' => "📅 Sorry! Tasks can only be created during weekdays (Monday to Friday).",
            'pending_only_update' => "🔒 This task cannot be edited because its current status doesn't allow modifications. Only tasks with 'Pending' status can be edited.\n\n💡 Tip: Completed, cancelled or in-progress tasks are protected against changes to maintain data integrity.",
            'pending_only_delete' => "🔒 This task cannot be deleted because its current status doesn't allow removal. Only tasks with 'Pending' status can be deleted.\n\n💡 Reason: Tasks that are in progress, completed or cancelled contain important project history information.",
            'too_recent_delete' => "⏰ This task is too recent to be deleted. For security reasons, please wait a few days before trying again.\n\n🛡️ This rule prevents accidental deletion of newly created tasks.",
            'status_protection_info' => "🛡️ Status Protection: This operation is only allowed for tasks with 'Pending' status. Tasks with other statuses are protected to preserve project history.",
            'general_error' => "😓 Something didn't go as expected. Please try again in a few moments.",
            'form_invalid' => "📝 Please check the form information and try again."
        ];

        $message = $messages[$type] ?? $messages['general_error'];

        if ($context) {
            $message .= "\n\n" . $context;
        }

        return $message;
    }

    /**
     * Information messages
     */
    public static function getInfoMessage(string $type): string
    {
        $messages = [
            'empty_list' => "📋 No tasks found. How about creating your first task?",
            'loading' => "⏳ Loading your tasks...",
            'saving' => "💾 Saving your changes...",
            'processing' => "⚙️ Processing your request..."
        ];

        return $messages[$type] ?? "ℹ️ Information not available.";
    }

    /**
     * Confirmation messages
     */
    public static function getConfirmationMessage(string $action, string $itemName = ''): string
    {
        $messages = [
            'delete' => "🗑️ Are you sure you want to delete" . ($itemName ? " '{$itemName}'" : " this task") . "?\n\nThis action cannot be undone.",
            'complete' => "🏆 Do you want to mark" . ($itemName ? " '{$itemName}'" : " this task") . " as completed?",
            'cancel' => "❌ Are you sure you want to cancel? All unsaved changes will be lost."
        ];

        return $messages[$action] ?? "❓ Do you want to continue with this action?";
    }

    /**
     * Generate CSS class based on message type for styling
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
     * Generate icon based on message type
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
