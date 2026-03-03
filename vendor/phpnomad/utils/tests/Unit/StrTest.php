<?php

namespace PHPNomad\Tests\Unit;

use PHPNomad\Core\Tests\TestCase;
use PHPNomad\Utils\Helpers\Str;

class StrTest extends TestCase
{
    /**
     * Test the Str::before() method with various inputs
     *
     * @return void
     */
    public function testBefore(): void
    {
        // Test case from the issue
        $result = Str::before('wpl::public/assets/icon.svg', '::');
        $this->assertEquals('wpl', $result);

        // Test with single character delimiter
        $result = Str::before('hello world', ' ');
        $this->assertEquals('hello', $result);

        // Test with multiple occurrences (should return before first occurrence)
        $result = Str::before('one-two-three', '-');
        $this->assertEquals('one', $result);

        // Test with delimiter at the beginning
        $result = Str::before('::test', '::');
        $this->assertEquals('', $result);

        // Test with longer delimiter
        $result = Str::before('prefix___suffix', '___');
        $this->assertEquals('prefix', $result);

        // Test with URL-like string
        $result = Str::before('https://example.com/path', '://');
        $this->assertEquals('https', $result);
    }

    /**
     * Test the Str::before() method when delimiter is not found
     * Should return the original string (consistent with Str::after behavior)
     *
     * @return void
     */
    public function testBeforeWhenDelimiterNotFound(): void
    {
        // When delimiter is not found, should return the original string
        $result = Str::before('hello world', 'xyz');
        $this->assertEquals('hello world', $result);
    }

    /**
     * Test the Str::before() method with empty delimiter
     * Should return the original string (consistent with Str::after behavior)
     *
     * @return void
     */
    public function testBeforeWithEmptyDelimiter(): void
    {
        // When delimiter is empty, should return the original string
        $result = Str::before('hello world', '');
        $this->assertEquals('hello world', $result);
    }

    /**
     * Test the Str::after() method with various inputs
     *
     * @return void
     */
    public function testAfter(): void
    {
        // Test case matching before() tests
        $result = Str::after('wpl::public/assets/icon.svg', '::');
        $this->assertEquals('public/assets/icon.svg', $result);

        // Test with single character delimiter
        $result = Str::after('hello world', ' ');
        $this->assertEquals('world', $result);

        // Test with multiple occurrences (should return after first occurrence)
        $result = Str::after('one-two-three', '-');
        $this->assertEquals('two-three', $result);

        // Test with delimiter at the end
        $result = Str::after('test::', '::');
        $this->assertEquals('', $result);

        // Test with longer delimiter
        $result = Str::after('prefix___suffix', '___');
        $this->assertEquals('suffix', $result);

        // Test with URL-like string
        $result = Str::after('https://example.com/path', '://');
        $this->assertEquals('example.com/path', $result);
    }

    /**
     * Test the Str::after() method when delimiter is not found
     * Should return the original string
     *
     * @return void
     */
    public function testAfterWhenDelimiterNotFound(): void
    {
        // When delimiter is not found, should return the original string
        $result = Str::after('hello world', 'xyz');
        $this->assertEquals('hello world', $result);
    }

    /**
     * Test the Str::after() method with empty delimiter
     * Should return the original string
     *
     * @return void
     */
    public function testAfterWithEmptyDelimiter(): void
    {
        // When delimiter is empty, should return the original string
        $result = Str::after('hello world', '');
        $this->assertEquals('hello world', $result);
    }
}
