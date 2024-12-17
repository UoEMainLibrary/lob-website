<?php

namespace Drupal\lob_computed_field\Form;

use Drupal;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\node\Entity\Node;

class ConfigForm extends FormBase
{
  /**
   * Returns a unique string identifying the form.
   *
   * The returned ID should be a unique string that can be a valid PHP function
   * name, since it's used in hook implementation names such as hook_form_FORM_ID_alter().
   *
   * @return string   *   The unique string identifying the form.
   */
  public function getFormId(): string
  {
    return 'lob_computed_field_config';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state): array
  {
    // Count the number of items to be recomputed.
    $n = Drupal::entityQuery('node')->accessCheck()->condition('type', 'concept')->count()->execute();

    $form['rebuild'] = [
      '#type' => 'markup',
      '#markup' => "<p>Recompute the <code>parent_path</code> values of all <b>$n</b> computed fields for nodes of type <code>concept</code>.</p>",
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    // Add a submit button that handles the submission of the form.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Recompute'),
      '#ajax' => [
        'progress' => [
          'type' => 'throbber',
          'message' => 'Processing..'
        ]
      ]
    ];

    if ($n == 0) {
      $form['actions']['submit']['#disabled'] = TRUE;

      $this->messenger()->addStatus("There are no entities with <pre>parent_path</pre> computed fields.");
    }

    return $form;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $nodes = Drupal::entityQuery('node')->accessCheck()->condition('type', 'concept')->execute();

    $n = 0;

    foreach ($nodes as $nid) {
      // Update the node..
      $node = Node::load($nid);

      $node->field_parent_path->value = computed_field_field_parent_path_compute(NULL, $node, NULL, 0);

      $node->save();

      $n++;
    }

    $msg = new TranslatableMarkup("Successfully recomputed $n fields. <a href='/admin/config/lob_computed_field/result'>View results</a>");

    $this->messenger()->addStatus($msg);
  }
}
