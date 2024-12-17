<?php

// namespace Drupal\taxonomy_tree\Plugin\views\filter;

// use Drupal\views\Plugin\views\filter\FilterPluginBase;

// /**
//  * Filters entities that reference to themselves.
//  *
//  * @ingroup views_filter_handlers
//  *
//  * @ViewsFilter("self")
//  */
// class SelfReferenceOperator extends FilterPluginBase {
//   /**
//    * The equal query operator.
//    *
//    * @var string
//    */
//   const EQUAL = '=';

//   /**
//    * Returns an array of operator information.
//    *
//    * @return array
//    */
//   protected function operators() {
//     return [
//       'equals_self' => [
//         'title' => $this->t('Is self'),
//         'method' => 'queryOpSelf',
//         'short' => $this->t('equals_self'),
//         'values' => 1,
//         'query_operator' => self::EQUAL,
//       ],
//     ];
//   }

//   /**
//    * Adds a where condition to the query for an entity reference value.
//    *
//    * @param string $field
//    *   The field name to add the where condition for.
//    * @param string $query_operator
//    *   (optional) Either self::EQUAL or self::NOT_EQUAL. Defaults to
//    *   self::EQUAL.
//    */
//   protected function queryOpSelf($field) {
//     $this->query->addWhere($this->options['group'], $field, $field, self::EQUAL);
//   }
// }