<?php

namespace Bolt\Nut\Output;

use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Helper\Helper;

/**
 * Holds text that could be overwritten in console.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
final class Capture
{
    private $text = '';

    public function append($text)
    {
        $text = $this->cleanAutocomplete($text);

        $this->text .= $text;
    }

    public function overwrite(OutputFormatterInterface $formatter, $width)
    {
        $lines = $this->getLines($this->text, $width, $formatter);

        if ($lines[count($lines) - 1] === '') {
            array_pop($lines);
        }

        $lineCount = count($lines);

        if (!$lines) {
            return '';
        }

        $overwrite = '';
        // go to first line
        $overwrite .= sprintf("\033[%dF", $lineCount);
        // fill whitespace for each line
        foreach ($lines as $line) {
            $length = Helper::strlenWithoutDecoration($formatter, $line);
            $overwrite .= str_repeat("\x20", $length) . PHP_EOL;
        }
        // go back to first line
        $overwrite .= sprintf("\033[%dF", $lineCount);

        return $overwrite;
    }

    private function cleanAutocomplete($newText)
    {
        if ($newText === "\033[K") {
            return '';
        }
        if ($newText === "\033[1D") {
            $this->text = substr($this->text, 0, -1);

            return '';
        }
        if ($newText === "\0338") {
            $index = strrpos($this->text, "\0337");
            $this->text = substr($this->text, 0, $index);

            return '';
        }

        return $newText;
    }

    private function getLines($text, $width, OutputFormatterInterface $formatter)
    {
        $rawLines = explode(PHP_EOL, $text);

        $lines = [];
        foreach ($rawLines as $line) {
            $length = Helper::strlenWithoutDecoration($formatter, $line);
            if ($length <= $width) {
                $lines[] = $line;
            } else {
                $lines = array_merge($lines, $this->chunk($line, $width));
            }
        }

        return $lines;
    }

    /**
     * Splits buffer into parts of given width accounting for ANSI styling.
     *
     * @param string $text
     * @param int    $width
     *
     * @return string[]
     */
    private function chunk($text, $width)
    {
        $chunks = [];
        $current = '';
        $currentLength = 0;
        $styleStack = [];

        $bufferLength = mb_strwidth($text);
        for ($i = 0; $i < $bufferLength; ++$i) {
            if ($text[$i] === "\033") { // start of formatting?
                // Add to current and skip length.
                $length = strpos($text, 'm', $i) - $i;
                $style = substr($text, $i, $length + 1);
                $current .= $style;
                $i += $length;

                $codes = explode(';', substr($style, 2, -1));
                $start = (bool) array_diff($codes, [22, 24, 25, 27, 28, 39, 49]);
                if ($start) {
                    $styleStack[] = $codes;
                } else {
                    array_pop($styleStack);
                }
            } else {
                $current .= $text[$i];
                ++$currentLength;
            }

            if ($currentLength >= $width || $i + 1 === $bufferLength) {
                foreach (array_reverse($styleStack) as $startStyleCodes) {
                    $endStyleCodes = [];
                    foreach ($startStyleCodes as $code) {
                        if (strlen($code) === 1) {
                            $endStyleCodes[] = '2' . $code;
                        } elseif ($code[0] === '3') {
                            $endStyleCodes[] = '39';
                        } elseif ($code[0] === '4') {
                            $endStyleCodes[] = '49';
                        }
                    }
                    $current .= sprintf("\033[%sm", implode(';', $endStyleCodes));
                }
                $chunks[] = $current;
                $current = '';
                $currentLength = 0;

                foreach ($styleStack as $startStyleCodes) {
                    $current .= sprintf("\033[%sm", implode(';', $startStyleCodes));
                }
            }
        }

        return $chunks;
    }
}
