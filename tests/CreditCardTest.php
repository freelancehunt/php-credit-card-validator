<?php

use Freelancehunt\Validators\CreditCard;

class CreditCardTest extends \PHPUnit\Framework\TestCase
{
    // card numbers of Visa, Mastercard, AmEx, Diners Club, Discover and JCB taken from:
    // https://www.paypalobjects.com/en_GB/vhelp/paypalmanager_help/credit_card_numbers.htm

    // Card numbers of Visa Electron, Maestro and Dankort taken from:
    // http://support.worldpay.com/support/kb/bg/testandgolive/tgl5103.html

    protected $validCards = [

        // Debit cards
        CreditCard::TYPE_VISA_ELECTRON => [
            '4917300800000000',
        ],
        CreditCard::TYPE_MAESTRO => [
            '6759649826438453',
            '6799990100000000019',
            '5018643742501295',
            '6759684243686913',
            '6761285178632108',
            '5020876293948327',
            '6762106027891050',
            '6763011045883825',
            '5038290812833145',
            '6304418808547453',
            '5893061183737700',
        ],
        CreditCard::TYPE_FORBRUGSFORENINGEN => [
            '6007220000000004',
        ],
        CreditCard::TYPE_DANKORT => [
            '5019717010103742',
        ],

        // Credit cards
        CreditCard::TYPE_VISA => [
            '4111111111111111',
            '4012888888881881',
            '4222222222222',
            '4462030000000000',
            '4484070000000000',
            '4921818425002311',
            '4001919257537193',
            '4987654321098769',
            '4444333322221111455',
            '4539935586467516275',
        ],
        CreditCard::TYPE_MASTERCARD => [
            '5555555555554444',
            '5454545454545454',
            '2221000002222221',
            '2223000010089800',
            '2223000048400011',
        ],
        CreditCard::TYPE_AMEX => [
            '378282246310005',
            '371449635398431',
            '378734493671000', // American Express Corporate
        ],
        CreditCard::TYPE_DINERS_CLUB => [
            '30569309025904',
            '38520000023237',
            '36700102000000',
            '36148900647913',
        ],
        CreditCard::TYPE_DISCOVER => [
            '6011111111111117',
            '6011000990139424',
        ],
        CreditCard::TYPE_UNIONPAY => [
            '6271136264806203568',
            '6236265930072952775',
            '6204679475679144515',
            '6216657720782466507',
        ],
        CreditCard::TYPE_JCB => [
            '3530111333300000',
            '3566002020360505',
        ],
        CreditCard::TYPE_ELO => [
            '4389351648020055',
            '6362970000457013',
        ],
    ];

    public function testCardsTypes()
    {
        foreach ($this->validCards as $type => $numbers) {
            foreach ($numbers as $number) {
                $result = CreditCard::validCreditCard($number);

                $this->assertEquals(true, $result['valid'], 'Invalid card, expected valid. Type: ' . $type . ', Number: ' . $number);
                $this->assertEquals($type, $result['type'], 'Invalid type. Number: ' . $number . ', Expected: ' . $type . ', Actual: ' . $result['type']);
            }
        }
    }

    public function testNumbers()
    {
        // Empty number
        $result = CreditCard::validCreditCard('');
        $this->assertEquals(false, $result['valid']);

        // Number with spaces
        $result = CreditCard::validCreditCard('       ');
        $this->assertEquals(false, $result['valid']);

        // Valid number
        $result = CreditCard::validCreditCard('4242424242424242');
        $this->assertEquals(true, $result['valid']);

        // Valid number with dashes
        $result = CreditCard::validCreditCard('4242-4242-4242-4242');
        $this->assertEquals(true, $result['valid']);

        // Valid number with spaces
        $result = CreditCard::validCreditCard('4242 4242 4242 4242');
        $this->assertEquals(true, $result['valid']);

        // More than 16 digits
        $result = CreditCard::validCreditCard('42424242424242424');
        $this->assertEquals(false, $result['valid']);

        // Less than 10 digits
        $result = CreditCard::validCreditCard('424242424');
        $this->assertEquals(false, $result['valid']);

        // Valid predefined card type
        $result = CreditCard::validCreditCard('4242424242424242', CreditCard::TYPE_VISA);
        $this->assertEquals(true, $result['valid']);

        // Valid among predefined card types
        $result = CreditCard::validCreditCard('4242424242424242', [CreditCard::TYPE_MASTERCARD, CreditCard::TYPE_VISA]);
        $this->assertEquals(true, $result['valid']);

        // Invalid any of predefined card types
        $result = CreditCard::validCreditCard('4242424242424242', [CreditCard::TYPE_MASTERCARD, CreditCard::TYPE_VISA_ELECTRON]);
        $this->assertEquals(false, $result['valid']);
    }

    public function testLuhn()
    {
        $result = CreditCard::validCreditCard('4242424242424241');
        $this->assertEquals(false, $result['valid']);
    }

    public function testDate()
    {
        // Invalid month
        $this->assertEquals(false, CreditCard::validDate(date('Y'), '13'));

        // Invalid year
        $this->assertEquals(false, CreditCard::validDate(date('Y'), '15'));

        // Integer values
        $this->assertEquals(true, CreditCard::validDate(intval(date('Y')), intval(date('m'))));

        // Not numbers
        $this->assertEquals(false, CreditCard::validDate('j201', 'd4'));

        // Past year, future month
        $timestamp = strtotime('-1 month');
        $this->assertEquals(false, CreditCard::validDate(date('Y', $timestamp) - 1, date('m', $timestamp)));

        // Current year, past month
        $timestamp = strtotime('-1 month');
        $this->assertEquals(false, CreditCard::validDate(date('Y', $timestamp), date('m', $timestamp)));

        // Current year, current month
        $this->assertEquals(true, CreditCard::validDate(date('Y'), date('m')));

        // Next year
        $timestamp = strtotime('+1 year');
        $this->assertEquals(true, CreditCard::validDate(date('Y', $timestamp), date('m', $timestamp)));
    }

    public function testCvc()
    {
        // Empty
        $this->assertEquals(false, CreditCard::validCvc('', ''));

        // Empty type
        $this->assertEquals(false, CreditCard::validCvc('123', ''));

        // Empty number
        $this->assertEquals(false, CreditCard::validCvc('', CreditCard::TYPE_VISA));

        // Valid
        $this->assertEquals(true, CreditCard::validCvc('123', CreditCard::TYPE_VISA));

        // Non digits
        $this->assertEquals(false, CreditCard::validCvc('12e', CreditCard::TYPE_VISA));

        // Less than 3 digits
        $this->assertEquals(false, CreditCard::validCvc('12', CreditCard::TYPE_VISA));

        // More than 3 digits
        $this->assertEquals(false, CreditCard::validCvc('1234', CreditCard::TYPE_VISA));
    }
}
