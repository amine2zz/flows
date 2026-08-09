// script.js

(function () {
    'use strict';

    var secsLeft = SECS_LEFT;
    var serverSecs = 0;

    function parseHms(hms) {
        var p = (hms || '00:00:00').split(':');
        return (parseInt(p[0], 10) || 0) * 3600
             + (parseInt(p[1], 10) || 0) * 60
             + (parseInt(p[2], 10) || 0);
    }

    function syncServerClock(hms) {
        serverSecs = parseHms(hms);
    }

    function fmtServerClock() {
        var h = Math.floor(serverSecs / 3600) % 24;
        var m = Math.floor((serverSecs % 3600) / 60);
        var s = serverSecs % 60;
        return (h < 10 ? '0' : '') + h + ':'
             + (m < 10 ? '0' : '') + m + ':'
             + (s < 10 ? '0' : '') + s;
    }

    function fmt(s) {
        var m = Math.floor(s / 60);
        var sec = s % 60;
        return (m < 10 ? '0' : '') + m + ' min ' + (sec < 10 ? '0' : '') + sec + ' sec';
    }

    function tick() {
        if (secsLeft <= 0) { window.location.reload(); return; }
        var t = fmt(secsLeft);
        var el = document.getElementById('countdown');
        if (el) el.textContent = t;
        var el2 = document.getElementById('countdownNotice');
        if (el2) el2.textContent = t;
        var st = document.getElementById('serverTime');
        if (st) st.textContent = fmtServerClock();
        serverSecs = (serverSecs + 1) % 86400;
        secsLeft--;
    }

    var initialServer = document.getElementById('serverTime');
    if (initialServer) syncServerClock(initialServer.textContent.trim());
    tick();
    setInterval(tick, 1000);

// ASCII-only section markers (avoid encoding issues on some hosts)
    // --- City accordion ---
    document.querySelectorAll('.city-header').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var body     = btn.nextElementSibling;
            var expanded = btn.getAttribute('aria-expanded') === 'true';
            btn.setAttribute('aria-expanded', expanded ? 'false' : 'true');
            if (expanded) {
                body.classList.remove('is-open');
                btn.querySelector('.city-arrow').innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-chevron"><polyline points="6 9 12 15 18 9"></polyline></svg>';
            } else {
                body.classList.add('is-open');
                btn.querySelector('.city-arrow').innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-chevron up"><polyline points="18 15 12 9 6 15"></polyline></svg>';
                // Smooth scroll to city body on mobile
                if (window.innerWidth <= 820) {
                    setTimeout(function() {
                        body.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }, 100);
                }
                // Load history for first place in city
                var firstRow = body.querySelector('.place-row');
                if (firstRow && histPanelInner) {
                    var placeId = parseInt(firstRow.dataset.id, 10);
                    var nameEl = firstRow.querySelector('.row-name');
                    var placeName = nameEl ? nameEl.textContent.trim() : 'Lieu';
                    loadPanelHistory(placeId, placeName);
                }
            }
        });
    });

    // --- Search ---
    var searchInput = document.getElementById('searchInput');
    var searchClear = document.getElementById('searchClear');

    function doSearch() {
        var q = searchInput.value.trim().toLowerCase();
        searchClear.style.display = q ? 'inline' : 'none';

        document.querySelectorAll('.city-block').forEach(function (cityBlock) {
            var cityName   = cityBlock.dataset.city || '';
            var rows       = cityBlock.querySelectorAll('.place-row');
            var cityMatch  = cityName.indexOf(q) !== -1;
            var anyVisible = false;

            rows.forEach(function (row) {
                var placeName = row.dataset.place || '';
                var show = !q || cityMatch || placeName.indexOf(q) !== -1;
                row.style.display = show ? '' : 'none';
                if (show) anyVisible = true;
            });

            cityBlock.style.display = (!q || anyVisible) ? '' : 'none';

            if (q && anyVisible) {
                var header = cityBlock.querySelector('.city-header');
                var body   = cityBlock.querySelector('.city-body');
                header.setAttribute('aria-expanded', 'true');
                body.classList.add('is-open');
                header.querySelector('.city-arrow').innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-chevron up"><polyline points="18 15 12 9 6 15"></polyline></svg>';
            }
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', doSearch);
        searchClear.addEventListener('click', function () {
            searchInput.value = '';
            doSearch();
            searchInput.focus();
        });
    }

    // --- Vote submission ---
    var voting = false;

    window.showVotedMessage = function() {
        var t = document.getElementById('toastMsg');
        if (!t) {
            t = document.createElement('div');
            t.id = 'toastMsg';
            t.className = 'toast-msg';
            document.body.appendChild(t);
        }
        t.textContent = "Vous ne pouvez voter qu'une seule fois toutes les 15 minutes.";
        t.classList.add('show');
        setTimeout(function() { t.classList.remove('show'); }, 3000);
    };

    window.castVote = function (placeId, value) {
        if (voting) return;
        if (HAS_VOTED_SLOT) {
            showVotedMessage();
            return;
        }
        voting = true;

        // Disable ALL vote buttons immediately
        document.querySelectorAll('.rbtn').forEach(function (b) { b.disabled = true; });

        fetch('vote.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body:    'vote=' + encodeURIComponent(value) + '&place_id=' + placeId
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.success) {
                HAS_VOTED_SLOT  = true;
                MY_VOTED_PLACE  = placeId;
                MY_VOTE_VALUE   = value;
                markVotedRow(placeId, value);
                lockAllOtherRows(placeId);
                refreshPlace(placeId);
            } else if (data.duplicate) {
                HAS_VOTED_SLOT = true;
                showVotedMessage();
                lockAllOtherRows(MY_VOTED_PLACE || placeId);
            } else {
                // Re-enable on real error
                document.querySelectorAll('.rbtn').forEach(function (b) { b.disabled = false; });
                voting = false;
            }
        })
        .catch(function () {
            document.querySelectorAll('.rbtn').forEach(function (b) { b.disabled = false; });
            voting = false;
        });
    };

    function markVotedRow(placeId, value) {
        var row = document.getElementById('card-' + placeId);
        if (!row) return;
        var btnsDiv = row.querySelector('.row-btns');
        if (btnsDiv) {
            var span = document.createElement('span');
            span.className   = 'row-voted ' + (value === 'working' ? 'rv-yes' : 'rv-no');
            var svg = value === 'working' ? '<svg class="icon-sm" viewBox="0 0 24 24" fill="#10B981" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="16 8 10 14 6 10"></polyline></svg>' : '<svg class="icon-sm" viewBox="0 0 24 24" fill="#EF4444" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>';
            span.innerHTML   = svg + (value === 'working' ? ' Marche' : ' Ne marche pas');
            btnsDiv.parentNode.replaceChild(span, btnsDiv);
        }
        row.classList.remove('row--yes', 'row--no');
        row.classList.add(value === 'working' ? 'row--yes' : 'row--no');
    }

    function lockAllOtherRows(votedPlaceId) {
        document.querySelectorAll('.place-row').forEach(function (row) {
            var pid = parseInt(row.dataset.id, 10);
            if (pid === votedPlaceId) return;
            var btnsDiv = row.querySelector('.row-btns');
            if (btnsDiv) {
                var span = document.createElement('span');
                span.className   = 'row-locked';
                span.title       = 'Deja vote. Attendez 15min.';
                span.onclick     = showVotedMessage;
                span.innerHTML   = '<svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>';
                btnsDiv.parentNode.replaceChild(span, btnsDiv);
            }
        });
    }

    // --- Live results ---
    function applyCount(pid, d) {
        var pctW = document.getElementById('pctW-' + pid);
        var pctN = document.getElementById('pctN-' + pid);
        var tot  = document.getElementById('tot-'  + pid);
        var bars = document.getElementById('bars-' + pid);
        if (pctW) pctW.textContent = d.pw + '%';
        if (pctN) pctN.textContent = d.pn + '%';
        if (tot)  tot.textContent  = d.tot + 'v';
        if (bars) {
            var track = bars.querySelector('.row-bar-track');
            var fill  = bars.querySelector('.row-bar-fill');
            if (fill) fill.style.width = d.pw + '%';
            if (track) {
                if (d.tot > 0) track.classList.add('has-votes');
                else           track.classList.remove('has-votes');
            }
        }
    }

    function refreshPlace(placeId) {
        fetch('batch_results.php', { cache: 'no-store' })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.error) return;
            var st = document.getElementById('serverTime');
            if (st) st.textContent = data.server_time;
            if (data.server_time) syncServerClock(data.server_time);
            secsLeft = data.secs_left;
            if (data.counts && data.counts[placeId]) applyCount(placeId, data.counts[placeId]);
        })
        .catch(function () {});
    }

    function pollAll() {
        if (document.hidden) return;
        fetch('batch_results.php')
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.error) return;
            var st = document.getElementById('serverTime');
            if (st) st.textContent = data.server_time;
            if (data.server_time) syncServerClock(data.server_time);
            secsLeft = data.secs_left;
            if (!data.counts) return;
            document.querySelectorAll('.place-row').forEach(function (row) {
                var pid = parseInt(row.dataset.id, 10);
                if (data.counts[pid]) applyCount(pid, data.counts[pid]);
            });
        })
        .catch(function () {});
    }

    // Poll every 8s; skip when tab hidden
    pollAll();
    setInterval(pollAll, 8000);
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) pollAll();
    });

    // --- Statistics ---
    function loadStats() {
        fetch('stats.php', { cache: 'no-store' })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.error) return;
            var st = document.getElementById('statsTime');
            if (st) st.textContent = 'Mis a jour ' + data.server_time;

            var el = document.getElementById('statTotalVotes');
            if (el) el.textContent = data.total_votes;

            var el = document.getElementById('statAllTimeVotes');
            if (el) el.textContent = data.all_time_total_votes;

            var el = document.getElementById('statWorkingVotes');
            if (el) el.textContent = data.all_time_working_votes;

            var el = document.getElementById('statNotWorkingVotes');
            if (el) el.textContent = data.all_time_not_working_votes;

            var el = document.getElementById('statPlacesWithVotes');
            if (el) el.textContent = data.places_with_votes;

            var el = document.getElementById('statPlacesWorking');
            if (el) el.textContent = data.places_working;

            var el = document.getElementById('statTotalPlaces');
            if (el) el.textContent = data.total_places;

            var el = document.getElementById('statCoverage');
            if (el) el.textContent = data.coverage_pct + '%';
        })
        .catch(function () {});
    }

    loadStats();
    setInterval(loadStats, 10000);

    // --- Suggest a place ---
    var suggestToggle = document.getElementById('suggestToggle');
    var suggestForm   = document.getElementById('suggestForm');
    var suggestSend   = document.getElementById('suggestSend');
    var suggestMsg    = document.getElementById('suggestMsg');

    if (suggestToggle && suggestForm) {
        suggestToggle.addEventListener('click', function () {
            var isOpen = suggestForm.classList.contains('is-open');
            if (isOpen) {
                suggestForm.classList.remove('is-open');
                suggestToggle.textContent = '+ Suggerer un lieu manquant';
            } else {
                suggestForm.classList.add('is-open');
                suggestToggle.textContent = '- Fermer';
            }
        });
    }

    if (suggestSend) {
        suggestSend.addEventListener('click', function () {
            var city = document.getElementById('suggestCity').value.trim();
            var name = document.getElementById('suggestName').value.trim();
            if (!city || !name) {
                suggestMsg.textContent = 'Veuillez remplir la ville et le lieu.';
                suggestMsg.className   = 'suggest-msg error';
                return;
            }
            suggestSend.disabled = true;
            fetch('suggest.php', {
                method:  'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body:    'city=' + encodeURIComponent(city) + '&name=' + encodeURIComponent(name)
            })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                suggestMsg.textContent = d.message;
                suggestMsg.className   = 'suggest-msg ' + (d.success ? 'success' : 'error');
                if (d.success) {
                    document.getElementById('suggestCity').value = '';
                    document.getElementById('suggestName').value = '';
                }
                suggestSend.disabled = false;
            })
            .catch(function () {
                suggestMsg.textContent = 'Erreur reseau.';
                suggestMsg.className   = 'suggest-msg error';
                suggestSend.disabled   = false;
            });
        });
    }

    // --- History panel + modal ---
    var histPanelInner = document.getElementById('histPanelInner');
    var activeHistPlaceId = null;
    var activeHistPlaceName = '';

    function historyRowsHtml(history) {
        var html = '<ul class="hist-list">';
        history.forEach(function (row) {
            var cls   = row.status === 'working' ? 'status-ok' : (row.status === 'not_working' ? 'status-ko' : 'status-unk');
            var label = row.status === 'working' ? 'Marche' : (row.status === 'not_working' ? 'Ne marche pas' : '?');
            html += '<li class="hist-item">'
                  + '<span class="hist-period">' + row.period + '</span>'
                  + '<span class="badge ' + cls + '">' + label + '</span>'
                  + '<span class="hist-votes">' + row.working + '/' + row.not_working + '</span>'
                  + '</li>';
        });
        html += '</ul>';
        return html;
    }

    function tableHtml(history) {
        var html = '<div class="table-wrap"><table><thead><tr>'
                 + '<th>Creneau</th><th>Resultat</th><th>Marche</th><th>Ne marche pas</th><th>Total</th>'
                 + '</tr></thead><tbody>';
        history.forEach(function (row) {
            var cls   = row.status === 'working' ? 'status-ok' : (row.status === 'not_working' ? 'status-ko' : 'status-unk');
            var label = row.status === 'working' ? 'Disponible' : (row.status === 'not_working' ? 'Coupee' : 'Inconnu');
            html += '<tr>'
                  + '<td class="td-period">' + row.period + '</td>'
                  + '<td><span class="badge ' + cls + '">' + label + '</span></td>'
                  + '<td class="td-num">' + row.working + '</td>'
                  + '<td class="td-num">' + row.not_working + '</td>'
                  + '<td class="td-num">' + row.total + '</td>'
                  + '</tr>';
        });
        html += '</tbody></table></div>';
        return html;
    }

    function loadPanelHistory(placeId, placeName) {
        if (!histPanelInner) return;
        activeHistPlaceId   = placeId;
        activeHistPlaceName = placeName;

        document.querySelectorAll('.place-row.is-selected').forEach(function (r) {
            r.classList.remove('is-selected');
        });
        var selected = document.getElementById('card-' + placeId);
        if (selected) selected.classList.add('is-selected');

        histPanelInner.innerHTML =
            '<div class="hist-panel-head">'
          +   '<strong class="hist-panel-title">' + placeName + '</strong>'
          +   '<span class="hist-panel-sub">Derniers creneaux</span>'
          + '</div>'
          + '<p class="loading">Chargement...</p>';

        fetch('results.php?place_id=' + placeId + '&limit=5&hours=6', { cache: 'no-store' })
        .then(function (r) { return r.json(); })
        .then(function (d) {
            if (activeHistPlaceId !== placeId) return;
            var body = '<div class="hist-panel-head">'
                     +   '<strong class="hist-panel-title">' + placeName + '</strong>'
                     +   '<span class="hist-panel-sub">Derniers creneaux</span>'
                     + '</div>';

            if (!d.history || d.history.length === 0) {
                body += '<p class="no-data">Aucun historique recent.</p>';
            } else {
                body += historyRowsHtml(d.history);
            }

            body += '<button type="button" class="btn-more-hist" id="btnMoreHist">Plus d\'historique</button>';
            histPanelInner.innerHTML = body;

            var moreBtn = document.getElementById('btnMoreHist');
            if (moreBtn) {
                moreBtn.addEventListener('click', function () {
                    openModal(placeId, placeName);
                });
            }
        })
        .catch(function () {
            if (activeHistPlaceId !== placeId) return;
            histPanelInner.innerHTML =
                '<div class="hist-panel-head">'
              +   '<strong class="hist-panel-title">' + placeName + '</strong>'
              + '</div>'
              + '<p class="no-data">Erreur de chargement.</p>';
        });
    }

    document.querySelectorAll('.row-name').forEach(function (el) {
        el.addEventListener('click', function () {
            var row     = el.closest('.place-row');
            var placeId = parseInt(row.dataset.id, 10);
            var nameEl  = el.cloneNode(true);
            var svg     = nameEl.querySelector('.icon-history');
            if (svg) svg.remove();
            openModal(placeId, nameEl.textContent.trim());
        });
    });

    var modal        = document.getElementById('histModal');
    var modalOverlay = document.getElementById('modalOverlay');
    var modalTitle   = document.getElementById('modalTitle');
    var modalBody    = document.getElementById('modalBody');
    var modalClose   = document.getElementById('modalClose');

    function openModal(placeId, placeName) {
        if (!modal) return;
        modalTitle.textContent = placeName + ' - 24 dernieres heures';
        modalBody.innerHTML    = '<p class="loading">Chargement...</p>';
        modal.classList.add('is-open');
        modalOverlay.classList.add('is-open');
        document.body.style.overflow = 'hidden';

        fetch('results.php?place_id=' + placeId + '&limit=48&hours=24', { cache: 'no-store' })
        .then(function (r) { return r.json(); })
        .then(function (d) {
            if (!d.history || d.history.length === 0) {
                modalBody.innerHTML = '<p class="no-data">Aucune donnee pour les 24 dernieres heures.</p>';
                return;
            }
            modalBody.innerHTML = tableHtml(d.history);
        })
        .catch(function () {
            modalBody.innerHTML = '<p class="no-data">Erreur de chargement.</p>';
        });
    }

    function closeModal() {
        if (!modal) return;
        modal.classList.remove('is-open');
        modalOverlay.classList.remove('is-open');
        document.body.style.overflow = '';
    }

    if (modalClose)   modalClose.addEventListener('click', closeModal);
    if (modalOverlay) modalOverlay.addEventListener('click', closeModal);

})();
