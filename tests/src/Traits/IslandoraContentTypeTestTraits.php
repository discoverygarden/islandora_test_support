<?php

namespace Drupal\Tests\islandora_test_support\Traits;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\FileInterface;
use Drupal\islandora\IslandoraUtils;
use Drupal\link\LinkItemInterface;
use Drupal\media\Entity\Media;
use Drupal\media\MediaInterface;
use Drupal\media\MediaTypeInterface;
use Drupal\node\NodeInterface;
use Drupal\node\NodeTypeInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\taxonomy\VocabularyInterface;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
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
  use EntityReferenceTestTrait;
  use ContentTypeCreationTrait;
  use MediaTypeCreationTrait;
  use InteractsWithEntities;
  use InstallsModules;
  use UserCreationTrait;
  use TaxonomyTestTrait;

  /**
   * Node type for node creation.
   *
   * @var \Drupal\node\NodeTypeInterface|\Drupal\node\Entity\NodeType
   */
  protected NodeTypeInterface $contentType;

  /**
   * Media type for media creation.
   *
   * @var \Drupal\media\MediaTypeInterface
   */
  protected MediaTypeInterface $mediaType;

  /**
   * Vocabulary for islandora model.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected VocabularyInterface $islandoraModelVocabulary;

  /**
   * Vocabulary for media use.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected VocabularyInterface $islandoraMediaUseVocabulary;

  /**
   * {@inheritDoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function prepareIslandoraContentType() : void {
    // Create content required for creating islandora-esque data.
    $this->contentType = $this->createContentType(['type' => 'page']);
    $this->mediaType = $this->createMediaType('file', ['id' => 'file']);
    $this->islandoraModelVocabulary = $this->createVocabulary([
      'name' => 'Islandora Models',
      'vid' => 'islandora_models',
    ]);
    $this->islandoraMediaUseVocabulary = $this->createVocabulary([
      'name' => 'Islandora Media Use',
      'vid' => 'islandora_media_use',
    ]);

    // Add external URI field to vocabularies.
    FieldStorageConfig::create([
      'field_name' => IslandoraUtils::EXTERNAL_URI_FIELD,
      'entity_type' => 'taxonomy_term',
      'type' => 'link',
    ])->save();

    FieldConfig::create([
      'entity_type' => 'taxonomy_term',
      'field_name' => IslandoraUtils::EXTERNAL_URI_FIELD,
      'bundle' => $this->islandoraModelVocabulary->id(),
      'settings' => ['link_type' => LinkItemInterface::LINK_EXTERNAL],
    ])->save();


    FieldConfig::create([
      'entity_type' => 'taxonomy_term',
      'field_name' => IslandoraUtils::EXTERNAL_URI_FIELD,
      'bundle' => $this->islandoraMediaUseVocabulary->id(),
      'settings' => ['link_type' => LinkItemInterface::LINK_EXTERNAL],
    ])->save();

    // Create media of field which targets node.
    $this->createEntityReferenceField('media',
      $this->mediaType->id(), IslandoraUtils::MEDIA_OF_FIELD,
      "Media Of", $this->contentType->getEntityType()->getBundleOf());

    // Create media use field which targets taxonomy islandora media use.
    $this->createEntityReferenceField('media',
      $this->mediaType->id(), IslandoraUtils::MEDIA_USAGE_FIELD,
      "Media Use", 'taxonomy_term', 'default',
      ['target_bundles' => [$this->islandoraMediaUseVocabulary->id()]]);

    // Create member_of field which targets node.
    $this->createEntityReferenceField('node',
      $this->contentType->id(), IslandoraUtils::MEMBER_OF_FIELD,
      "Member Of", $this->contentType->getEntityType()->getBundleOf());

    // Create content type (islandora model) field.
    $this->createEntityReferenceField('node', $this->contentType->id(), IslandoraUtils::MODEL_FIELD, 'Islandora Model',
      'taxonomy_term', 'default',
      ['target_bundles' => [$this->islandoraModelVocabulary->id()]]);
  }

  /**
   * Helper; create a node.
   *
   * @return \Drupal\node\NodeInterface
   *   A created (and saved) node entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createNode($member_of = NULL) : NodeInterface {
    if (empty($this->contentType)) {
      $this->prepareIslandoraContentType();
    }
    /** @var \Drupal\node\NodeInterface $entity */
    $entity = $this->createEntity('node', [
      'type' => $this->contentType->getEntityTypeId(),
      'title' => $this->randomString(),
      IslandoraUtils::MEMBER_OF_FIELD => $member_of,
    ]);

    return $entity;
  }

  /**
   * Helper; create a collection.
   *
   * @return \Drupal\node\NodeInterface
   *   A created (and saved) node entity of the type collection.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createCollection() : NodeInterface {
    if (empty($this->contentType)) {
      $this->prepareIslandoraContentType();
    }

    // Create collection term.
    $collectionModel = $this->addTermToVocabulary($this->islandoraModelVocabulary, 'Collection', 'http://purl.org/dc/dcmitype/Collection');

    /** @var \Drupal\node\NodeInterface $entity */
    $entity = $this->createEntity('node', [
      'type' => $this->contentType->getEntityTypeId(),
      'title' => $this->randomString(),
      IslandoraUtils::MODEL_FIELD => $collectionModel,
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
   * @param \Drupal\taxonomy\TermInterface $mediaUseTerm
   *   Islandora media use which should be assigned to the media.
   *
   * @return \Drupal\media\MediaInterface
   *   A created (and saved) media entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createMedia(FileInterface $file, NodeInterface $node, $mediaUseTerm = NULL) : MediaInterface {
    if (empty($this->mediaType) || empty($this->contentType)) {
      $this->prepareIslandoraContentType();
    }

    /** @var \Drupal\media\MediaInterface $entity */
    $entity = Media::create([
      'bundle' => $this->mediaType->id(),
      'name' => $this->randomString(6),
      IslandoraUtils::MEDIA_OF_FIELD => $node,
      $this->getMediaFieldName() => $file,
      IslandoraUtils::MEDIA_USAGE_FIELD => $mediaUseTerm,
    ]);

    $entity->setPublished()->save();
    return $entity;
  }

  /**
   * Helper; create original file.
   *
   * @return \Drupal\media\Entity\MediaInterface
   *   Media with use original file.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createOriginalFileMedia(FileInterface $file, NodeInterface $node, $mediaUseTerm = NULL) : MediaInterface {
    if (empty($this->mediaType) || empty($this->contentType)) {
      $this->prepareIslandoraContentType();
    }

    $mediaUseTerm = $this->addTermToVocabulary($this->islandoraMediaUseVocabulary, 'Original File', 'http://pcdm.org/use#OriginalFile');

    return $this->createMedia($file, $node, $mediaUseTerm);
  }

  /**
   * Helper; created service file.
   *
   * @return \Drupal\media\Entity\MediaInterface
   *   Media with use service file.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createServiceFileMedia(FileInterface $file, NodeInterface $node, $mediaUseTerm = NULL) : MediaInterface {
    if (empty($this->mediaType) || empty($this->contentType)) {
      $this->prepareIslandoraContentType();
    }

    $mediaUseTerm = $this->addTermToVocabulary($this->islandoraMediaUseVocabulary, 'Service File', 'http://pcdm.org/use#ServiceFile');

    return $this->createMedia($file, $node, $mediaUseTerm);
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
   * Helper; populates islandora models taxonomy..
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function populateIslandoraModelsTaxonomy(): void {
    $islandoraModels = [
      'Collection' => 'http://purl.org/dc/dcmitype/Collection',
      'Digital Document' => 'https://schema.org/DigitalDocument',
    ];
    foreach ($islandoraModels as $model => $uri) {
      $term = $this->createTerm($this->islandoraModelVocabulary, [
        'name' => $model,
      ]);
      $term->set(IslandoraUtils::EXTERNAL_URI_FIELD, ['uri' => $uri]);
      $term->save();
    }

  }

  /**
   * Helper; creates taxonomy terms.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function addTermToVocabulary($vocabulary, $mediaUse, $uri): TermInterface {
    $term = $this->createTerm($vocabulary, [
      'name' => $mediaUse,
    ]);
    $term->set(IslandoraUtils::EXTERNAL_URI_FIELD, ['uri' => $uri]);
    $term->save();

    return $term;
  }

}
