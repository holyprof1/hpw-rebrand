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
        var autoSlide = null;

        function stopCarouselAutoSlide() {
            if (autoSlide) {
                window.clearInterval(autoSlide);
                autoSlide = null;
            }
        }

        function startCarouselAutoSlide() {
            stopCarouselAutoSlide();

            autoSlide = window.setInterval(function () {
                var step = Math.max(220, Math.round(carousel.clientWidth * 0.82));
                var nextLeft = carousel.scrollLeft + step;
                var endReached = carousel.scrollLeft + carousel.clientWidth >= carousel.scrollWidth - 40;

                if (endReached) {
                    carousel.scrollTo({ left: 0, behavior: 'smooth' });
                    return;
                }

                carousel.scrollTo({ left: nextLeft, behavior: 'smooth' });
            }, 4300);
        }

        carousel.addEventListener('mouseenter', stopCarouselAutoSlide);
        carousel.addEventListener('mouseleave', startCarouselAutoSlide);
        carousel.addEventListener('touchstart', stopCarouselAutoSlide, { passive: true });
        carousel.addEventListener('touchend', startCarouselAutoSlide, { passive: true });
        carousel.addEventListener('focusin', stopCarouselAutoSlide);
        carousel.addEventListener('focusout', startCarouselAutoSlide);

        document.addEventListener('visibilitychange', function () {
            if (document.hidden) {
                stopCarouselAutoSlide();
                return;
            }
            startCarouselAutoSlide();
        });

        window.addEventListener('resize', startCarouselAutoSlide);
        startCarouselAutoSlide();
    });

    document.querySelectorAll('.home .live-reviews-grid').forEach(function (carousel) {
        var cards = Array.prototype.slice.call(carousel.children || []);
        var timer = null;
        var activeIndex = 0;

        if (cards.length < 2) return;

        function isMobileReviewsCarousel() {
            return window.innerWidth <= 900;
        }

        function syncActiveIndex() {
            var closestIndex = 0;
            var closestDistance = Number.POSITIVE_INFINITY;

            cards.forEach(function (card, index) {
                var distance = Math.abs(card.offsetLeft - carousel.scrollLeft);
                if (distance < closestDistance) {
                    closestDistance = distance;
                    closestIndex = index;
                }
            });

            activeIndex = closestIndex;
        }

        function goToCard(index) {
            if (!cards[index]) return;
            activeIndex = index;
            carousel.scrollTo({
                left: cards[index].offsetLeft,
                behavior: 'smooth'
            });
        }

        function stopAutoPlay() {
            if (timer) {
                window.clearInterval(timer);
                timer = null;
            }
        }

        function startAutoPlay() {
            stopAutoPlay();
            if (!isMobileReviewsCarousel()) return;

            timer = window.setInterval(function () {
                syncActiveIndex();
                goToCard((activeIndex + 1) % cards.length);
            }, 4200);
        }

        carousel.addEventListener('scroll', syncActiveIndex, { passive: true });
        carousel.addEventListener('mouseenter', stopAutoPlay);
        carousel.addEventListener('mouseleave', startAutoPlay);
        carousel.addEventListener('focusin', stopAutoPlay);
        carousel.addEventListener('focusout', startAutoPlay);
        carousel.addEventListener('touchstart', stopAutoPlay, { passive: true });
        carousel.addEventListener('touchend', startAutoPlay, { passive: true });

        document.addEventListener('visibilitychange', function () {
            if (document.hidden) {
                stopAutoPlay();
                return;
            }
            startAutoPlay();
        });

        window.addEventListener('resize', function () {
            syncActiveIndex();
            startAutoPlay();
        });

        syncActiveIndex();
        startAutoPlay();
    });

    document.body.classList.add('hpw-no-copy');

    function allowsSelection(target) {
        return !!(target && target.closest('input, textarea, button, select, option, label, [contenteditable="true"], .comment-form, .review-form, .salary-form, .email-capture-form'));
    }

    ['copy', 'cut', 'contextmenu', 'dragstart', 'selectstart'].forEach(function (eventName) {
        document.addEventListener(eventName, function (event) {
            if (allowsSelection(event.target)) return;
            event.preventDefault();
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

    (function () {
        var form = document.getElementById('review-form');
        if (!form) return;

        var reviewerType = form.querySelector('[name="reviewer_type"]');
        if (!reviewerType) return;

        var contextField = form.querySelector('[data-company-review-field="context"]');
        var salaryField = form.querySelector('[data-company-review-field="salary"]');
        var interviewField = form.querySelector('[data-company-review-field="interview"]');
        var issueField = form.querySelector('[data-company-review-field="issue"]');
        var roleInput = form.querySelector('[name="company_role"]');
        var locationInput = form.querySelector('[name="company_location"]');
        var salaryInput = form.querySelector('[name="salary_range"]');
        var interviewInput = form.querySelector('[name="interview_stage"]');
        var issueInput = form.querySelector('[name="experience_issue"]');

        function getRating() {
            var selected = form.querySelector('[name="rating"]:checked');
            return selected ? parseInt(selected.value, 10) : 0;
        }

        function toggleField(field, visible) {
            if (!field) return;
            field.hidden = !visible;
        }

        function updateCompanyReviewForm() {
            var type = reviewerType.value || '';
            var rating = getRating();
            var isStaff = type === 'staff' || type === 'former-staff';
            var isInterview = type === 'interview-candidate';
            var hasType = !!type;

            toggleField(contextField, hasType);

            toggleField(salaryField, isStaff);
            toggleField(interviewField, isInterview);
            toggleField(issueField, isStaff && rating > 0 && rating <= 2);

            if (roleInput) {
                roleInput.placeholder = isInterview
                    ? 'e.g. Product Manager candidate'
                    : (type === 'partner-vendor'
                        ? 'e.g. Agency partner, vendor'
                        : 'e.g. Product Designer, customer success, client');
            }

            if (locationInput) {
                locationInput.placeholder = isInterview ? 'e.g. Lagos interview hub, remote' : 'e.g. Lagos, Nigeria';
            }

            if (!isStaff && salaryInput) {
                salaryInput.value = '';
            }

            if (!isInterview && interviewInput) {
                interviewInput.value = '';
            }

            if (!(isStaff && rating > 0 && rating <= 2) && issueInput) {
                issueInput.value = '';
            }
        }

        reviewerType.addEventListener('change', updateCompanyReviewForm);
        form.querySelectorAll('[name="rating"]').forEach(function (radio) {
            radio.addEventListener('change', updateCompanyReviewForm);
        });

        updateCompanyReviewForm();
    })();

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
            var reviewerType = form.querySelector('[name="reviewer_type"]');

            if (!rating) return 'Please select a star rating.';
            if (!name.trim()) return 'Please enter your name.';
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return 'Please enter a valid email.';
            if (reviewerType && !reviewerType.value) return 'Please choose how you know this company.';
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
                '&reviewer_type=' + encodeURIComponent((form.querySelector('[name="reviewer_type"]') || {}).value || '') +
                '&company_role=' + encodeURIComponent((form.querySelector('[name="company_role"]') || {}).value || '') +
                '&company_location=' + encodeURIComponent((form.querySelector('[name="company_location"]') || {}).value || '') +
                '&salary_range=' + encodeURIComponent((form.querySelector('[name="salary_range"]') || {}).value || '') +
                '&interview_stage=' + encodeURIComponent((form.querySelector('[name="interview_stage"]') || {}).value || '') +
                '&experience_issue=' + encodeURIComponent((form.querySelector('[name="experience_issue"]') || {}).value || '') +
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
