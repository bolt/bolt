<?php

namespace Bolt\Storage\Mapping;

/**
 * Trait for mapping a ContentType 'title' to a column name.
 *
 * @deprecated Find something less fugly for v3
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
            'title', 'name', 'caption', 'subject',   // EN
            'titel', 'naam', 'onderwerp',            // NL
            'nom', 'sujet',                          // FR
            'nombre', 'sujeto',                      // ES
            'titulo', 'nome', 'subtitulo', 'assunto' // PT
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
