<?php declare(strict_types=1);
namespace Scripto\Form;

use Laminas\Form\Form;

class RevisionRevertForm extends Form
{
    public function init(): void
    {
        $this->add([
            'name' => 'submit_revisionrevertform',
            'type' => 'submit',
            'attributes' => [
                'value' => 'Confirm reversion', // @translate
            ],
        ]);
    }
}
