<?php

namespace Drupal\lob_skos\Services;

class VirtuosoAdapter
{
  private int $connection = 0;

  public function connect($dsn, $username, $password): bool
  {
    $this->connection = odbc_connect($dsn, $username, $password);

    return $this->isConnected();
  }

  public function isConnected(): bool
  {
    return $this->connection > 0;
  }

  public function executeQuery($sparql)
  {
    if ($this->isConnected()) {
      return odbc_exec($this->connection, "CALL DB.DBA.SPARQL_EVAL('$sparql', NULL, 0)");
    } else {
      return false;
    }
  }
}
