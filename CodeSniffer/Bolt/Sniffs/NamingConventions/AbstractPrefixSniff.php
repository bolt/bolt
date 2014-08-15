<?php
/**
 * This file is part of the Symfony-coding-standard (phpcs standard)
 *
 * PHP version 5
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @author   Ludovic Fleury <ludo.fleury@gmail.com>
 * @license  MIT License
 * @link     https://github.com/ludofleury/Symfony-coding-standard
 */

/**
 * Symfony_Sniffs_NamingConventions_AbstractPrefixSniff.
 *
 * Throws errors if abstract classes names are not suffixed with "Abstract".
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @author   Ludovic Fleury <ludo.fleury@gmail.com>
 * @license  MIT License
 * @link     https://github.com/ludofleury/Symfony-coding-standard
 */
class Bolt_Sniffs_NamingConventions_AbstractPrefixSniff implements PHP_CodeSniffer_Sniff
{
    /**
     * A list of tokenizers this sniff supports.
     *
     * @var array
     */
    public $supportedTokenizers = array(
        'PHP',
    );

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(T_ABSTRACT);
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile All the tokens found in the document.
     * @param int                  $stackPtr  The position of the current token in
     *                                        the stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens   = $phpcsFile->getTokens();
        $line     = $tokens[$stackPtr]['line'];

        while ($tokens[$stackPtr]['line'] == $line) {
            if ('T_STRING' == $tokens[$stackPtr]['type']) {
                if (strpos($tokens[$stackPtr]['content'], 'Abstract') !== 0) {
                    $phpcsFile->addError(
                        'Abstract class is not prefixed with "Abstract"',
                        $stackPtr
                    );
                }
                break;
            }
            $stackPtr++;
        }
    }
}
