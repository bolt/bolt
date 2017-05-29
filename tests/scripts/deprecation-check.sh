#!/usr/bin/env bash

FAILED_CHECKS="0"

function add_fail() {
    FAILED_CHECKS=1
    echo ""
    echo -e "\033[0;31m[FAILED]\033[0m"
}
function add_pass() {
    echo ""
    echo -e "\033[0;32m[PASSED]\033[0m"
}

echo ""
echo "Checking Twig namespace aliases"
echo "-------------------------------"
echo ""
grep -rn -P '((?<!@see|@expectedException)(^use T|\\T))(wig_\w+)\s*;' src/ tests/phpunit/ tests/codeception/
[[ $? -eq 0 ]] && add_fail || add_pass
echo ""

echo "Checking PHPUnit namespace aliases"
echo "----------------------------------"
echo ""
grep -rn -P '((?<!@see)(^use P|\\P))(HPUnit_(Framework|Util|Extensions|Runner|TextUI|Exception)_\w+)\s*;' tests/phpunit/ tests/codeception/
[[ $? -eq 0 ]] && add_fail || add_pass
echo ""

echo "Checking PHPUnit mocks"
echo "----------------------"
echo ""
grep -rn -P 'this\->getMock\(' tests/phpunit/ tests/codeception/
[[ $? -eq 0 ]] && add_fail || add_pass
echo ""

exit $FAILED_CHECKS
