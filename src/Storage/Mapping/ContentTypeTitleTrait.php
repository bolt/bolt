<?php

namespace Bolt\Storage\Mapping;

use Bolt\Common\Deprecated;

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
     * @return string
     */
    protected function getTitleColumnName($contentType)
    {
        Deprecated::method();

        $names = $this->getTitleColumnNames($contentType);

        return reset($names);
    }

    /**
     * Get an array of the column name(s) of the title.
     *
     * @param ContentType|array $contentType
     *
     * @return array
     */
    protected function getTitleColumnNames($contentType)
    {
        Deprecated::method();

        // If we specified a specific field name or array of field names as 'title'.
        if (!empty($contentType['title_format'])) {
            return (array) $contentType['title_format'];
        }

        $names = [
            'title', 'name', 'caption', 'subject', 'heading', //EN
            'titel', 'naam', 'onderwerp', // NL
            'nom', 'sujet', // FR
            'nombre', 'sujeto', // ES
            'titulo', 'nome', 'subtitulo', 'assunto', // PT
        ];

        foreach ($names as $name) {
            if (isset($contentType['fields'][$name])) {
                return [$name];
            }
        }

        // Otherwise, grab the first field of type 'text', and assume that's the title.
        if (!empty($contentType['fields'])) {
            foreach ($contentType['fields'] as $key => $field) {
                if ($field['type'] === 'text') {
                    return [$key];
                }
            }
        }

        // If this is a ContentType without any text fields, we can't provide a proper title.
        return [];
    }
}
