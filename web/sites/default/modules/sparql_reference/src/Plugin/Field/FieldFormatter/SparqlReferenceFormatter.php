<?php

namespace Drupal\sparql_reference\Plugin\Field\FieldFormatter;

use Drupal\Core\Url;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Renders a URI that was retrieved from a SPARQL endpoint.
 *
 * @FieldFormatter(
 *   id = "sparql_ref_formatter",
 *   label = @Translation("Link (SPARQL reference)"),
 *   description = @Translation("Renders a URI that was retrieved from a SPARQL endpoint."),
 *   field_types = {
 *      "entity_reference",
 *  }
 * )
 */
class SparqlReferenceFormatter extends EntityReferenceFormatterBase
{
  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode)
  {
    $elements = [];

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      $label = $entity->label();
      $uri = $entity->get('web_tid')->getString();

      if(empty($uri)) {
        $uri = $entity->toUrl();
      } else {
        $uri = Url::fromUri($uri);
      }

      $elements[$delta] = [
        '#type' => 'link',
        '#title' => $label,
        '#url' => $uri,
        '#options' => $uri->getOptions(),
      ];

      if (!empty($items[$delta]->_attributes)) {
        $elements[$delta]['#options'] += ['attributes' => []];
        $elements[$delta]['#options']['attributes'] += $items[$delta]->_attributes;

        // Unset field item attributes since they have been included in the
        // formatter output and shouldn't be rendered in the field template.
        unset($items[$delta]->_attributes);
      }

      $elements[$delta]['#cache']['tags'] = $entity->getCacheTags();
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'csv_column' => 'first_name', // Column name that you want to display.
        'show_file' => 0,
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state)
  {
    $form = parent::settingsForm($form, $form_state);

    $form['csv_column'] = [
      '#title' => $this->t('Column name from CSV file.'),
      '#description' => $this->t('The column name that you want to render from the uploaded CSV file. It is expected that all uploaded CSV files should be of the same format.'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('csv_column'),
    ];

    $form['show_file'] = [
      '#title' => $this->t('Display CSV file.'),
      '#description' => $this->t('Check this checkbox to display the CSV file in generic file formatter.'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('show_file'),
    ];

    return $form;
  }
}
