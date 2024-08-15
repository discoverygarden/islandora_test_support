<?php

namespace Drupal\Tests\islandora_test_support\Traits;

use Drupal\Core\Entity\EntityInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\FileInterface;
use Drupal\islandora\IslandoraUtils;
use Drupal\media\Entity\Media;
use Drupal\media\MediaInterface;
use Drupal\media\MediaTypeInterface;
use Drupal\node\NodeInterface;
use Drupal\node\NodeTypeInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\VocabularyInterface;
use Drupal\Tests\field\Traits\EntityReferenceFieldCreationTrait;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;
use Drupal\Tests\test_support\Traits\Installs\InstallsModules;
use Drupal\Tests\test_support\Traits\Support\InteractsWithEntities;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Useful test traits for Islandora. Creates Islanodra node, media and files.
 */
trait IslandoraContentTypeTestTraits {
  use EntityReferenceFieldCreationTrait;
  use ContentTypeCreationTrait;
  use MediaTypeCreationTrait;
  use InteractsWithEntities;
  use InstallsModules;
  use UserCreationTrait;
  use TaxonomyTestTrait;

  /**
   * Node type for node creation.
   *
   * @var \Drupal\node\NodeTypeInterface
   */
  protected NodeTypeInterface $contentType;

  /**
   * Media type for media creation.
   *
   * @var \Drupal\media\MediaTypeInterface
   */
  protected MediaTypeInterface $mediaType;

  /**
   * Vocabulary for islandora models.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected VocabularyInterface $modelsVocabulary;

  /**
   * {@inheritDoc}
   */
  protected function prepareIslandoraContentType() : void {
    // Create content required for creating islandora-esque data.
    $this->contentType = $this->createContentType(['type' => 'page']);
    $this->mediaType = $this->createMediaType('file', ['id' => 'file']);
    $this->createEntityReferenceField('media',
      $this->mediaType->id(), IslandoraUtils::MEDIA_OF_FIELD,
      "Media Of", $this->contentType->getEntityType()->getBundleOf());

    // Create islandora_models vocabulary.
    $this->modelsVocabulary = $this->createModelsVocabulary();
    $this->createEntityReferenceField(
      'node',
      'page',
      IslandoraUtils::MODEL_FIELD,
      'Model',
      'taxonomy_term',
      'default',
      ['target_bundles' => ['islandora_models']]
    );

  }

  /**
   * Helper; create a node.
   *
   * @return \Drupal\node\NodeInterface
   *   A created (and saved) node entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createNode() : NodeInterface {
    if (empty($this->contentType)) {
      $this->prepareIslandoraContentType();
    }
    /** @var \Drupal\node\NodeInterface $entity */
    $entity = $this->createEntity('node', [
      'type' => $this->contentType->id(),
      'title' => $this->randomString(),
    ]);
    return $entity;
  }

  /**
   * Helper; create a file entity.
   *
   * @return \Drupal\file\FileInterface
   *   A created (and saved) file entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createFile() : FileInterface {
    /** @var \Drupal\file\FileInterface $entity */
    $entity = $this->createEntity('file', [
      'uri' => $this->createUri(),
    ]);
    return $entity;
  }

  /**
   * Creates a file and returns its URI.
   *
   * @return string
   *   File URI.
   */
  protected function createUri() {
    $filepath = 'test file ' . $this->randomMachineName();
    $scheme = 'public';
    $filepath = $scheme . '://' . $filepath;
    $contents = "file_put_contents() doesn't seem to appreciate empty strings so let's put in some data.";

    file_put_contents($filepath, $contents);
    $this->assertFileExists($filepath);
    return $filepath;
  }

  /**
   * Helper; create an Islandora-esque media entity.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file entity to which the media should refer.
   * @param \Drupal\node\NodeInterface $node
   *   A node to which the media should belong using Islandora's "media of"
   *   field.
   *
   * @return \Drupal\media\MediaInterface
   *   A created (and saved) media entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createMedia(FileInterface $file, NodeInterface $node) : MediaInterface {
    if (empty($this->mediaType) || empty($this->contentType)) {
      $this->prepareIslandoraContentType();
    }

    /** @var \Drupal\media\MediaInterface $entity */
    $entity = Media::create([
      'bundle' => $this->mediaType->id(),
      'name' => $this->randomString(6),
      IslandoraUtils::MEDIA_OF_FIELD => $node,
      $this->getMediaFieldName() => $file,
    ]);

    $entity->setPublished()->save();
    return $entity;
  }

  /**
   * Helper; get the name of the source field of our created media type.
   *
   * @return string
   *   The name of the field.
   */
  protected function getMediaFieldName() : string {
    return $this->mediaType->getSource()->getSourceFieldDefinition($this->mediaType)->getName();
  }

  /**
   * Helper; Creates the islandora models vocabulary.
   */
  protected function createModelsVocabulary() : Vocabulary {
    $vocabulary = $this->createVocabulary(['vid' => 'islandora_models']);

    // Create link type field on vocabulary for external uri.
    $field_name = 'field_external_uri';

    // Create a field with settings to validate.
    $storage_definition = [
      'field_name' => $field_name,
      'entity_type' => 'taxonomy_term',
      'type' => 'link',
      'cardinality' => 1,
    ];

    FieldStorageConfig::create($storage_definition)->save();

    $field_definition = [
      'field_name' => $field_name,
      'entity_type' => 'taxonomy_term',
      'bundle' => $vocabulary->id(),
      'label' => $this->randomMachineName() . '_label',
    ];

    FieldConfig::create($field_definition)->save();

    return $vocabulary;
  }

  /**
   * Creates a non-islandora node.
   */
  public function createNonIslandoraNode(): NodeInterface {
    /** @var \Drupal\node\NodeInterface $entity */
    return $this->createEntity('node', [
      'type' => $this->contentType->id(),
      'title' => $this->randomString(),
    ]);
  }

}
