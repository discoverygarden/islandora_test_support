<?php

namespace Drupal\Tests\islandora_test_support\Traits;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\Query\SelectInterface;

/**
 * Useful test traits for creating database queries.
 */
trait DatabaseQueryTestTraits {

  /**
   * Default operation.
   */
  protected static string $viewOp = 'view';

  /**
   * Generates base media query.
   */
  protected function generateBaseMediaQuery(): SelectInterface {
    return Database::getConnection()->select('media', 'm')->fields('m');
  }

  /**
   * Generates base file query.
   */
  protected function generateBaseFileQuery(): SelectInterface {
    return Database::getConnection()->select('file_managed', 'f')->fields('f');
  }

  /**
   * Generates base node query.
   */
  protected function generateBaseNodeQuery(): SelectInterface {
    return Database::getConnection()->select('node', 'n')->fields('n');
  }

  /**
   * Adds the relevant access tag.
   */
  protected function addAccessTag($query, $tag) {
    $query->addTag($tag);
  }

  /**
   * Adds the correct user to the query.
   */
  protected function addUser($query, $user) {
    $query->addMetaData('account', $user);
  }

  /**
   * Generates Media select query tagged with media_access.
   */
  protected function generateMediaSelectAccessQuery($user, $operation = NULL): SelectInterface {
    return $this->attachAccessControl(
      $this->generateBaseMediaQuery(),
      'media_access',
      $user,
      $operation
    );
  }

  /**
   * Generates file select query tagged with file_access.
   */
  protected function generateFileSelectAccessQuery($user, $operation = NULL): SelectInterface {
    return $this->attachAccessControl(
      $this->generateBaseFileQuery(),
      'file_access',
      $user,
      $operation
    );
  }

  /**
   * Generates node select query tagged with node_access.
   */
  protected function generateNodeSelectAccessQuery($user, $operation = NULL): SelectInterface {
    return $this->attachAccessControl(
      $this->generateBaseNodeQuery(),
      'node_access',
      $user,
      $operation
    );
  }

  /**
   * Adds the correct access tag, operation and user to query.
   */
  protected function attachAccessControl($query, $tag, $user, $operation) {
    $this->addAccessTag($query, $tag);
    $this->addUser($query, $user);
    $this->addOperation($query, $operation);

    return $query;
  }

  /**
   * Adds the relevant operation.
   *
   * View used by default.
   */
  protected function addOperation($query, $operation = NULL) {
    $operation ? $query->addMetaData('op',
      $operation) : $query->addMetaData('op', static::$viewOp);
  }

}
