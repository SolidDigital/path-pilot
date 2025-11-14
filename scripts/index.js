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
      case 'dockRight': // drawer closer
        return (
          '<svg class="pp-icon" viewBox="0 0 24 24" aria-hidden="true">' +
            '<rect x="4" y="5" width="16" height="14" rx="1.5" ry="1.5" fill="none" stroke="currentColor" stroke-width="2" />' +
            '<rect x="13" y="5" width="7" height="14" fill="currentColor" />' +
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
    const TAB_DEFS = [
      {
        id: 'recommendations',
        label: 'Recommendations',
        icon: 'compass',
      },
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
    panelClose.innerHTML = ppIcon('dockRight');
    panel.appendChild(panelClose);

    // Panel header title (changes with active tab)
    const panelHeader = document.createElement('div');
    panelHeader.className = 'pp-drawer-title';
    panelHeader.textContent = 'Recommendations';
    panel.appendChild(panelHeader);

    // --- Tab content containers ---
    // Recommendations (Dynamic)
    const recSection = document.createElement('div');
    recSection.className = 'pp-tab-root pp-tab-recommendations';
    const recList = document.createElement('ul');
    recList.className = 'pp-drawer-rec-list';
    recSection.appendChild(recList);
    panel.appendChild(recSection);

    // Page summary (placeholder for now)
    const summarySection = document.createElement('div');
    summarySection.className = 'pp-tab-root pp-tab-summary';
    summarySection.style.display = 'none';
    summarySection.innerHTML = '<div style="color:#aaa;font-size:0.9rem;">Page summary coming soon.</div>';
    panel.appendChild(summarySection);

    // Search (placeholder for now)
    const searchSection = document.createElement('div');
    searchSection.className = 'pp-tab-root pp-tab-search';
    searchSection.style.display = 'none';
    searchSection.innerHTML = '<div style="color:#aaa;font-size:0.9rem;">Search coming soon.</div>';
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
    function renderRecommendations(recs) {
      recList.innerHTML = '';
      if (!recs || recs.length === 0) {
        recList.innerHTML = '<li style="color:#aaa;font-size:0.95em;">No recommendations yet.</li>';
        return;
      }
      recs.forEach(function(rec) {
        const li = document.createElement('li');
        // Title row with title, percent, and badge(s) horizontally aligned
        const titleRow = document.createElement('div');
        titleRow.className = 'pp-drawer-rec-title-row';
        
        // New meta container for badge and percent
        const recMeta = document.createElement('div');
        recMeta.className = 'pp-rec-meta';

        // Badge pill
        if (rec.badge) {
          const badge = document.createElement('span');
          badge.className = 'pp-drawer-rec-badge pp-badge-' + rec.badge;
          if (rec.badge === 'conversion_path') badge.textContent = 'Recommended';
          else if (rec.badge === 'popular') badge.textContent = 'Popular';
          else if (rec.badge === 'related') badge.textContent = 'Related';
          else if (rec.badge === 'newest') badge.textContent = 'Newest';
          recMeta.appendChild(badge);
        }
        // Percentage (small, subtle, dark gray, no pill)
        if (rec.score && rec.score > 0) {
          const percent = document.createElement('span');
          percent.className = 'pp-drawer-rec-percent';
          percent.textContent = Math.round(rec.score) + '%';
          recMeta.appendChild(percent);
        }
        
        // Append meta only if it has content
        if (recMeta.children.length > 0) {
          titleRow.appendChild(recMeta);
        }
        
        // Title
        const title = document.createElement('span');
        title.className = 'pp-drawer-rec-title';
        title.textContent = rec.title;
        titleRow.appendChild(title);

        li.appendChild(titleRow);
        // Add synopsis under the title
        if (rec.synopsis) {
          const synopsis = document.createElement('div');
          synopsis.className = 'pp-drawer-rec-synopsis';
          synopsis.textContent = rec.synopsis;
          li.appendChild(synopsis);
        }
        li.style.cursor = 'pointer';
        li.addEventListener('click', function() {
          // Track click
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
        });
        recList.appendChild(li);
      });
    }
    
    function showRecommendationSkeletons(count = 3) {
      const ul = panel.querySelector('.pp-drawer-rec-list');
      if (!ul) return;
      ul.innerHTML = '';
      for (let i = 0; i < count; i++) {
        ul.innerHTML += `
          <li class="pp-rec-skeleton">
            <div class="pp-skeleton-title"></div>
            <div class="pp-skeleton-synopsis"></div>
            <div class="pp-skeleton-synopsis" style="width: 70%;"></div>
          </li>
        `;
      }
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
    // Default view shows recs when panel first expands

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
      // Load data on first open
      if (!panel.dataset.loaded) {
        fetchRecommendations();
        panel.dataset.loaded = '1';
      }
      persistDrawerState();
    }

    function collapseSidebar() {
      if (!isExpanded) return;
      isExpanded = false;
      sidebar.classList.remove('expanded');
      sidebar.classList.add('collapsed');
      sidebar.setAttribute('aria-hidden', 'true');
      document.body.classList.remove('pp-sidebar-open');
      activeTabId = null;
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
          tab.section.style.display = isActive ? 'block' : 'none';
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
        expandSidebar();
        return;
      }
      setActiveTab(tab.id);
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

    // --- Restore persisted state on load ---
    const savedState = loadDrawerState();
    if (savedState && savedState.open) {
      const fallbackId = tabs.length ? tabs[0].id : null;
      const tabId = savedState.tab && tabs.some(t => t.id === savedState.tab)
        ? savedState.tab
        : fallbackId;
      if (tabId) {
        setActiveTab(tabId);
        expandSidebar();
      }
    }
    
  } // End of initPathPilot function

})();