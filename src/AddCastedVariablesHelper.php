<?php

declare(strict_types=1);

namespace Sunnysideup\AddCastedVariables;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HTMLReadonlyField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\ORM\FieldType\DBField;


class AddCastedVariablesHelper
{
    use Injectable;

    protected $originatingObject = null;

    /**
     *
     *
     * @param mixed $fields
     * @param string $tabName - e.g. Root.About
     * @param mixed $otherFieldsToAdd
     *              provide similar to casting in terms of the array format
     * @param mixed $fieldsToSkip
     *              provide a simple list of field names
     * @return void
     */
    public function AddCastingFields($originatingObject, FieldList $fields, ?string $tabName = 'Root.FullDetails', ?array $otherFieldsToAdd = [], ?array $fieldsToSkip = [])
    {
        // @todo: consider using ⓘ as the title for the tab
        $this->originatingObject = $originatingObject;
        $otherFieldsToAdd = [
            'Created' => 'Datetime',
            'LastEdited' => 'Datetime',
        ] + $otherFieldsToAdd;

        $fieldsToSkip = [
            'CSSClasses',
            'Title',
        ] + $fieldsToSkip;
        $fieldsToSkip = array_flip($fieldsToSkip);

        $otherFieldsToAdd = array_diff_key($otherFieldsToAdd, $fieldsToSkip);
        foreach ($otherFieldsToAdd as $name => $type) {
            $this->addCastingField($fields, $tabName, $name, $type);
        }
        $castedFields = $this->originatingObject->config()->get('casting');
        $castedFields = array_diff_key($castedFields, $fieldsToSkip, $otherFieldsToAdd);
        foreach ($castedFields as $name => $type) {
            $this->addCastingField($fields, $tabName, $name, $type);
        }
    }


    protected function addCastingField(FieldList $fields, string $tabName, string $name, string $type)
    {
        $methodName = 'get' . $name;
        if ($this->originatingObject->hasMethod($methodName)) {
            $v = $this->originatingObject->$methodName();
        } elseif ($this->originatingObject->hasMethod($name)) {
            $v = $this->originatingObject->$name();
        } else {
            $v = $this->originatingObject->dbObject($name);
        }
        if (!($v instanceof DBField)) {
            $v = DBField::create_field($type, $v);
        }
        if ($v->hasMethod('Nice')) {
            $niceValue = $v->Nice();
        } else {
            // $niceValue = $v->forTemplate();
            $niceValue = $v->RAW();
        }
        if (is_array($niceValue)) {
            $niceValue = implode(', ', $niceValue);
        }
        $className = ReadonlyField::class;
        if ($type === 'HTMLText' || $this->isHtmlOrEscaped($niceValue)) {
            $className = HTMLReadonlyField::class;
        }
        $fields->addFieldsToTab(
            $tabName,
            [
                $className::create($name . 'NICE', $this->originatingObject->fieldLabel($name), $niceValue),
            ]
        );
    }
    protected function isHtmlOrEscaped(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        // Check for HTML tags
        if ($value !== strip_tags($value)) {
            return true;
        }

        // Check for common HTML entities
        if (preg_match('/&(?:[a-zA-Z]{2,8}|#\d{2,5}|#x[0-9a-fA-F]{2,4});/', $value)) {
            return true;
        }

        return false;
    }
}
