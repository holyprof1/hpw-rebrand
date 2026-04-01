(function () {
    'use strict';

    var config = window.holyprofwebSearch || {};

    function escHTML(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function request(body, callback) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', config.ajaxurl || '/wp-admin/admin-ajax.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function () {
            if (xhr.readyState !== 4) return;
            if (xhr.status !== 200) {
                callback(new Error('network'));
                return;
            }
            try {
                callback(null, JSON.parse(xhr.responseText));
            } catch (err) {
                callback(err);
            }
        };
        xhr.send(body);
    }

    var toggle = document.getElementById('menu-toggle');
    var nav = document.getElementById('site-navigation');
    if (toggle && nav) {
        toggle.addEventListener('click', function () {
            var isOpen = nav.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', String(isOpen));
        });
    }

    document.querySelectorAll('.left-nav-parent').forEach(function (button) {
        button.addEventListener('click', function (event) {
            if (button.classList.contains('left-nav-parent--static')) return;
            if (event.target.classList.contains('left-nav-parent-link')) return;
            var group = button.closest('.left-nav-group');
            if (!group) return;
            var isOpen = group.classList.toggle('is-open');
            button.setAttribute('aria-expanded', String(isOpen));
        });
    });

    document.querySelectorAll('.post-carousel').forEach(function (carousel) {
        if (carousel.children.length < 2) return;
        var autoSlide = window.setInterval(function () {
            if (window.innerWidth <= 768) return;
            carousel.scrollBy({ left: 280, behavior: 'smooth' });
            if (carousel.scrollLeft + carousel.clientWidth >= carousel.scrollWidth - 40) {
                window.setTimeout(function () {
                    carousel.scrollTo({ left: 0, behavior: 'smooth' });
                }, 900);
            }
        }, 4500);

        carousel.addEventListener('mouseenter', function () {
            window.clearInterval(autoSlide);
        });
    });

    document.querySelectorAll('.accordion-trigger').forEach(function (button) {
        button.addEventListener('click', function () {
            var item = button.closest('.accordion-item');
            if (!item) return;
            var isOpen = item.classList.toggle('is-open');
            button.setAttribute('aria-expanded', String(isOpen));
        });
    });

    document.querySelectorAll('.reaction-btn').forEach(function (button) {
        button.addEventListener('click', function () {
            var bar = button.closest('.reactions-bar');
            if (!bar || !config.reaction_nonce) return;

            var postId = bar.getAttribute('data-post-id');
            var reaction = button.getAttribute('data-reaction');
            if (!postId || !reaction) return;

            request(
                'action=holyprofweb_reaction' +
                '&post_id=' + encodeURIComponent(postId) +
                '&reaction=' + encodeURIComponent(reaction) +
                '&nonce=' + encodeURIComponent(config.reaction_nonce),
                function (err, json) {
                    if (err || !json || !json.success) return;
                    var count = button.querySelector('.reaction-count');
                    if (count) count.textContent = json.data.count;
                    button.classList.add('is-active');
                }
            );
        });
    });

    var starPicker = document.querySelector('.review-star-picker');
    var starHint = document.querySelector('.review-star-hint');
    if (starPicker && starHint) {
        var labels = starPicker.querySelectorAll('.review-star-label');
        var labelsText = ['', 'Poor', 'Fair', 'Good', 'Very Good', 'Excellent'];

        labels.forEach(function (label) {
            label.addEventListener('mouseenter', function () {
                var radio = document.getElementById(label.getAttribute('for'));
                if (radio) starHint.textContent = labelsText[parseInt(radio.value, 10)] || '';
            });
        });

        starPicker.querySelectorAll('.review-star-radio').forEach(function (radio) {
            radio.addEventListener('change', function () {
                starHint.textContent = labelsText[parseInt(radio.value, 10)] + ' (' + radio.value + '/5)';
            });
        });
    }

    function bindAjaxForm(options) {
        var form = document.getElementById(options.formId);
        var error = document.getElementById(options.errorId);
        var submit = document.getElementById(options.submitId);
        if (!form || !submit) return;

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            if (error) {
                error.textContent = '';
                error.hidden = true;
            }

            var validation = options.validate(form);
            if (validation) {
                if (error) {
                    error.textContent = validation;
                    error.hidden = false;
                }
                return;
            }

            submit.disabled = true;
            var originalText = submit.textContent;
            submit.textContent = options.loadingText;

            request(options.serialize(form), function (err, json) {
                submit.disabled = false;
                submit.textContent = originalText;

                if (err || !json || !json.success) {
                    if (error) {
                        error.textContent = json && json.data ? json.data : options.failureText;
                        error.hidden = false;
                    }
                    return;
                }

                options.onSuccess(form, json.data);
            });
        });
    }

    bindAjaxForm({
        formId: 'review-form',
        errorId: 'review-error',
        submitId: 'review-submit',
        loadingText: 'Posting...',
        failureText: 'Could not submit your review.',
        validate: function (form) {
            var name = (form.querySelector('[name="reviewer_name"]') || {}).value || '';
            var email = (form.querySelector('[name="reviewer_email"]') || {}).value || '';
            var rating = (form.querySelector('[name="rating"]:checked') || {}).value || '';
            var content = (form.querySelector('[name="review_content"]') || {}).value || '';

            if (!rating) return 'Please select a star rating.';
            if (!name.trim()) return 'Please enter your name.';
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return 'Please enter a valid email.';
            if (content.trim().length < 10) return 'Please enter a short review.';
            return '';
        },
        serialize: function (form) {
            return 'action=holyprofweb_submit_review' +
                '&nonce=' + encodeURIComponent(config.review_nonce || '') +
                '&post_id=' + encodeURIComponent(form.getAttribute('data-post-id') || '') +
                '&reviewer_name=' + encodeURIComponent((form.querySelector('[name="reviewer_name"]') || {}).value || '') +
                '&reviewer_email=' + encodeURIComponent((form.querySelector('[name="reviewer_email"]') || {}).value || '') +
                '&rating=' + encodeURIComponent((form.querySelector('[name="rating"]:checked') || {}).value || '') +
                '&review_content=' + encodeURIComponent((form.querySelector('[name="review_content"]') || {}).value || '') +
                '&site_url=' + encodeURIComponent((form.querySelector('[name="site_url"]') || {}).value || '');
        },
        onSuccess: function (form, data) {
            var wrap = form.closest('.review-form-wrap');
            if (wrap) {
                wrap.innerHTML = '<div class="review-form-success"><h4>Thank you for your review!</h4><p>' + escHTML(data.message || 'Your review was submitted.') + '</p></div>';
            }
        }
    });

    bindAjaxForm({
        formId: 'salary-form',
        errorId: 'salary-error',
        submitId: 'salary-submit',
        loadingText: 'Submitting...',
        failureText: 'Could not submit salary data.',
        validate: function (form) {
            var required = ['submitter_name', 'submitter_email', 'salary_company', 'salary_role', 'salary_amount'];
            for (var i = 0; i < required.length; i++) {
                var field = form.querySelector('[name="' + required[i] + '"]');
                if (!field || !String(field.value || '').trim()) return 'Please fill all required fields.';
            }
            var email = (form.querySelector('[name="submitter_email"]') || {}).value || '';
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return 'Please enter a valid email.';
            return '';
        },
        serialize: function (form) {
            return 'action=holyprofweb_submit_salary' +
                '&nonce=' + encodeURIComponent(config.salary_nonce || '') +
                '&post_id=' + encodeURIComponent(form.getAttribute('data-post-id') || '') +
                '&submitter_name=' + encodeURIComponent((form.querySelector('[name="submitter_name"]') || {}).value || '') +
                '&submitter_email=' + encodeURIComponent((form.querySelector('[name="submitter_email"]') || {}).value || '') +
                '&salary_company=' + encodeURIComponent((form.querySelector('[name="salary_company"]') || {}).value || '') +
                '&salary_role=' + encodeURIComponent((form.querySelector('[name="salary_role"]') || {}).value || '') +
                '&salary_amount=' + encodeURIComponent((form.querySelector('[name="salary_amount"]') || {}).value || '') +
                '&salary_location=' + encodeURIComponent((form.querySelector('[name="salary_location"]') || {}).value || '') +
                '&salary_currency=' + encodeURIComponent((form.querySelector('[name="salary_currency"]') || {}).value || '') +
                '&salary_work_life=' + encodeURIComponent((form.querySelector('[name="salary_work_life"]') || {}).value || '');
        },
        onSuccess: function (form, data) {
            var wrap = form.closest('.review-form-wrap');
            if (wrap) {
                wrap.innerHTML = '<div class="review-form-success"><h4>Salary submitted</h4><p>' + escHTML(data.message || 'Your salary data is now in admin review.') + '</p></div>';
            }
        }
    });

    document.querySelectorAll('.email-capture-form').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            var input = form.querySelector('.email-capture-input');
            if (!input || !input.value) return;

            request(
                'action=holyprofweb_email_capture' +
                '&email=' + encodeURIComponent(input.value) +
                '&nonce=' + encodeURIComponent(config.nonce || ''),
                function (err, json) {
                    if (err || !json || !json.success) return;
                    var box = form.closest('.email-capture-box');
                    if (box) {
                        box.innerHTML = '<p class="email-capture-title">You are on the list.</p><p class="email-capture-note">We will send new salary and review updates to your inbox.</p>';
                    }
                }
            );
        });
    });
})();
