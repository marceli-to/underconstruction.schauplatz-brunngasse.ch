/**
 * Search Highlight Alpine.js Component
 * Highlights search terms and handles accordion opening from search results
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('searchHighlight', () => ({
        highlightTerm: '',
        accordionIndex: null,
        
        init() {
            // Get highlight parameter from URL
            const urlParams = new URLSearchParams(window.location.search);
            this.highlightTerm = urlParams.get('highlight');
            
            if (this.highlightTerm && this.highlightTerm.trim()) {
                // Handle accordion opening first if needed
                this.handleAccordionOpening();
                
                // Highlight terms after a short delay
                setTimeout(() => {
                    this.highlightTerms(this.highlightTerm.trim());
                    this.scrollToFirstHighlight();
                }, 100);
            }
        },

        handleAccordionOpening() {
            const hash = window.location.hash;
            
            if (hash && hash.startsWith('#item-')) {
                // Add a small delay to ensure Alpine is fully initialized
                setTimeout(() => {
                    const targetElement = document.querySelector(hash);
                    
                    if (targetElement) {
                        // Find the accordion wrapper (parent container that has { selected: null })
                        const accordionWrapper = targetElement.parentElement;
                        
                        if (accordionWrapper) {
                            const allAccordions = accordionWrapper.querySelectorAll('[id^=\'item-\']');
                            const index = Array.from(allAccordions).indexOf(targetElement);
                            
                            if (index !== -1) {
                                // Use Alpine's $data to set the selected property
                                Alpine.$data(accordionWrapper).selected = index;
                                this.accordionIndex = index;
                            }
                        }
                    }
                }, 200);
            }
        },

        highlightTerms(searchTerm) {
            // First try to highlight the full search term
            const fullTermNodes = this.findTextNodes(searchTerm);
            
            if (fullTermNodes.length > 0) {
                // Full term found, highlight it
                fullTermNodes.forEach(textNode => {
                    this.highlightInTextNode(textNode, searchTerm);
                });
            } else {
                // Full term not found, try individual words (prioritize first word)
                const words = searchTerm.split(/[\s+]+/).filter(word => word.length > 0);
                
                if (words.length > 1) {
                    // Try the first word
                    const firstWordNodes = this.findTextNodes(words[0]);
                    if (firstWordNodes.length > 0) {
                        firstWordNodes.forEach(textNode => {
                            this.highlightInTextNode(textNode, words[0]);
                        });
                    } else {
                        // First word not found, try other words
                        for (let i = 1; i < words.length; i++) {
                            const wordNodes = this.findTextNodes(words[i]);
                            if (wordNodes.length > 0) {
                                wordNodes.forEach(textNode => {
                                    this.highlightInTextNode(textNode, words[i]);
                                });
                                break; // Stop after finding the first matching word
                            }
                        }
                    }
                }
            }
        },

        findTextNodes(searchTerm) {
            const walker = document.createTreeWalker(
                document.body,
                NodeFilter.SHOW_TEXT,
                {
                    acceptNode: (node) => {
                        // Skip script and style tags
                        const parent = node.parentNode;
                        if (parent.tagName === 'SCRIPT' || parent.tagName === 'STYLE') {
                            return NodeFilter.FILTER_REJECT;
                        }
                        
                        // Only process nodes that contain the search term (case-insensitive)
                        if (node.textContent.toLowerCase().includes(searchTerm.toLowerCase())) {
                            return NodeFilter.FILTER_ACCEPT;
                        }
                        
                        return NodeFilter.FILTER_REJECT;
                    }
                }
            );

            const textNodes = [];
            let node;
            
            // Collect all text nodes that contain the search term
            while (node = walker.nextNode()) {
                textNodes.push(node);
            }
            
            return textNodes;
        },

        highlightInTextNode(textNode, searchTerm) {
            const text = textNode.textContent;
            const parent = textNode.parentNode;
            
            // Create regex for case-insensitive matching with word boundaries
            const regex = new RegExp(`(${this.escapeRegExp(searchTerm)})`, 'gi');
            
            if (regex.test(text)) {
                // Create a temporary container
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = text.replace(regex, '<mark class="search-highlight bg-sunella px-2">$1</mark>');
                
                // Replace the text node with the highlighted content
                while (tempDiv.firstChild) {
                    parent.insertBefore(tempDiv.firstChild, textNode);
                }
                
                // Remove the original text node
                parent.removeChild(textNode);
            }
        },

        escapeRegExp(string) {
            return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        },

        scrollToFirstHighlight() {
            // Wait longer if we opened an accordion
            const delay = this.accordionIndex !== null ? 300 : 100;
            
            setTimeout(() => {
                const firstHighlight = document.querySelector('.search-highlight');
                const hash = window.location.hash;
                
                // If there's a hash anchor, try to find that element first
                let targetElement = null;
                if (hash) {
                    try {
                        targetElement = document.querySelector(hash);
                    } catch (e) {
                        // Invalid selector, ignore
                    }
                }
                
                // If no anchor target or no highlights in anchor area, scroll to first highlight
                if (!targetElement || !targetElement.querySelector('.search-highlight')) {
                    if (firstHighlight) {
                        targetElement = firstHighlight;
                    }
                }
                
                if (targetElement) {
                    this.scrollToElementWithAccordionSupport(targetElement);
                }

            }, delay);
        },

        scrollToElementWithAccordionSupport(targetElement) {
            // Check if element is inside an accordion that might be animating
            const accordion = targetElement.closest('[x-data]');
            
            if (accordion) {
                // Wait for potential accordion animations to complete
                this.waitForAccordionAnimation(targetElement);
            } else {
                // No accordion, scroll immediately
                this.scrollToElement(targetElement);
            }
        },

        waitForAccordionAnimation(targetElement) {
            let attempts = 0;
            const maxAttempts = 20; // Max 2 seconds (20 * 100ms)
            let lastHeight = targetElement.offsetHeight;
            let stableCount = 0;
            
            const checkStability = () => {
                attempts++;
                const currentHeight = targetElement.offsetHeight;
                
                // Check if height is stable (accordion finished animating)
                if (Math.abs(currentHeight - lastHeight) < 5) {
                    stableCount++;
                    if (stableCount >= 3) {
                        // Height has been stable for 3 checks, animation likely complete
                        this.scrollToElement(targetElement);
                        return;
                    }
                } else {
                    stableCount = 0;
                }
                
                lastHeight = currentHeight;
                
                if (attempts < maxAttempts) {
                    setTimeout(checkStability, 100);
                } else {
                    // Fallback: scroll anyway after max attempts
                    this.scrollToElement(targetElement);
                }
            };
            
            checkStability();
        },

        scrollToElement(targetElement) {
            // Scroll with offset to account for fixed headers
            const offsetTop = targetElement.getBoundingClientRect().top + window.pageYOffset - 100;
            
            window.scrollTo({
                top: offsetTop,
                behavior: 'smooth'
            });
        }
    }));
});