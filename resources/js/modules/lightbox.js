import { Fancybox } from '@fancyapps/ui';
import '@fancyapps/ui/dist/fancybox/fancybox.css';

Fancybox.bind('[data-fancybox]', {
  hideScrollbar: false,
  Images: {
    zoom: false,
  },
  Thumbs: false,
  Toolbar: {
    display: {
      left: [],
      middle: [],
      right: ['close'],
    },
  },

  // Custom caption function
  caption: function(fancybox, slide) {
    const currentIndex = slide.index + 1;
    const totalSlides = fancybox.carousel ? fancybox.carousel.slides.length : fancybox.items?.length || 0;
    
    // Get caption from multiple possible sources
    let originalCaption = '';
    if (slide.caption) {
      originalCaption = slide.caption;
    } 
    else if (slide.triggerEl) {
      originalCaption = slide.triggerEl.getAttribute('data-caption') || '';
    } 
    else if (slide.src && slide.src.includes('data-caption')) {
      // Fallback for edge cases
      originalCaption = slide.src.split('data-caption="')[1]?.split('"')[0] || '';
    }
    
    // If no caption found, return empty string to avoid "Image not found"
    if (!originalCaption) {
      return `<div>${currentIndex}/${totalSlides}</div>`;
    }
    return `<div>${currentIndex}/${totalSlides}</div>` + `<div>${originalCaption}</div>`;
  },
  on: {
    loaded: (fancybox) => {
      updateBackdropColor(fancybox);
    },
    "Carousel.change": (fancybox) => {
      updateBackdropColor(fancybox);
    },
  },
});

function updateBackdropColor(fancybox) {
  const backdrop = fancybox.container.querySelector('.fancybox__backdrop');
  if (!backdrop) return;
  
  // Get current slide - carousel should be properly initialized when loaded event fires
  const currentIndex = fancybox.carousel ? fancybox.carousel.page : 0;
  const currentSlide = fancybox.carousel && fancybox.carousel.slides 
    ? fancybox.carousel.slides[currentIndex]
    : null;
  
  if (currentSlide && currentSlide.triggerEl) {
    // Remove any existing background color classes
    backdrop.className = backdrop.className.replace(/\bbg-\w+/g, '');
    
    // Get the background color from the trigger element's data attribute or class
    const bgColor = currentSlide.triggerEl.getAttribute('data-bg-color') || getBgColorFromClasses(currentSlide.triggerEl);
    if (bgColor) {
      backdrop.classList.add(bgColor);
    }
  }
}

function getBgColorFromClasses(element) {
  // Extract background color class from element's classList
  const classList = Array.from(element.classList);
  const bgClass = classList.find(cls => cls.startsWith('bg-'));
  return bgClass;
}