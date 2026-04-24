import Swiper from 'swiper';
import { Pagination, EffectCoverflow, Autoplay } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/pagination';
import 'swiper/css/effect-coverflow';
document.addEventListener('DOMContentLoaded', () => {
  const el = document.querySelector('.gallery-swiper');
  if (!el) return;
  const mobile = window.matchMedia('(max-width: 768px)').matches;
  new Swiper(el, {
    modules: [Pagination, EffectCoverflow, Autoplay],
    slidesPerView: mobile ? 1.2 : 3,
    spaceBetween: 20,
    centeredSlides: mobile,
    effect: mobile ? 'coverflow' : 'slide',
    coverflowEffect: { rotate: 20, depth: 100, modifier: 1, slideShadows: false },
    autoplay: { delay: 4000 },
    pagination: { el: '.swiper-pagination', clickable: true },
    loop: true,
  });
});
