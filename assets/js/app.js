// CareerNavigator - small vanilla JavaScript helpers

document.addEventListener('DOMContentLoaded', function () {

    // Mobile sidebar toggle
    var toggle = document.querySelector('[data-toggle-menu]');
    var nav = document.getElementById('side-nav');
    if (toggle && nav) {
        toggle.addEventListener('click', function () {
            nav.classList.toggle('open');
        });
    }

    // Ask for confirmation before deleting something
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!window.confirm(form.getAttribute('data-confirm'))) {
                event.preventDefault();
            }
        });
    });

    // Interest suggestion chips: click to add the interest to the text field
    var interests = document.getElementById('interests');
    document.querySelectorAll('[data-interest]').forEach(function (chip) {
        chip.addEventListener('click', function () {
            if (!interests) return;
            var value = chip.getAttribute('data-interest');
            var current = interests.value.split(',').map(function (s) { return s.trim(); }).filter(Boolean);
            var exists = current.some(function (s) { return s.toLowerCase() === value.toLowerCase(); });
            if (!exists) {
                current.push(value);
                interests.value = current.join(', ');
            }
        });
    });

    // Live word counter for interview answers
    var answer = document.getElementById('answer');
    var counter = document.getElementById('answer-count');
    if (answer && counter) {
        var update = function () {
            var words = answer.value.trim().split(/\s+/).filter(Boolean).length;
            counter.textContent = words + ' words';
        };
        answer.addEventListener('input', update);
        update();
    }

    // Client-side check that the two password fields match
    document.querySelectorAll('form[data-password-match]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            var p1 = form.querySelector('[name="password"], [name="new_password"]');
            var p2 = form.querySelector('[name="confirm_password"]');
            if (p1 && p2 && p1.value !== p2.value) {
                event.preventDefault();
                alert('The two passwords do not match.');
                p2.focus();
            }
        });
    });
});
