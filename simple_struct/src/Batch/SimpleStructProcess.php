<?php

namespace Drupal\simple_struct\Batch;

use Drupal\Core\Database\Database;
use Drupal\Core\TypedData\Exception\MissingDataException;
use Drupal\simple_struct\Traits\EntityDrupalTrait;
use Drupal\simple_struct\Traits\PredesTrait;

class SimpleStructProcess
{
    /**
     * @throws MissingDataException
     */
    public static function processBatchActivity($node,$structSimplificationForm, &$context): void
    {
        $participantes = $structSimplificationForm->getAllReferencedEntities($node, 'field_participante');
        foreach ($participantes as $participante) {
            self::insertData(
                nid: $node->id(),
                evento:$node->getTitle(),
                sector: $structSimplificationForm->getSector($node),
                codigo_actividad: $structSimplificationForm->getCodigoActividad($node),
                codigo_sub_actividad: $structSimplificationForm->getCodigoSubActividad($node),
                actividad: $structSimplificationForm->getValueReferenceField($node, 'field_actividad', 'title'),
                sub_actividad: $node->field_subactividad->value,
                participante: $participante->getTitle(),
                sexo: $participante->get('field_sexo')->value,
                distrito: $structSimplificationForm->getValueReferenceField($participante, 'field_distrito', 'name')
            );
        }
    }

    /**
     * @throws \Exception
     */
    public static function finishedBatch($success, $results, $operations): void
    {
        if ($success) {
            $message = t('Se simplifico la entidad ');
        } else {
            $message = t('Finished with an error.');
        }
        \Drupal::messenger()->addMessage($message);
    }


    public static function insertData($nid,$evento,$sector,$codigo_actividad,$codigo_sub_actividad,$actividad,
                                            $sub_actividad,$participante,$sexo,$distrito ): void
    {
        $connection = Database::getConnection();
        $tabla = 'simple_struct_data';
        $connection->insert($tabla)
            ->fields([
                'nid' => $nid,
                'evento' => $evento,
                'sector' => $sector,
                'codigo_actividad' => $codigo_actividad,
                'codigo_sub_actividad' => $codigo_sub_actividad,
                'actividad' => $actividad,
                'sub_actividad' => $sub_actividad,
                'participante' => $participante,
                'sexo' => $sexo,
                'distrito' => $distrito,
            ])
            ->execute();

    }
}