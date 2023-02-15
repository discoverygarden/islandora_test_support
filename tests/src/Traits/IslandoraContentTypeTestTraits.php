<?php

namespace Drupal\Tests\islandora_test_support\Traits;

use Drupal\file\FileInterface;
use Drupal\islandora\IslandoraUtils;
use Drupal\media\Entity\Media;
use Drupal\media\MediaInterface;
use Drupal\media\MediaTypeInterface;
use Drupal\node\NodeInterface;
use Drupal\node\NodeTypeInterface;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
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
   * {@inheritDoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function prepareIslandoraContentType() : void {
    // Create content required for creating islandora-esque data.
    $this->contentType = $this->createContentType(['type' => 'page']);
    $this->mediaType = $this->createMediaType('file', ['id' => 'file']);
    $this->createEntityReferenceField('media',
      $this->mediaType->id(), IslandoraUtils::MEDIA_OF_FIELD,
      "Media Of", $this->contentType->getEntityType()->getBundleOf());
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
      'type' => $this->contentType->getEntityTypeId(),
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

}
