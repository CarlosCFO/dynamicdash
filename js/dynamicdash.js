(function() {

    var ROOT_DOC = (typeof CFG_GLPI !== 'undefined' && CFG_GLPI.root_doc)
                   ? CFG_GLPI.root_doc : '';
    var PLUGIN_URL = ROOT_DOC + '/plugins/dynamicdash/ajax/getcards.php';

    function isTicketListPage() {
        var path = window.location.pathname;
        return path.indexOf('/front/ticket.php') !== -1
            && path.indexOf('/front/ticket.form.php') === -1;
    }

    function getCriteriaFromUrl() {
        var params = new URLSearchParams(window.location.search);
        var criteria = {};
        params.forEach(function(value, key) {
            if (key.indexOf('criteria') === 0
                || key.indexOf('sort') === 0
                || key === 'is_deleted'
                || key === 'as_map'
                || key === 'reset') {
                criteria[key] = value;
            }
        });
        return criteria;
    }

    function fetchCards(callback) {
        var criteria = getCriteriaFromUrl();
        var queryString = new URLSearchParams(criteria).toString();
        var url = PLUGIN_URL + (queryString ? '?' + queryString : '');

        var xhr = new XMLHttpRequest();
        xhr.open('GET', url, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    try {
                        var data = JSON.parse(xhr.responseText);
                        callback(null, data);
                    } catch (e) {
                        callback(e, null);
                    }
                } else {
                    callback(new Error('HTTP ' + xhr.status), null);
                }
            }
        };
        xhr.send();
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    function buildDrilldownUrl(cardKey) {
        var base = ROOT_DOC + '/front/ticket.php';
        var filters = {
            'open':    'criteria[0][field]=12&criteria[0][searchtype]=equals&criteria[0][value]=notclosed',
            'overdue': 'criteria[0][field]=82&criteria[0][searchtype]=equals&criteria[0][value]=1',
            'pending': 'criteria[0][field]=12&criteria[0][searchtype]=equals&criteria[0][value]=4',
            'new':     'criteria[0][field]=12&criteria[0][searchtype]=equals&criteria[0][value]=1',
            'sla':     'criteria[0][field]=12&criteria[0][searchtype]=equals&criteria[0][value]=notclosed',
            'reopened':'criteria[0][field]=12&criteria[0][searchtype]=equals&criteria[0][value]=notclosed',
            'tma':     'criteria[0][field]=12&criteria[0][searchtype]=equals&criteria[0][value]=notclosed',
            'tms':     'criteria[0][field]=12&criteria[0][searchtype]=equals&criteria[0][value]=notold',
            'csat':    'criteria[0][field]=12&criteria[0][searchtype]=equals&criteria[0][value]=notold',
            'aging':   'criteria[0][field]=12&criteria[0][searchtype]=equals&criteria[0][value]=notclosed'
        };
        return filters[cardKey] ? base + '?' + filters[cardKey] : null;
    }

    function renderDashboard(data) {
        var existing = document.getElementById('dynamicdash');
        if (existing) {
            existing.remove();
        }

        if (!data || !data.cards || Object.keys(data.cards).length === 0) {
            return;
        }

        var container = document.createElement('div');
        container.className = 'dynamicdash-container';
        container.id = 'dynamicdash';

        var cardOrder = [
            'open', 'overdue', 'pending', 'new', 'sla',
            'tma', 'tms', 'csat', 'aging', 'reopened'
        ];

        cardOrder.forEach(function(cardKey) {
            if (!data.cards[cardKey]) return;

            var card = data.cards[cardKey];
            var el = document.createElement('div');
            el.className = 'dynamicdash-card';
            el.setAttribute('data-state', card.state);
            el.setAttribute('data-card', cardKey);
            el.setAttribute('data-count',
                String(card.value).replace(/[^0-9]/g, '') || '0');
            el.title = card.detail || card.label;

            el.innerHTML =
                '<span class="dd-value">'
                    + escapeHtml(String(card.value)) +
                '</span>' +
                '<span class="dd-label">'
                    + escapeHtml(card.label) +
                '</span>' +
                '<i class="dd-icon" data-icon="'
                    + escapeHtml(card.icon) +
                '"></i>';

            el.addEventListener('click', function() {
                var url = buildDrilldownUrl(cardKey);
                if (url) {
                    window.open(url, '_blank');
                }
            });

            container.appendChild(el);
        });

        var btnRefresh = document.createElement('button');
        btnRefresh.className = 'dynamicdash-refresh';
        btnRefresh.id = 'dynamicdash-refresh';
        btnRefresh.title = 'Atualizar indicadores';
        btnRefresh.innerHTML = '&#x1F504;';
        btnRefresh.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            refreshDashboard();
        });
        container.appendChild(btnRefresh);

        var target = document.querySelector('.search_page');
        if (target && target.parentNode) {
            target.parentNode.insertBefore(container, target);
        } else {
            var main = document.querySelector('main')
                    || document.getElementById('page');
            if (main) {
                main.insertBefore(container, main.firstChild);
            }
        }
    }

    function refreshDashboard() {
        var btn = document.getElementById('dynamicdash-refresh');
        if (btn) {
            btn.classList.add('dd-loading');
        }

        var criteria = getCriteriaFromUrl();
        criteria['_nocache'] = Date.now();
        var queryString = new URLSearchParams(criteria).toString();
        var url = PLUGIN_URL + '?' + queryString;

        var xhr = new XMLHttpRequest();
        xhr.open('GET', url, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (btn) {
                    btn.classList.remove('dd-loading');
                }
                if (xhr.status === 200) {
                    try {
                        var respdata = JSON.parse(xhr.responseText);
                        if (respdata && respdata.success) {
                            renderDashboard(respdata);
                        }
                    } catch (e) {
                        // silencioso
                    }
                }
            }
        };
        xhr.send();
    }

    function init() {
        if (!isTicketListPage()) {
            return;
        }
        fetchCards(function(err, data) {
            if (!err && data && data.success) {
                renderDashboard(data);
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
