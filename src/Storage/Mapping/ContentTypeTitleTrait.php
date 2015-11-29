<?php

namespace Bolt\Storage\Mapping;

/**
 * Trait for mapping a ContentType 'title' to a column name.
 *
 * @deprecated Find something less fugly for v3.0
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
trait ContentTypeTitleTrait
{
    /**
     * Get the likely column name of the title.
     *
     * @param ContentType|array $contentType
     *
     * @return array
     */
    protected function getTitleColumnName($contentType)
    {
        $fields = $contentType['fields'];
        $names = [
            // EN
            'title', 'name', 'caption', 'subject',
            // NL
            'titel', 'naam', 'onderwerp',
            // FR
            'nom', 'sujet',
            // ES
            'nombre', 'sujeto',
            // PT
            'titulo', 'nome', 'subtitulo', 'assunto',
        ];

        foreach ($fields as $name => $values) {
            if (in_array($name, $names)) {
                return $name;
            }
        }

        foreach ($fields as $name => $values) {
            if ($values['type'] === 'text') {
                return $name;
            }
        }
    }
}
