import './styles/app.css';
import AOS from 'aos';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import './js/hero-parallax.js';
import './js/cards-hover.js';
import './js/gallery-swiper.js';
import './js/registration-modal.js';

gsap.registerPlugin(ScrollTrigger);
document.addEventListener('DOMContentLoaded', () => {
  AOS.init({ duration: 700, once: true });
});
