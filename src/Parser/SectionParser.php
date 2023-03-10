<?php

namespace Hub\Client\Parser;

use Hub\Client\Model\Section;
use Hub\Client\Model\SectionValue;

class SectionParser
{
    public function parse($xml)
    {
        //echo $xml; exit();
        $sections = [];

        $rootNode = simplexml_load_string($xml);

        foreach ($rootNode->forms->form as $formNode) {
            $section = $this->parseSection($formNode);
            $sections[] = $section;
        }
        foreach ($rootNode->eocs->eoc as $eocNode) {
            foreach ($eocNode->forms->form as $formNode) {
                $section = $this->parseSection($formNode);
                $sections[] = $section;
            }
        }

        return $sections;
    }

    public function parseSection($formNode)
    {
        $section = new Section();
        $section->setKey((string)$formNode['keyid']);
        $section->setLabel((string)$formNode['label']);
        $section->setId((string)$formNode['uuid']);
        $section->setCreatedAt((string)$formNode['createstamp']);
        
        if (isset($formNode->values)) {
            foreach ($formNode->values->value as $valueNode) {
                $value = new SectionValue();
                $value->setLabel(trim((string)$valueNode['label']));
                $value->setKey(trim((string)$valueNode['keyid']));
                $value->setValue(trim((string)$valueNode['value']));
                $value->setRepeat(trim((string)$valueNode['repeat']));
                $section->addValue($value);
            }
        }
        if (isset($formNode->value)) {
            foreach ($formNode->value as $valueNode) {
                $value = new SectionValue();
                $value->setLabel(trim((string)$valueNode['label']));
                $value->setKey(trim((string)$valueNode['keyid']));
                $value->setValue(trim((string)$valueNode['value']));
                $value->setRepeat(trim((string)$valueNode['repeat']));
                $section->addValue($value);
            }
        }

        return $section;
    }
}
