<!DOCTYPE html>
<html lang="en" class="layout-navbar-fixed layout-wide" dir="ltr" data-skin="default" data-assets-path="{{ asset('assets/') }}/" data-bs-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title>Purchase 3D Model | 3DHub Data Portal</title>
  <link rel="icon" type="image/x-icon" href="{{ asset('assets/') }}/img/favicon/favicon.ico">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&amp;display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('assets/') }}/vendor/fonts/iconify-icons.css">
  <link rel="stylesheet" href="{{ asset('assets/') }}/vendor/libs/pickr/pickr-themes.css">
  <link rel="stylesheet" href="{{ asset('assets/') }}/vendor/css/core.css">
  <link rel="stylesheet" href="{{ asset('assets/') }}/css/demo.css">
  <link rel="stylesheet" href="{{ asset('assets/') }}/css/client-responsive.css">
  <link rel="stylesheet" href="{{ asset('assets/') }}/vendor/css/pages/front-page.css">
  <link rel="stylesheet" href="{{ asset('assets/') }}/vendor/css/pages/front-page-payment.css">
  <script src="{{ asset('assets/') }}/vendor/js/helpers.js"></script>
  <script src="{{ asset('assets/') }}/js/front-config.js"></script>
</head>
<body>
  <script>
    (function() {
      var AUTH_API = (window.TemaDataPortal_API_BASE || window.location.origin);
      var LANDING = window.location.origin + '/html/front-pages/{{ route('landing') }}';
      var removalHandled = false;
      function checkRemoval() {
        return fetch(AUTH_API + '/api/auth/me', { credentials: 'include' })
          .then(function(r) { return r.json(); })
          .then(function(d) {
            if (d && (d.account_removed || d.removal_reason)) {
              if (!removalHandled) {
                removalHandled = true;
                alert(d.message || ('Your account has been removed.' + (d.removal_reason ? (' Reason: ' + d.removal_reason) : '')));
              }
              window.location.href = '/html/front-pages/{{ route('login') }}';
              return false;
            }
            return d;
          });
      }

      checkRemoval().then(function(d) {
        if (!d) return;
        if (!d.loggedIn || (d.role !== 'registered' && d.role !== 'trusted' && d.role !== 'admin')) {
          window.__authRequired = true;
          window.__landingUrl = LANDING + (d.loggedIn ? '?message=login_required' : '');
          function showPurchaseAuthPrompt() {
            var promptEl = document.getElementById('purchase-signin-prompt');
            var linkEl = document.getElementById('purchase-signin-link');
            if (promptEl) promptEl.classList.remove('d-none');
            if (linkEl && window.__landingUrl) linkEl.href = window.__landingUrl;
            var btn = document.getElementById('purchase-with-tokens-btn');
            if (btn) { btn.disabled = true; btn.title = 'Sign in to purchase 3D models.'; }
          }
          if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', showPurchaseAuthPrompt);
          else showPurchaseAuthPrompt();
          return;
        }
        window.__authRole = d.role;
      }).catch(function() {});

      setInterval(function () {
        if (removalHandled) return;
        checkRemoval().catch(function () { });
      }, 60000);
    })();
  </script>

  <nav class="layout-navbar shadow-none py-0">
    <div class="container">
      <div class="navbar navbar-expand-lg landing-navbar px-3 px-md-8">
        <a href="{{ route('landing') }}" class="app-brand-link d-flex py-0 me-4">
          <img src="{{ asset('assets/') }}/img/front-pages/landing-page/3DHub logo1.png" alt="3DHub" style="height: 50px; width: auto; object-fit: contain;" />
          <span class="app-brand-text demo menu-text fw-bold ms-2">3DHub</span>
        </a>
        <div class="ms-auto">
          <a href="{{ route('landing') }}#landingShowCase" class="btn btn-label-secondary me-2">← Showcase</a>
          <a href="payment-page.html" class="btn btn-outline-primary">Reload tokens</a>
        </div>
      </div>
    </div>
  </nav>

  <section class="section-py bg-body first-section-pt">
    <div class="container">
      <div id="purchase-signin-prompt" class="alert alert-info mb-4 d-none" role="alert">
        <a href="#" id="purchase-signin-link">Sign in</a> to purchase 3D models.
      </div>
      <div class="card px-3">
        <div class="card-body p-md-8">
          <h4 class="mb-4">Purchase 3D Model</h4>
          <p class="mb-2 fw-semibold" id="purchase-model-name">—</p>
          <p class="mb-2 text-body-secondary" id="purchase-model-price">—</p>
          <p class="mb-4">Your wallet: <strong id="purchase-wallet-balance">—</strong> tokens. Rate: RM <span id="purchase-rate">2</span> per token.</p>
          <p class="mb-3">
            <a href="payment-page.html" id="purchase-reload-link">Need more tokens? Reload tokens here.</a>
          </p>
          <button type="button" class="btn btn-primary" id="purchase-with-tokens-btn">Purchase with tokens</button>
          <div id="purchase-message" class="alert mt-4 d-none" role="alert"></div>
          <p class="mt-4 mb-0">
            <a href="{{ route('landing') }}#landingShowCase" class="text-body">← Back to Showcase</a>
          </p>
        </div>
      </div>
    </div>
  </section>

  <footer class="landing-footer bg-body footer-text py-4">
    <div class="container text-center">
      <a href="{{ route('landing') }}" class="app-brand-link d-inline-flex align-items-center">
        <img src="{{ asset('assets/') }}/img/front-pages/landing-page/3DHub logo1.png" alt="3DHub" class="footer-3dhub-logo-img" style="height: 48px; width: auto; filter: brightness(4.2) contrast(1.35) drop-shadow(0 0 2px rgba(255,255,255,0.95)) drop-shadow(0 0 6px rgba(255,255,255,0.6));" />
        <span class="text-white fw-bold ms-2">3DHub</span>
      </a>
    </div>
  </footer>

  <script src="{{ asset('assets/') }}/vendor/libs/popper/popper.js"></script>
  <script src="{{ asset('assets/') }}/vendor/js/bootstrap.js"></script>
  <script src="{{ asset('assets/') }}/js/front-main.js"></script>
  <script>
    (function() {
      var API = window.TemaDataPortal_API_BASE || (window.location.protocol + '//' + window.location.host);
      var params = new URLSearchParams(window.location.search);
      var modelId = params.get('id') || params.get('model') || '';
      var rate = 2;
      var modelData = null;
      var walletBalance = null;

      function showMsg(text, isError) {
        var el = document.getElementById('purchase-message');
        if (!el) return;
        el.textContent = text || '';
        el.className = 'alert mt-4 ' + (isError ? 'alert-danger' : 'alert-success');
        el.classList.remove('d-none');
      }
      function hideMsg() {
        var el = document.getElementById('purchase-message');
        if (el) el.classList.add('d-none');
      }

      function loadModel() {
        if (!modelId) { showMsg('Missing model ID in URL. Use ?id=MODEL_ID', true); return; }
        fetch(API + '/api/map-data/' + encodeURIComponent(modelId), { credentials: 'include' })
          .then(function(r) {
            if (!r.ok) throw new Error('Model not found');
            return r.json();
          })
          .then(function(d) {
            modelData = d;
            var nameEl = document.getElementById('purchase-model-name');
            var priceEl = document.getElementById('purchase-model-price');
            if (nameEl) nameEl.textContent = d.title || modelId;
            var tokens = Number(d.purchase_price_tokens || 0);
            if (priceEl) {
              if (tokens > 0) priceEl.textContent = tokens + ' token' + (tokens !== 1 ? 's' : '') + ' (RM ' + (tokens * rate).toFixed(2) + ')';
              else priceEl.textContent = 'This model is not available for token purchase.';
            }
          })
          .catch(function() { showMsg('Could not load 3D model details.', true); });
      }

      function loadWallet() {
        fetch(API + '/api/token/wallet', { credentials: 'include' })
          .then(function(r) { return r.json(); })
          .then(function(d) {
            if (d.success) {
              walletBalance = Number(d.balance || 0);
              rate = Number(d.tokenMyrRate || 2);
              var balanceEl = document.getElementById('purchase-wallet-balance');
              var rateEl = document.getElementById('purchase-rate');
              if (balanceEl) balanceEl.textContent = walletBalance.toFixed(2);
              if (rateEl) rateEl.textContent = rate;
            }
          })
          .catch(function() {
            var balanceEl = document.getElementById('purchase-wallet-balance');
            if (balanceEl) balanceEl.textContent = '—';
          });
      }

      document.addEventListener('DOMContentLoaded', function() {
        loadModel();
        loadWallet();

        var btn = document.getElementById('purchase-with-tokens-btn');
        if (btn) btn.addEventListener('click', function() {
          if (!modelId || !modelData) { showMsg('Model not loaded.', true); return; }
          var tokens = Number(modelData.purchase_price_tokens || 0);
          if (!tokens || tokens <= 0) { showMsg('This model is not available for token purchase.', true); return; }
          hideMsg();
          btn.disabled = true;
          fetch(API + '/api/token/purchase-map-data', {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ mapDataID: modelId })
          })
            .then(function(r) { return r.json(); })
            .then(function(d) {
              btn.disabled = false;
              if (d.success) {
                if (d.alreadyOwned) {
                  showMsg('You already own this 3D model.', false);
                } else {
                  showMsg('Purchase complete. You now own this 3D model.', false);
                  loadWallet();
                }
                return;
              }
              if (d.code === 'INSUFFICIENT_TOKENS' || (d.message && d.message.indexOf('Insufficient') !== -1)) {
                showMsg('Insufficient tokens. Please reload tokens to complete this purchase.', true);
                return;
              }
              showMsg(d.message || 'Purchase failed.', true);
            })
            .catch(function(e) {
              btn.disabled = false;
              showMsg(e.message || 'Network error.', true);
            });
        });
      });
    })();
  </script>
</body>
</html>
