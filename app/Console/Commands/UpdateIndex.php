<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Statamic\Facades\Entry;
use Illuminate\Support\Str;

class UpdateIndex extends Command
{
  protected $signature = 'update:index';
  protected $description = 'Updates the searchable content for all entries';

  public function handle()
  {
    $this->info('Starting index update...');

    // Get all entries from pages collection
    $pages = Entry::query()->where('collection', 'pages')->get();
    $this->updateEntries($pages, 'pages');

    // Get all entries from posts collection
    $press_reviews = Entry::query()->where('collection', 'press_reviews')->get();
    $this->updateEntries($press_reviews, 'press_reviews');

    // Get all entries from events collection
    $publications = Entry::query()->where('collection', 'publications')->get();
    $this->updateEntries($publications, 'publications');

    // Get all entries from agenda collection
    $agenda = Entry::query()->where('collection', 'agenda')->get();
    $this->updateEntries($agenda, 'agenda');

    // Update search index
    $this->call('statamic:search:update', ['--all' => true]);

    $this->info('Index update completed!');
  }

  private function updateEntries($entries, $type)
  {
    foreach ($entries as $entry)
    {
      $searchableContent = $this->getSearchableContent($entry);
      $accordionAnchors = $this->getAccordionAnchors($entry);
      $entryUrl = $entry->url();
      $entry->set('searchable_content', $searchableContent);
      $entry->set('accordion_anchors', $accordionAnchors);
      $entry->set('entry_url', $entryUrl);
      $entry->save();
      $this->info("Updated {$type} entry: {$entry->get('title')} - URL: {$entryUrl}");
    }
  }

  private function getSearchableContent($entry)
  {
    $content = [];

    // Add basic fields
    $content[] = $entry->get('title');
    $this->info("Title: " . $entry->get('title'));

    // Handle SEO metadata
    if ($entry->get('open_graph_title')) {
      $content[] = $entry->get('open_graph_title');
      $this->info("Added SEO title");
    }
    if ($entry->get('open_graph_description')) {
      $content[] = $entry->get('open_graph_description');
      $this->info("Added SEO description");
    }

    // Handle tags (taxonomy)
    if ($entry->get('tags')) {
      $tags = $entry->get('tags');
      if (is_array($tags)) {
        foreach ($tags as $tag) {
          $content[] = is_string($tag) ? $tag : (isset($tag['title']) ? $tag['title'] : '');
        }
        $this->info("Added " . count($tags) . " tags");
      }
    }

    // Handle page elements for pages
    if ($entry->collection()->handle() === 'pages')
    {
      $pageElements = $entry->get('page_elements');
      
      if (is_array($pageElements))
      {
        foreach ($pageElements as $element)
        {
          $this->info("Processing element type: " . ($element['type'] ?? 'unknown'));
          switch ($element['type'] ?? '') {
            case 'editor':
              if (isset($element['editor'])) {
                $this->processBardField($element['editor'], $content);
              } elseif (isset($element['content'])) {
                $this->processBardField($element['content'], $content);
              }
              break;
            case 'accordion':
              if (isset($element['accordion_elements']) && is_array($element['accordion_elements'])) {
                foreach ($element['accordion_elements'] as $item) {
                  if (isset($item['accordion_title'])) {
                    $content[] = $item['accordion_title'];
                    $this->info("Added accordion title: " . $item['accordion_title']);
                  }
                  if (isset($item['accordion_element_contents']) && is_array($item['accordion_element_contents'])) {
                    foreach ($item['accordion_element_contents'] as $contentItem) {
                      if (isset($contentItem['editor'])) {
                        $this->processBardField($contentItem['editor'], $content);
                      }
                    }
                  }
                }
              }
              break;
            case 'single_image':
              if (isset($element['image_caption'])) {
                $content[] = $element['image_caption'];
                $this->info("Added image caption");
              }
              if (isset($element['image_alt'])) {
                $content[] = $element['image_alt'];
                $this->info("Added image alt text");
              }
              break;
            case 'image_gallery':
              if (isset($element['images']) && is_array($element['images'])) {
                foreach ($element['images'] as $image) {
                  if (isset($image['caption'])) {
                    $content[] = $image['caption'];
                    $this->info("Added gallery image caption");
                  }
                  if (isset($image['alt'])) {
                    $content[] = $image['alt'];
                    $this->info("Added gallery image alt text");
                  }
                }
              }
              break;
            case 'contact_information':
              if (isset($element['contact_text'])) {
                $content[] = $element['contact_text'];
                $this->info("Added contact information");
              }
              break;
          }
        }
      }
    }

    // Handle editor field for agenda entries
    if ($entry->collection()->handle() === 'agenda') {
      $editor = $entry->get('editor');
      if ($editor) {
        if (is_array($editor)) {
          $this->processBardField($editor, $content);
        } else {
          $content[] = $editor;
        }
        $this->info("Added agenda editor field");
      }

      // Handle summary field for agenda entries (also in Bard format)
      $summary = $entry->get('summary');
      if ($summary) {
        if (is_array($summary)) {
          $this->processBardField($summary, $content);
        } else {
          $content[] = $summary;
        }
        $this->info("Added agenda summary field");
      }
    }

    // Handle regular content field
    $regularContent = $entry->get('content');
    if ($regularContent)
    {
      if (is_array($regularContent))
      {
        $this->processBardField($regularContent, $content);
      } 
      else
      {
        $content[] = $regularContent;
      }
      $this->info("Added content field");
    }

    // Handle description field (especially for publications with Bard content)
    $description = $entry->get('description');
    if ($description)
    {
      if (is_array($description))
      {
        $this->processBardField($description, $content);
      }
      else
      {
        $content[] = $description;
      }
      $this->info("Added description field");
    }

    // Ensure all content items are strings and filter out empty values
    $content = array_map(function($item){
      return is_array($item) ? '' : (string)$item;
    }, $content);
    
    // Filter out empty values and join with spaces
    $searchableContent = implode(' ', array_filter($content));
    $this->info("Final content length: " . strlen($searchableContent));
    
    return $searchableContent;
  }

  private function processContentBlock($block, &$content)
  {
    // Handle text directly in the block
    if (isset($block['text']))
    {
      $content[] = $block['text'];
      $this->info("Added text: " . substr($block['text'], 0, 50) . "...");
      return;
    }

    // Handle nested content
    if (isset($block['content']) && is_array($block['content']))
    {
      foreach ($block['content'] as $item)
      {
        if (is_array($item)) {
          // Handle text with marks (like links)
          if (isset($item['text']))
          {
            $content[] = $item['text'];
            $this->info("Added marked text: " . substr($item['text'], 0, 50) . "...");
          }
          // Recursively process nested content
          if (isset($item['content']))
          {
            $this->processContentBlock($item, $content);
          }
        } 
        elseif (is_string($item))
        {
          $content[] = $item;
          $this->info("Added string content: " . substr($item, 0, 50) . "...");
        }
      }
    }
  }

  private function processBardField($bardContent, &$content)
  {
    if (!is_array($bardContent)) {
      return;
    }

    foreach ($bardContent as $block) {
      if (!is_array($block)) {
        continue;
      }

      // Handle different Bard block types
      switch ($block['type'] ?? '') {
        case 'paragraph':
        case 'heading':
        case 'text':
          if (isset($block['content'])) {
            $this->extractTextFromBardContent($block['content'], $content);
          }
          break;
        case 'bulletList':
        case 'orderedList':
          if (isset($block['content'])) {
            $this->extractTextFromBardContent($block['content'], $content);
          }
          break;
        case 'listItem':
          if (isset($block['content'])) {
            $this->extractTextFromBardContent($block['content'], $content);
          }
          break;
        case 'link':
          if (isset($block['content'])) {
            $this->extractTextFromBardContent($block['content'], $content);
          }
          // Also extract link URL if needed for context
          if (isset($block['attrs']['href'])) {
            $content[] = $block['attrs']['href'];
          }
          break;
        case 'set':
          // Handle set elements (like custom link sets)
          if (isset($block['attrs']['values'])) {
            $values = $block['attrs']['values'];
            if (isset($values['link_text'])) {
              $content[] = $values['link_text'];
              $this->info("Added set link text: " . $values['link_text']);
            }
            if (isset($values['link'])) {
              $content[] = $values['link'];
              $this->info("Added set link URL");
            }
          }
          break;
        default:
          // For any other block type, try to extract text content
          if (isset($block['text'])) {
            $content[] = $block['text'];
          } elseif (isset($block['content'])) {
            $this->extractTextFromBardContent($block['content'], $content);
          }
      }
    }
  }

  private function extractTextFromBardContent($bardContent, &$content)
  {
    if (!is_array($bardContent)) {
      return;
    }

    foreach ($bardContent as $item) {
      if (is_array($item)) {
        if (isset($item['text'])) {
          $content[] = $item['text'];
          $this->info("Added Bard text: " . substr($item['text'], 0, 50) . "...");
        }
        if (isset($item['content'])) {
          $this->extractTextFromBardContent($item['content'], $content);
        }
      } elseif (is_string($item)) {
        $content[] = $item;
        $this->info("Added Bard string: " . substr($item, 0, 50) . "...");
      }
    }
  }

  private function getAccordionAnchors($entry)
  {
    $anchors = [];

    // Only process pages with accordion elements
    if ($entry->collection()->handle() === 'pages') {
      $pageElements = $entry->get('page_elements');
      
      if (is_array($pageElements)) {
        foreach ($pageElements as $element) {
          if (($element['type'] ?? '') === 'accordion') {
            if (isset($element['accordion_elements']) && is_array($element['accordion_elements'])) {
              foreach ($element['accordion_elements'] as $item) {
                if (isset($item['accordion_title'])) {
                  $title = $item['accordion_title'];
                  $slug = Str::slug($title);
                  $anchors[] = [
                    'title' => $title,
                    'anchor' => "item-{$slug}",
                    'content' => $this->getAccordionContent($item)
                  ];
                  $this->info("Added accordion anchor: item-{$slug}");
                }
              }
            }
          }
        }
      }
    }

    return $anchors;
  }

  private function getAccordionContent($accordionItem)
  {
    $content = [];
    
    if (isset($accordionItem['accordion_element_contents']) && is_array($accordionItem['accordion_element_contents'])) {
      foreach ($accordionItem['accordion_element_contents'] as $contentItem) {
        if (isset($contentItem['editor'])) {
          $this->processBardField($contentItem['editor'], $content);
        }
      }
    }
    
    return implode(' ', array_filter($content));
  }

}
