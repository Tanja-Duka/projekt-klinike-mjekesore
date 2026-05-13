// ============================================================
// search.js - AJAX Live Search (navbar)
// ============================================================

$(function () {
    var $input   = $('#navSearch');
    var $results = $('#searchResults');
    if (!$input.length) return;

    var searchTimer = null;

    $input.on('input', function () {
        clearTimeout(searchTimer);
        var q = $(this).val().trim();
        if (q.length < 2) { $results.empty().removeClass('show'); return; }

        searchTimer = setTimeout(function () {
            $.ajax({
                url:     BASE_URL + '/api/search.php',
                method:  'GET',
                data:    { q: q },
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function (data) { renderResults(data); }
            });
        }, 300);
    });

    $input.on('focus', function () {
        if ($results.children().length > 0) $results.addClass('show');
    });

    $(document).on('click', function (e) {
        if (!$(e.target).closest('.navbar-search').length) {
            $results.removeClass('show');
        }
    });

    function renderResults(data) {
        $results.empty();
        var hasDoctors  = data.doctors  && data.doctors.length  > 0;
        var hasServices = data.services && data.services.length > 0;

        if (!hasDoctors && !hasServices) {
            $results.append('<div class="search-empty">Nuk u gjet asgjë.</div>');
            $results.addClass('show');
            return;
        }

        if (hasDoctors) {
            $results.append('<div class="search-group-label">Mjekët</div>');
            $.each(data.doctors, function (i, d) {
                var avatarHtml = d.photo_url
                    ? '<img src="' + esc(d.photo_url) + '" alt="">'
                    : '<span class="search-initials">' + initials(d.name) + '</span>';
                $results.append(
                    '<a href="' + BASE_URL + '/patient/reserve.php?doctor_id=' + encodeURIComponent(d.id) + '" class="search-item">' +
                        '<span class="search-av">' + avatarHtml + '</span>' +
                        '<span class="search-info">' +
                            '<strong>' + esc(d.name) + '</strong>' +
                            '<span>' + esc(d.specialization || '') + '</span>' +
                        '</span>' +
                        '<span class="search-price">' + esc(d.fee_formatted || '') + '</span>' +
                    '</a>'
                );
            });
        }

        if (hasServices) {
            $results.append('<div class="search-group-label">Shërbimet</div>');
            $.each(data.services, function (i, s) {
                $results.append(
                    '<a href="' + BASE_URL + '/public/services.php" class="search-item">' +
                        '<span class="search-icon">' + esc(s.icon || '⚕') + '</span>' +
                        '<span class="search-info">' +
                            '<strong>' + esc(s.name) + '</strong>' +
                            '<span>' + esc(s.category || '') + '</span>' +
                        '</span>' +
                        '<span class="search-price">' + esc(s.price_formatted || '') + '</span>' +
                    '</a>'
                );
            });
        }

        $results.addClass('show');
    }

    function initials(name) {
        return (name || '').split(' ')
            .map(function (w) { return w.charAt(0); })
            .join('').toUpperCase().slice(0, 2);
    }

    function esc(str) {
        return $('<span>').text(str || '').html();
    }
});
