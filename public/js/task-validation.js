/**
 * Validações do lado cliente para formulários de tarefas
 */
class TaskFormValidator {
  constructor(formElement) {
    this.form = formElement;
    this.errors = {};
    this.init();
  }

  init() {
    this.attachEventListeners();
    this.setupRealTimeValidation();
  }

  attachEventListeners() {
    // Validação em tempo real durante digitação
    const titleField = this.form.querySelector("#title");
    const descriptionField = this.form.querySelector("#description");
    const dueDateField = this.form.querySelector("#due_date");

    if (titleField) {
      titleField.addEventListener("blur", () => this.validateTitle());
      titleField.addEventListener("input", () => this.clearError("title"));
    }

    if (descriptionField) {
      descriptionField.addEventListener("blur", () =>
        this.validateDescription()
      );
      descriptionField.addEventListener("input", () =>
        this.clearError("description")
      );
    }

    if (dueDateField) {
      dueDateField.addEventListener("blur", () => this.validateDueDate());
      dueDateField.addEventListener("change", () => this.validateDueDate());
    }

    // Validação antes do envio
    this.form.addEventListener("submit", (e) => {
      if (!this.validateForm()) {
        e.preventDefault();
        this.showValidationSummary();
      }
    });
  }

  setupRealTimeValidation() {
    // Contador de caracteres para título
    const titleField = this.form.querySelector("#title");
    if (titleField) {
      const counter = document.createElement("small");
      counter.className = "form-text text-muted";
      counter.id = "title-counter";
      titleField.parentNode.appendChild(counter);

      titleField.addEventListener("input", () => {
        const length = titleField.value.length;
        counter.textContent = `${length}/200 caracteres`;

        if (length > 200) {
          counter.className = "form-text text-danger";
        } else if (length > 180) {
          counter.className = "form-text text-warning";
        } else {
          counter.className = "form-text text-muted";
        }
      });
    }

    // Contador de caracteres para descrição
    const descriptionField = this.form.querySelector("#description");
    if (descriptionField) {
      const counter = document.createElement("small");
      counter.className = "form-text text-muted";
      counter.id = "description-counter";
      descriptionField.parentNode.appendChild(counter);

      descriptionField.addEventListener("input", () => {
        const length = descriptionField.value.length;
        counter.textContent = `${length}/1000 caracteres`;

        if (length > 1000) {
          counter.className = "form-text text-danger";
        } else if (length > 900) {
          counter.className = "form-text text-warning";
        } else {
          counter.className = "form-text text-muted";
        }
      });
    }
  }

  validateTitle() {
    const titleField = this.form.querySelector("#title");
    const title = titleField.value.trim();

    this.clearError("title");

    if (!title) {
      this.addError("title", "O título da tarefa é obrigatório");
      return false;
    }

    if (title.length < 3) {
      this.addError("title", "O título deve ter pelo menos 3 caracteres");
      return false;
    }

    if (title.length > 200) {
      this.addError("title", "O título não pode ter mais de 200 caracteres");
      return false;
    }

    // Validar caracteres especiais
    const pattern =
      /^[a-zA-Z0-9\s\-_.,!?áéíóúàèìòùâêîôûãõçÁÉÍÓÚÀÈÌÒÙÂÊÎÔÛÃÕÇ]+$/;
    if (!pattern.test(title)) {
      this.addError("title", "O título contém caracteres inválidos");
      return false;
    }

    return true;
  }

  validateDescription() {
    const descriptionField = this.form.querySelector("#description");
    const description = descriptionField.value;

    this.clearError("description");

    if (description && description.length > 1000) {
      this.addError(
        "description",
        "A descrição não pode ter mais de 1000 caracteres"
      );
      return false;
    }

    return true;
  }

  validateDueDate() {
    const dueDateField = this.form.querySelector("#due_date");
    const dueDate = dueDateField.value;

    this.clearError("due_date");

    if (dueDate) {
      const selectedDate = new Date(dueDate);
      const now = new Date();

      if (isNaN(selectedDate.getTime())) {
        this.addError("due_date", "Data de vencimento inválida");
        return false;
      }

      // Apenas verificar se está no passado para criação de novas tarefas
      if (selectedDate < now && !this.form.querySelector("#id").value) {
        this.addError(
          "due_date",
          "A data de vencimento não pode ser no passado"
        );
        return false;
      }
    }

    return true;
  }

  validateForm() {
    let isValid = true;

    if (!this.validateTitle()) isValid = false;
    if (!this.validateDescription()) isValid = false;
    if (!this.validateDueDate()) isValid = false;

    return isValid;
  }

  addError(field, message) {
    if (!this.errors[field]) {
      this.errors[field] = [];
    }
    this.errors[field].push(message);
    this.showFieldError(field, message);
  }

  clearError(field) {
    delete this.errors[field];
    this.hideFieldError(field);
  }

  showFieldError(field, message) {
    const fieldElement = this.form.querySelector(`#${field}`);
    if (!fieldElement) return;

    // Adicionar classe de erro
    fieldElement.classList.add("is-invalid");

    // Remover mensagem de erro anterior
    const existingError =
      fieldElement.parentNode.querySelector(".invalid-feedback");
    if (existingError) {
      existingError.remove();
    }

    // Adicionar nova mensagem de erro
    const errorDiv = document.createElement("div");
    errorDiv.className = "invalid-feedback";
    errorDiv.textContent = message;
    fieldElement.parentNode.appendChild(errorDiv);
  }

  hideFieldError(field) {
    const fieldElement = this.form.querySelector(`#${field}`);
    if (!fieldElement) return;

    fieldElement.classList.remove("is-invalid");

    const errorDiv = fieldElement.parentNode.querySelector(".invalid-feedback");
    if (errorDiv) {
      errorDiv.remove();
    }
  }

  showValidationSummary() {
    const errorCount = Object.keys(this.errors).length;
    if (errorCount === 0) return;

    const alertDiv = document.createElement("div");
    alertDiv.className = "alert alert-danger alert-dismissible fade show mt-3";
    alertDiv.innerHTML = `
            <h6><i class="fas fa-exclamation-triangle"></i> Foram encontrados ${errorCount} erro(s) no formulário:</h6>
            <ul class="mb-0">
                ${Object.values(this.errors)
                  .flat()
                  .map((error) => `<li>${error}</li>`)
                  .join("")}
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

    // Inserir antes do formulário
    this.form.parentNode.insertBefore(alertDiv, this.form);

    // Scroll para o topo
    alertDiv.scrollIntoView({ behavior: "smooth", block: "center" });

    // Auto-remover após 10 segundos
    setTimeout(() => {
      if (alertDiv.parentNode) {
        alertDiv.remove();
      }
    }, 10000);
  }
}

// Inicializar validador quando o DOM estiver pronto
document.addEventListener("DOMContentLoaded", function () {
  const taskForm = document.querySelector('#task-form, form[name="task-form"]');
  if (taskForm) {
    new TaskFormValidator(taskForm);
  }
});

// Validação adicional para AJAX
function validateTaskDataBeforeSubmit(data) {
  const errors = {};

  // Validar título
  if (!data.title || data.title.trim().length === 0) {
    errors.title = ["O título da tarefa é obrigatório"];
  } else {
    const title = data.title.trim();
    if (title.length < 3) {
      errors.title = ["O título deve ter pelo menos 3 caracteres"];
    } else if (title.length > 200) {
      errors.title = ["O título não pode ter mais de 200 caracteres"];
    }
  }

  // Validar descrição
  if (data.description && data.description.length > 1000) {
    errors.description = ["A descrição não pode ter mais de 1000 caracteres"];
  }

  // Validar data de vencimento
  if (data.due_date) {
    const dueDate = new Date(data.due_date);
    const now = new Date();

    if (isNaN(dueDate.getTime())) {
      errors.due_date = ["Data de vencimento inválida"];
    } else if (dueDate < now) {
      errors.due_date = ["A data de vencimento não pode ser no passado"];
    }
  }

  return {
    isValid: Object.keys(errors).length === 0,
    errors: errors,
  };
}
