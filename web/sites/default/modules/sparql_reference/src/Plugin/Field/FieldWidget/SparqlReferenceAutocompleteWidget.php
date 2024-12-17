<?php

namespace Drupal\sparql_reference\Plugin\Field\FieldWidget;

use Drupal;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;

define('REFERENCE_EXPRESSION', '/(?<label>.*)\<(?<uri>.*)\>/');

/**
 * An autocomplete widget that calls our external reference term lookup handler.
 *
 * @FieldWidget(
 *     id="sparql_ref_autocomplete_widget",
 *     module="sparql_reference",
 *     label=@Translation("Autocomplete (SPARQL endpoint)"),
 *     field_types={
 *         "entity_reference"
 *     }
 * )
 */
class SparqlReferenceAutocompleteWidget extends WidgetBase
{
  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state)
  {
    $value = "";

    if (isset($items->target_id)) {
      $term = Term::load($items->target_id);

      $value .= $term->getName();

      if (isset($term->web_tid)) {
        $value .= " <" . $term->web_tid->value . ">";
      }
    }

    $element += [
      '#type' => 'textfield',
      '#placeholder' => t('Enter a term..'),
      '#description' => $this->getSetting('sparql_endpoint'),
      '#autocomplete_route_name' => 'sparql_reference.autocomplete',
      '#autocomplete_route_parameters' => [
        'sparql_endpoint' => $this->getSetting('sparql_endpoint'),
        'sparql_query' => $this->getSetting('sparql_query')
      ],
      '#default_value' => $value,
      '#element_validate' => [
        [$this, 'validate'],
      ]
    ];

    return ['value' => $element];
  }

  /**
   * {@inheritdoc}
   */
  public static function validate($element, FormStateInterface $form_state)
  {
    $value = $element['#value'];

    if (!empty($value)) {
      preg_match(REFERENCE_EXPRESSION, $value, $match);

      if (!filter_var($match['uri'], FILTER_VALIDATE_URL)) {
        $form_state->setError($element, 'This field must contain a valid URI enclosed in angle brackets: <https://example.org>');
      }
    }
  }

  public function massageFormValues(array $values, array $form, FormStateInterface $form_state)
  {
    if(count($form_state->getErrors()) == 0) {
      $value = $values[0]["value"];

      preg_match(REFERENCE_EXPRESSION, $value, $match);

      $terms = Drupal::entityQuery('taxonomy_term')
        ->accessCheck()
        ->condition('web_tid', $match['uri'])
        ->range(0, 1)
        ->execute();

      if (count($terms) > 0) {
        $values[0]["target_id"] = array_values($terms)[0];
      } else {
        try {
          $vid = $this->getSetting("vid");

          $label = $match['label'] ?? "";
          $uri = $match['uri'];

          if ($label != "") {
            $term = Term::create([
              'vid' => $vid,
              'web_tid' => $uri,
              'name' => $label,
            ]);

            $term->save();

            $values[0]["target_id"] = $term->id();

            $this->messenger()->addStatus("Created term <b>$label</b> in vocabulary <b>$vid</b>");
          }

        } catch (EntityStorageException $ex) {
          $this->messenger()->addError($ex->getMessage());
        }
      }
    }

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings()
  {
    return [
        'vid' => NULL,
        'sparql_endpoint' => 'https://dbpedia.org/sparql/',
        'sparql_query' => 'SELECT DISTINCT ?value ?label WHERE { ?value rdfs:label ?label . FILTER(CONTAINS(?label, @input)) }',
        'max_results' => 10
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state)
  {
    $options = [];

    foreach (Vocabulary::loadMultiple() as $vocabulary) {
      $options[$vocabulary->id()] = $vocabulary->label();
    }

    $element['vid'] = array(
      '#type' => 'select',
      '#title' => t('Vocabulary'),
      '#description' => 'The taxonomy to which the referenced terms will be added.',
      '#options' => $options,
      '#default_value' => $this->getSetting('vid'),
      '#required' => TRUE
    );

    $element['sparql_endpoint'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Endpoint'),
      '#description' => 'The SPARQL endpoint to be queried. Use @input as a placeholder for the user input.',
      '#default_value' => $this->getSetting('sparql_endpoint'),
      '#required' => TRUE
    ];

    $element['sparql_query'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Query'),
      '#description' => 'The SPARQL query to be executed.',
      '#default_value' => $this->getSetting('sparql_query'),
      '#required' => TRUE
    ];

    $element['max_results'] = [
      '#type' => 'number',
      '#title' => $this->t('Max results'),
      '#default_value' => $this->getSetting('max_results'),
      '#required' => TRUE,
      '#min' => 1,
      '#max' => 50,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary()
  {
    $summary = [];

    $endpoint = $this->getSetting('sparql_endpoint');
    $vocabulary = $this->getSetting('vid');

    if (strlen($vocabulary) > 0) {
      $vocabulary = Vocabulary::load($vocabulary)->label();
    }

    $summary[] = $this->t('Vocabulary: @vocabulary', array('@vocabulary' => $vocabulary));
    $summary[] = $this->t('Endpoint: @endpoint', array('@endpoint' => $endpoint));

    return $summary;
  }
}
