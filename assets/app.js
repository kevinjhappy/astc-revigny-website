import './styles/app.css';
import AOS from 'aos';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import './js/navbar.js';
import './js/hero-parallax.js';
import './js/cards-hover.js';
import './js/gallery-swiper.js';
import './js/registration-modal.js';
import './js/contact-modal.js';
import './js/gallery-lightbox.js';

gsap.registerPlugin(ScrollTrigger);
document.addEventListener('DOMContentLoaded', () => {
  AOS.init({ duration: 650, once: true, offset: 60 });
});
