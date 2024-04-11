<?php

namespace Drupal\simple_struct\Traits;

use Drupal;
use Drupal\node\NodeInterface;

trait PredesTrait
{
    use EntityDrupalTrait;
    function getSector(NodeInterface $node)
    {
        $activity = $this->getEntityReferenceField($node, 'field_actividad');
        $sector = $this->getEntityReferenceField($activity, 'field_sector');
        return $sector->getName();
    }
    function getCodigoActividad(NodeInterface $node)
    {
        $activity = $this->getEntityReferenceField($node, 'field_actividad');
        $codigo = $this->getEntityReferenceField($activity, 'field_codigo_actividad');
        return $codigo->getName();
    }
    function getCodigoSubActividad(NodeInterface $node)
    {
        $codigo = $this->getEntityReferenceField($node, 'field_codigo_subactividad');
        return $codigo->getName();
    }

}