<?php

declare(strict_types = 1);

namespace Ranine\Testing\Drupal\Traits;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ranine\Testing\Traits\MockObjectCreationTrait;

/**
 * For mocking entity type manager objects.
 *
 * This trait is only for use in test classes.
 */
trait MockEntityTypeManagerCreationTrait {

  use MockObjectCreationTrait;

  /**
   * Creates and returns a mock entity type manager.
   *
   * The mock entity storage objects defined will have (only) their load(),
   * loadByProperties(), and loadMultiple() methods properly defined. The mock
   * entity type manager will have only its getStorage() method properly
   * defined.
   *
   * @param array[] $entitiesAndTypes
   *   This array should have the following structure. The mock entity objects
   *   should all define a working toArray() method. Type names are in round
   *   brackets (), and placeholders are in curly brackets {}:
   *   [
   *     (string) {entity_type_id} => [
   *       'storage_interface' =>
   *         (string) {fully_qualified_name_of_entity_storage_interface},
   *       'entities' => [
   *         {entity_id} =>
   *           (\Drupal\Core\Entity\EntityInterface) {mock_entity_object},
   *         {...},
   *       ]
   *     ], {...},
   *   ]
   *
   * @throws \LogicException
   *   Thrown if current object is not a \PHPUnit\Framework\TestCase object.
   */
  private function getMockEntityTypeManager(array $entitiesAndTypes) : MockObject&EntityTypeManagerInterface {
    if (!($this instanceof TestCase)) {
      throw new \LogicException('The object this method is called upon must be a \\PHPUnit\\Framework\\TestCase instance.');
    }

    // Create the mock storage objects.
    /** @var \Drupal\Core\Entity\EntityStorageInterface[] */
    $entityStorageObjects = [];
    foreach ($entitiesAndTypes as $entityTypeId => $storageDefinition) {
      /** @var string */
      $storageInterfaceName = $storageDefinition['storage_interface'];
      /** @var \Drupal\Core\Entity\EntityInterface[] */
      $entities = $storageDefinition['entities'];
      /** @var \PHPUnit\Framework\MockObject\MockObject|\Drupal\Core\Entity\EntityStorageInterface */
      $storage = $this->createMockNoAutoMethodConfig($storageInterfaceName);
      $storage->method('load')->willReturnCallback(fn($id) => $entities[$id] ?? NULL);
      $storage->method('loadMultiple')->willReturnCallback(function (?array $ids) use ($entities) : array {
        if ($ids === NULL) {
          return $entities;
        }
        else {
          $entitiesFound = [];
          foreach ($ids as $id) {
            if (array_key_exists($id, $entities)) {
              $entitiesFound[$id] = $entities[$id];
            }
          }
          return $entitiesFound;
        }
      });
      $storage->method('loadByProperties')->willReturnCallback(function (array $values) use ($entities) : array {
        $entitiesFound = [];
        foreach ($entities as $id => $entity) {
          $properties = $entity->toArray();
          foreach ($values as $property => $targetValue) {
            if (!array_key_exists($property, $properties) || $properties[$property] !== $targetValue) {
              continue 2;
            }
          }
          $entitiesFound[$id] = $entity;
        }

        return $entitiesFound;
      });
      $entityStorageObjects[$entityTypeId] = $storage;
    }

    // Create the mock entity type manager.
    $mockEntityTypeManager = $this->createMockNoAutoMethodConfig('\\Drupal\\Core\\Entity\\EntityTypeManagerInterface');
    $mockEntityTypeManager->method('getStorage')->willReturnCallback(function (string $entity_type_id) use ($entityStorageObjects) : EntityStorageInterface {
      if (array_key_exists($entity_type_id, $entityStorageObjects)) {
        return $entityStorageObjects[$entity_type_id];
      }
      else {
        throw new PluginNotFoundException('Entity storage type could not be found.');
      }
    });

    return $mockEntityTypeManager;
  }

}
