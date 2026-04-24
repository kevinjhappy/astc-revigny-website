import { gsap } from 'gsap';

let _clipId = 0;

function makeBall(size, opacity) {
  const el = document.createElement('div');
  el.className = 'tennis-deco-ball';
  el.style.cssText = `position:absolute;width:${size}px;height:${size}px;opacity:${opacity};pointer-events:none;z-index:0;`;
  return el;
}

function makeRacket(size, opacity) {
  const id = `rcc${++_clipId}`;
  const el = document.createElement('div');
  el.className = 'tennis-deco-racket';
  el.style.cssText = `position:absolute;width:${size}px;pointer-events:none;z-index:0;`;
  el.innerHTML = `<svg viewBox="0 0 60 100" xmlns="http://www.w3.org/2000/svg" width="${size}" style="display:block">
    <defs><clipPath id="${id}"><ellipse cx="30" cy="32" rx="21" ry="27"/></clipPath></defs>
    <g clip-path="url(#${id})" stroke="white" stroke-width="1.5" stroke-opacity="${opacity * 2}">
      <line x1="0" y1="16" x2="60" y2="16"/><line x1="0" y1="24" x2="60" y2="24"/>
      <line x1="0" y1="32" x2="60" y2="32"/><line x1="0" y1="40" x2="60" y2="40"/>
      <line x1="0" y1="48" x2="60" y2="48"/>
      <line x1="14" y1="0" x2="14" y2="62"/><line x1="22" y1="0" x2="22" y2="62"/>
      <line x1="30" y1="0" x2="30" y2="62"/><line x1="38" y1="0" x2="38" y2="62"/>
      <line x1="46" y1="0" x2="46" y2="62"/>
    </g>
    <ellipse cx="30" cy="32" rx="21" ry="27" fill="none" stroke="white" stroke-width="3" stroke-opacity="${opacity}"/>
    <path d="M26 59 L24 70 L36 70 L34 59Z" fill="white" fill-opacity="${opacity}"/>
    <rect x="24" y="69" width="12" height="29" rx="5" fill="white" fill-opacity="${opacity}"/>
  </svg>`;
  return el;
}

function place(section, el, leftPct, topPct) {
  el.style.left = leftPct + '%';
  el.style.top = topPct + '%';
  section.appendChild(el);
  return el;
}

function floatAnim(el, distance, duration, delay) {
  gsap.to(el, {
    y: `+=${distance}`,
    x: `+=${distance * 0.4}`,
    duration,
    repeat: -1,
    yoyo: true,
    ease: 'sine.inOut',
    delay,
  });
}

function rotateAnim(el, startRot, delta, duration, delay) {
  gsap.set(el, { rotation: startRot });
  gsap.to(el, {
    rotation: startRot + delta,
    duration,
    repeat: -1,
    yoyo: true,
    ease: 'sine.inOut',
    delay,
  });
}

document.addEventListener('DOMContentLoaded', () => {
  // --- Hero ---
  const hero = document.querySelector('.hero');
  if (hero) {
    const b1 = place(hero, makeBall(95, 0.13), 6, 12);
    floatAnim(b1, 22, 4.2, 0);

    const b2 = place(hero, makeBall(55, 0.10), 82, 62);
    floatAnim(b2, 16, 3.1, 1.2);

    const b3 = place(hero, makeBall(38, 0.09), 55, 78);
    floatAnim(b3, 12, 3.8, 0.6);

    const r1 = place(hero, makeRacket(130, 0.18), 72, 5);
    rotateAnim(r1, -30, 8, 5.5, 0.3);
    floatAnim(r1, 18, 5.5, 0.3);

    const r2 = place(hero, makeRacket(80, 0.12), -2, 55);
    rotateAnim(r2, 20, -6, 6, 1);
    floatAnim(r2, 14, 6, 1);
  }

  // --- Gallery ---
  const gallery = document.querySelector('.gallery');
  if (gallery) {
    gallery.style.position = 'relative';
    gallery.style.overflow = 'hidden';

    const b4 = place(gallery, makeBall(80, 0.12), 4, 15);
    floatAnim(b4, 20, 4, 0.5);

    const b5 = place(gallery, makeBall(50, 0.10), 88, 60);
    floatAnim(b5, 14, 3.3, 1.5);

    const r3 = place(gallery, makeRacket(110, 0.15), 85, 5);
    rotateAnim(r3, 25, -7, 6.5, 0.8);
    floatAnim(r3, 16, 6.5, 0.8);
  }

  // --- Tournaments ---
  const tournaments = document.querySelector('#tournaments');
  if (tournaments) {
    tournaments.style.position = 'relative';
    tournaments.style.overflow = 'hidden';
    const b6 = place(tournaments, makeBall(70, 0.06), 92, 10);
    floatAnim(b6, 18, 4.5, 0.4);
    b6.style.background = '#1A2B6D';
  }
});
