<?php

namespace Drupal\Tests\islandora_test_support\Traits;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\Query\SelectInterface;

/**
 * Useful test traits for creating database queries.
 */
trait DatabaseQueryTestTraits {

  /**
   * Access tags by entity type.
   */
  protected static array $accessTags = [
    'node' => 'node_access',
    'file' => 'file_access',
    'media' => 'media_access',
  ];

  /**
   * Default operation.
   */
  protected static string $viewOp = 'view';

  /**
   * Generates select access query.
   */
  public function generateSelectAccessQuery($type, $user, $operation = NULL): SelectInterface {
    switch ($type) {
      case 'media':
        $query = $this->generateBaseMediaQuery();
        break;

      case 'file':
        $query = $this->generateBaseFileQuery();
        break;

      case 'node':
        $query = $this->generateBaseNodeQuery();
      default:
    }

    $this->addAccessTag($query, $type);
    $this->addUser($query, $user);
    $this->addOperation($query, $operation);

    return $query;
  }

  /**
   * Generates base media query.
   */
  public function generateBaseMediaQuery(): SelectInterface {
    return Database::getConnection()->select('media', 'm')->fields('m');
  }

  /**
   * Generates base file query.
   */
  public function generateBaseFileQuery(): SelectInterface {
    return Database::getConnection()->select('file_managed', 'f')->fields('f');
  }

  /**
   * Generates base node query.
   */
  public function generateBaseNodeQuery(): SelectInterface {
    return Database::getConnection()->select('node', 'n')->fields('n');
  }

  /**
   * Adds the relevant access tag.
   */
  public function addAccessTag(&$query, $type) {
    $query->addTag(self::$accessTags[$type]);
  }

  /**
   * Adds the correct user to the query.
   */
  public function addUser(&$query, $user) {
    $query->addMetaData('account', $user);
  }

  /**
   * Adds the relevant operation.
   */
  public function addOperation(&$query, $operation = NULL) {
    $operation ? $query->addMetaData('op',
      $operation) : $query->addMetaData('op', self::$viewOp);
  }

}
