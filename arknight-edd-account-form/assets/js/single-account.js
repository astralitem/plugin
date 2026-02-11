(function () {
  var gallery = document.querySelector('[data-arkn-gallery]');
  var lightbox = document.querySelector('[data-arkn-lightbox]');
  if (!gallery) {
    return;
  }

  var slides = Array.prototype.slice.call(gallery.querySelectorAll('[data-gallery-slide]'));
  var thumbs = Array.prototype.slice.call(gallery.querySelectorAll('[data-gallery-thumb]'));
  var prev = gallery.querySelector('[data-gallery-prev]');
  var next = gallery.querySelector('[data-gallery-next]');

  if (!slides.length) {
    return;
  }

  var index = 0;
  for (var i = 0; i < slides.length; i += 1) {
    if (slides[i].classList.contains('is-active')) {
      index = i;
      break;
    }
  }

  function activate(newIndex) {
    index = (newIndex + slides.length) % slides.length;

    for (var s = 0; s < slides.length; s += 1) {
      var isActiveSlide = s === index;
      slides[s].classList.toggle('is-active', isActiveSlide);
      slides[s].setAttribute('aria-hidden', isActiveSlide ? 'false' : 'true');
      slides[s].setAttribute('tabindex', isActiveSlide ? '0' : '-1');
    }

    for (var t = 0; t < thumbs.length; t += 1) {
      var isActiveThumb = t === index;
      thumbs[t].classList.toggle('is-active', isActiveThumb);
      thumbs[t].setAttribute('aria-current', isActiveThumb ? 'true' : 'false');
    }
  }

  if (prev) {
    prev.addEventListener('click', function () {
      activate(index - 1);
    });
  }

  if (next) {
    next.addEventListener('click', function () {
      activate(index + 1);
    });
  }

  thumbs.forEach(function (thumb) {
    thumb.addEventListener('click', function () {
      var to = Number(thumb.getAttribute('data-index'));
      if (!Number.isNaN(to)) {
        activate(to);
      }
    });
  });

  if (lightbox) {
    var lightboxImg = lightbox.querySelector('[data-lightbox-image]');
    var close = lightbox.querySelector('[data-lightbox-close]');

    lightbox.hidden = true;
    document.body.classList.remove('arkn-lightbox-open');

    function openLightbox(src, alt) {
      if (!lightboxImg || !src) {
        return;
      }

      lightboxImg.setAttribute('src', src);
      lightboxImg.setAttribute('alt', alt || '');
      lightbox.hidden = false;
      document.body.classList.add('arkn-lightbox-open');
    }

    function closeLightbox() {
      lightbox.hidden = true;
      document.body.classList.remove('arkn-lightbox-open');
    }

    slides.forEach(function (slide) {
      slide.addEventListener('click', function () {
        var img = slide.querySelector('img');
        var src = slide.getAttribute('data-full') || (img ? img.getAttribute('src') : '');
        var alt = img ? img.getAttribute('alt') : '';
        openLightbox(src, alt);
      });
    });

    if (close) {
      close.addEventListener('click', closeLightbox);
    }

    lightbox.addEventListener('click', function (event) {
      if (event.target === lightbox) {
        closeLightbox();
      }
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && !lightbox.hidden) {
        closeLightbox();
      }
      if (event.key === 'ArrowLeft') {
        activate(index - 1);
      }
      if (event.key === 'ArrowRight') {
        activate(index + 1);
      }
    });
  }

  activate(index);
})();
