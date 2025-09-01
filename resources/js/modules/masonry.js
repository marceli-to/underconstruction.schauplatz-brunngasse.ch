import { MasonryLayout } from '../vendor/masonry-layout.js';

// Make MasonryLayout globally accessible
window.MasonryLayout = MasonryLayout;

// Initialize masonry for publications
const publicationsMasonry = MasonryLayout.init('#publication-masonry-container', {
  itemSelector: '.masonry-item',
  minColumnWidth: 300,
  maxColumns: 2,
  gutter: 18
});

// Initialize masonry for teasers
const teasersMasonry = MasonryLayout.init('#teaser-masonry-container', {
  itemSelector: '.masonry-item',
  minColumnWidth: 300,
  maxColumns: 3,
  gutter: 18
});
