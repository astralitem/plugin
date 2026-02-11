(function () {
  const forms = document.querySelectorAll('.arkn-account-form');

  const setError = (form, message, field) => {
    const feedback = form.querySelector('.arkn-form-feedback');
    if (feedback) {
      feedback.textContent = message;
      feedback.classList.add('is-error');
    }

    if (field) {
      field.classList.add('arkn-invalid');
      field.focus();
    }
  };

  const clearError = (form) => {
    const feedback = form.querySelector('.arkn-form-feedback');
    if (feedback) {
      feedback.textContent = '';
      feedback.classList.remove('is-error');
    }
    form.querySelectorAll('.arkn-invalid').forEach((el) => el.classList.remove('arkn-invalid'));
  };

  const initCheckboxCards = (form) => {
    form.querySelectorAll('.arkn-card input[type="checkbox"]').forEach((checkbox) => {
      const card = checkbox.closest('.arkn-card');
      if (!card) return;

      const applyState = () => card.classList.toggle('is-selected', checkbox.checked);
      applyState();
      checkbox.addEventListener('change', applyState);
    });
  };

  const initCustomSelect = (form) => {
    form.querySelectorAll('[data-select-wrap]').forEach((wrap) => {
      const trigger = wrap.querySelector('[data-select-trigger]');
      const menu = wrap.querySelector('[data-select-menu]');
      const label = wrap.querySelector('[data-select-label]');
      const select = wrap.querySelector('select');

      if (!trigger || !menu || !label || !select) return;

      trigger.addEventListener('click', () => {
        const isOpen = trigger.getAttribute('aria-expanded') === 'true';
        trigger.setAttribute('aria-expanded', String(!isOpen));
        menu.hidden = isOpen;
      });

      menu.querySelectorAll('[data-option]').forEach((optionBtn) => {
        optionBtn.addEventListener('click', () => {
          const value = optionBtn.getAttribute('data-option') || '';
          select.value = value;
          label.textContent = optionBtn.textContent || 'انتخاب کنید';
          trigger.setAttribute('aria-expanded', 'false');
          menu.hidden = true;
        });
      });

      document.addEventListener('click', (event) => {
        if (!wrap.contains(event.target)) {
          trigger.setAttribute('aria-expanded', 'false');
          menu.hidden = true;
        }
      });
    });
  };

	const initUploadPreview = (form) => {
    const input = form.querySelector('.arkn-image-input');
    const preview = form.querySelector('[data-upload-preview]');
    if (!input || !preview) return;

    const renderPreview = () => {
      preview.innerHTML = '';
      const maxImages = Number(input.getAttribute('data-max-images')) || 10;
      const files = Array.from(input.files || []);

      if (files.length > maxImages) {
        input.value = '';
        setError(form, `حداکثر ${maxImages} تصویر قابل انتخاب است.`, input);
        return;
      }

      files.forEach((file) => {
        if (!file.type.startsWith('image/')) return;

        const item = document.createElement('div');
        item.className = 'arkn-upload-preview__item';

        const img = document.createElement('img');
        img.alt = file.name;
        img.loading = 'lazy';
        img.src = URL.createObjectURL(file);

        const name = document.createElement('span');
        name.textContent = file.name;

        item.appendChild(img);
        item.appendChild(name);
        preview.appendChild(item);
      });
    };

    input.addEventListener('change', () => {
      clearError(form);
      renderPreview();
    });
  };


  forms.forEach((form) => {
    initCheckboxCards(form);
    initCustomSelect(form);
	initUploadPreview(form);

    form.addEventListener('submit', (event) => {
      clearError(form);

	    const uploadInput = form.querySelector('.arkn-image-input');
      if (uploadInput) {
        const maxImages = Number(uploadInput.getAttribute('data-max-images')) || 10;
        const files = Array.from(uploadInput.files || []);
        if (files.length > maxImages) {
          event.preventDefault();
          setError(form, `حداکثر ${maxImages} تصویر قابل انتخاب است.`, uploadInput);
          return;
        }
      }

      const requiredFields = form.querySelectorAll('[required]');
      for (const field of requiredFields) {
        if (!field.value || String(field.value).trim() === '') {
          event.preventDefault();
          setError(form, field.getAttribute('data-error') || 'لطفاً این فیلد را تکمیل کنید.', field);
          return;
        }

        if (field.type === 'number') {
          const value = Number(field.value);
          const min = Number(field.getAttribute('min'));
          const max = Number(field.getAttribute('max'));
          if (Number.isNaN(value) || value < min || value > max) {
            event.preventDefault();
            setError(form, field.getAttribute('data-error') || 'مقدار وارد شده معتبر نیست.', field);
            return;
          }
        }
      }

      const submitButton = form.querySelector('.arkn-submit');
      if (submitButton) {
        submitButton.disabled = true;
        submitButton.classList.add('is-loading');
        submitButton.textContent = 'در حال ارسال...';
      }
    });
  });
})();
