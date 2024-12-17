<?php

namespace Drupal\taxonomy_tree\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;

/**
 * Renders a tree of taxonomy terms.
 *
 * @FieldFormatter(
 *   id = "tree_formatter",
 *   label = @Translation("Tree"),
 *   description = @Translation("Renders a tree of taxonomy terms using the concept term API."),
 *   field_types = {
 *      "entity_reference",
 *  }
 * )
 */
class TreeFormatter extends EntityReferenceFormatterBase
{
  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode)
  {
    $nid = 0;
    $route_name = \Drupal::routeMatch()->getRouteName();

    if ($route_name == 'entity.node.canonical') {
      $node = \Drupal::routeMatch()->getParameter('node');
      $nid = $node->id();
    } elseif ($route_name == 'entity.node.preview') {
      $node = \Drupal::routeMatch()->getParameter('node_preview');
      $nid = $node->id();
    }

    //$path = drupal_get_path('module', 'taxonomy_tree');
    $path = \Drupal::service('extension.list.module')->getPath('taxonomy_tree');
    $markup = twig_render_template($path . '/templates/tree.html.twig', array(
      // Needed to prevent notices when Twig debugging is enabled.
      'theme_hook_original' => 'not-applicable',
      'nid' => $nid,
    ));

    $elements = [
      0 => [
        'uid' => [
          '#markup' => $markup->__toString(),
          '#allowed_tags' => [
            'div',
            'script',
          ],
        ],
      ]
    ];

    return $elements;
  }
}
