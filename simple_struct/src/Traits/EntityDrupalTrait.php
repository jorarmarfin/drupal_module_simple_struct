<?php

namespace Drupal\simple_struct\Traits;

use Drupal;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

trait EntityDrupalTrait
{
    function test(): string
    {
        return 'Hello from EntityDrupalTrait';
    }
    /**
     * Loads and returns a node entity based on the provided node ID.
     *
     * This function is a wrapper for the Node::load method, simplifying the process
     * of fetching a node entity. It returns the node object if found. If the node
     * cannot be found, it logs an error message and returns NULL.
     *
     * @param int $nid The node ID.
     * @return Node|null The loaded node entity, or NULL if no node exists with the provided ID.
     */
    function getNode(int $nid): ?Node
    {
        $node = Node::load($nid);
        if (!$node) {
            Drupal::logger('custom_module')->log(RfcLogLevel::WARNING, 'No node found with the ID @nid.', ['@nid' => $nid]);
            return NULL;
        }
        return $node;
    }
    /**
     * Retrieves the Node ID (NID) for a given title and content type.
     *
     * @param string $title The title of the node.
     * @param string $contentType The machine name of the content type.
     *
     * @return int|null The NID of the node if found, otherwise NULL.
     * @throws InvalidPluginDefinitionException
     * @throws PluginNotFoundException
     */
    function getNidByTitleAndType(string $title, string $contentType): ?int
    {
        $nodeStorage = Drupal::entityTypeManager()->getStorage('node');
        $query = $nodeStorage->getQuery();

        $nids = $query->condition('type', $contentType)
            ->condition('title', $title)
            ->accessCheck(true)
            ->execute();

        // Return the first NID if available, otherwise NULL.
        return !empty($nids) ? reset($nids) : null;
    }
    /**
     * Retrieves the value of a specified field from a node.
     *
     * @param int $nid The Node ID.
     * @param string $field The field name.
     *
     * @return mixed The field value, or NULL if the node or field doesn't exist.
     */
    function getFieldByNid(int $nid, string $field): mixed
    {
        $node = Node::load($nid);

        // Verifica si el nodo existe.
        if (!$node) {
            return null; // O manejar como se prefiera.
        }

        // Maneja el campo 'title' como un caso especial.
        if ($field === 'title') {
            return $node->getTitle();
        }

        // Verifica si el campo existe y tiene un valor.
        $fieldValue = $node->get($field)->getValue();
        if (!empty($fieldValue)) {
            // Retorna el primer valor del campo. Se asume que todos los campos tienen este formato.
            return $fieldValue[0]['value'];
        }

        // Retorna NULL si el campo no tiene valores.
        return null;
    }
    /**
     * Retrieves a list of titles for nodes of a specified content type and field ID.
     *
     * This function searches for nodes of a given content type where a specified field
     * matches a given ID, and returns an associative array of node IDs and their titles.
     * It logs an error and returns an empty array if an exception occurs during the fetch.
     *
     * @param string $contentType The machine name of the content type.
     * @param string $field The field name to be matched.
     * @param int $fieldId The ID to match in the specified field.
     * @return array An associative array of node IDs and their titles.
     */
    function getListTitleByTypeFieldId(string $contentType, string $field, int $fieldId): array {
        try {
            $nodes = Drupal::entityTypeManager()->getStorage('node')->loadByProperties([
                'type' => $contentType,
                $field => $fieldId,
            ]);
            $titles = [];
            foreach ($nodes as $key => $node) {
                $titles[$key] = $node->getTitle();
            }
        } catch (\Exception $e) {
            // Log the exception to the Drupal watchdog.
            \Drupal::logger('custom_module')->log(RfcLogLevel::ERROR, 'Failed to load nodes: @message', ['@message' => $e->getMessage()]);
            return []; // Return an empty array in case of error.
        }

        return $titles;
    }
    /**
     * Retrieves nodes by content type.
     *
     * @param string $type The machine name of the content type.
     * @return array An array of node entities of the specified type.
     * @throws InvalidPluginDefinitionException
     * @throws PluginNotFoundException
     */
    function getNodesByType(string $type): array
    {
        $query = Drupal::entityTypeManager()->getStorage('node')->getQuery();
        $nids = $query->condition('type', $type)
            ->accessCheck(TRUE) // Explicitly set access check
            ->execute();

        return Node::loadMultiple($nids);
    }
    /**
     * Retrieves the value of a specified field from a referenced entity.
     *
     * This function extracts the value of a field from an entity that is referenced by another field within a node. It is useful for scenarios where you need to access information from related entities without direct access.
     *
     * @param \Drupal\Core\Entity\EntityInterface $node The node from which the referenced entity is retrieved.
     * @param string $reference The field name of the reference field in the node.
     * @param string $field The field name of the value to be retrieved from the referenced entity.
     * @return mixed The value of the specified field from the referenced entity, or NULL if the reference or entity does not exist.
     *
     * @example
     * // Assume $node is a node object containing a field_reference_entity field which references another node.
     * $value = getValueReferenceField($node, 'field_reference_entity', 'field_name_of_interest');
     *
     * Note:
     * - If the field 'title' is requested, the getTitle() method is used, which is typical for retrieving the title of an entity in Drupal.
     * - For any other fields, the value is accessed directly, assuming the field exists and is accessible.
     */
    function getValueReferenceField(EntityInterface $node, string $reference, string $field): mixed
    {
        // Obtener el valor del campo de referencia para cargar la entidad referenciada.
        $referencia = $node->get($reference)->first();
        if (!$referencia) {
            return NULL; // Referencia no encontrada.
        }

        // Cargar la entidad referenciada.
        $entidadReferenciada = $referencia->get('entity')->getTarget()->getValue();
        if (!$entidadReferenciada) {
            return NULL; // Entidad referenciada no encontrada.
        }

        // Obtener el valor del campo especificado de la entidad referenciada.
        return ($field=='title')? $entidadReferenciada->getTitle() : $entidadReferenciada->get($field)->value;
    }
    /**
     * Retrieves values of a specified field from all entities referenced by a given field in a node.
     *
     * This function fetches all entities referenced by a specified field in a node and returns an array containing the values of a specified field from each referenced entity.
     *
     * @param EntityInterface $node The node from which the referenced entities are retrieved.
     * @param string $reference The field name of the reference field in the node.
     * @param string $field The field name from which to retrieve the value in the referenced entities.
     * @return array An array of values from the specified field of all referenced entities, or an empty array if no references exist.
     */
    function getAllValuesFromReferenceField(EntityInterface $node, string $reference, string $field): array {
        $referencias = $node->get($reference)->referencedEntities();
        $values = [];

        foreach ($referencias as $entidadReferenciada) {
            if ($field == 'title' && method_exists($entidadReferenciada, 'getTitle')) {
                $values[] = $entidadReferenciada->getTitle(); // Get title if the field is 'title'
            } else if ($entidadReferenciada->hasField($field) && !$entidadReferenciada->get($field)->isEmpty()) {
                $fieldValue = $entidadReferenciada->get($field)->value;
                $values[] = $fieldValue; // Get the field value
            } else {
                $values[] = NULL; // Field not found or empty
            }
        }

        return $values;
    }
    /**
     * Retrieves all entities referenced by a specified field in a node.
     *
     * This function fetches and returns all entities that are referenced by a specified field in a given node.
     * It includes validation to ensure that the node and the field are valid. If any validation fails,
     * it logs an appropriate message and returns an empty array.
     *
     * @param EntityInterface $node The node from which the referenced entities are retrieved.
     * @param string $reference The field name of the reference field in the node.
     * @return array An array of referenced entities, or an empty array if no references exist or if any validation fails.
     */
    function getAllReferencedEntities(EntityInterface $node, string $reference): array
    {
        // Ensure the node exists and the field is valid
        if (!$node->hasField($reference)) {
            \Drupal::logger('custom_module')->log(RfcLogLevel::WARNING, 'Invalid node or reference field: @reference', ['@reference' => $reference]);
            return [];
        }

        // Retrieve all referenced entities
        $referencias = $node->get($reference)->referencedEntities();

        // Log if no entities are found
        if (empty($referencias)) {
            \Drupal::logger('custom_module')->log(RfcLogLevel::NOTICE, 'No referenced entities found for field: @reference on node ID: @nid', [
                '@reference' => $reference,
                '@nid' => $node->id()
            ]);
        }

        return $referencias;
    }
    /**
     * Retrieves a referenced entity from a specified entity reference field in a node.
     *
     * This function fetches the entity object that is referenced by a field in a given node. It is useful for directly accessing the referenced entity to perform further operations or to retrieve additional data from it.
     *
     * @param \Drupal\Core\Entity\EntityInterface $node The node from which to retrieve the referenced entity.
     * @param string $reference The field name of the reference field in the node.
     * @return \Drupal\Core\Entity\EntityInterface|null The entity referenced by the specified field, or NULL if the reference does not exist or the referenced entity could not be loaded.
     *
     * @example
     * // Assume $node is a node object containing a field_reference_entity field which references another entity.
     * $referencedEntity = getEntityReferenceField($node, 'field_reference_entity');
     *
     * Note:
     * - This function returns the full entity object, allowing access to all its properties and methods.
     * - If the reference or the entity does not exist, NULL is returned.
     */
    function getEntityReferenceField(EntityInterface $node, string $reference): mixed
    {
        // Obtener el valor del campo de referencia para cargar la entidad referenciada.
        $referencia = $node->get($reference)->first();
        if (!$referencia) {
            return NULL; // Referencia no encontrada.
        }

        // Cargar la entidad referenciada.
        $entidadReferenciada = $referencia->get('entity')->getTarget()->getValue();
        return (!$entidadReferenciada) ? NULL : $entidadReferenciada;
    }
    /**
     * Retrieves the name of a taxonomy term by its ID.
     *
     * @param int $termId The taxonomy term ID.
     *
     * @return string|null The name of the taxonomy term, or NULL if not found.
     */
    function getTaxonomyTermById(int $termId): ?string
    {
        $term = Term::load($termId);

        if (!$term) {
            \Drupal::logger('custom_module')->log(RfcLogLevel::WARNING, 'Taxonomy term with ID @termId not found.', ['@termId' => $termId]);
            return null;
        }

        return $term->getName();
    }
    /**
     * Retrieves a list of taxonomy terms by taxonomy vocabulary.
     *
     * @param string $taxonomy The machine name of the taxonomy vocabulary.
     *
     * @return array An associative array of taxonomy term IDs and their names.
     */
    function getTaxonomyList(string $taxonomy): array {
        $listado = [];

        try {
            $termStorage = Drupal::entityTypeManager()->getStorage('taxonomy_term');
            $terms = $termStorage->loadTree($taxonomy, 0, NULL, TRUE);

            foreach ($terms as $term) {
                if ($term instanceof Term) {
                    $listado[$term->id()] = $term->getName();
                }
            }
        } catch (\Exception $e) {
            // Registra el error en el sistema de log de Drupal.
            \Drupal::logger('custom_module')->log(RfcLogLevel::ERROR, 'Error loading taxonomy terms for vocabulary @vocabulary: @message', [
                '@vocabulary' => $taxonomy,
                '@message' => $e->getMessage(),
            ]);
        }

        return $listado;
    }
}