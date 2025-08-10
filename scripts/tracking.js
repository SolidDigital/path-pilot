// Path Pilot - Frontend tracking script
class PathPilotTracker {
    constructor() {
        this.sessionId = this.getSessionId();
        this.currentPath = window.location.pathname;
        this.postId = (typeof path_pilot_data !== 'undefined' && path_pilot_data.post_id) ? path_pilot_data.post_id : this.getPostId();
        this.startTime = Date.now();
        this.maxScrollDepth = 0;
        this.trackScrollDepth();
        this.setupEventListeners();
    }

    getSessionId() {
        let sid = localStorage.getItem('path_pilot_sid');
        if (!sid) {
            sid = 'pp_' + Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
            localStorage.setItem('path_pilot_sid', sid);
        }
        return sid;
    }

    getPostId() {
        // Try to get from the body class first (most reliable for both posts and pages)
        const bodyClasses = document.body.className.split(' ');
        for (const cls of bodyClasses) {
            if (cls.startsWith('postid-')) {
                return parseInt(cls.replace('postid-', ''), 10);
            }
        }

        // Try to get post ID from canonical link (works for numeric permalinks)
        const canonical = document.querySelector('link[rel="canonical"]');
        if (canonical) {
            const url = new URL(canonical.href);
            const pathParts = url.pathname.split('/').filter(Boolean);
            if (pathParts.length > 0) {
                const lastPart = pathParts[pathParts.length - 1];
                if (/^\d+$/.test(lastPart)) {
                    return parseInt(lastPart, 10);
                }
            }
        }

        // Check for post ID in the REST API URL pattern
        const restLinks = document.querySelectorAll('link[rel="https://api.w.org/"]');
        if (restLinks.length > 0) {
            const href = restLinks[0].getAttribute('href');
            const match = href.match(/\/wp-json\/wp\/v2\/posts\/(\d+)/);
            if (match && match[1]) {
                return parseInt(match[1], 10);
            }
        }

        // Check for single-post body class (indicates this is a blog post)
        if (bodyClasses.includes('single-post') || bodyClasses.includes('single')) {
            console.log('Path Pilot: Detected blog post but could not determine post ID');
            console.log('Path Pilot: Body classes:', bodyClasses.join(' '));
            console.log('Path Pilot: Current URL:', window.location.href);
            
            // Try one more approach - look for shortlink meta tag
            const shortlink = document.querySelector('link[rel="shortlink"]');
            if (shortlink) {
                const href = shortlink.getAttribute('href');
                const match = href.match(/\?p=(\d+)/);
                if (match && match[1]) {
                    console.log('Path Pilot: Found post ID via shortlink:', match[1]);
                    return parseInt(match[1], 10);
                }
            }
        }

        return 0; // Default if we can't determine post ID
    }

    detectDeviceType() {
        const userAgent = navigator.userAgent;
        // Basic detection of mobile devices
        if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(userAgent)) {
            if (/iPad|Android(?!.*Mobile)/i.test(userAgent) || (window.innerWidth >= 768 && window.innerWidth <= 1024)) {
                return 'tablet';
            }
            return 'mobile';
        }
        return 'desktop';
    }

    getBrowserInfo() {
        const ua = navigator.userAgent;
        let browser = 'unknown';
        let browserVersion = '';
        let os = 'unknown';
        
        // Detect browser
        if (ua.includes('Firefox/')) {
            browser = 'Firefox';
            const match = ua.match(/Firefox\/(\d+\.\d+)/);
            browserVersion = match ? match[1] : '';
        } else if (ua.includes('Chrome/') && !ua.includes('Edg/')) {
            browser = 'Chrome';
            const match = ua.match(/Chrome\/(\d+\.\d+)/);
            browserVersion = match ? match[1] : '';
        } else if (ua.includes('Safari/') && !ua.includes('Chrome/')) {
            browser = 'Safari';
            const match = ua.match(/Version\/(\d+\.\d+)/);
            browserVersion = match ? match[1] : '';
        } else if (ua.includes('Edg/')) {
            browser = 'Edge';
            const match = ua.match(/Edg\/(\d+\.\d+)/);
            browserVersion = match ? match[1] : '';
        } else if (ua.includes('MSIE ') || ua.includes('Trident/')) {
            browser = 'Internet Explorer';
            const match = ua.match(/(?:MSIE |rv:)(\d+\.\d+)/);
            browserVersion = match ? match[1] : '';
        }
        
        // Detect OS
        if (ua.includes('Windows')) {
            os = 'Windows';
        } else if (ua.includes('Macintosh') || ua.includes('Mac OS X')) {
            os = 'macOS';
        } else if (ua.includes('Linux')) {
            os = 'Linux';
        } else if (ua.includes('Android')) {
            os = 'Android';
        } else if (ua.includes('iPhone') || ua.includes('iPad') || ua.includes('iPod')) {
            os = 'iOS';
        }
        
        return {
            browser,
            browserVersion,
            os
        };
    }

    getReferrer() {
        return document.referrer || '';
    }

    isEntrance() {
        // Check if this is an entrance page (no referrer or external referrer)
        const referrer = this.getReferrer();
        return !referrer || !referrer.includes(window.location.hostname);
    }

    trackScrollDepth() {
        window.addEventListener('scroll', () => {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            const scrollHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            
            if (scrollHeight > 0) {
                const scrollDepth = Math.floor((scrollTop / scrollHeight) * 100);
                if (scrollDepth > this.maxScrollDepth) {
                    this.maxScrollDepth = scrollDepth;
                }
            }
        });
    }

    setupEventListeners() {
        // Track when user is about to leave the page
        window.addEventListener('beforeunload', this.trackPageExit.bind(this));
        
        // Track clicks on links
        document.addEventListener('click', (e) => {
            const link = e.target.closest('a');
            if (link) {
                this.trackLinkClick(link);
            }
        });
    }

    trackPageExit() {
        const duration = Math.round((Date.now() - this.startTime) / 1000); // Duration in seconds
        this.trackEvent(duration, 'pageview', {}, true);
    }

    trackLinkClick(link) {
        // Only track internal links
        const url = new URL(link.href, window.location.origin);
        if (url.hostname === window.location.hostname) {
            const metadata = {
                link_text: link.innerText.trim().substring(0, 100),
                link_href: link.href,
                link_position: this.getLinkPosition(link)
            };
            
            const duration = Math.round((Date.now() - this.startTime) / 1000); // Duration in seconds
            this.trackEvent(duration, 'link_click', metadata);
        }
    }
    
    getLinkPosition(element) {
        const rect = element.getBoundingClientRect();
        return {
            x: rect.left + window.scrollX,
            y: rect.top + window.scrollY,
            viewport_x: rect.left,
            viewport_y: rect.top
        };
    }

    trackEvent(duration = 0, event_type = 'pageview', customMetadata = {}, isExit = false) {
        // Check if we should skip tracking on this domain
        if (!this.shouldTrackOnCurrentDomain()) {
            console.log('Path Pilot: Skipping tracking - not on subscription domain');
            return;
        }
        
        const browserInfo = this.getBrowserInfo();
        
        const metadata = {
            ...customMetadata,
            url: window.location.href,
            title: document.title
        };
        
        // Categorize duration for easier analysis
        let durationCategory = 'very_short';
        if (duration >= 5 && duration < 30) {
            durationCategory = 'short';
        } else if (duration >= 30 && duration < 120) {
            durationCategory = 'medium';
        } else if (duration >= 120) {
            durationCategory = 'long';
        }
        
        // Add duration category to metadata
        metadata.duration_category = durationCategory;
        
        // Send the data to the server
        const data = {
            sid: this.sessionId,
            path: this.currentPath,
            post_id: this.postId,
            device_type: this.detectDeviceType(),
            referrer: this.getReferrer(),
            browser_name: browserInfo.browser,
            browser_version: browserInfo.browserVersion,
            os_name: browserInfo.os,
            duration: duration,
            time_on_page: duration,
            entrance: this.isEntrance(),
            exit: isExit,
            scroll_depth: this.maxScrollDepth,
            screen_width: window.screen.width,
            screen_height: window.screen.height,
            viewport_width: window.innerWidth,
            viewport_height: window.innerHeight,
            metadata: metadata
        };

        // Helper for base64 encoding (handles Unicode)
        function toBase64(str) {
            return btoa(unescape(encodeURIComponent(str)));
        }
        const b64Payload = toBase64(JSON.stringify(data));

        // Use the Fetch API to send the data as base64
        fetch(path_pilot_data.rest_url + 'event', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': path_pilot_data.nonce
            },
            body: JSON.stringify({ data: b64Payload })
        })
        .catch(error => console.error('Path Pilot tracking error:', error));
    }

    shouldTrackOnCurrentDomain() {
        // Don't track on localhost and .local domains for development
        const hostname = window.location.hostname;
        if (hostname === 'localhost' || hostname.endsWith('.local')) {
            return false;
        }
        
        // For Pro version, only track on valid subscription domains
        // Domain validation is handled server-side - this is a client-side guard
        // TODO: This could be enhanced to check against a whitelist of valid domains
        // For now, allow tracking on production domains (server will validate)
        return true;
    }
}

// Initialize the tracker when the DOM is fully loaded
document.addEventListener('DOMContentLoaded', () => {
    // Only initialize if path_pilot_data exists (it should be localized by WordPress)
    if (typeof path_pilot_data !== 'undefined') {
        window.pathPilotTracker = new PathPilotTracker();
        window.pathPilotTracker.trackEvent(); // Initial page view
    }
}); 