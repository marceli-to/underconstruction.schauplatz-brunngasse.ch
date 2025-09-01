<?php

namespace App\Modifiers;

use Statamic\Modifiers\Modifier;
use Statamic\Facades\Entry;
use Illuminate\Support\Str;

class AccordionForContentType extends Modifier
{
    public function index($value, $params, $context)
    {
        if (empty($value) || empty($params)) {
            return null;
        }

        $pageSlug = $value; // e.g., "diskurs"
        $contentType = $params[0]; // e.g., "publications"
        $targetLocale = $params[1] ?? 'de'; // target locale, defaults to 'de'
        
        // Find the page entry by slug
        $entries = Entry::query()
            ->where('collection', 'pages')
            ->where('slug', $pageSlug)
            ->get();
        
        if ($entries->isEmpty()) {
            return null;
        }
        
        $sourceEntry = $entries->first();
        
        // Get the target entry (could be same entry or its translation)
        $targetEntry = $this->getTargetEntry($sourceEntry, $targetLocale);
        
        if (!$targetEntry) {
            return null;
        }
        
        // Find the accordion that contains the specified content type
        $accordionElements = $targetEntry->get('page_elements', []);
        
        foreach ($accordionElements as $element) {
            if ($element['type'] === 'accordion' && isset($element['accordion_elements'])) {
                foreach ($element['accordion_elements'] as $accordionElement) {
                    if (isset($accordionElement['accordion_title']) && isset($accordionElement['accordion_element_contents'])) {
                        // Check if this accordion contains the content type we're looking for
                        foreach ($accordionElement['accordion_element_contents'] as $content) {
                            if (isset($content['type']) && $content['type'] === $contentType) {
                                // Found it! Return the accordion anchor
                                return 'item-' . Str::slug($accordionElement['accordion_title']);
                            }
                        }
                    }
                }
            }
        }
        
        return null;
    }
    
    private function getTargetEntry($sourceEntry, $targetLocale)
    {
        // If already in target locale, return as is
        if ($sourceEntry->locale() === $targetLocale) {
            return $sourceEntry;
        }
        
        // Find translated version
        if ($sourceEntry->hasOrigin()) {
            // This is a translation, get the origin first
            $originEntry = $sourceEntry->origin();
            if ($originEntry->locale() === $targetLocale) {
                return $originEntry;
            }
            
            // Look for other translations
            $translations = Entry::query()
                ->where('collection', 'pages')
                ->where('origin', $originEntry->id())
                ->where('locale', $targetLocale)
                ->get();
                
            return $translations->first();
        } else {
            // This is the origin, find its translation
            $translations = Entry::query()
                ->where('collection', 'pages')
                ->where('origin', $sourceEntry->id())
                ->where('locale', $targetLocale)
                ->get();
                
            return $translations->first();
        }
    }
}