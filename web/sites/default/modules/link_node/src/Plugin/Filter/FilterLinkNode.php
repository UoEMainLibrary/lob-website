<?php
/*
This file is link_node module - It allows users to link a node to another node.

Copyright (c) 2004 Mark Howell
Copyright (c) 2007 Tom Chiverton

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/

namespace Drupal\link_node\Plugin\Filter;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Render\Markup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\FilterProcessResult;
use Drupal\node\NodeAccessControlHandler;
use Drupal\node\Entity\Node;

// Notes
// - Removed support for Drupal 6 image nodes
// - Removed access rights check for links as these will eventually be handled when the link is navigated.
//

/**
 * Implements an input filter that allows a node to link to other nodes.
 *
 * @Filter(
 *   id="link_node",
 *   title=@Translation("Convert node link tokens into HTML links."),
 *   description=@Translation("Replaces [node:id] tokens with HTML anchor tags."),
 *   type=Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 * )
 */
class FilterLinkNode extends FilterBase
{
    private $allowedVars = ['title'];

    private function moduleExists($id) {
        return \Drupal::moduleHandler()->moduleExists($id);
    }

    private function getParameters($text) {
        $result = [];

        if($text) {
            $params_exp = '/,\s*(?<param>[\w_-]+)\s*=\s*(?<value>[^,]+)\s*/';
            $params = [];

            if (preg_match_all($params_exp, $text, $params_match)) {
                foreach ($params_match["param"] as $key => $p) {
                    $v = $params_match["value"][0];

                    if($v) {
                        $result[strtolower($p)] = trim(trim($v), "\"");
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Execute the filter on given text.
     */
    public function process($text, $lang) {
        $nodes_exp = '/(?<node>\[node:(?<nid>\d+)(?<params>[^\]]*)\])/i';

        if (preg_match_all($nodes_exp, $text, $nodes_match)) {
            for($i = 0; $i < count($nodes_match["node"]); $i++) {
                $nid = $nodes_match['nid'][$i];

                if ($this->moduleExists('i18n') && $this->moduleExists('translation')) {
                    $nid = translation_node_nid($nid, i18n_get_lang(), $nid);
                }

                $node = Node::load($nid);

                if ($node) {
                    $link = $node->toLink();
                    $params = $this->getParameters($nodes_match["params"][$i]);

                    if(array_key_exists('title', $params)) {
                        $link->setText($params['title']);
                    }

                    $n = $nodes_match['node'][$i];

                    $text = str_replace($n, $link->toString(), $text);
                }

            }
        }

        return new FilterProcessResult($text);
    }

    // /**
    //  * {@inheritdoc}
    //  */
    // public function settingsForm(array $form, FormStateInterface $form_state) {
    //     $form['filter_link_node'] = array(
    //         '#type' => 'fieldset',
    //         '#title' => t("Link Node Codes"),
    //         '#collapsible' => TRUE,
    //         '#collapsed' => FALSE
    //     );

    //     $form['filter_link_node']['link_node'] = array(
    //         '#type' => 'item',
    //         '#title' => t('Instructions'),
    //         '#value' => t('This filter translates special node tags into links to other nodes.
    //       Syntax: [node:node_id,title="some title"]; every param but node_id is optional.
    //       Each list below should be a comma separated list (no spaces) of what node properties users are
    //       allowed to override for the purposes of displaying a node. E.g. <em>title,border</em>')
    //     );

    //     foreach (node_get_types() as $type => $name) {
    //         $form['filter_link_node']["link_node_allowed_vars_$type"] = array(
    //             '#type' => 'textfield',
    //             '#title' => t("Allowed parameters for %type nodes", array('%type' => $type)),
    //             '#default_value' => variable_get("link_node_allowed_vars_$type", ""),
    //             '#size' => 40,
    //             '#maxlength' => 256
    //         );
    //     }

    //     return $form;
    // }

    // /**
    //  * {@inheritdoc}
    //  */
    // function help($section = "admin/help#link_node")
    // {
    //     $output = "";

    //     switch ($section) {
    //         case 'admin/help#link_node':
    //             $output = $this->t('
    //             <h2>Nodes that link to other nodes</h2>

    //             You can link nodes to other nodes using the following syntax:<br>
    //             [node:<em>node_id</em>,title="some title"]

    //             <h3>Some examples:</h3>

    //             [node:123]<br>
    //             [node:123,title="original"]<br>

    //             <h3>Currently available parameters:</h3>
    //             <pre>');

    //             foreach ($this->$allowedVars as $var) {
    //                 $text .= $var . " ";
    //             }

    //             $output .= $this->t('</pre>');

    //             break;
    //     }

    //     return $output;
    // }

    /**
     * {@inheritdoc}
     */
    public function tips($long = FALSE) {
        if ($long) {
            $text = $this->t('
            <h4>Linking nodes to other nodes:</h4>
            You can link nodes to other nodes using the following syntax:

            <pre>[node:<em>node_id</em>,title="val2"]</pre>

            <h5><strong>Examples:</strong></h5>
            <ul>
                <li><code>[node:123]</code></li>
                <li><code>[node:123, title="original"]</code></li>
            </ul>

            <h5><strong>Currently available parameters:</strong></h5>');

            foreach ($this->allowedVars as $var) {
                $text .= "<code>" . $var . "</code> ";
            }

            return $text;
        } else {
            return $this->t('You can link nodes to other nodes using the following syntax: [node:<em>node_id</em>,title="some title"]');
        }
    }
}
