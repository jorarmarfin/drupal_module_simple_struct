<?php
namespace Drupal\simple_struct\Controller;
use Drupal\Core\Controller\ControllerBase;

class TestController extends ControllerBase
{
    public function test():array
    {

        return [
            '#theme' => 'test',
            '#data' => [
                'name' => 'John Doe',
                'age' => 30,],
        ];
    }

}