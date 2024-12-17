<?php

require_once DRUPAL_ROOT . '/autoload.php';

use EasyRdf\RdfNamespace;

/**
 * Initialize the static EasyRDF namespace manager with the vocabulary prefixes required for LoB.
 */
function initializeRdfNamespaces() {
  RdfNamespace::set("dc", dc());
  RdfNamespace::set("lob", lob());
  RdfNamespace::set("lobc", lobc());
  RdfNamespace::set("skos", skos());
}

/**
 * Return a URI in the Dublin Core namespace.
 *
 * @param string $localName A local name which is appended to the namespace URI.
 * @return string|null
 */
function dc(string $localName = ""): string
{
  return "http://purl.org/dc/terms/" . $localName;
}

/**
 * Return a URI in the lob vocabulary namespace.
 *
 * @param string $localName A local name which is appended to the namespace URI.
 * @return string|null
 */
function lob(string $localName = ""): string
{
  return "http://w3id.org/lob/" . $localName;
}

/**
 * Return a URI in the lob concept namespace.
 *
 * @param string $localName A local name which is appended to the namespace URI.
 * @return string|null
 */
function lobc(string $localName = ""): string
{
  return "http://w3id.org/lob/concept/" . $localName;
}

/**
 * Return a URI in the SKOS namespace.
 *
 * @param string $localName A local name which is appended to the namespace URI.
 * @return string|null
 */
function skos(string $localName = ""): string
{
  return "http://www.w3.org/2004/02/skos/core#" . $localName;
}
