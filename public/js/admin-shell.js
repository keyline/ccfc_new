(function ($) {
    'use strict';

    $(function () {
        var $body = $('body.ccfc-admin');
        var $wrapper = $body.find('.wrapper').first();
        var $sidebar = $body.find('.main-sidebar');
        var $navigation = $body.find('.sidebar-navigation');
        var $menu = $('#admin-sidebar-menu');
        var $menuToggle = $('[data-widget="pushmenu"]');
        var $search = $('#admin-menu-search');
        var $searchContainer = $search.closest('.sidebar-search');
        var $searchClear = $('.sidebar-search-clear');
        var desktopBreakpoint = 768;
        var searchIsActive = false;

        if (!$body.length) {
            return;
        }

        if (!$('#sidebar-overlay').length) {
            $wrapper.append('<div id="sidebar-overlay" aria-hidden="true"></div>');
        }

        function isMobile() {
            return window.innerWidth < desktopBreakpoint;
        }

        function updateToggleState() {
            var isExpanded = isMobile()
                ? $body.hasClass('sidebar-open')
                : !$body.hasClass('sidebar-collapse');

            $menuToggle.attr('aria-expanded', isExpanded ? 'true' : 'false');
        }

        function adjustDataTables() {
            window.setTimeout(function () {
                if ($.fn.dataTable) {
                    $($.fn.dataTable.tables(true)).DataTable().columns.adjust();
                }
            }, 240);
        }

        function toggleSidebar() {
            if (isMobile()) {
                $body.toggleClass('sidebar-open');
            } else {
                $body.toggleClass('sidebar-collapse');

                try {
                    window.localStorage.setItem(
                        'ccfc-admin-sidebar',
                        $body.hasClass('sidebar-collapse') ? 'collapsed' : 'expanded'
                    );
                } catch (error) {
                    // Browsers may block local storage in private contexts.
                }
            }

            updateToggleState();
            adjustDataTables();
        }

        if (!isMobile()) {
            try {
                if (window.localStorage.getItem('ccfc-admin-sidebar') === 'collapsed') {
                    $body.addClass('sidebar-collapse');
                }
            } catch (error) {
                // The expanded state is a safe default when storage is unavailable.
            }
        }

        $menuToggle.on('click.ccfcAdmin', function (event) {
            event.preventDefault();
            toggleSidebar();
        });

        $(document).on('click.ccfcAdmin', '#sidebar-overlay', function () {
            $body.removeClass('sidebar-open');
            updateToggleState();
        });

        $(document).on('keydown.ccfcAdmin', function (event) {
            if (event.key === 'Escape' && $body.hasClass('sidebar-open')) {
                $body.removeClass('sidebar-open');
                updateToggleState();
                $menuToggle.trigger('focus');
            }
        });

        $menu.find('.has-treeview').each(function () {
            var $item = $(this);
            var $toggle = $item.children('.nav-dropdown-toggle').first();
            var label = $.trim($toggle.find('p').first().clone().children().remove().end().text());

            $toggle.attr({
                'aria-expanded': $item.hasClass('menu-open') ? 'true' : 'false',
                'title': label
            });
        });

        $menu.find('.nav-link:not(.nav-dropdown-toggle)').each(function () {
            var $link = $(this);
            var label = $.trim($link.find('p').first().text());

            if (label) {
                $link.attr('title', label);
            }
        });

        $menu.on('click.ccfcAdmin', '.nav-dropdown-toggle', function (event) {
            event.preventDefault();

            var $toggle = $(this);
            var $item = $toggle.parent('.has-treeview');
            var $submenu = $toggle.next('.nav-treeview');
            var willOpen = !$item.hasClass('menu-open');

            $item.toggleClass('menu-open', willOpen);
            $toggle.attr('aria-expanded', willOpen ? 'true' : 'false');
            $submenu.stop(true, true)[willOpen ? 'slideDown' : 'slideUp'](180);
        });

        $menu.on('click.ccfcAdmin', '.nav-link:not(.nav-dropdown-toggle)', function () {
            if (isMobile()) {
                $body.removeClass('sidebar-open');
                updateToggleState();
            }
        });

        function rememberMenuState() {
            $menu.find('.has-treeview').each(function () {
                $(this).data('ccfc-pre-search-open', $(this).hasClass('menu-open'));
            });
        }

        function restoreMenuState() {
            $menu.find('.has-treeview').each(function () {
                var $item = $(this);
                var wasOpen = $item.data('ccfc-pre-search-open');

                $item.toggleClass('menu-open', !!wasOpen);
                $item.children('.nav-dropdown-toggle').first()
                    .attr('aria-expanded', wasOpen ? 'true' : 'false');
                $item.children('.nav-treeview').first().toggle(!!wasOpen);
                $item.removeData('ccfc-pre-search-open');
            });
        }

        function revealSectionHeaders() {
            $menu.children('.nav-header').each(function () {
                var $header = $(this);
                var $cursor = $header.next();
                var hasVisibleItem = false;

                while ($cursor.length && !$cursor.hasClass('nav-header')) {
                    if ($cursor.hasClass('nav-item') && !$cursor.hasClass('nav-search-hidden')) {
                        hasVisibleItem = true;
                        break;
                    }
                    $cursor = $cursor.next();
                }

                $header.toggleClass('nav-search-hidden', !hasVisibleItem);
            });
        }

        function filterMenu() {
            var query = $.trim($search.val()).toLowerCase();
            var matchCount = 0;

            $searchContainer.toggleClass('has-value', query.length > 0);

            if (!query) {
                if (searchIsActive) {
                    restoreMenuState();
                }

                searchIsActive = false;
                $menu.find('.nav-item, .nav-header').removeClass('nav-search-hidden');
                $navigation.removeClass('search-empty');
                return;
            }

            if (!searchIsActive) {
                rememberMenuState();
                searchIsActive = true;
            }

            $menu.find('.nav-item, .nav-header').addClass('nav-search-hidden');
            $menu.find('.has-treeview').removeClass('menu-open');
            $menu.find('.nav-treeview').hide();

            $menu.find('.nav-link').each(function () {
                var $link = $(this);
                var $label = $link.find('p').first().clone();
                $label.children().remove();
                var label = $.trim($label.text()).toLowerCase();

                if (label.indexOf(query) === -1) {
                    return;
                }

                matchCount += 1;

                var $item = $link.closest('.nav-item');
                $item.removeClass('nav-search-hidden');

                if ($item.hasClass('has-treeview')) {
                    $item.find('.nav-item').removeClass('nav-search-hidden');
                    $item.children('.nav-treeview').show();
                }

                $item.parentsUntil($menu, '.nav-item').each(function () {
                    var $parent = $(this);
                    $parent.removeClass('nav-search-hidden').addClass('menu-open');
                    $parent.children('.nav-treeview').show();
                    $parent.children('.nav-dropdown-toggle').attr('aria-expanded', 'true');
                });
            });

            revealSectionHeaders();
            $navigation.toggleClass('search-empty', matchCount === 0);
        }

        $search.on('input.ccfcAdmin', filterMenu);

        $searchClear.on('click.ccfcAdmin', function () {
            $search.val('');
            filterMenu();
            $search.trigger('focus');
        });

        $(window).on('resize.ccfcAdmin', function () {
            if (isMobile()) {
                $body.removeClass('sidebar-collapse');
            } else {
                $body.removeClass('sidebar-open');

                try {
                    $body.toggleClass(
                        'sidebar-collapse',
                        window.localStorage.getItem('ccfc-admin-sidebar') === 'collapsed'
                    );
                } catch (error) {
                    // Keep the current state if storage cannot be read.
                }
            }
            updateToggleState();
        });

        var $activeLink = $menu.find('.nav-link.active').first();
        if ($activeLink.length) {
            window.setTimeout(function () {
                var navigationTop = $navigation.scrollTop();
                var linkTop = $activeLink.position().top;
                var linkBottom = linkTop + $activeLink.outerHeight();
                var viewportHeight = $navigation.innerHeight();

                if (linkBottom > viewportHeight || linkTop < 0) {
                    $navigation.animate({
                        scrollTop: navigationTop + linkTop - (viewportHeight / 3)
                    }, 180);
                }
            }, 120);
        }

        if ($.fn.dataTable) {
            $('table#example.datatable').each(function () {
                if (!$.fn.dataTable.isDataTable(this)) {
                    $(this).DataTable();
                }
            });
        }

        window.setTimeout(function () {
            $('.autoHideAlert').alert('close');
        }, 5000);

        updateToggleState();
    });
})(jQuery);
