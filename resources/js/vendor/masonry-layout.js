export const MasonryLayout = (function() {
  'use strict';

  // Private variables
  const instances = new Map();
  let resizeTimeout;

  // Private methods
  function layoutMasonryResponsive(container, itemSelector, options = {}) {
    const {
      minColumnWidth = 200,
      maxColumns = Infinity,
      gutter = 10
    } = options;

    const items = Array.from(container.querySelectorAll(itemSelector));
    const containerWidth = container.clientWidth;

    // Calculate column count with limits
    const columnCount = Math.min(
      maxColumns,
      Math.max(1, Math.floor(containerWidth / (minColumnWidth + gutter)))
    );

    const columnWidth = (containerWidth - (gutter * (columnCount - 1))) / columnCount;
    const columnHeights = new Array(columnCount).fill(0);

    items.forEach(item => {
      item.style.width = `${columnWidth}px`;

      const minCol = columnHeights.indexOf(Math.min(...columnHeights));
      const x = minCol * (columnWidth + gutter);
      const y = columnHeights[minCol];

      item.style.transform = `translate(${x}px, ${y}px)`;

      columnHeights[minCol] += item.offsetHeight + gutter;
    });

    container.style.height = `${Math.max(...columnHeights)}px`;
  }

  function handleResize() {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(() => {
      instances.forEach(instance => {
        instance.layout();
      });
    }, 100);
  }

  // Public API
  return {
    init(selector, options = {}) {
      const container = typeof selector === 'string' 
        ? document.querySelector(selector) 
        : selector;

      if (!container) {
        return null;
      }

      const defaultOptions = {
        itemSelector: '.masonry-item',
        minColumnWidth: 200,
        maxColumns: 3,
        gutter: 10,
        autoResize: true
      };

      const config = { ...defaultOptions, ...options };
      
      const instance = {
        container,
        config,
        layout() {
          // Ensure container has relative positioning for absolute positioning of items
          if (getComputedStyle(container).position === 'static') {
            container.style.position = 'relative';
          }
          
          layoutMasonryResponsive(container, config.itemSelector, config);
          
          // Make container visible after layout is complete
          container.style.visibility = 'visible';
        },
        destroy() {
          instances.delete(container);
          if (instances.size === 0) {
            window.removeEventListener('resize', handleResize);
          }
        }
      };

      instances.set(container, instance);

      // Hide container initially to prevent FOUC
      container.style.visibility = 'hidden';

      // Set up resize handling if this is the first instance
      if (instances.size === 1 && config.autoResize) {
        window.addEventListener('resize', handleResize);
      }

      // Initial layout
      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => instance.layout());
      } else {
        // Small delay to ensure all styles are applied
        setTimeout(() => instance.layout(), 10);
      }

      return instance;
    },

    // Utility method to refresh all instances
    refreshAll() {
      instances.forEach(instance => {
        instance.layout();
      });
    }
  };
})();