document.addEventListener('DOMContentLoaded', () => {
  const modal = document.getElementById('contact-modal');
  if (!modal) return;
  const form = document.getElementById('contact-form');
  const feedback = document.getElementById('contact-feedback');
  const openBtn = document.getElementById('open-contact-modal');

  const open = () => {
    form.reset();
    feedback.className = 'modal-feedback';
    feedback.textContent = '';
    modal.classList.add('open');
  };
  const close = () => modal.classList.remove('open');

  openBtn?.addEventListener('click', open);
  modal.querySelectorAll('[data-close]').forEach(b => b.addEventListener('click', close));
  modal.addEventListener('click', e => { if (e.target === modal) close(); });

  form.addEventListener('submit', async e => {
    e.preventDefault();
    const submitBtn = form.querySelector('button[type=submit]');
    submitBtn.disabled = true;
    const payload = {
      email: form.email.value,
      subject: form.subject.value,
      message: form.message.value,
    };
    try {
      const res = await fetch('/api/contact', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });
      const data = await res.json();
      if (data.ok) {
        feedback.className = 'modal-feedback ok';
        feedback.textContent = data.message;
        form.reset();
      } else {
        feedback.className = 'modal-feedback err';
        feedback.textContent = data.message || Object.values(data.errors || {}).join(' ');
      }
    } catch {
      feedback.className = 'modal-feedback err';
      feedback.textContent = 'Erreur réseau. Veuillez réessayer.';
    } finally {
      submitBtn.disabled = false;
    }
  });
});
