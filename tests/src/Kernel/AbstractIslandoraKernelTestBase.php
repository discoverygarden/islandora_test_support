<?php

namespace Drupal\Tests\islandora_test_support\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\islandora_test_support\Traits\IslandoraContentTypeTestTraits;

/**
 * Abstract class for creating islandora objects.
 */
abstract class AbstractIslandoraKernelTestBase extends KernelTestBase {

  use IslandoraContentTypeTestTraits;

  /**
   * Entity types to be installed for test setup.
   */
  private static array $entityTypes = [
    'node',
    'media',
    'file',
    'taxonomy_term',
    'user',
    'media_type',
  ];

  /**
   * Schemas to be installed for test setup.
   *
   * Modules and the schemas from those modules
   * that should be installed.
   */
  private static array $schemasToInstall = [
    'node' => ['node_access'],
    'file' => ['file_usage'],
    'user' => ['users_data'],
  ];

  /**
   * Set up initial content requirements.
   *
   * Installs required config.
   * Creates media of field, member of field, content type and media type.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function setUp(): void {
    parent::setUp();

    // Install required modules with dependencies.
    $this->enableModuleWithDependencies([
      'user',
      'node',
      'media',
      'taxonomy',
      'field',
      'file',
      'image',
      'system',
      'text',
      'link',
    ]);

    $this->installConfig([
      'node',
      'user',
      'taxonomy',
    ]);

    // Install schemas for node, file and user.
    foreach (static::$schemasToInstall as $module => $schema) {
      $this->installSchema($module, $schema);
    }

    // Install entity schemas for node, file and media.
    foreach (static::$entityTypes as $entityType) {
      $this->installEntitySchema($entityType);
    }

    $this->prepareIslandoraContentType();
  }

}
