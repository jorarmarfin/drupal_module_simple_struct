<?php
namespace Drupal\simple_struct\Form;

use Drupal;
use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\simple_struct\Traits\EntityDrupalTrait;
use Drupal\simple_struct\Traits\PredesTrait;

class StructSimplificationForm extends FormBase {

    use EntityDrupalTrait, PredesTrait;
    /**
     * {@inheritdoc}
     */
    public function getFormId(): string
    {
        return 'struct_simplification_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Simplificar estructura'),
            '#button_type' => 'primary',
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state): void
    {
        Drupal::database()->truncate('simple_struct_data')->execute();
        $nodes = $this->getNodesByType('evento');
        $operations = [];
        foreach ($nodes as $key=> $node) {

            $operations[] = ['\Drupal\simple_struct\Batch\SimpleStructProcess::processBatchActivity', [$node, new StructSimplificationForm()]];

        }
        $batch = [
            'title' => $this->t('Simplificando...'),
            'operations' => $operations,
            'finished' => '\Drupal\simple_struct\Batch\SimpleStructProcess::finishedBatch',
        ];
        batch_set($batch);
    }
    public function testSubmitForm(array &$form, FormStateInterface $form_state): void
    {
        Drupal::database()->truncate('simple_struct_data')->execute();
        $nodes = $this->getNodesByType('evento');
        foreach ($nodes as $key=> $node) {
            $connection = Database::getConnection();
            $tabla = 'simple_struct_data';
            $participantes = $this->getAllReferencedEntities($node, 'field_participante');
            foreach ($participantes as $participante) {
                $connection->insert($tabla)
                    ->fields([
                        'nid' => $node->id(),
                        'evento' => $node->getTitle(),
                        'sector' => $this->getSector($node),
                        'codigo_actividad' => $this->getCodigoActividad($node),
                        'codigo_sub_actividad' => $this->getCodigoSubActividad($node),
                        'actividad' => $this->getValueReferenceField($node, 'field_actividad', 'title'),
                        'sub_actividad' => $node->field_subactividad->value,
                        'participante' => $participante->getTitle(),
                        'sexo' => $participante->get('field_sexo')->value,
                        'distrito' => $this->getValueReferenceField($participante, 'field_distrito', 'name'),

                    ])
                    ->execute();
            }
        }
    }

}
