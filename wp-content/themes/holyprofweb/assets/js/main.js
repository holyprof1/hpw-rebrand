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
        var reviewContent = form.querySelector('[name="review_content"]');
        var issueLabel = form.querySelector('label[for="experience-issue"]');
        var interviewLabel = form.querySelector('label[for="interview-stage"]');

        function getRating() {
            var selected = form.querySelector('[name="rating"]:checked');
            return selected ? parseInt(selected.value, 10) : 0;
        }

        function toggleField(field, visible) {
            if (!field) return;
            field.hidden = !visible;
            field.classList.toggle('is-visible', !!visible);
        }

        function setSelectOptions(select, options, placeholder) {
            if (!select) return;
            var current = select.value;
            var html = '<option value="">' + placeholder + '</option>';

            options.forEach(function (option) {
                html += '<option value="' + option.value + '">' + option.label + '</option>';
            });

            select.innerHTML = html;

            if (current && options.some(function (option) { return option.value === current; })) {
                select.value = current;
            }
        }

        function updateCompanyReviewForm() {
            var type = reviewerType.value || '';
            var rating = getRating();
            var isStaff = type === 'staff' || type === 'former-staff';
            var isInterview = type === 'interview-candidate';
            var isPartner = type === 'partner-vendor';
            var isCustomer = type === 'customer-client';
            var isAffected = type === 'affected-user' || type === 'scam-reporter';
            var isJobSeeker = type === 'job-seeker';
            var hasType = !!type;
            var showIssue = false;
            var rolePlaceholder = 'e.g. Product Designer, customer success, client';
            var locationPlaceholder = 'e.g. Lagos, Nigeria';
            var reviewPlaceholder = 'Share what it feels like to work with this company, interview here, partner with them, or buy from them...';
            var interviewPlaceholder = 'Optional: e.g. 700k fixed, salary expectation form, no range shared';
            var interviewTitle = 'Salary they asked or pay expectation mentioned';
            var issueTitle = 'What happened?';
            var issueOptions = [
                { value: 'other', label: 'Other issue' }
            ];

            toggleField(contextField, hasType);

            if (isStaff) {
                rolePlaceholder = type === 'former-staff' ? 'e.g. Former product manager' : 'e.g. Backend engineer, operations lead';
                reviewPlaceholder = 'Share what the work, management, pay, pressure, and team culture feel like inside this company...';
                showIssue = rating > 0 && rating <= 2;
                issueOptions = [
                    { value: 'pay', label: 'Pay / benefits issue' },
                    { value: 'management', label: 'Management problem' },
                    { value: 'culture', label: 'Culture / toxic environment' },
                    { value: 'workload', label: 'Workload / burnout' },
                    { value: 'communication', label: 'Communication problem' },
                    { value: 'other', label: 'Other issue' }
                ];
            } else if (isInterview) {
                rolePlaceholder = 'e.g. Product Manager candidate';
                locationPlaceholder = 'e.g. Lagos interview hub, remote';
                reviewPlaceholder = 'Share how the interview felt, what stage you reached, how they communicated, and whether the process looked serious...';
                interviewTitle = 'Salary they asked or expectation they mentioned';
                showIssue = rating > 0 && rating <= 3;
                issueOptions = [
                    { value: 'interview', label: 'Interview process issue' },
                    { value: 'communication', label: 'Communication problem' },
                    { value: 'fraud', label: 'Fraud / scam concern' },
                    { value: 'other', label: 'Other issue' }
                ];
            } else if (isPartner) {
                rolePlaceholder = 'e.g. Agency partner, logistics vendor';
                reviewPlaceholder = 'Share how they behave as a business partner, how they pay, communicate, or handle deals...';
                showIssue = rating > 0 && rating <= 3;
                issueOptions = [
                    { value: 'billing', label: 'Billing / payment issue' },
                    { value: 'communication', label: 'Communication problem' },
                    { value: 'management', label: 'Management problem' },
                    { value: 'fraud', label: 'Fraud / scam concern' },
                    { value: 'other', label: 'Other issue' }
                ];
            } else if (isCustomer) {
                rolePlaceholder = 'e.g. customer, subscriber, account holder';
                reviewPlaceholder = 'Share how the company treated you as a customer, how support responded, and what went wrong or right...';
                showIssue = true;
                issueOptions = [
                    { value: 'support', label: 'Support / service issue' },
                    { value: 'product', label: 'Product / delivery issue' },
                    { value: 'billing', label: 'Billing / payment issue' },
                    { value: 'fraud', label: 'Fraud / scam concern' },
                    { value: 'other', label: 'Other issue' }
                ];
            } else if (isAffected) {
                rolePlaceholder = type === 'scam-reporter' ? 'e.g. victim, witness, reporter' : 'e.g. affected user, community member';
                reviewPlaceholder = 'Explain what happened, what money, access, or trust issue you faced, and what others should watch out for...';
                issueTitle = type === 'scam-reporter' ? 'What scam or red flag did you notice?' : 'What happened?';
                showIssue = true;
                issueOptions = [
                    { value: 'fraud', label: 'Fraud / scam concern' },
                    { value: 'billing', label: 'Billing / payment issue' },
                    { value: 'support', label: 'Support / service issue' },
                    { value: 'communication', label: 'Communication problem' },
                    { value: 'other', label: 'Other issue' }
                ];
            } else if (isJobSeeker) {
                rolePlaceholder = 'e.g. Applicant, graduate trainee';
                reviewPlaceholder = 'Share what you noticed as a job seeker, whether the role felt real, and how they handled the process...';
                interviewTitle = 'Salary or pay range mentioned';
                interviewPlaceholder = 'Optional: e.g. no salary stated, 500k cap, negotiable after probation';
                showIssue = rating > 0 && rating <= 3;
                issueOptions = [
                    { value: 'interview', label: 'Interview / hiring issue' },
                    { value: 'communication', label: 'Communication problem' },
                    { value: 'fraud', label: 'Fraud / scam concern' },
                    { value: 'other', label: 'Other issue' }
                ];
            }

            toggleField(salaryField, isStaff);
            toggleField(interviewField, isInterview || isJobSeeker);
            toggleField(issueField, hasType && showIssue);

            if (roleInput) {
                roleInput.placeholder = rolePlaceholder;
            }

            if (locationInput) {
                locationInput.placeholder = locationPlaceholder;
            }

            if (reviewContent) {
                reviewContent.placeholder = reviewPlaceholder;
            }

            if (interviewInput) {
                interviewInput.placeholder = interviewPlaceholder;
            }

            if (interviewLabel) {
                interviewLabel.textContent = interviewTitle;
            }

            if (issueLabel) {
                issueLabel.textContent = issueTitle;
            }

            setSelectOptions(issueInput, issueOptions, 'Select what best fits');

            if (!isStaff && salaryInput) {
                salaryInput.value = '';
            }

            if (!(isInterview || isJobSeeker) && interviewInput) {
                interviewInput.value = '';
            }

            if (!(hasType && showIssue) && issueInput) {
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
