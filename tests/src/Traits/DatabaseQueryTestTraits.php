<?php

namespace Drupal\Tests\islandora_test_support\Traits;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Useful test traits for creating database queries.
 */
trait DatabaseQueryTestTraits {

  /**
   * Default operation.
   */
  protected static string $viewOp = 'view';

  /**
   * Generates Media select query tagged with media_access.
   */
  protected function generateMediaSelectAccessQuery(
    $user,
    $operation = NULL
  ): SelectInterface {
    return $this->attachAccessControl($this->generateBaseMediaQuery(),
      'media_access', $user, $operation);
  }

  /**
   * Adds the correct access tag, operation and user to query.
   */
  protected function attachAccessControl(
    SelectInterface $query,
    string $tag,
    AccountInterface $user,
    ?string $operation
  ): SelectInterface {
    $this->addAccessTag($query, $tag);
    $this->addUser($query, $user);
    $this->addOperation($query, $operation);

    return $query;
  }

  /**
   * Adds the relevant access tag.
   *
   * The access tag could be node_access, media_access, file_access etc.
   */
  protected function addAccessTag(SelectInterface $query, string $tag) {
    $query->addTag($tag);
  }

  /**
   * Adds the correct user to the query.
   */
  protected function addUser(SelectInterface $query, AccountInterface $user) {
    $query->addMetaData('account', $user);
  }

  /**
   * Adds the relevant operation.
   *
   * View used by default.
   */
  protected function addOperation(
    SelectInterface $query,
    ?string $operation = NULL
  ) {
    $operation ? $query->addMetaData('op',
      $operation) : $query->addMetaData('op', static::$viewOp);
  }

  /**
   * Generates base media query.
   */
  protected function generateBaseMediaQuery(): SelectInterface {
    return Database::getConnection()->select('media', 'm')->fields('m');
  }

  /**
   * Generates file select query tagged with file_access.
   */
  protected function generateFileSelectAccessQuery(
    AccountInterface $user,
    ?string $operation = NULL
  ): SelectInterface {
    return $this->attachAccessControl($this->generateBaseFileQuery(),
      'file_access', $user, $operation);
  }

  /**
   * Generates base file query.
   */
  protected function generateBaseFileQuery(): SelectInterface {
    return Database::getConnection()->select('file_managed', 'f')->fields('f');
  }

  /**
   * Generates node select query tagged with node_access.
   */
  protected function generateNodeSelectAccessQuery(
    AccountInterface $user,
    ?string $operation = NULL
  ): SelectInterface {
    return $this->attachAccessControl($this->generateBaseNodeQuery(),
      'node_access', $user, $operation);
  }

  /**
   * Generates base node query.
   */
  protected function generateBaseNodeQuery(): SelectInterface {
    return Database::getConnection()->select('node', 'n')->fields('n');
  }

}
