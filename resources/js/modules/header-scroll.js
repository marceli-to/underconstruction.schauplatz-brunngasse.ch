document.addEventListener('alpine:init', () => {
  Alpine.store('menu', { isOpen: false });

  Alpine.data('headerScroll', () => ({
    scrollY: 0,
    lastScrollY: 0,
    headerVisible: true,
    offset: 300,

    // thresholds
    scrollUpThreshold: 100,
    scrollDownThreshold: 30,

    // accumulators
    upAccum: 0,
    downAccum: 0,

    init() {
      this.scrollY = this.lastScrollY = window.pageYOffset;

      const onScroll = () => {
        this.scrollY = window.pageYOffset;
        const delta = this.scrollY - this.lastScrollY;

        // Keep header visible while menu is open
        if (this.$store.menu.isOpen) {
          this.headerVisible = true;
          this.upAccum = this.downAccum = 0;
          this.lastScrollY = this.scrollY;
          return;
        }

        // Always show near the top
        if (this.scrollY <= this.offset) {
          this.headerVisible = true;
          this.upAccum = this.downAccum = 0;
          this.lastScrollY = this.scrollY;
          return;
        }

        if (delta > 0) {            // scrolling down
          this.downAccum += delta;
          this.upAccum = 0;
          if (this.downAccum >= this.scrollDownThreshold) {
            this.headerVisible = false;
          }
        } else if (delta < 0) {     // scrolling up
          this.upAccum += -delta;
          this.downAccum = 0;
          if (this.upAccum >= this.scrollUpThreshold) {
            this.headerVisible = true;
          }
        }

        this.lastScrollY = this.scrollY;
      };

      // rAF throttle to avoid firing every pixel
      let ticking = false;
      window.addEventListener('scroll', () => {
        if (!ticking) {
          requestAnimationFrame(() => {
            onScroll();
            ticking = false;
          });
          ticking = true;
        }
      }, { passive: true });
    }
  }));

  Alpine.data('menuToggle', () => ({
    get isOpen() { return this.$store.menu.isOpen; },
    toggle() { this.$store.menu.isOpen = !this.$store.menu.isOpen; }
  }));
});
