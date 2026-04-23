document.addEventListener('DOMContentLoaded', () => {
  const modal = document.getElementById('registration-modal');
  if (!modal) return;
  const form = document.getElementById('registration-form');
  const feedback = document.getElementById('modal-feedback');
  const nameEl = modal.querySelector('[data-tournament-name]');
  const membersNotice = modal.querySelector('.members-notice');

  const open = (id, name, type) => {
    form.reset();
    feedback.className = 'modal-feedback';
    feedback.textContent = '';
    form.tournamentId.value = id;
    nameEl.textContent = name;
    if (membersNotice) membersNotice.style.display = type === 'MEMBERS_ONLY' ? 'block' : 'none';
    modal.classList.add('open');
  };
  const close = () => modal.classList.remove('open');

  document.querySelectorAll('[data-register]').forEach(btn => {
    btn.addEventListener('click', () => open(btn.dataset.register, btn.dataset.name, btn.dataset.type));
  });
  modal.querySelectorAll('[data-close]').forEach(b => b.addEventListener('click', close));
  modal.addEventListener('click', e => { if (e.target === modal) close(); });

  form.addEventListener('submit', async e => {
    e.preventDefault();
    const submitBtn = form.querySelector('button[type=submit]');
    submitBtn.disabled = true;
    const payload = {
      tournamentId: form.tournamentId.value,
      lastName: form.lastName.value,
      firstName: form.firstName.value,
      phone: form.phone.value,
      email: form.email.value || undefined,
    };
    try {
      const res = await fetch('/api/register', {
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
