<?php declare(strict_types=1);
namespace Scripto\Form;

use Laminas\Form\Form;

class ScriptoLogoutForm extends Form
{
    public function init(): void
    {
        $this->setAttribute('class', 'disable-unsaved-warning');
        $this->add([
            'name' => 'scripto-logout',
            'type' => 'submit',
            'attributes' => [
                'value' => 'Log out', // @translate
            ],
        ]);
    }
}
