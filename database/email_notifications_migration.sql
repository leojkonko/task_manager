-- Adicionar coluna para controlar se o lembrete foi enviado
ALTER TABLE tasks ADD COLUMN reminder_sent BOOLEAN DEFAULT FALSE AFTER due_date;

-- Criar índice para melhorar performance das consultas de notificação
CREATE INDEX idx_tasks_due_date_reminder ON tasks(due_date, reminder_sent, status);
CREATE INDEX idx_tasks_status_due_date ON tasks(status, due_date);