(function () {
    'use strict';

    const $ = document.querySelector.bind(document);
    const $$ = document.querySelectorAll.bind(document);

    const runBtn = $('#ago-run-btn');
    const selectAll = $('#ago-select-all');
    const selectNone = $('#ago-select-none');
    const deactivateCard = $('#ago-deactivate-card');
    const deactivateBtn = $('#ago-deactivate-btn');
    const resultBox = $('#ago-setup-result');

    if (!runBtn || typeof agosetupData === 'undefined') return;

    const t = agosetupData.i18n;

    selectAll.addEventListener('click', function () {
        $$('.ago-task input[type="checkbox"]').forEach(function (cb) { cb.checked = true; });
    });

    selectNone.addEventListener('click', function () {
        $$('.ago-task input[type="checkbox"]').forEach(function (cb) { cb.checked = false; });
    });

    runBtn.addEventListener('click', function () {
        var tasks = {};
        var checked = false;

        $$('.ago-task input[type="checkbox"]').forEach(function (cb) {
            if (cb.checked) {
                tasks[cb.name] = true;
                checked = true;
            }
        });

        if (!checked) {
            resultBox.style.display = 'block';
            resultBox.className = 'error';
            resultBox.textContent = t.noTask;
            return;
        }

        if (tasks.set_timezone) {
            var tzSelect = $('#ago-timezone-select');
            if (tzSelect) {
                tasks.timezone_value = tzSelect.value;
            }
        }

        $$('.ago-task-status').forEach(function (s) {
            s.textContent = '';
            s.className = 'ago-task-status';
        });

        runBtn.classList.add('running');
        runBtn.textContent = t.running;
        resultBox.style.display = 'none';

        fetch(agosetupData.restUrl + '/run', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': agosetupData.nonce,
            },
            body: JSON.stringify(tasks),
        })
        .then(function (r) {
            if (!r.ok) throw new Error(String(r.status));
            return r.json();
        })
        .then(function (data) {
            var results = data.results || {};
            var allOk = true;
            var count = 0;

            Object.keys(results).forEach(function (key) {
                count++;
                var status = document.querySelector('.ago-task-status[data-task="' + key + '"]');
                if (!results[key].ok) allOk = false;
                if (!status) return;

                status.className = 'ago-task-status ' + (results[key].ok ? 'ok' : 'fail');
                status.textContent = results[key].msg;
            });

            resultBox.style.display = 'block';
            if (allOk && count > 0) {
                resultBox.className = 'success';
                resultBox.textContent = t.done.replace('%d', count);
                deactivateCard.style.display = 'block';
            } else {
                resultBox.className = 'error';
                resultBox.textContent = t.issues;
            }
        })
        .catch(function (err) {
            resultBox.style.display = 'block';
            resultBox.className = 'error';
            resultBox.textContent = t.error + ' ' + err.message;
        })
        .finally(function () {
            runBtn.classList.remove('running');
            runBtn.textContent = t.run;
        });
    });

    if (deactivateBtn) {
        deactivateBtn.addEventListener('click', function () {
            deactivateBtn.disabled = true;
            deactivateBtn.textContent = t.deactivating;

            fetch(agosetupData.restUrl + '/deactivate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': agosetupData.nonce,
                },
            })
            .then(function (r) {
                if (!r.ok) throw new Error(String(r.status));
                window.location.href = agosetupData.adminUrl + 'plugins.php?deactivate=true';
            })
            .catch(function () {
                deactivateBtn.disabled = false;
                deactivateBtn.textContent = t.deactivate;
            });
        });
    }
})();
