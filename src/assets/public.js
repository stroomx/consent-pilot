window.dataLayer = window.dataLayer || [];
function consentpilot_gtag() { dataLayer.push(arguments); }
consentpilot_gtag('consent', 'default', {
  ad_storage: 'denied',
  analytics_storage: 'denied',
  ad_user_data: 'denied',
  ad_personalization: 'denied',
  functionality_storage: 'denied',
  personalization_storage: 'denied',
  security_storage: 'granted'
});
consentpilot_gtag('set', 'url_passthrough', true);
consentpilot_gtag('set', 'ads_data_redaction', true);

jQuery(function ($) {
  const ConsentPilot = (() => {
    const hideBanner = () => $('#consentpilot-banner').hide();
    const showBanner = () => $('#consentpilot-banner').show();
    const showModal = (() => {
      $('#consentpilot-modal').attr('open', '').attr('aria-hidden', 'false');
      $('body').css('overflow','hidden');
    });
    const hideModal = (() => {
      $('#consentpilot-modal').removeAttr('open', '').attr('aria-hidden', 'true');
      $('body').css('overflow','auto');
    });

    const fields = {
      analytics: $('#consentpilot-analytics'),
      marketing: $('#consentpilot-marketing'),
      preferences: $('#consentpilot-preferences')
    };
    
    const updateGoogleConsent = (prefs) => {
      consentpilot_gtag('consent','update', {
        analytics_storage: prefs.analytics ? 'granted' : 'denied',
        ad_storage: prefs.marketing ? 'granted' : 'denied',
        ad_user_data: prefs.marketing ? 'granted' : 'denied',
        ad_personalization: prefs.marketing ? 'granted' : 'denied',
        functionality_storage: prefs.preferences ? 'granted' : 'denied',
        personalization_storage: prefs.preferences ? 'granted' : 'denied',
        security_storage: 'granted',
      });
    };

    const loadTags = (prefs) => {
      if (prefs.analytics || prefs.marketing || prefs.preferences) {
        window.dataLayer.push({'gtm.start': new Date().getTime(), event: 'gtm.js'});
        const gtm = document.createElement('script');
        gtm.src = `https://www.googletagmanager.com/gtm.js?id=${consentpilot.gtm}`;
        gtm.async = true;
        $('head').append(gtm);
      }
    };

    const persist = (prefs) => {
      const match = document.cookie.match(new RegExp('(^| )consentpilot=([^;]+)'));
      const consent = JSON.parse(match?.[2] || '{}');
      // Don't update if no change in preferences
      if (Object.keys(consent).length && Object.keys(consent.prefs).length && (JSON.stringify(consent.prefs) === JSON.stringify(prefs))) {
        return;
      }
      const data = {
        "id": ([1e7]+-1e3+-4e3+-8e3+-1e11).replace(/[018]/g, c =>
          (c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString(16)
        ),
        "prefs": prefs
      }
      // Expire existing cookie
      if (Object.keys(consent).length && Object.keys(consent.id).length) {
        data.expired_id = consent.id;
      }
      
      // Save AJAX
      $.post(`${consentpilot.siteurl}/wp-json/consent-pilot/v1/consent`, data, function(response){
        const expiry = new Date(response.expiry * 1000).toUTCString();  
        // Get path from consentpilot.siteurl
        let path = '/' + consentpilot.siteurl.split('/').pop();
        // Set path to root if it matches the current hostname
        if (path === '/' + window.location.hostname) {
          path = '/';
        }
        // Set the cookie
        document.cookie = `consentpilot=${JSON.stringify(data)};expires=${expiry};path=${path};SameSite=Lax;Secure;`;
      });
    };

    const acceptAll = () => {
      fields.analytics.prop("checked", true);
      fields.marketing.prop("checked", true);
      fields.preferences.prop("checked", true);
      save();
    };

    const rejectAll = () => {
      fields.analytics.prop("checked", false);
      fields.marketing.prop("checked", false);
      fields.preferences.prop("checked", false);
      save();
    };

    const save = () => {
      const prefs = {
        analytics: fields.analytics.is(':checked'),
        marketing: fields.marketing.is(':checked'),
        preferences: fields.preferences.is(':checked')
      };
      hideModal();
      updateGoogleConsent(prefs);
      if (consentpilot.gtm.length) {
        loadTags(prefs);
      }
      persist(prefs);
    };

    const choice = (value) => {
      hideBanner();
      switch (value) {
        case 'granted': return acceptAll();
        case 'essential': return rejectAll();
        case 'save': return save();
        default: showModal();
      }
    };

    const initBanner = () => {
      const match = document.cookie.match(new RegExp('(^| )consentpilot=([^;]+)'));
      const consent = JSON.parse(match?.[2] || '{}');
      if (Object.keys(consent).length && Object.keys(consent.prefs).length) {
        fields.analytics.prop("checked", consent.prefs.analytics);
        fields.marketing.prop("checked", consent.prefs.marketing);
        fields.preferences.prop("checked", consent.prefs.preferences);
        updateGoogleConsent(consent.prefs);
        loadTags(consent.prefs);
        hideBanner();
      } else {
        showBanner();
      }
    }

    try {
      initBanner();
    } catch (e) {
      showBanner();
    }
    
    return {init: initBanner, choice: choice };
    
  })();
  $('.consentpilot button, .consentpilot-preferences').on('click', function() {
    ConsentPilot.choice(this.value);
  });
  ConsentPilot.init();

  function consentpilot_getContrastColor(color) {
    if (color.includes('rgb')) {
      const match = color.match(/\d+/g); // extract all numbers
      var r = parseInt(match[0], 10);
      var g = parseInt(match[1], 10);
      var b = parseInt(match[2], 10);
    } else {
      color = color.replace('#', '');
      // Parse r, g, b values
      var r = parseInt(color.substr(0, 2), 16);
      var g = parseInt(color.substr(2, 2), 16);
      var b = parseInt(color.substr(4, 2), 16);
    }
    // Calculate relative luminance
    const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
    // Return black for light backgrounds, white for dark backgrounds
    return luminance > 0.5 ? '#000000' : '#FFFFFF';
  }

  $('.consentpilot .button-primary').each(function(){
    $(this).css('color', consentpilot_getContrastColor($(this).css('background-color')));
  });

});