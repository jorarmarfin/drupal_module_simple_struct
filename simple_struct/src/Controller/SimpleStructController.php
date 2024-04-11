<?php

namespace Drupal\simple_struct\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\simple_struct\Form\StructSimplificationForm;

class SimpleStructController extends ControllerBase
{
    public function content(): array
    {
        return \Drupal::formBuilder()->getForm(StructSimplificationForm::class);
    }

}