import { gsap } from 'gsap';
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-card]').forEach(card => {
    card.addEventListener('mouseenter', () => gsap.to(card, { y: -6, boxShadow: '0 12px 24px rgba(0,0,0,.12)', duration: .25 }));
    card.addEventListener('mouseleave', () => gsap.to(card, { y: 0, boxShadow: '0 4px 12px rgba(0,0,0,.06)', duration: .25 }));
  });
});
