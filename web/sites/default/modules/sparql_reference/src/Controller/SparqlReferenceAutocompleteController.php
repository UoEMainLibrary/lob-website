<?php

namespace Drupal\sparql_reference\Controller;

use Drupal;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Xss;

/**
 * Class SparqlReferenceAutocompleteController
 * @package Drupal\sparql_reference\Controller
 */
class SparqlReferenceAutocompleteController
{
  protected $minInputLength = 3;

  protected $maxResults = 20;

  /**
   * @return JsonResponse
   */
  public function handleAutocomplete(Request $request)
  {
    $results = [];

    $input = $request->query->get('q');

    if (!$input || strlen($input) < $this->minInputLength) {
      return new JsonResponse($results);
    }

    $endpoint = $request->query->get('sparql_endpoint');

    if (!filter_var($endpoint, FILTER_VALIDATE_URL)) {
      return new JsonResponse($results);
    }

    $query = $request->query->get('sparql_query');
    $query = str_replace('@input', '"' . Xss::filter($input) . '"', $query);
    $query .= " LIMIT $this->maxResults";

    // Get and parse the JSON query response.
    $request = Drupal::httpClient()->get($endpoint, [
      'headers' => [
        'Accept' => 'application/json',
        'User-Agent' => 'ligatus.org.uk/lob'
      ],
      'query' => [
        'query' => $query,
        'implicit' => 'true',
        '_implicit' => 'false',
        '_equivalent' => 'false',
        '_form' => '/sparql',
      ]
    ]);

    if ($request->getStatusCode() != 200) {
      return new JsonResponse($results);
    }

    $response = json_decode($request->getBody()->getContents());

    foreach ($response->results->bindings as $binding) {
      array_push($results, [
        'value' => $binding->label->value . ' <' . $binding->value->value . '>',
        'label' => $binding->label->value . ' <small class="text-muted">' . $binding->value->value . '</small>'
      ]);
    }

    return new JsonResponse($results);
  }
}
