# tl;dr

Create 

use like this:

```php

class MyDataObject extends DataObject
{

    private static $casting = [
        'SomethingCool' => 'Varchar',
    ]

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        Injector::inst()->get(AddCastedVariablesHelper::class)->AddCastingFields(
            $this,
            $fields,
            [
                'MyOtherMethod' => 'Varchar',
            ],
            [
                'LastEdited',
            ]
        );
    }

    public function getSomethingCool(): string
    {
        return 'Do the mamba!';
    }
}

```
