import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
gsap.registerPlugin(ScrollTrigger);
document.addEventListener('DOMContentLoaded', () => {
  const bg = document.querySelector('[data-parallax]');
  if (!bg) return;
  gsap.to(bg, {
    yPercent: 20, ease: 'none',
    scrollTrigger: { trigger: '.hero', start: 'top top', end: 'bottom top', scrub: true },
  });
});
