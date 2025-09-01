// Accordion module for Alpine.js
export const AccordionItem = (index) => ({
  open: false,
  resizeObserver: null,
  
  init() {
    // Check if this item should be opened via deep linking
    this.open = this.selected === index;
    
    // Set up resize observer to watch content changes
    this.resizeObserver = new ResizeObserver(() => {
      if (this.open) {
        this.$refs.container.style.maxHeight = this.$refs.container.scrollHeight + 'px';
      }
    });
    
    this.resizeObserver.observe(this.$refs.content);
  },
  
  toggle() {
    // Simply toggle this item's open state independently
    this.open = !this.open;
  },
  
  updateHeight() {
    if (this.open) {
      // Start from 0px to enable animation
      this.$refs.container.style.maxHeight = '0px';
      
      setTimeout(() => {
        if (window.MasonryLayout) {
          window.MasonryLayout.refreshAll();
        }
        
        // Animate to the actual height
        setTimeout(() => {
          this.$refs.container.style.maxHeight = this.$refs.container.scrollHeight + 'px';
        }, 100);

      }, 10);
    } else {
      this.$refs.container.style.maxHeight = '0px';
      setTimeout(() => {
        if (window.MasonryLayout) {
          window.MasonryLayout.refreshAll();
        }
      }, 350);
    }
  }
});

// Make it globally accessible for Alpine.js
window.AccordionItem = AccordionItem;