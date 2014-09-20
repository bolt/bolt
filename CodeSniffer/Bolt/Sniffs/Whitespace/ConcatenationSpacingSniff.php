<?php
/**
 * Makes sure there are spaces between the concatenation operator (.) and the strings being concatenated.
 */

class Bolt_Sniffs_Whitespace_ConcatenationSpacingSniff implements PHP_CodeSniffer_Sniff
{
    public function register()
    {
        return array(T_STRING_CONCAT);
    }

    private function charsFound($string)
    {
        $chars = count_chars($string);
        $found = array();
        foreach (array(32 => 'space', 9 => 'tab') as $ord => $name) {
            if ($chars[$ord] > 0) {
                $found[] = $chars[$ord] . ' ' . $name . ($chars[$ord] > 1 ? 's' : '');
            }
        }

        return ($string == '' ? '0' : join(', ', $found)) . ' found';
    }

    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $space_before = ($tokens[$stackPtr - 1]['code'] === T_WHITESPACE) ? $tokens[$stackPtr - 1]['content'] : '';
        if ($tokens[$stackPtr - 2]['code'] === T_WHITESPACE && strpos($tokens[$stackPtr - 2]['content'], "\n") !== false) {
            $space_before = ' ';
        }

        $space_after = ($tokens[$stackPtr + 1]['code'] === T_WHITESPACE) ? $tokens[$stackPtr + 1]['content'] : '';

        // No whitespace at all around
        if ($space_before == '' && $space_after == '') {
            $phpcsFile->addError(
                'Expected 1 space before and 1 space after concatenation operator; 0 found',
                $stackPtr,
                'SpacingConcatOp'
            );
        } else {
            // No whitespace before or more than 1 space before
            if ($space_before != ' ') {
                $phpcsFile->addError(
                    'Expected 1 space before concatenation operator; ' . $this->charsFound($space_before),
                    $stackPtr,
                    'SpacingBeforeConcatOp'
                );
            }
            // No whitespace after or more than 1 space after or not line end
            if ($space_after != ' ' && $space_after != "\n" && $space_after != " \n") {
                $phpcsFile->addError(
                    'Expected 1 space after concatenation operator; ' . $this->charsFound($space_after),
                    $stackPtr,
                    'SpacingAfterConcatOp'
                );
            }
        }
    }
}
