document.addEventListener('DOMContentLoaded', () => {
  const overlay = document.createElement('div');
  overlay.className = 'lb-overlay';
  overlay.innerHTML = `
    <button class="lb-close" aria-label="Fermer">&times;</button>
    <button class="lb-nav lb-prev" aria-label="Précédent">&#8249;</button>
    <img class="lb-img" src="" alt="">
    <button class="lb-nav lb-next" aria-label="Suivant">&#8250;</button>
  `;
  document.body.appendChild(overlay);

  const lbImg = overlay.querySelector('.lb-img');
  let srcs = [];
  let idx = 0;

  const show = (i) => {
    idx = (i + srcs.length) % srcs.length;
    lbImg.src = srcs[idx];
    overlay.classList.add('open');
  };
  const close = () => overlay.classList.remove('open');

  overlay.querySelector('.lb-close').addEventListener('click', close);
  overlay.querySelector('.lb-prev').addEventListener('click', (e) => { e.stopPropagation(); show(idx - 1); });
  overlay.querySelector('.lb-next').addEventListener('click', (e) => { e.stopPropagation(); show(idx + 1); });
  overlay.addEventListener('click', (e) => { if (e.target === overlay) close(); });
  document.addEventListener('keydown', (e) => {
    if (!overlay.classList.contains('open')) return;
    if (e.key === 'Escape') close();
    if (e.key === 'ArrowLeft') show(idx - 1);
    if (e.key === 'ArrowRight') show(idx + 1);
  });

  document.addEventListener('click', (e) => {
    const img = e.target.closest('.gallery-swiper .swiper-slide img');
    if (!img) return;
    srcs = [...document.querySelectorAll('.gallery-swiper .swiper-slide img')].map(i => i.src);
    const i = srcs.indexOf(img.src);
    show(i >= 0 ? i : 0);
  });
});
