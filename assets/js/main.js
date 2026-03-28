/* ============================================================
   Solicitador — main.js
   ============================================================ */

(function () {
  'use strict';

  /* ── Mobile nav toggle ──────────────────────────────────── */
  const navToggle = document.getElementById('navToggle');
  const navLinks  = document.getElementById('navLinks');
  if (navToggle && navLinks) {
    navToggle.addEventListener('click', function () {
      const open = navLinks.classList.toggle('open');
      navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    document.querySelectorAll('.nav-links a').forEach(function (link) {
      link.addEventListener('click', function () {
        navLinks.classList.remove('open');
        navToggle.setAttribute('aria-expanded', 'false');
      });
    });
    document.addEventListener('click', function (e) {
      if (!navToggle.contains(e.target) && !navLinks.contains(e.target)) {
        navLinks.classList.remove('open');
        navToggle.setAttribute('aria-expanded', 'false');
      }
    });
  }

  /* ── Smooth scroll for anchor links ─────────────────────── */
  document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
    anchor.addEventListener('click', function (e) {
      const target = document.querySelector(this.getAttribute('href'));
      if (target) {
        e.preventDefault();
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });

  /* ── Date picker: set min = tomorrow, disable weekends ──── */
  function getTomorrow() {
    var d = new Date();
    d.setDate(d.getDate() + 1);
    return d;
  }

  function toInputDate(d) {
    var y = d.getFullYear();
    var m = ('0' + (d.getMonth() + 1)).slice(-2);
    var day = ('0' + d.getDate()).slice(-2);
    return y + '-' + m + '-' + day;
  }

  var dateInputs = document.querySelectorAll('input[type="date"]');
  dateInputs.forEach(function (input) {
    if (!input.min) {
      input.min = toInputDate(getTomorrow());
    }
    input.addEventListener('change', function () {
      var selected = new Date(this.value + 'T00:00:00');
      var dow = selected.getDay(); // 0=Sun, 6=Sat
      if (dow === 0 || dow === 6) {
        var msg = (window.i18n && window.i18n.weekendDate) ? window.i18n.weekendDate : '';
        this.setCustomValidity(msg);
        this.reportValidity();
        this.value = '';
      } else {
        this.setCustomValidity('');
      }
    });
  });

  /* ── Booking form client-side validation ─────────────────── */
  var bookingForm = document.querySelector('.booking-form');
  if (bookingForm) {
    bookingForm.addEventListener('submit', function (e) {
      var valid = true;
      var t = window.i18n || {};
      var msgRequired    = t.required     || '';
      var msgEmail       = t.invalidEmail || '';
      var msgNif         = t.invalidNif   || '';
      var msgPastDate    = t.pastDate     || '';
      var msgWeekend     = t.weekendDate  || '';

      // Clear previous errors
      bookingForm.querySelectorAll('.field-error-js').forEach(function (el) {
        el.remove();
      });
      bookingForm.querySelectorAll('.has-error-js').forEach(function (el) {
        el.classList.remove('has-error-js');
      });

      function showError(fieldEl, msg) {
        fieldEl.closest('.form-group').classList.add('has-error-js');
        var span = document.createElement('span');
        span.className = 'field-error field-error-js';
        span.textContent = msg;
        fieldEl.insertAdjacentElement('afterend', span);
        valid = false;
      }

      // Name
      var nameField = document.getElementById('name');
      if (nameField && nameField.value.trim() === '') {
        showError(nameField, msgRequired);
      }

      // Email
      var emailField = document.getElementById('email');
      if (emailField) {
        if (emailField.value.trim() === '') {
          showError(emailField, msgRequired);
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailField.value.trim())) {
          showError(emailField, msgEmail);
        }
      }

      // Phone
      var phoneField = document.getElementById('phone');
      if (phoneField && phoneField.value.trim() === '') {
        showError(phoneField, msgRequired);
      }

      // NIF (optional but validate if filled)
      var nifField = document.getElementById('nif');
      if (nifField && nifField.value.trim() !== '' && !/^\d{9}$/.test(nifField.value.trim())) {
        showError(nifField, msgNif);
      }

      // Service
      var serviceField = document.getElementById('service_slug');
      if (serviceField && serviceField.value === '') {
        showError(serviceField, msgRequired);
      }

      // Date
      var dateField = document.getElementById('preferred_date');
      if (dateField) {
        if (dateField.value === '') {
          showError(dateField, msgRequired);
        } else {
          var selected = new Date(dateField.value + 'T00:00:00');
          var tomorrow = getTomorrow();
          tomorrow.setHours(0, 0, 0, 0);
          if (selected < tomorrow) {
            showError(dateField, msgPastDate);
          } else {
            var dow = selected.getDay();
            if (dow === 0 || dow === 6) {
              showError(dateField, msgWeekend);
            }
          }
        }
      }

      // Time
      var timeField = document.getElementById('preferred_time');
      if (timeField && timeField.value === '') {
        showError(timeField, msgRequired);
      }

      if (!valid) {
        e.preventDefault();
        var firstError = bookingForm.querySelector('.has-error-js input, .has-error-js select');
        if (firstError) firstError.focus();
        return;
      }

      // Loading state
      var submitBtn = bookingForm.querySelector('button[type="submit"]');
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = (t.sending) ? t.sending : '⏳ A enviar...';
      }
    });
  }

  /* ── Generic form submit loading state ──────────────────── */
  document.querySelectorAll('form:not(.booking-form):not(.filter-form)').forEach(function (form) {
    form.addEventListener('submit', function () {
      var btn = form.querySelector('button[type="submit"]');
      if (btn && !btn.disabled) {
        setTimeout(function () {
          btn.disabled = true;
          btn.style.opacity = '0.7';
        }, 50);
      }
    });
  });

  /* ── Auto-dismiss alerts after 5 seconds ────────────────── */
  document.querySelectorAll('.alert-success').forEach(function (alert) {
    setTimeout(function () {
      alert.style.transition = 'opacity 0.5s';
      alert.style.opacity = '0';
      setTimeout(function () { alert.style.display = 'none'; }, 500);
    }, 5000);
  });

})();
