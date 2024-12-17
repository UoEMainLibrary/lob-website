<?php

namespace Drupal\taxonomy_tree\Plugin\views\field;

use Drupal\views\Plugin\views\field\NumericField;
use Drupal\views\ResultRow;

/**
 * Field handler for the concept tree hierarchy.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("narrower_term_count")
 */
class NarrowerTermCountField extends NumericField
{
    /**
     * {@inheritdoc}
     */
    public function query()
    {
        // Add a sub query to the existing view query that will select the term count.
        $subQuery = \Drupal::database()->select('node__field_parent');
        $subQuery->addField('node__field_parent', 'field_parent_target_id', 'parent_id');
        $subQuery->addExpression("COUNT(field_parent_target_id)", 'narrower_term_count');
        $subQuery->groupBy("node__field_parent.field_parent_target_id");

        // Add the sub query as a component of a join.
        $joinDefinition = [
            'table formula' => $subQuery,
            'left_table' => 'node_field_data',
            'left_field' => 'nid',
            'table' => 'node__children',
            'field' => 'parent_id',
            'adjust' => FALSE,
        ];

        // Create a join object and create a relationship between the main query and the sub query.
        $join = \Drupal::service('plugin.manager.views.join')->createInstance('standard', $joinDefinition);
        $this->query->addRelationship('node__children', $join, 'node_field_data');

        // Add the field to the Views interface.
        $this->query->addField(NULL, 'narrower_term_count', 'narrower_term_count');
    }

    /**
     * {@inheritdoc}
     */
    public function render(ResultRow $row)
    {
        if (is_null($row->narrower_term_count)) {
            // Ensure a null value is printed as a 0.
            $row->narrower_term_count = 0;
        }

        // Calling parent::render() provides null to the View for some reason..
        return $row->narrower_term_count;
    }
}
