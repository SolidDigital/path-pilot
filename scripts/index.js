// Path Pilot left-side navigation drawer (vanilla JS)
(function() {

  // Wait for DOM to be ready
  document.addEventListener('DOMContentLoaded', function() {
    initPathPilot();
  });

  function initPathPilot() {
  
  // --- Inject icon font CSS if not present ---
  if (!document.querySelector('link[href*="path-pilot-icons.css"]')) {
    var iconFontLink = document.createElement('link');
    iconFontLink.rel = 'stylesheet';
    iconFontLink.href = (window.PathPilotStatus && window.PathPilotStatus.icon_css_url) || '/wp-content/plugins/path-pilot/assets/css/path-pilot-icons.css';
    document.head.appendChild(iconFontLink);
  }

  // --- Drawer State ---
  let drawerOpen = false;

  // --- Drawer Container ---
  const drawer = document.createElement('div');
  drawer.id = 'path-pilot-drawer';
  drawer.className = 'pp-drawer minimized';
  document.body.appendChild(drawer);

  // --- Drawer Handle (Modern, slim, right-rounded rectangle) ---
  const drawerHandle = document.createElement('button');
  drawerHandle.className = 'pp-drawer-handle';
  drawerHandle.innerHTML = '<i class="icon-pilot-icon"></i>';
  drawer.appendChild(drawerHandle);

  // Beacon effect after 15s, only if drawer is closed
  setTimeout(function() {
    if (!drawer.classList.contains('expanded')) {
      drawerHandle.classList.add('pp-beacon');
    }
  }, 15000);
  drawerHandle.addEventListener('click', function() {
    drawerHandle.classList.remove('pp-beacon');
  });

  // Also remove beacon if drawer is opened by any means
  const observer = new MutationObserver(function() {
    if (drawer.classList.contains('expanded')) {
      drawerHandle.classList.remove('pp-beacon');
    }
  });
  observer.observe(drawer, { attributes: true, attributeFilter: ['class'] });

  // --- Drawer Content ---
  const drawerContent = document.createElement('div');
  drawerContent.className = 'pp-drawer-content';
  drawer.appendChild(drawerContent);

  // --- Recommendations Section (Dynamic) ---
  const recSection = document.createElement('div');
  recSection.className = 'pp-drawer-recommendations';
  recSection.innerHTML = `
    <div class="pp-drawer-title">RECOMMENDED FOR YOU</div>
    <ul class="pp-drawer-rec-list"></ul>
  `;
  const recList = recSection.querySelector('.pp-drawer-rec-list');
  drawerContent.appendChild(recSection);

  // --- Chat functionality is now handled by Path Pilot Pro plugin ---

  // --- Path Pilot Advert/Footer ---
  const footer = document.createElement('div');
  footer.className = 'pp-drawer-footer';
  footer.innerHTML = 'Path Pilot &mdash; <a href="https://pathpilot.app" target="_blank" rel="noopener" class="pp-footer-link">pathpilot.app</a>';
  drawerContent.appendChild(footer);

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
          fetch('/wp-json/path-pilot/v1/rec-click', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ session_id: sid, page_id: rec.page_id })
          });
        }
        window.location.href = rec.url;
      });
      recList.appendChild(li);
    });
  }
  
  function showRecommendationSkeletons(count = 3) {
    const ul = document.querySelector('.pp-drawer-rec-list');
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

    fetch('/wp-json/path-pilot/v1/suggest', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
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
  fetchRecommendations();

  // --- Toggle Drawer ---
  drawerHandle.addEventListener('click', function() {
    drawerOpen = !drawerOpen;
    drawer.classList.toggle('minimized', !drawerOpen);
    drawer.classList.toggle('expanded', drawerOpen);
    if (drawerOpen) {
      document.body.classList.add('pp-drawer-open');
    } else {
      document.body.classList.remove('pp-drawer-open');
    }
  });
  
  } // End of initPathPilot function

})(); 