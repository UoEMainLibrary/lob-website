<?php

namespace Drupal\lob_skos\Services;

require_once DRUPAL_ROOT . '/autoload.php';
require __DIR__ . '/../vocabularies.php';

use Drupal;
use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\File\FileSystemInterface;
use Drupal\node\Entity\Node;
use EasyRdf\Graph;
use EasyRdf\Literal;
use EasyRdf\Resource;

class SkosExporter
{
  protected Graph $graph;

  protected ImmutableConfig $site;

  protected FileSystemInterface $fileSystem;

  public function __construct()
  {
    $this->site = Drupal::config('system.site');
    $this->fileSystem = Drupal::service("file_system");
  }

  public function export($file, $syntax)
  {
    // Initialize the EasyRDF namespaces for serialization.
    initializeRdfNamespaces();

    // Prepare the export graph.
    $this->graph = new Graph(lob());

    // Add concept scheme metadata.
    $scheme = $this->graph->resource($this->graph->getUri(), [skos('ConceptScheme')]);
    $scheme->add(skos('prefLabel'), new Literal($this->site->get('name'), 'en'));
    $scheme->set(dc('date'), date('c'));

    // Select all top nodes and add as skos:hasTopConcept
    $top_ids = Drupal::entityQuery('node')
      ->accessCheck()
      ->condition('type', 'concept')
      ->condition('field_parent', NULL, 'IS NULL')
      ->execute();

    foreach ($top_ids as $id) {
      $scheme->add(skos('hasTopConcept'), new Resource(lobc($id)));
    }

    // Select all nodes of type 'concept'.
    $node_ids = Drupal::entityQuery('node')
      ->accessCheck()
      ->condition('type', 'concept')
      ->execute();

    // Export the nodes into RDF.
    foreach ($node_ids as $id) {
      // Load the node from the db.
      $node = Node::load($id);

      $this->exportNode($node);
    }

    // Serialise the graph into the given syntax.
    $output = $this->graph->serialise($syntax);

    // Export it as a random file.
    return $this->fileSystem->saveData($output, $file);
  }

  private function exportNode($node)
  {
    $uri = lobc($node->id());

    // Create the resource in the graph.
    $concept = $this->graph->resource($uri, ["skos:Concept"]);

    // Add the preferred labels.
    $this->addLabels($concept, skos('prefLabel'), $node->get('field_preferred_label'));

    // Add the alternative labels.
    $this->addLabels($concept, skos('altLabel'), $node->get('field_alternative_label'));

    // Add the scope notes.
    $this->addNotes($concept, skos('scopeNote'), $node->get('field_note_scope'));

    // Add the contextual notes.
    $this->addNotes($concept, skos('note'), $node->get('field_note_context'));

    // Add the parent concepts.
    $this->addBroaderConcepts($concept, $node->get('field_broader_concept'));

    // Set the child concepts.
    $this->addNarrowerConcepts($concept, $node->id());
  }

  private function addLiteralValue($resource, $property, $value, $lang)
  {
    if (!empty($value)) {
      $literal = new Literal(Html::escape($value), $lang);

      $resource->add($property, $literal);
    }
  }

  private function addLabels($resource, $property, $fields)
  {
    foreach ($fields as $field) {
      foreach ($field as $f) {
        if ($f->getName() == 'target_id') {
          $label = Node::load($f->getValue());

          $title = $label->getTitle();
          $lang = $label->language() ? $label->language()->getId() : NULL;

          $this->addLiteralValue($resource, $property, $title, $lang);
        }
      }
    }
  }

  private function addNotes($resource, $property, $fields)
  {
    foreach ($fields as $field) {

      // get field value run a query here o pick up the text from the note content item
      foreach ($field as $f) {
        if ($f->getName() == 'target_id') {
          $node = Node::load($f->getValue());

          $note = $node->get('field_note');
          $note = $note->getString();
          // remove possible final new line character which produces problematic ttl
          $note = str_replace(array("\r", "\n"), '', $note);
          $lang = $node->language() ? $node->language()->getId() : NULL;

          $this->addLiteralValue($resource, $property, $note, $lang);
        }
      }

      foreach ($field as $f) {
        if ($f->getName() == 'value') {
          $value = $f->getValue();
          $lang = $fields->getLangcode();

          $this->addLiteralValue($resource, $property, $value, $lang);
        }
      }
    }
  }

  private function addBroaderConcepts($resource, $fields)
  {
    if (!empty($fields)) {
      $resource->add(skos('inScheme'), new Resource(lob()));

      foreach ($fields as $field) {
        foreach ($field as $f) {
          if ($f->getName() == 'target_id') {
            $id = $f->getValue();

            $resource->add(skos('broader'), new Resource(lobc($id)));
          }
        }
      }
    } else {
      $resource->add(skos('topConceptOf'), new Resource(lob()));
    }
  }

  private function addNarrowerConcepts($resource, $nid)
  {
    $ids = Drupal::entityQuery('node')
      ->accessCheck()
      ->condition('type', 'concept')
      ->condition('field_broader_concept', $nid)
      ->sort('nid', 'ASC')
      ->execute();

    foreach ($ids as $id) {
      $resource->add(skos('narrower'), new Resource(lobc($id)));
    }
  }

  public function getConceptCount()
  {
    return Drupal::entityQuery("node")->accessCheck()->condition('type', 'concept')->count()->execute();
  }

  public function getLabelCount()
  {
    return Drupal::entityQuery("node")->accessCheck()->condition('type', 'label')->count()->execute();
  }
}
