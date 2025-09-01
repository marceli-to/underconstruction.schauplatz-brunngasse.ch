<?php

namespace App\Modifiers;

use Statamic\Modifiers\Modifier;
use Statamic\Facades\Entry;

class TranslatedSlug extends Modifier
{
    public function index($value, $params, $context)
    {
        if (empty($value) || empty($params)) {
            return null;
        }

        $targetLocale = $params[0];

        // Find the entry by slug in any locale
        $entries = Entry::query()
            ->where('collection', 'pages')
            ->where('slug', $value)
            ->get();
        
        if ($entries->isEmpty()) {
            return null;
        }
        
        $sourceEntry = $entries->first();
        
        // If the source entry is already in the target locale, return its URL
        if ($sourceEntry->locale() === $targetLocale) {
            return $this->buildUrl($sourceEntry, $targetLocale);
        }
        
        // Look for the translated version
        // Case 1: Source entry has translations (we need to find one with target locale)
        if ($sourceEntry->hasOrigin()) {
            // This entry is a translation, find the origin and then its other translations
            $originEntry = $sourceEntry->origin();
            if ($originEntry->locale() === $targetLocale) {
                return $this->buildUrl($originEntry, $targetLocale);
            }
            
            // Look for other translations of the origin
            $translations = Entry::query()
                ->where('collection', 'pages')
                ->where('origin', $originEntry->id())
                ->where('locale', $targetLocale)
                ->get();
                
            if ($translations->isNotEmpty()) {
                return $this->buildUrl($translations->first(), $targetLocale);
            }
        } else {
            // Case 2: Source entry is the origin, find its translations
            $translations = Entry::query()
                ->where('collection', 'pages')
                ->where('origin', $sourceEntry->id())
                ->where('locale', $targetLocale)
                ->get();
                
            if ($translations->isNotEmpty()) {
                return $this->buildUrl($translations->first(), $targetLocale);
            }
        }
        
        return null;
    }
    
    private function buildUrl($entry, $locale)
    {
        if ($locale === 'de') {
            return $entry->slug();
        }
        
        return $locale . '/' . $entry->slug();
    }
}