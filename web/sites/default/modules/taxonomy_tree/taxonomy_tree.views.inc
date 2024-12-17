<?php

/**
 * Registers a custom field type for the standard Drupal views module that
 * provides the number of narrower concepts for a given concept.
 *
 * See src/Plugin/views/field/NarrowerTermCountField.php
 *
 * Implements hook_views_data().
 */
function taxonomy_tree_views_data()
{
  // Register the narrower terms count custom field, used for showing/hiding the tree toggles in the tree  view.
  $data['views']['table']['group'] = 'Custom';

  $data['views']['table']['join'] = [
    '#global' => [],
  ];

  $data['views']['narrower_term_count_field'] = [
    'title' => 'Narrower Term Count',
    'help' => 'Number of narrower terms.',
    'field' => [
      'id' => 'narrower_term_count',
    ],
  ];

  // Register the self reference filter which is used for filtering top concepts.
  /*
  $data['node_field_data']['nid'] = array(
    'filter' => array(
      'title' => t('Is self'),
      'help' => t('Filters entities that reference to themselves.'),
      'id' => 'self'
    ),
  );
  */

  $data['node__field_parent']['field_parent_target_id']['filter'] = [
    'field' => 'field_parent_target_id',
    'table' => 'node__field_parent',
    'id' => 'self',
    'entity_type' => 'entity_reference',
    'allow empty' => FALSE,
  ];

  return $data;
}
