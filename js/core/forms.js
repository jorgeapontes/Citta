/**
 * Validação de formulários no frontend.
 *
 * IMPORTANTE: o backend de envio ainda não foi definido (seção 19 do
 * briefing do projeto — não inventar API/serviço externo sem consulta).
 * Este arquivo só valida os campos e prepara o payload; o envio real
 * (para e-mail, CRM, backend próprio etc.) deve ser conectado no ponto
 * marcado como TODO em `sendForm()` antes de ir para produção.
 */
(function () {
  const validators = {
    nome: (v) => (v.trim().length >= 3 ? '' : 'Informe seu nome completo.'),
    email: (v) => (/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v.trim()) ? '' : 'Informe um e-mail válido.'),
    telefone: (v) => (v.replace(/\D/g, '').length >= 10 ? '' : 'Informe um telefone com DDD.'),
    cidade: (v) => (v.trim().length >= 2 ? '' : 'Informe sua cidade.'),
    mensagem: (v) => (v.trim().length >= 10 ? '' : 'Escreva uma mensagem com pelo menos 10 caracteres.'),
  };

  function validateField(field) {
    const name = field.name;
    const validator = validators[name];
    const wrapper = field.closest('.form-field');
    if (!validator || !wrapper) return true;

    const message = validator(field.value);
    let errorEl = wrapper.querySelector('.form-error');

    if (message) {
      wrapper.setAttribute('data-invalid', 'true');
      field.setAttribute('aria-invalid', 'true');
      if (!errorEl) {
        errorEl = document.createElement('small');
        errorEl.className = 'form-error';
        errorEl.setAttribute('role', 'alert');
        wrapper.appendChild(errorEl);
      }
      errorEl.textContent = message;
      return false;
    }

    wrapper.removeAttribute('data-invalid');
    field.removeAttribute('aria-invalid');
    if (errorEl) errorEl.remove();
    return true;
  }

  async function sendForm(data) {
    // TODO: conectar a um backend real quando definido (e-mail, CRM, etc).
    // Exemplo futuro:
    // const res = await fetch('/api/contato', { method: 'POST', body: JSON.stringify(data) });
    // if (!res.ok) throw new Error('Falha no envio');
    console.info('[forms] Dados prontos para envio (backend ainda não conectado):', data);
    return Promise.resolve();
  }

  function initForm(form) {
    const fields = Array.from(form.querySelectorAll('input[name], textarea[name], select[name]'));
    const statusEl = form.querySelector('[data-form-status]');

    fields.forEach((field) => {
      field.addEventListener('blur', () => validateField(field));
    });

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const allValid = fields.map(validateField).every(Boolean);
      if (!allValid) {
        if (statusEl) {
          statusEl.hidden = false;
          statusEl.className = 'form-status form-status--error';
          statusEl.textContent = 'Verifique os campos destacados antes de enviar.';
        }
        return;
      }

      const submitBtn = form.querySelector('[type="submit"]');
      if (submitBtn) submitBtn.disabled = true;

      const data = Object.fromEntries(new FormData(form).entries());

      try {
        await sendForm(data);
        if (statusEl) {
          statusEl.hidden = false;
          statusEl.className = 'form-status form-status--success';
          statusEl.textContent = 'Recebemos seus dados. Nossa equipe entrará em contato em breve.';
        }
        form.reset();
      } catch (err) {
        if (statusEl) {
          statusEl.hidden = false;
          statusEl.className = 'form-status form-status--error';
          statusEl.textContent = 'Não foi possível enviar agora. Tente novamente ou fale pelo WhatsApp.';
        }
      } finally {
        if (submitBtn) submitBtn.disabled = false;
      }
    });
  }

  function boot() {
    document.querySelectorAll('[data-validate-form]').forEach(initForm);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
