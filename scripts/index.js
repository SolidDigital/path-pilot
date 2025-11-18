// Path Pilot left-side navigation drawer (vanilla JS)
(function() {

  // Lightweight inline SVG icon helper (Material-style, no extra requests)
  function ppIcon(name) {
    switch (name) {
      case 'compass': // recommendations
        return (
          '<svg class="pp-icon" viewBox="0 0 24 24" aria-hidden="true">' +
            '<circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="2" />' +
            '<polygon points="12,6 8,16 12,13 16,16" fill="currentColor" />' +
          '</svg>'
        );
      case 'info': // page summary
        return (
          '<svg class="pp-icon" viewBox="0 0 24 24" aria-hidden="true">' +
            '<circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="2" />' +
            '<rect x="11" y="10" width="2" height="7" fill="currentColor" />' +
            '<circle cx="12" cy="8" r="1" fill="currentColor" />' +
          '</svg>'
        );
      case 'search': // magnifying glass
        return (
          '<svg class="pp-icon" viewBox="0 0 24 24" aria-hidden="true">' +
            '<circle cx="11" cy="11" r="5.5" fill="none" stroke="currentColor" stroke-width="2" />' +
            '<line x1="15" y1="15" x2="20" y2="20" stroke="currentColor" stroke-width="2" stroke-linecap="round" />' +
          '</svg>'
        );
      case 'dockRight': // drawer closer (desktop)
        return (
          '<svg class="pp-icon" viewBox="0 0 24 24" aria-hidden="true">' +
            '<rect x="4" y="5" width="16" height="14" rx="1.5" ry="1.5" fill="none" stroke="currentColor" stroke-width="2" />' +
            '<rect x="13" y="5" width="7" height="14" fill="currentColor" />' +
          '</svg>'
        );
      case 'close': // mobile close icon
        return (
          '<svg class="pp-icon" viewBox="0 0 24 24" aria-hidden="true">' +
            '<line x1="6" y1="6" x2="18" y2="18" stroke="currentColor" stroke-width="2" stroke-linecap="round" />' +
            '<line x1="18" y1="6" x2="6" y2="18" stroke="currentColor" stroke-width="2" stroke-linecap="round" />' +
          '</svg>'
        );
      default:
        return '';
    }
  }

  // Wait for DOM to be ready
  document.addEventListener('DOMContentLoaded', function() {
    initPathPilot();
  });

  function initPathPilot() {
  
  // --- Check if drawer should be shown ---
  if (window.PathPilotStatus && window.PathPilotStatus.insights_only === true) {
    console.log('Path Pilot: Insights only mode enabled - skipping drawer initialization');
    return; // Exit early if insights only mode is enabled
  }
  
  // --- Inject icon font CSS if not present ---
  if (!document.querySelector('link[href*="path-pilot-icons.css"]')) {
    var iconFontLink = document.createElement('link');
    iconFontLink.rel = 'stylesheet';
    iconFontLink.href = (window.PathPilotStatus && window.PathPilotStatus.icon_css_url) || '/wp-content/plugins/path-pilot/assets/css/path-pilot-icons.css';
    document.head.appendChild(iconFontLink);
  }

    let pathPilotNonce = window.PathPilotStatus ? window.PathPilotStatus.nonce : null;

    function refreshNonce() {
        return fetch('/wp-json/path-pilot/v1/nonce')
            .then(res => res.json())
            .then(data => {
                if (data.nonce) {
                    pathPilotNonce = data.nonce;
                }
                return pathPilotNonce;
            })
            .catch(() => {
                return pathPilotNonce;
            });
    }

    // Refresh nonce immediately to counteract caching
    refreshNonce();

    async function apiFetch(url, options) {
        const doFetch = (nonce) => {
            const headers = {
                ...options.headers,
                'X-WP-Nonce': nonce
            };
            return fetch(url, { ...options, headers });
        };

        let response = await doFetch(pathPilotNonce);

        if (response.status === 403) { // Forbidden, likely a nonce issue
            const newNonce = await refreshNonce();
            response = await doFetch(newNonce);
        }

        return response;
    }

    // --- Sidebar state ---
    function isMobileViewport() {
      if (typeof window === 'undefined') return false;
      if (window.matchMedia) {
        try {
          return window.matchMedia('(max-width: 900px)').matches;
        } catch (e) {
          // fall through
        }
      }
      return window.innerWidth <= 900;
    }
    let isExpanded = false;
    let activeTabId = null;
    const DRAWER_STORAGE_KEY = 'path_pilot_drawer_state';

    function loadDrawerState() {
      try {
        const raw = window.localStorage ? localStorage.getItem(DRAWER_STORAGE_KEY) : null;
        if (!raw) return null;
        return JSON.parse(raw);
      } catch (e) {
        return null;
      }
    }

    function persistDrawerState() {
      try {
        if (!window.localStorage) return;
        // On mobile, do not persist open state so the panel always
        // starts collapsed on each page load.
        if (isMobileViewport()) {
          localStorage.removeItem(DRAWER_STORAGE_KEY);
          return;
        }
        const state = {
          open: isExpanded,
          tab: activeTabId || null
        };
        localStorage.setItem(DRAWER_STORAGE_KEY, JSON.stringify(state));
      } catch (e) {
        // fail silently if storage is unavailable
      }
    }

    // --- Tab configuration (pluggable) ---
    // Free version should only expose the Site Navigation tab.
    // Pro adds Page Summary and Search (chat) tabs.
    const hasProFeatures =
      !!(window.PathPilotPro) ||
      !!(window.PathPilotStatus && (window.PathPilotStatus.has_pro || window.PathPilotStatus.is_pro));

    const TAB_DEFS = [
      {
        id: 'recommendations',
        label: 'Site navigation',
        icon: 'compass',
      },
      // Conditionally include Pro-only tabs
      ...(hasProFeatures
        ? [
            {
              id: 'summary',
              label: 'Page summary',
              icon: 'info',
            },
            {
              id: 'search',
              label: 'Search',
              icon: 'search',
            },
          ]
        : []),
    ];

    // --- Sidebar Container (always visible on the left) ---
    const sidebar = document.createElement('div');
    sidebar.id = 'path-pilot-sidebar';
    sidebar.className = 'pp-sidebar collapsed';
    sidebar.setAttribute('role', 'complementary');
    sidebar.setAttribute('aria-label', 'Path Pilot panel');
    sidebar.setAttribute('aria-hidden', 'true');
    document.body.appendChild(sidebar);
    // Let the theme know to reserve space for the sidebar
    document.body.classList.add('pp-sidebar-present');

    // Inner container to stack brand + nav
    const sidebarInner = document.createElement('div');
    sidebarInner.className = 'pp-sidebar-inner';
    sidebar.appendChild(sidebarInner);

    // --- Brand / top icon (links to pathpilot.app) ---
    const brand = document.createElement('button');
    brand.type = 'button';
    brand.className = 'pp-sidebar-brand';
    brand.innerHTML = '<i class="icon-pilot-icon" aria-hidden="true"></i>';
    brand.setAttribute('title', 'Path Pilot');
    brand.setAttribute('aria-label', 'Open Path Pilot website');
    brand.addEventListener('click', function () {
      try {
        window.open('https://pathpilot.app', '_blank', 'noopener,noreferrer');
      } catch (e) {
        // Fallback: change location if popup blocked
        window.location.href = 'https://pathpilot.app';
      }
    });
    sidebarInner.appendChild(brand);

    // --- Vertical Nav ---
    const nav = document.createElement('nav');
    nav.className = 'pp-sidebar-nav';
    nav.setAttribute('aria-label', 'Path Pilot navigation');
    sidebarInner.appendChild(nav);

    // --- Content panel (appears to the right of the nav when expanded) ---
    const panel = document.createElement('div');
    panel.className = 'pp-sidebar-content';
    sidebar.appendChild(panel);

    // Panel close button (top-right inside drawer)
    const panelClose = document.createElement('button');
    panelClose.type = 'button';
    panelClose.className = 'pp-panel-close';
    panelClose.setAttribute('aria-label', 'Close Path Pilot panel');
    // Desktop uses dockRight icon; mobile uses X/close icon
    panelClose.innerHTML = isMobileViewport() ? ppIcon('close') : ppIcon('dockRight');
    panel.appendChild(panelClose);

    // Panel header title (changes with active tab)
    const panelHeader = document.createElement('div');
    panelHeader.className = 'pp-drawer-title';
    panelHeader.textContent = 'Site navigation';
    panel.appendChild(panelHeader);

    // --- Tab content containers ---
    // Recommendations (Dynamic)
    const recSection = document.createElement('div');
    recSection.className = 'pp-tab-root pp-tab-recommendations';
    panel.appendChild(recSection);

    // Page summary tab root (content managed by its controller / plugin)
    const summarySection = document.createElement('div');
    summarySection.className = 'pp-tab-root pp-tab-summary';
    summarySection.style.display = 'none';
    panel.appendChild(summarySection);

    // Search tab root (Pro chat plugs into this container)
    const searchSection = document.createElement('div');
    searchSection.className = 'pp-tab-root pp-tab-search';
    searchSection.style.display = 'none';
    panel.appendChild(searchSection);

    // Map sections by tab id for pluggable use
    const TAB_SECTIONS = {
      recommendations: recSection,
      summary: summarySection,
      search: searchSection,
    };

    // Build nav buttons from TAB_DEFS
    const tabs = TAB_DEFS.map(def => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'pp-nav-btn';
      btn.setAttribute('data-view', def.id);
      btn.setAttribute('title', def.label);
      btn.setAttribute('aria-label', def.label);
      btn.innerHTML = ppIcon(def.icon);
      nav.appendChild(btn);
      return {
        ...def,
        button: btn,
        section: TAB_SECTIONS[def.id] || null,
      };
    });



    // --- Recommendations Logic ---
    // Client-side tracking of recently visited pages (last 3 distinct)
    function getRecentPageHistory() {
      if (typeof window === 'undefined' || !window.localStorage) return [];
      try {
        const raw = localStorage.getItem('path_pilot_recent_pages');
        if (!raw) return [];
        const parsed = JSON.parse(raw);
        return Array.isArray(parsed) ? parsed : [];
      } catch (e) {
        return [];
      }
    }

    function recordCurrentPageInHistory() {
      if (typeof window === 'undefined' || !window.localStorage) return;
      const path = window.location.pathname || '';
      const url = window.location.href || path || '';
      const title = document.title || url || path || '';

      let history = getRecentPageHistory().filter(p => p.path !== path);
      history.unshift({ path, url, title });
      if (history.length > 3) {
        history = history.slice(0, 3);
      }

      try {
        localStorage.setItem('path_pilot_recent_pages', JSON.stringify(history));
      } catch (e) {
        // Ignore storage failures
      }
    }

    // Record this page as visited as soon as Path Pilot initializes
    recordCurrentPageInHistory();

    function createRecClickHandler(rec) {
      return function (event) {
        if (event && typeof event.preventDefault === 'function') {
          event.preventDefault();
        }
        if (typeof rec.page_id !== 'undefined') {
          const sid = (document.cookie.match(/path_pilot_sid=([^;]+)/) || [])[1] || '';
          apiFetch('/wp-json/path-pilot/v1/rec-click', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({ session_id: sid, page_id: rec.page_id })
          });
        }
        window.location.href = rec.url;
      };
    }

    function buildCardRow(rec, opts) {
      const showScore = !opts || opts.showScore !== false;

      const row = document.createElement('a');
      row.className = 'pp-rec-card-row';
      if (rec.url) {
        row.href = rec.url;
      }

      const title = document.createElement('div');
      title.className = 'pp-rec-card-title';
      title.textContent = rec.title;
      row.appendChild(title);

      const meta = document.createElement('div');
      meta.className = 'pp-rec-card-meta';

      if (showScore && rec.score && rec.score > 0) {
        const percent = document.createElement('span');
        percent.className = 'pp-rec-card-percent';
        percent.textContent = Math.round(rec.score) + '%';
        meta.appendChild(percent);
      }

      row.appendChild(meta);
      row.addEventListener('click', createRecClickHandler(rec));
      return row;
    }

    function buildSimpleListItem(rec, opts) {
      const li = document.createElement('li');
      li.className = 'pp-rec-list-item';
      const row = buildCardRow(rec, opts);
      li.appendChild(row);
      return li;
    }

    function renderRecommendations(recs) {
      recSection.innerHTML = '';
      if (!recs || recs.length === 0) {
        recSection.innerHTML = '<div style="color:#aaa;font-size:0.95em;">No recommendations yet.</div>';
        return;
      }

      const layout = document.createElement('div');
      layout.className = 'pp-rec-layout';
      recSection.appendChild(layout);

      // Group recs by badge where possible
      const suggested = recs.filter(r => r.badge === 'conversion_path');
      const popular = recs.filter(r => r.badge === 'popular');
      const related = recs.filter(r => r.badge === 'related');
      const newest = recs.filter(r => r.badge === 'newest');

      function fallback(source, start, end) {
        if (source.length) return source;
        return recs.slice(start, end);
      }

      const suggestedNext = fallback(suggested, 0, 3);
      // Always ensure "Most Popular" has something to show by falling back
      // to the first few overall recommendations when there are no explicit "popular" items.
      const mostPopular = fallback(popular, 0, 3);
      const trending = fallback(newest, 6, 9);
      const currentPath = window.location && window.location.pathname ? window.location.pathname : '';
      const recentPages = getRecentPageHistory().filter(p => p.path !== currentPath);

      function addListGroup(titleText, items, options = {}) {
        if (!items || !items.length) return;
        const group = document.createElement('section');
        group.className = 'pp-rec-group';
        if (options.isSuggested) {
          group.classList.add('pp-rec-group-suggested');
        }

        const title = document.createElement('div');
        title.className = 'pp-rec-group-title';
        title.textContent = titleText;
        group.appendChild(title);

        if (options.isSuggested) {
          const aiBadge = document.createElement('div');
          aiBadge.className = 'pp-rec-ai-badge';
          aiBadge.innerHTML = '<svg width="30" height="18" viewBox="0 0 12 14" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M11.3997 5.58699C8.86707 4.64902 6.87087 2.65284 5.93292 0.12024C5.87386 -0.04008 5.64675 -0.04008 5.58699 0.12024C4.64902 2.65284 2.65284 4.64904 0.12024 5.58699C-0.04008 5.64605 -0.04008 5.87316 0.12024 5.93292C2.65284 6.87088 4.64904 8.86707 5.58699 11.3997C5.64605 11.56 5.87316 11.56 5.93292 11.3997C6.87088 8.86707 8.86707 6.87087 11.3997 5.93292C11.56 5.87386 11.56 5.64675 11.3997 5.58699Z" fill="#9A9A9A"></path><g transform="translate(10 0)"><path d="M4.27437 2.09465C3.32375 1.74378 2.57487 0.994967 2.22402 0.0442969C2.20223 -0.0147656 2.11574 -0.0147656 2.09465 0.0442969C1.74378 0.994922 0.994967 1.7438 0.0442969 2.09465C-0.0147656 2.11644 -0.0147656 2.20293 0.0442969 2.22402C0.994922 2.57489 1.7438 3.3237 2.09465 4.27437C2.11644 4.33343 2.20293 4.33343 2.22402 4.27437C2.57489 3.32375 3.3237 2.57487 4.27437 2.22402C4.33344 2.20223 4.33344 2.11574 4.27437 2.09465Z" fill="#9A9A9A"></path></g></svg>';
          group.appendChild(aiBadge);
        }

        const ul = document.createElement('ul');
        ul.className = 'pp-rec-list';
        items.forEach(rec => {
          ul.appendChild(buildSimpleListItem(rec, { showScore: !!options.isSuggested }));
        });
        group.appendChild(ul);
        layout.appendChild(group);
      }

      addListGroup('Suggested Next', suggestedNext, { isSuggested: true });
      addListGroup('Most Popular', mostPopular);
      addListGroup('Recently Visited', recentPages);
      addListGroup('Trending', trending);
    }
    
    function showRecommendationSkeletons(count = 3) {
      recSection.innerHTML = '';
      const skeletonWrap = document.createElement('div');
      skeletonWrap.className = 'pp-rec-skeleton-wrap';
      for (let i = 0; i < count; i++) {
        skeletonWrap.innerHTML += `
          <div class="pp-rec-skeleton">
            <div class="pp-skeleton-title"></div>
            <div class="pp-skeleton-synopsis"></div>
            <div class="pp-skeleton-synopsis" style="width: 70%;"></div>
          </div>
        `;
      }
      recSection.appendChild(skeletonWrap);
    }

    function fetchRecommendations() {
      showRecommendationSkeletons(3); // Show 3 skeletons while loading

      apiFetch('/wp-json/path-pilot/v1/suggest', {
        method: 'POST',
        headers: { 
          'Content-Type': 'application/json'
       },
        body: JSON.stringify({
          current: window.location.pathname,
          history: JSON.parse(sessionStorage.getItem('path_pilot_history') || '[]')
        })
      })
      .then(r => r.json())
      .then(data => {
        console.log('Path Pilot: Received recommendations data:', data);
        if (data && data.recommendations) {
          renderRecommendations(data.recommendations);
        } else {
          console.warn('Path Pilot: No recommendations in response');
          renderRecommendations([]);
        }
      })
      .catch(err => {
        console.error('Path Pilot: Error fetching recommendations:', err);
        renderRecommendations([]);
      });
    }
    // Default view: start loading recommendations as soon as Path Pilot initializes
    fetchRecommendations();

    // --- Summary tab logic ---
    let summaryLoaded = false;

    function showSummarySkeletons(count = 3) {
      summarySection.innerHTML = '';
      for (let i = 0; i < count; i++) {
        summarySection.innerHTML += `
          <div class="pp-rec-skeleton">
            <div class="pp-skeleton-title"></div>
            <div class="pp-skeleton-synopsis"></div>
            <div class="pp-skeleton-synopsis" style="width: 70%;"></div>
          </div>
        `;
      }
    }

    async function loadPageSummary() {
      if (summaryLoaded) return;
      summaryLoaded = true;

      const cacheKey = 'path_pilot_summary_' + window.location.pathname;

      // Try localStorage cache first (24h TTL)
      try {
        if (window.localStorage) {
          const raw = localStorage.getItem(cacheKey);
          if (raw) {
            const cached = JSON.parse(raw);
            const ttl = 24 * 60 * 60 * 1000; // 24h
            if (cached && cached.timestamp && (Date.now() - cached.timestamp) < ttl) {
              summarySection.innerHTML = '';

              const pageTitleEl = document.createElement('div');
              pageTitleEl.className = 'pp-summary-page-title';
              pageTitleEl.textContent = document.title || '';
              summarySection.appendChild(pageTitleEl);

              const summaryBody = document.createElement('div');
              summaryBody.className = 'pp-summary-body';
              summaryBody.innerHTML = cached.summary_html || '';
              summarySection.appendChild(summaryBody);

              if (Array.isArray(cached.related_pages) && cached.related_pages.length) {
                const relatedWrap = document.createElement('div');
                relatedWrap.className = 'pp-summary-related';
                relatedWrap.innerHTML = `
                  <div class="pp-summary-related-title">Related pages</div>
                  <ul class="pp-summary-related-list">
                    ${cached.related_pages.map(p => `<li><a href="${p.url}">${p.title}</a></li>`).join('')}
                  </ul>
                `;
                summarySection.appendChild(relatedWrap);
              }
              return;
            }
          }
        }
      } catch (e) {
        // Ignore cache errors and fall back to network
      }

      showSummarySkeletons(3);

      try {
        const res = await fetch('/wp-json/path-pilot/v1/summary', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          credentials: 'same-origin',
          body: JSON.stringify({ path: window.location.pathname })
        });
        const data = await res.json();

        summarySection.innerHTML = '';

        const pageTitleEl = document.createElement('div');
        pageTitleEl.className = 'pp-summary-page-title';
        pageTitleEl.textContent = document.title || '';
        summarySection.appendChild(pageTitleEl);

        if (!data || data.error) {
          summarySection.innerHTML = `<div style="color:#aaa;font-size:0.9rem;">${(data && data.error) || 'Unable to load summary.'}</div>`;
          return;
        }

        const summaryBody = document.createElement('div');
        summaryBody.className = 'pp-summary-body';
        summaryBody.innerHTML = data.summary_html;
        summarySection.appendChild(summaryBody);

        if (Array.isArray(data.related_pages) && data.related_pages.length) {
          const relatedWrap = document.createElement('div');
          relatedWrap.className = 'pp-summary-related';
          relatedWrap.innerHTML = `
            <div class="pp-summary-related-title">Related pages</div>
            <ul class="pp-summary-related-list">
              ${data.related_pages.map(p => `<li><a href="${p.url}">${p.title}</a></li>`).join('')}
            </ul>
          `;
          summarySection.appendChild(relatedWrap);
        }

        // Persist to localStorage cache for 24 hours
        try {
          if (window.localStorage) {
            localStorage.setItem(cacheKey, JSON.stringify({
              timestamp: Date.now(),
              summary_html: data.summary_html,
              related_pages: data.related_pages || []
            }));
          }
        } catch (e) {
          // Ignore storage errors
        }
      } catch (err) {
        console.error('Path Pilot: Error loading page summary:', err);
        summarySection.innerHTML = '<div style="color:#aaa;font-size:0.9rem;">Error loading summary.</div>';
      }
    }

    // helper to update panel title when tab changes
    function setPanelTitle(text) {
      const header = panel.querySelector('.pp-drawer-title');
      if (header) {
        header.textContent = text;
      }
    }

    // --- Expand/Collapse helpers ---
    function expandSidebar() {
      if (isExpanded) return;
      isExpanded = true;
      sidebar.classList.remove('collapsed');
      sidebar.classList.add('expanded');
      sidebar.setAttribute('aria-hidden', 'false');
      document.body.classList.add('pp-sidebar-open');
      persistDrawerState();
    }

    function collapseSidebar() {
      if (!isExpanded) return;

      // If focus is inside the sidebar (e.g., on the close button),
      // move focus out before hiding it from assistive tech.
      try {
        const activeEl = document.activeElement;
        if (activeEl && sidebar.contains(activeEl)) {
          // Prefer focusing the brand button; fall back to body.
          const fallbackTarget = sidebar.querySelector('.pp-sidebar-brand') || document.body;
          if (fallbackTarget && typeof fallbackTarget.focus === 'function') {
            fallbackTarget.focus();
          }
        }
      } catch (e) {
        // Ignore focus errors
      }

      isExpanded = false;
      sidebar.classList.remove('expanded');
      sidebar.classList.add('collapsed');
      sidebar.setAttribute('aria-hidden', 'true');
      document.body.classList.remove('pp-sidebar-open');
      activeTabId = null;
      // Clear active visuals and hide tab sections when collapsed
      tabs.forEach(tab => {
        if (tab.button) {
          tab.button.classList.remove('is-active');
        }
        if (tab.section) {
          tab.section.style.display = 'none';
        }
      });
      persistDrawerState();
    }

    function setActiveTab(tabId) {
      activeTabId = tabId;
      tabs.forEach(tab => {
        const isActive = tab.id === tabId;
        if (tab.button) {
          tab.button.classList.toggle('is-active', isActive);
        }
        if (tab.section) {
          // For active tabs, rely on CSS-defined display (flex/block).
          // For inactive tabs, hide via inline style.
          tab.section.style.display = isActive ? '' : 'none';
        }
      });
      const tabMeta = tabs.find(t => t.id === tabId);
      if (tabMeta) {
        setPanelTitle(tabMeta.label);
      }
    }

    function handleTabClick(tab) {
      if (!isExpanded) {
        setActiveTab(tab.id);
        if (tab.id === 'summary') {
          loadPageSummary();
        }
        expandSidebar();
        return;
      }
      setActiveTab(tab.id);
      if (tab.id === 'summary') {
        loadPageSummary();
      }
      persistDrawerState();
    }

    // --- Nav interactions ---
    tabs.forEach(tab => {
      if (!tab.button) return;
      tab.button.addEventListener('click', function() {
        handleTabClick(tab);
      });
    });

    // Panel close button (top-right)
    panelClose.addEventListener('click', function() {
      collapseSidebar();
    });

    // Close with Escape
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        collapseSidebar();
      }
    });

    // --- Restore persisted state on load (desktop only) ---
    const savedState = isMobileViewport() ? null : loadDrawerState();
    if (savedState && savedState.open) {
      const fallbackId = tabs.length ? tabs[0].id : null;
      const tabId = savedState.tab && tabs.some(t => t.id === savedState.tab)
        ? savedState.tab
        : fallbackId;
      if (tabId) {
        setActiveTab(tabId);
        expandSidebar();
        if (tabId === 'summary') {
          loadPageSummary();
        }
      }
    }
    
  } // End of initPathPilot function

})();