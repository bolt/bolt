<?php

namespace Bolt\Field;

class Base implements FieldInterface
{

    public $name;
    public $template;

    public function __construct($name, $template)
    {
        $this->name = $name;
        $this->template = $template;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getTemplate()
    {
        return $this->template;
    }

    public function getStorageType()
    {
        return 'text';
    }

    public function getStorageOptions()
    {
        return array();
    }

    public function getDecodedValue($value, $fieldinfo)
    {
        $returnvalue = $value;
        $allowtwig = !empty($fieldinfo['allowtwig']);

        switch ($name) {
            case 'markdown':

                $returnvalue = $this->preParse($value, $allowtwig);

                // Parse the field as Markdown, return HTML
                $returnvalue = \ParsedownExtra::instance()->text($returnvalue);

                // Sanitize/clean the HTML.
                $maid = new \Maid\Maid(
                    array(
                        'output-format' => 'html',
                        'allowed-tags' => array('html', 'head', 'body', 'section', 'div', 'p', 'br', 'hr', 's', 'u', 'strong', 'em', 'i', 'b', 'li', 'ul', 'ol', 'menu', 'blockquote', 'pre', 'code', 'tt', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'dd', 'dl', 'dh', 'table', 'tbody', 'thead', 'tfoot', 'th', 'td', 'tr', 'a', 'img'),
                        'allowed-attribs' => array('id', 'class', 'name', 'value', 'href', 'src')
                    )
                );
                $returnvalue = $maid->clean($returnvalue);
                $returnvalue = new \Twig_Markup($returnvalue, 'UTF-8');
                break;

            case 'html':
            case 'text':
            case 'textarea':

                $returnvalue = $this->preParse($value, $allowtwig);
                $returnvalue = new \Twig_Markup($returnvalue, 'UTF-8');

                break;

            case 'imagelist':
            case 'filelist':
                if (is_string($value)) {
                    // Parse the field as JSON, return the array
                    $returnvalue = json_decode($value);
                } else {
                    // Already an array, do nothing.
                    $returnvalue = $value;
                }
                break;

            case 'image':
                if (is_array($value) && isset($value['file'])) {
                    $returnvalue = $value['file'];
                } else {
                    $returnvalue = $value;
                }
                break;

            default:
                $returnvalue = $value;
                break;
        }

        return $returnvalue;
    }

    /**
     * If passed snippet contains Twig tags, parse the string as Twig, and return the results
     *
     * @param  string $snippet
     * @param $allowtwig
     * @return string
     */
    public function preParse($snippet, $allowtwig)
    {
        // Quickly verify that we actually need to parse the snippet!
        if ($allowtwig && preg_match('/[{][{%#]/', $snippet)) {
            $snippet = html_entity_decode($snippet, ENT_QUOTES, 'UTF-8');

            return $this->app['safe_render']->render($snippet, $this->getTemplateContext());
        }

        return $snippet;
    }
}
