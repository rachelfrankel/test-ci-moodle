<?php

use PHPUnit\Framework\TestCase;
use PhpOffice\PhpWord\Settings;

class convert_word_to_pdf_test extends TestCase
{
    protected $inputPathValid;
    protected $outputPathValid;
    protected $inputPathInvalid;
    protected $outputPathInvalid;
    protected $invalidFileFormat;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->inputPathValid = __DIR__ . '/files_to_test/daily.docx';
        $this->outputPathValid =  __DIR__ . '/files_to_test/daily.pdf';
        $this->inputPathInvalid = './tests/files_to_test/dailys';
        $this->outputPathInvalid = './tests/files_to_tests';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->outputPathValid)) {
            unlink($this->outputPathValid);
        }
        parent::tearDown();
    }

    public function testSuccessfulConversion()
    {
        require_once(__DIR__ . '/../convert_word_to_pdf.php');

        $result = convert_word_to_pdf($this->inputPathValid, $this->outputPathValid);  

        $this->assertTrue($result);
        $this->assertFileExists($this->outputPathValid);
    }

    public function testWithInvalidInputPath()
    {
        require_once(__DIR__ . '/../convert_word_to_pdf.php');

        $result = convert_word_to_pdf($this->inputPathInvalid, $this->outputPathValid);
       
        $this->assertFalse($result);
    }

    public function testWithInvalidOutputPath()
    {
        require_once(__DIR__ . '/../convert_word_to_pdf.php');

        $result = convert_word_to_pdf($this->inputPathValid, $this->outputPathInvalid);

        $this->assertFalse($result);
    }
}
