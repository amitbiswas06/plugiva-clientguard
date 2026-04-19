/**
 * Settings Guard UI
 * Disables critical fields when protection is active.
 *
 * @since 1.2.0
 */

(function () {
    "use strict";
    
    if (!window.pcgdSettingsGuard || !pcgdSettingsGuard.protectSiteUrls) {
        return;
    }

    document.addEventListener('DOMContentLoaded', function () {
        var siteUrl = document.getElementById('siteurl');
        var homeUrl = document.getElementById('home');

        if (siteUrl) {
            siteUrl.setAttribute('disabled', 'disabled');
        }

        if (homeUrl) {
            homeUrl.setAttribute('disabled', 'disabled');
        }
    });
})();