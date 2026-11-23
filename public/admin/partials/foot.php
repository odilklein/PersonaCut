<?php

declare(strict_types=1);
?>
                </div>
                <!-- / Content wrapper -->
                <div class="content-backdrop fade"></div>
            </div>
            <!-- / Layout page -->
        </div>

        <div class="layout-overlay layout-menu-toggle"></div>
        <div class="drag-target"></div>
    </div>
    <!-- / Layout wrapper -->

    <script src="<?= e(asset('vendor/libs/jquery/jquery.js')) ?>"></script>
    <script src="<?= e(asset('vendor/vuexy/bootstrap.bundle.js')) ?>"></script>
    <script src="<?= e(asset('vendor/libs/node-waves/node-waves.js')) ?>"></script>
    <script src="<?= e(asset('vendor/libs/perfect-scrollbar/perfect-scrollbar.js')) ?>"></script>
    <script src="<?= e(asset('vendor/js/menu.js')) ?>"></script>
    <script src="<?= e(asset('js/main.js')) ?>"></script>
    <script src="<?= e(asset('vendor/libs/select2/select2.js')) ?>"></script>
    <script src="<?= e(asset('vendor/libs/@algolia/autocomplete-js.js')) ?>"></script>

    <script>
        window.globalSearchConfig = <?= json_encode([
            'endpoint' => url('api/search.php'),
            'suggestions' => $sugestoesBusca,
        ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    </script>
    <script src="<?= e(asset('js/global-search.js')) ?>"></script>

    <script>
        (function () {
            var KEY = 'personacut-admin-theme';
            var template = document.documentElement.getAttribute('data-template') || 'vertical-menu-template';

            function applyTheme(value) {
                var resolved = value;
                if (value === 'system') {
                    resolved = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches
                        ? 'dark'
                        : 'light';
                }

                document.documentElement.setAttribute('data-bs-theme', resolved);
                document.documentElement.setAttribute('data-theme-pref', value);

                var meta = document.querySelector('meta[name="theme-color"]');
                if (meta) {
                    meta.setAttribute('content', resolved === 'dark' ? '#1a1412' : '#53201c');
                }

                try {
                    localStorage.setItem(KEY, value);
                    localStorage.setItem('templateCustomizer-' + template + '--Theme', value);
                } catch (e) {}

                if (window.Helpers) {
                    window.Helpers.setTheme(value);
                    if (window.Helpers.showActiveTheme) {
                        window.Helpers.showActiveTheme(value, false);
                    }
                }

                var icon = document.getElementById('themeIcon');
                if (icon) {
                    icon.className = 'icon-base ti icon-22px theme-icon-active tabler-'
                        + (resolved === 'dark' ? 'moon-stars' : 'sun');
                }
            }

            document.querySelectorAll('[data-bs-theme-value]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    applyTheme(btn.getAttribute('data-bs-theme-value'));
                });
            });

            document.addEventListener('DOMContentLoaded', function () {
                applyTheme(document.documentElement.getAttribute('data-theme-pref') || 'light');

                if (window.matchMedia) {
                    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function () {
                        var current = 'light';
                        try {
                            current = localStorage.getItem(KEY) || 'light';
                        } catch (e) {}
                        if (current === 'system') {
                            applyTheme('system');
                        }
                    });
                }
            });

            document.querySelectorAll('.toast').forEach(function (t) {
                if (window.bootstrap && bootstrap.Toast) {
                    new bootstrap.Toast(t, { delay: 5000 }).show();
                }
            });
        })();
    </script>
</body>
</html>
